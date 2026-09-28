<?php
/** StudentController - list, create, show, lookup. */
final class StudentController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::requireCan('students.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $q = trim((string)($_GET['q'] ?? ''));

        $sql = "SELECT s.*, c.name AS class_name,
                       COALESCE((SELECT SUM(oi.amount) FROM fee_obligation_items oi
                                 JOIN fee_obligations o ON o.id = oi.fee_obligation_id
                                 WHERE o.student_id = s.id),0) AS total_billed,
                       COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa
                                 JOIN fee_obligation_items oi ON oi.id = pa.fee_obligation_item_id
                                 JOIN fee_obligations o ON o.id = oi.fee_obligation_id
                                 WHERE o.student_id = s.id),0) AS total_paid
                FROM students s
                LEFT JOIN classes c ON c.id = s.class_id
                WHERE s.school_id = :s AND s.status = 'active'";
        $params = ['s' => $schoolId];

        if ($q !== '') {
            $sql .= " AND (s.full_name LIKE :q OR s.student_id LIKE :q OR s.reg_form_number LIKE :q)";
            $params['q'] = "%{$q}%";
        }
        $sql .= ' ORDER BY s.full_name LIMIT 200';

        $students = $db->fetchAll($sql, $params);
        foreach ($students as &$s) {
            $s['_balance'] = round((float)$s['total_billed'] - (float)$s['total_paid'], 2);
        }

        $classes = $db->fetchAll('SELECT * FROM classes WHERE school_id = :s ORDER BY name', ['s' => $schoolId]);
        render('students/index', ['title' => 'Students', 'students' => $students, 'classes' => $classes, 'q' => $q]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::requireCan('students.manage');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $classes = $db->fetchAll('SELECT * FROM classes WHERE school_id = :s ORDER BY name', ['s' => $schoolId]);
        $feeStructures = $db->fetchAll(
            "SELECT fs.*, t.name AS term_name FROM fee_structures fs
             JOIN terms t ON t.id = fs.term_id
             WHERE fs.school_id = :s AND fs.status = 'published' ORDER BY fs.id DESC",
            ['s' => $schoolId]
        );
        render('students/create', ['title' => 'Register student', 'classes' => $classes, 'feeStructures' => $feeStructures]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::requireCan('students.manage');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $regNo    = trim((string)($_POST['reg_form_number'] ?? ''));
        $classId  = (int)($_POST['class_id'] ?? 0);
        $structureId = (int)($_POST['fee_structure_id'] ?? 0);

        if ($fullName === '') {
            flash_set('error', 'Student full name is required.');
            redirect('/students/create');
        }

        $maxSeq = 237;
        foreach ($db->fetchAll("SELECT student_id FROM students WHERE school_id = :s", ['s' => $schoolId]) as $row) {
            if (preg_match('/(\d{7})-(\d)$/', $row['student_id'], $m)) {
                $maxSeq = max($maxSeq, (int)$m[1]);
            }
        }
        $nextSeq = $maxSeq + 1;
        $studentIdValue = StudentIdService::generate('KFS', $nextSeq);

        $sid = $db->insert('students', [
            'school_id'      => $schoolId,
            'student_id'     => $studentIdValue,
            'reg_form_number'=> $regNo ?: null,
            'full_name'      => $fullName,
            'class_id'       => $classId ?: null,
            'status'         => 'active',
        ]);

        AuditService::log($schoolId, Auth::id(), 'student.created', 'students', $sid);

        if ($structureId > 0) {
            $struct = $db->fetch('SELECT * FROM fee_structures WHERE id = :id AND school_id = :s',
                ['id' => $structureId, 's' => $schoolId]);
            if ($struct) {
                $termId = (int)$struct['term_id'];
                $items = $db->fetchAll('SELECT fee_item_id, amount FROM fee_structure_items WHERE fee_structure_id = :id',
                    ['id' => $structureId]);
                $total = array_sum(array_column($items, 'amount'));
                $invoice = 'INV-' . date('Y') . '-T' . $termId . '-' . str_pad((string)$sid, 4, '0', STR_PAD_LEFT);
                $obId = $db->insert('fee_obligations', [
                    'school_id' => $schoolId, 'student_id' => $sid, 'term_id' => $termId,
                    'invoice_no' => $invoice, 'total_amount' => $total, 'status' => 'unpaid',
                ]);
                foreach ($items as $item) {
                    $db->insert('fee_obligation_items', [
                        'fee_obligation_id' => $obId, 'fee_item_id' => (int)$item['fee_item_id'], 'amount' => (float)$item['amount'],
                    ]);
                }
                flash_set('success', "Student registered with Student ID {$studentIdValue}; invoice {$invoice} created.");
            }
        } else {
            flash_set('success', "Student registered with Student ID {$studentIdValue}.");
        }

        redirect('/students');
    }

    public function show(): void
    {
        Auth::requireLogin();
        Auth::requireCan('students.view');
        $db = Database::get();
        $sid = (int)($_GET['id'] ?? 0);

        $student = $db->fetch(
            "SELECT s.*, c.name AS class_name FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE s.id = :id", ['id' => $sid]
        );
        if (!$student) {
            abort(404, 'Student not found.');
        }
        if (!Auth::hasRole('platform_admin') && (int)$student['school_id'] !== (int)Auth::schoolId()) {
            abort(403, 'Cross-tenant access denied.');
        }

        $obligations = $db->fetchAll(
            "SELECT o.*, t.name AS term_name,
                    COALESCE((SELECT SUM(oi2.amount) FROM fee_obligation_items oi2
                              WHERE oi2.fee_obligation_id = o.id),0) AS billed,
                    o.paid_amount AS paid
             FROM fee_obligations o
             JOIN terms t ON t.id = o.term_id
             WHERE o.student_id = :sid ORDER BY o.created_at DESC", ['sid' => $sid]
        );

        $ledger = LedgerService::forStudent($sid);
        $payments = $db->fetchAll(
            "SELECT p.*, u.name AS confirmed_by_name FROM payments p
             LEFT JOIN users u ON u.id = p.confirmed_by
             WHERE p.student_id = :sid ORDER BY p.id DESC LIMIT 50", ['sid' => $sid]
        );
        $guardians = $db->fetchAll(
            "SELECT g.*, sg.relationship FROM guardians g
             JOIN student_guardians sg ON sg.guardian_id = g.id
             WHERE sg.student_id = :sid", ['sid' => $sid]
        );

        render('students/show', [
            'title' => 'Student: ' . $student['full_name'],
            'student' => $student, 'obligations' => $obligations,
            'ledger' => $ledger, 'payments' => $payments, 'guardians' => $guardians,
        ]);
    }

    /** JSON lookup by Student ID / name / reg no (used by the payment form). */
    public function lookup(): void
    {
        header('Content-Type: application/json');
        if (!Auth::user()) {
            echo json_encode(['found' => false]);
            return;
        }
        $q = trim((string)($_GET['id'] ?? ''));
        if ($q === '') {
            echo json_encode(['found' => false]);
            return;
        }

        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $normalized = StudentIdService::normalize($q);

        $student = $db->fetch(
            "SELECT s.*, c.name AS class_name FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE s.school_id = :s AND s.status = 'active'
               AND (s.student_id LIKE :norm OR s.full_name LIKE :like OR s.reg_form_number LIKE :like)
             LIMIT 1",
            ['s' => $schoolId, 'norm' => "%{$normalized}%", 'like' => "%{$q}%"]
        );

        if (!$student) {
            echo json_encode(['found' => false]);
            return;
        }

        echo json_encode([
            'found'       => true,
            'id'          => (int)$student['id'],
            'name'        => $student['full_name'],
            'class_name'  => $student['class_name'] ?? 'N/A',
            'student_id'  => $student['student_id'],
            'outstanding' => number_format(LedgerService::outstanding((int)$student['id']), 2),
        ]);
    }
}

