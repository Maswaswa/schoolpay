<?php
/** ParentController - parent portal: children, balances, receipts. */
final class ParentController
{
    public function index(): void
    {
        Auth::requireLogin();
        Auth::requireCan('children.view');
        $db = Database::get();

        $rows = $db->fetchAll(
            "SELECT s.id, s.student_id, s.full_name, c.name AS class_name,
                    COALESCE((SELECT SUM(oi.amount) FROM fee_obligation_items oi
                              JOIN fee_obligations o ON o.id = oi.fee_obligation_id
                              WHERE o.student_id = s.id),0) AS billed,
                    COALESCE((SELECT SUM(o2.paid_amount) FROM fee_obligations o2
                              WHERE o2.student_id = s.id),0) AS paid
             FROM student_guardians sg
             JOIN students s ON s.id = sg.student_id
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE sg.guardian_id = :uid AND s.status = 'active'
             ORDER BY s.full_name",
            ['uid' => Auth::id()]
        );

        $children = [];
        foreach ($rows as $r) {
            $children[] = [
                'id' => (int)$r['id'],
                'student_id' => $r['student_id'],
                'full_name' => $r['full_name'],
                'class_name' => $r['class_name'] ?? '',
                'outstanding' => round((float)$r['billed'] - (float)$r['paid'], 2),
            ];
        }

        render('parent/index', ['title' => 'My children', 'children' => $children]);
    }

    public function child(): void
    {
        Auth::requireLogin();
        $db = Database::get();
        $sid = (int)($_GET['id'] ?? 0);

        $link = $db->fetch(
            'SELECT id FROM student_guardians WHERE guardian_id = :uid AND student_id = :sid',
            ['uid' => Auth::id(), 'sid' => $sid]
        );
        if (!$link) { abort(403, 'This student is not linked to your account.'); }

        $student = $db->fetch(
            "SELECT s.*, c.name AS class_name FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE s.id = :id AND s.status = 'active'", ['id' => $sid]
        );
        if (!$student) { abort(404, 'Student not found.'); }

        $obligations = $db->fetchAll(
            "SELECT o.*, t.name AS term_name,
                    COALESCE((SELECT SUM(oi.amount) FROM fee_obligation_items oi WHERE oi.fee_obligation_id = o.id),0) AS billed
             FROM fee_obligations o
             JOIN terms t ON t.id = o.term_id
             WHERE o.student_id = :sid ORDER BY o.created_at DESC",
            ['sid' => $sid]
        );
        $payments = $db->fetchAll(
            "SELECT p.*, r.id AS receipt_id, r.receipt_number FROM payments p
             LEFT JOIN receipts r ON r.payment_id = p.id
             WHERE p.student_id = :sid AND p.status = 'confirmed'
             ORDER BY p.id DESC LIMIT 30",
            ['sid' => $sid]
        );

        render('parent/child', [
            'title' => $student['full_name'], 'student' => $student,
            'obligations' => $obligations, 'payments' => $payments,
        ]);
    }
}
