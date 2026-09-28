<?php
/** ReportController - daily collection report and outstanding balances. */
final class ReportController
{
    public function daily(): void
    {
        Auth::requireCan('reports.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $date = trim((string)($_GET['date'] ?? date('Y-m-d')));

        $totals = $db->fetchAll(
            "SELECT channel, COUNT(*) AS tx_count, SUM(amount) AS total
             FROM payments
             WHERE school_id = :s AND status = 'confirmed' AND DATE(confirmed_at) = :d
             GROUP BY channel ORDER BY channel",
            ['s' => $schoolId, 'd' => $date]
        );
        $grandTotal = array_sum(array_column($totals, 'total'));
        $txCount = array_sum(array_column($totals, 'tx_count'));

        $cashiers = $db->fetchAll(
            "SELECT u.name AS cashier_name, COUNT(p.id) AS tx_count, COALESCE(SUM(p.amount),0) AS total
             FROM payments p
             JOIN users u ON u.id = p.confirmed_by
             WHERE p.school_id = :s AND p.status = 'confirmed' AND DATE(p.confirmed_at) = :d
             GROUP BY p.confirmed_by ORDER BY total DESC",
            ['s' => $schoolId, 'd' => $date]
        );

        render('reports/daily', [
            'title' => 'Daily report', 'date' => $date,
            'totals' => $totals, 'grandTotal' => $grandTotal,
            'txCount' => $txCount, 'cashiers' => $cashiers,
        ]);
    }

    public function outstanding(): void
    {
        Auth::requireCan('reports.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $rows = $db->fetchAll(
            "SELECT s.id, s.student_id, s.full_name, c.name AS class_name,
                    COALESCE((SELECT SUM(oi.amount) FROM fee_obligation_items oi
                              JOIN fee_obligations o ON o.id = oi.fee_obligation_id
                              WHERE o.student_id = s.id),0) AS billed,
                    o.paid_amount AS paid
             FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             JOIN fee_obligations o ON o.student_id = s.id
             WHERE s.school_id = :s AND s.status = 'active'
             ORDER BY s.full_name",
            ['s' => $schoolId]
        );

        // Aggregate per student (a student may have several term invoices).
        $students = [];
        foreach ($rows as $r) {
            $sid = (int)$r['id'];
            if (!isset($students[$sid])) {
                $students[$sid] = [
                    'id' => $sid, 'student_id' => $r['student_id'], 'full_name' => $r['full_name'],
                    'class_name' => $r['class_name'], 'billed' => 0.0, 'paid' => 0.0,
                ];
            }
            $students[$sid]['billed'] += (float)$r['billed'];
            $students[$sid]['paid']   += (float)$r['paid'];
        }
        foreach ($students as &$st) {
            $st['outstanding'] = round($st['billed'] - $st['paid'], 2);
        }
        unset($st);
        usort($students, fn($a, $b) => $b['outstanding'] <=> $a['outstanding']);

        $totalOutstanding = array_sum(array_column($students, 'outstanding'));
        render('reports/outstanding', [
            'title' => 'Outstanding fees', 'students' => $students,
            'totalOutstanding' => $totalOutstanding,
        ]);
    }
}
