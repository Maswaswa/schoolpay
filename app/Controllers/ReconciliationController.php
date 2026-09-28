<?php
/** ReconciliationController - compare cash sessions, bank/mobile lines vs system records. */
final class ReconciliationController
{
    public function index(): void
    {
        Auth::requireCan('recon.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $sessions = $db->fetchAll(
            "SELECT cs.*, u.name AS cashier_name,
                    COALESCE((SELECT SUM(p.amount) FROM cash_payments cp JOIN payments p ON p.id = cp.payment_id
                              WHERE cp.cash_session_id = cs.id),0) AS cash_total,
                    (cs.opening_amount + COALESCE((SELECT SUM(p.amount) FROM cash_payments cp
                              JOIN payments p ON p.id = cp.payment_id WHERE cp.cash_session_id = cs.id),0))
                       AS expected_amount
             FROM cash_sessions cs
             JOIN users u ON u.id = cs.cashier_id
             WHERE cs.school_id = :s ORDER BY cs.id DESC LIMIT 30",
            ['s' => $schoolId]
        );

        // Non-cash channel totals per day (bank_transfer / mobile_money / cheque).
        $byChannel = $db->fetchAll(
            "SELECT channel, DATE(confirmed_at) AS pay_date, COUNT(*) AS tx_count, SUM(amount) AS total
             FROM payments
             WHERE school_id = :s AND status = 'confirmed' AND channel != 'cash'
             GROUP BY channel, DATE(confirmed_at)
             ORDER BY pay_date DESC, channel LIMIT 60",
            ['s' => $schoolId]
        );

        $discrepancies = array_filter($sessions, fn($s) => ($s['status'] ?? '') === 'discrepancy');

        render('reconciliation/index', [
            'title' => 'Reconciliation',
            'sessions' => $sessions,
            'byChannel' => $byChannel,
            'discrepancies' => $discrepancies,
        ]);
    }
}
