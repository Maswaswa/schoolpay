<?php
/**
 * CashSessionService - cashier cash sessions.
 * Open -> record payments -> close by declaring physical cash.
 * Expected vs declared difference creates a discrepancy for investigation.
 */
final class CashSessionService
{
    /** Open a new cash session for a cashier. */
    public static function open(int $schoolId, ?int $campusId, int $cashierId, float $openingAmount = 0): int
    {
        $active = Database::get()->fetch(
            "SELECT id FROM cash_sessions WHERE cashier_id = :c AND status = 'open'",
            ['c' => $cashierId]
        );
        if ($active) {
            throw new RuntimeException('Cashier already has an open cash session.');
        }
        $id = Database::get()->insert('cash_sessions', [
            'school_id'      => $schoolId,
            'campus_id'      => $campusId,
            'cashier_id'     => $cashierId,
            'opening_amount' => $openingAmount,
            'status'         => 'open',
        ]);
        AuditService::log($schoolId, $cashierId, 'cash.session.open', 'cash_sessions', $id, ['opening' => $openingAmount]);
        return $id;
    }

    public static function openSessionFor(int $cashierId): ?array
    {
        return Database::get()->fetch(
            "SELECT cs.*, u.name AS cashier_name, c.name AS campus_name
             FROM cash_sessions cs
             LEFT JOIN users u ON u.id = cs.cashier_id
             LEFT JOIN campuses c ON c.id = cs.campus_id
             WHERE cs.cashier_id = :c AND cs.status = 'open'",
            ['c' => $cashierId]
        );
    }

    /** Expected cash for a session = total cash payments recorded in it. */
    public static function expectedAmount(int $sessionId): float
    {
        return (float)Database::get()->scalar(
            "SELECT COALESCE(SUM(p.amount),0) FROM cash_payments cp
             JOIN payments p ON p.id = cp.payment_id
             WHERE cp.cash_session_id = :sid",
            ['sid' => $sessionId]
        );
    }

    public static function paymentsIn(int $sessionId): array
    {
        return Database::get()->fetchAll(
            "SELECT p.*, cp.cash_receipt_number, r.receipt_number AS rct
             FROM cash_payments cp
             JOIN payments p ON p.id = cp.payment_id
             LEFT JOIN receipts r ON r.payment_id = p.id
             WHERE cp.cash_session_id = :sid ORDER BY p.id ASC",
            ['sid' => $sessionId]
        );
    }

    /**
     * Close a session with the declared physical cash amount.
     *  difference = expected - declared. If != 0 -> status 'discrepancy'.
     */
    public static function close(int $sessionId, float $declaredAmount): array
    {
        $db = Database::get();
        $session = $db->fetch('SELECT * FROM cash_sessions WHERE id = :id', ['id' => $sessionId]);
        if (!$session || $session['status'] !== 'open') {
            throw new RuntimeException('Session is not open.');
        }
        $expected = self::expectedAmount($sessionId);
        $diff = round($expected - $declaredAmount, 2);
        $status = $diff == 0 ? 'closed' : 'discrepancy';

        $db->update('cash_sessions', [
            'expected_amount'  => $expected,
            'declared_amount'  => $declaredAmount,
            'difference_amount'=> $diff,
            'status'           => $status,
            'closed_at'        => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $sessionId]);

        AuditService::log($session['school_id'], $session['cashier_id'], 'cash.session.close', 'cash_sessions', $sessionId, [
            'expected' => $expected,
            'declared' => $declaredAmount,
            'difference' => $diff,
            'status' => $status,
        ]);

        if ($status === 'discrepancy') {
            Database::get()->insert('reconciliation_cases', [
                'school_id' => $session['school_id'],
                'case_type' => 'amount_mismatch',
                'status'    => 'open',
                'notes'     => "Cash session #{$sessionId} discrepancy: expected {$expected} declared {$declaredAmount} diff {$diff}",
            ]);
        }

        return ['expected' => $expected, 'declared' => $declaredAmount, 'difference' => $diff, 'status' => $status];
    }
}
