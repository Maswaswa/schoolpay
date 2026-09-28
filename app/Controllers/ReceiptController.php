<?php
/** ReceiptController - view and reprint payment receipts. */
final class ReceiptController
{
    public function show(): void
    {
        Auth::requireCan('receipt.view');
        $db = Database::get();
        $id = (int)($_GET['id'] ?? 0);
        $receipt = $db->fetch(
            "SELECT r.*, p.reference AS payment_reference, p.channel, p.amount AS payment_amount,
                    p.provider_ref, p.confirmed_at, p.school_id,
                    s.full_name AS student_name, s.student_id,
                    c.name AS class_name,
                    u.name AS recorded_by_name, sch.name AS school_name
             FROM receipts r
             JOIN payments p ON p.id = r.payment_id
             JOIN students s ON s.id = p.student_id
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN users u ON u.id = p.confirmed_by
             JOIN schools sch ON sch.id = p.school_id
             WHERE r.id = :id",
            ['id' => $id]
        );
        if (!$receipt) { abort(404, 'Receipt not found.'); }

        if (Auth::hasRole('parent')) {
            // Parents may only see receipts of their own children
            // (parent user id is linked via student_guardians.guardian_id).
            $childOk = $db->fetch(
                "SELECT sg.student_id FROM student_guardians sg
                 JOIN receipts r2 ON r2.id = :rid
                 JOIN payments p2 ON p2.id = r2.payment_id
                 WHERE sg.guardian_id = :uid AND sg.student_id = p2.student_id",
                ['rid' => $id, 'uid' => Auth::id()]
            );
            if (!$childOk) { abort(403, 'This receipt does not belong to your child.'); }
        } elseif ((int)$receipt['school_id'] !== (int)Auth::schoolId() && !Auth::hasRole('platform_admin')) {
            abort(403, 'Cross-tenant access denied.');
        }

        $allocations = $db->fetchAll(
            "SELECT pa.amount, fo.invoice_no, fi.name AS item_name
             FROM payment_allocations pa
             JOIN fee_obligation_items oi ON oi.id = pa.fee_obligation_item_id
             JOIN fee_obligations fo ON fo.id = oi.fee_obligation_id
             LEFT JOIN fee_items fi ON fi.id = oi.fee_item_id
             WHERE pa.payment_id = :pid",
            ['pid' => $receipt['payment_id']]
        );

        render('receipts/show', ['title' => 'Receipt ' . $receipt['receipt_number'], 'receipt' => $receipt, 'allocations' => $allocations]);
    }

    public function reprint(): void
    {
        Auth::requireCan('receipt.reprint');
        csrf_check();
        $receiptId = (int)($_POST['receipt_id'] ?? 0);
        PaymentService::reprintReceipt($receiptId);
        flash_set('success', 'Reprint recorded in audit trail.');
        redirect('/receipts/show?id=' . $receiptId);
    }
}
