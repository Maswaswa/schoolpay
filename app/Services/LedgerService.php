<?php
/**
 * LedgerService - append-only ledger. Money is never edited/deleted once posted;
 * corrections create compensating entries.
 */
final class LedgerService
{
    /** Current outstanding balance for a student (billed minus allocated). */
    public static function outstanding(int $studentId): float
    {
        $db = Database::get();
        $billed = (float)$db->scalar(
            "SELECT COALESCE(SUM(oi.amount),0) FROM fee_obligation_items oi
             JOIN fee_obligations o ON o.id = oi.fee_obligation_id
             WHERE o.student_id = :sid",
            ['sid' => $studentId]
        );
        $allocated = (float)$db->scalar(
            "SELECT COALESCE(SUM(pa.amount),0) FROM payment_allocations pa
             JOIN fee_obligation_items oi ON oi.id = pa.fee_obligation_item_id
             JOIN fee_obligations o ON o.id = oi.fee_obligation_id
             WHERE o.student_id = :sid",
            ['sid' => $studentId]
        );
        return round($billed - $allocated, 2);
    }

    /** Last running balance known for the student (for ledger history). */
    public static function lastBalance(int $studentId): float
    {
        $row = Database::get()->fetch(
            "SELECT balance_after FROM ledger_entries WHERE student_id = :sid
             ORDER BY id DESC LIMIT 1",
            ['sid' => $studentId]
        );
        return $row ? (float)$row['balance_after'] : self::outstanding($studentId);
    }

    /**
     * Post a credit (confirmed payment) to the ledger.
     * Reduces the student's outstanding balance.
     */
    public static function postCredit(
        int $schoolId,
        int $studentId,
        ?int $paymentId,
        float $amount,
        string $reason,
        string $reference,
        ?int $createdBy
    ): int {
        $before = self::outstanding($studentId);
        $after = round(max(0, $before - $amount), 2);
        return Database::get()->insert('ledger_entries', [
            'school_id'     => $schoolId,
            'student_id'    => $studentId,
            'payment_id'    => $paymentId,
            'entry_type'    => 'credit',
            'amount'        => $amount,
            'balance_after' => $after,
            'reason'        => $reason,
            'reference'     => $reference,
            'created_by'    => $createdBy,
        ]);
    }

    /**
     * Post a reversal/refund/adjustment entry that INCREASES outstanding
     * (compensating entry) without touching the original credit.
     */
    public static function postDebit(
        int $schoolId,
        int $studentId,
        ?int $paymentId,
        string $entryType,
        float $amount,
        string $reason,
        string $reference,
        ?int $createdBy
    ): int {
        $before = self::outstanding($studentId);
        $after = round($before + $amount, 2);
        return Database::get()->insert('ledger_entries', [
            'school_id'     => $schoolId,
            'student_id'    => $studentId,
            'payment_id'    => $paymentId,
            'entry_type'    => $entryType,
            'amount'        => $amount,
            'balance_after' => $after,
            'reason'        => $reason,
            'reference'     => $reference,
            'created_by'    => $createdBy,
        ]);
    }

    public static function forStudent(int $studentId): array
    {
        return Database::get()->fetchAll(
            "SELECT l.*, u.name AS user_name FROM ledger_entries l
             LEFT JOIN users u ON u.id = l.created_by
             WHERE l.student_id = :sid ORDER BY l.id ASC",
            ['sid' => $studentId]
        );
    }
}
