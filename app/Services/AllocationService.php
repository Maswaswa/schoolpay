<?php
/**
 * AllocationService - allocates a confirmed payment to unpaid fee obligation
 * items using an oldest-first policy. Never allocates more than the payment.
 */
final class AllocationService
{
    /**
     * Allocate $amount across the student's unpaid obligation items (oldest first).
     * Must run inside a transaction together with payment posting + ledger.
     *
     * @return float amount actually allocated
     */
    public static function allocate(
        int $schoolId,
        int $studentId,
        int $paymentId,
        float $amount,
        ?int $createdBy
    ): float {
        $db = Database::get();
        $remaining = $amount;
        $total = 0.0;

        $items = $db->fetchAll(
            "SELECT oi.id, oi.amount, oi.paid_amount, o.id AS obligation_id,
                    o.total_amount, o.paid_amount AS ob_paid
             FROM fee_obligation_items oi
             JOIN fee_obligations o ON o.id = oi.fee_obligation_id
             WHERE o.student_id = :sid AND oi.amount > oi.paid_amount
             ORDER BY o.created_at ASC, oi.id ASC",
            ['sid' => $studentId]
        );

        foreach ($items as $item) {
            if ($remaining <= 0) break;
            $itemRemaining = (float)$item['amount'] - (float)$item['paid_amount'];
            $take = min($remaining, $itemRemaining);
            if ($take <= 0) continue;

            $db->insert('payment_allocations', [
                'school_id'              => $schoolId,
                'payment_id'             => $paymentId,
                'fee_obligation_item_id' => $item['id'],
                'amount'                 => $take,
                'created_by'             => $createdBy,
            ]);

            // Update item and obligation paid amounts
            $newItemPaid = (float)$item['paid_amount'] + $take;
            $db->update('fee_obligation_items', ['paid_amount' => $newItemPaid], 'id = :id', ['id' => $item['id']]);

            $newObPaid = (float)$item['ob_paid'] + $take;
            $obStatus = $newObPaid >= (float)$item['total_amount'] ? 'paid' : 'partial';
            $db->update('fee_obligations', ['paid_amount' => $newObPaid, 'status' => $obStatus], 'id = :id', ['id' => $item['obligation_id']]);

            $remaining -= $take;
            $total += $take;
        }
        return round($total, 2);
    }
}
