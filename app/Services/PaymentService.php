<?php
/**
 * PaymentService - the core financial pipeline for BURSAR-RECORDED payments.
 *
 * MVP constraint: NO payment provider, gateway, or intermediary exists.
 * The bursar is the single point of payment acceptance. He/she records
 * money received at the school counter (cash) or verified directly into
 * the school's own bank account / mobile-money line (bank_transfer,
 * mobile_money, cheque). There is no intent/attempt/callback flow.
 *
 * Pipeline (one DB transaction): record -> allocate (oldest-first) ->
 * ledger credit -> receipt -> notification (queued) -> audit.
 * Recording is IDEMPOTENT on the external reference (slip/txn no.).
 */
final class PaymentService
{
    /** Channels the bursar may record (all manual, provider-free). */
    public const CHANNELS = [
        'cash'          => 'Cash (at school counter)',
        'bank_transfer' => 'Bank transfer / deposit slip',
        'mobile_money'  => 'Mobile money (to school line)',
        'cheque'        => 'Cheque',
    ];

    public static function newReference(string $prefix = 'PAY'): string
    {
        return strtoupper($prefix) . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Record a payment received by the bursar.
     *
     * @param string      $channel   one of PaymentService::CHANNELS keys
     * @param float       $amount    positive amount received
     * @param string|null $extRef    external reference: bank slip no., MoMo txn id,
     *                               cheque no. Null for cash (auto reference).
     * @param array       $cashData  optional ['cash_session_id'=>int,'cash_receipt_number'=>string,'notes'=>string]
     *
     * @return array ['payment_id'=>int,'duplicate'=>bool,'receipt_id'=>?int,'receipt_number'=>?string]
     */
    public static function recordPayment(
        int $schoolId,
        int $studentId,
        string $channel,
        float $amount,
        ?int $recordedBy,
        ?string $extRef = null,
        array $cashData = []
    ): array {
        if (!isset(self::CHANNELS[$channel])) {
            throw new InvalidArgumentException("Unknown payment channel '{$channel}'.");
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $db = Database::get();

        // Idempotency: the same external reference (slip / txn id) can never
        // be recorded twice, even across sessions or double form submits.
        if ($extRef !== null && $extRef !== '') {
            $existing = $db->fetch(
                'SELECT id FROM payments WHERE school_id = :s AND provider_ref = :ref',
                ['s' => $schoolId, 'ref' => $extRef]
            );
            if ($existing) {
                $rct = $db->fetch('SELECT id, receipt_number FROM receipts WHERE payment_id = :pid', ['pid' => $existing['id']]);
                return [
                    'payment_id'     => (int)$existing['id'],
                    'duplicate'      => true,
                    'receipt_id'     => $rct ? (int)$rct['id'] : null,
                    'receipt_number' => $rct['receipt_number'] ?? null,
                ];
            }
        }

        return $db->transaction(function ($db) use ($schoolId, $studentId, $channel, $amount, $recordedBy, $extRef, $cashData) {
            // Re-check inside the transaction (race safety).
            if ($extRef !== null && $extRef !== '') {
                $dup = $db->fetch(
                    'SELECT id FROM payments WHERE school_id = :s AND provider_ref = :ref',
                    ['s' => $schoolId, 'ref' => $extRef]
                );
                if ($dup) {
                    $rct = $db->fetch('SELECT id, receipt_number FROM receipts WHERE payment_id = :pid', ['pid' => $dup['id']]);
                    return [
                        'payment_id'     => (int)$dup['id'],
                        'duplicate'      => true,
                        'receipt_id'     => $rct ? (int)$rct['id'] : null,
                        'receipt_number' => $rct['receipt_number'] ?? null,
                    ];
                }
            }

            return self::postPayment($db, $schoolId, $studentId, $channel, $amount, $recordedBy, $extRef, $cashData);
        });
    }
    /** Create the payment + allocation + ledger + receipt, inside a transaction. */
    private static function postPayment(Database $db, int $schoolId, int $studentId, string $channel, float $amount, ?int $recordedBy, ?string $extRef, array $cashData): array
    {
        $reference = self::newReference('PAY');

        // 1. Create the payment record. provider_ref holds the external
        //    slip / transaction number; intent_id stays NULL (no provider).
        $paymentId = $db->insert('payments', [
            'school_id'     => $schoolId,
            'intent_id'     => null,
            'student_id'    => $studentId,
            'reference'     => $reference,
            'channel'       => $channel,
            'amount'        => $amount,
            'status'        => 'confirmed',
            'provider_ref'  => ($extRef !== '' ? $extRef : null),
            'confirmed_by'  => $recordedBy,
            'confirmed_at'  => date('Y-m-d H:i:s'),
        ]);

        // 2. Allocate to unpaid obligations (oldest first).
        $allocated = AllocationService::allocate($schoolId, $studentId, $paymentId, $amount, $recordedBy);
        $unallocated = round($amount - $allocated, 2); // credit in advance if overpaid

        // 3. Post ledger credit (reduces the student's outstanding balance).
        LedgerService::postCredit(
            $schoolId,
            $studentId,
            $paymentId,
            $amount,
            'Fee payment received via ' . self::CHANNELS[$channel] . ($unallocated > 0 ? " (advance credit: {$unallocated})" : ''),
            $reference,
            $recordedBy
        );

        // 4. Issue the receipt (unique, never-reused number).
        $receiptInfo = self::issueReceipt($schoolId, $paymentId, $amount, $recordedBy);

        // 5. Cash channel -> attach to the bursar's open cash session.
        if ($channel === 'cash') {
            $sessionId = $cashData['cash_session_id'] ?? null;
            if (!$sessionId) {
                $session = CashSessionService::openSessionFor((int)$recordedBy);
                $sessionId = $session ? (int)$session['id'] : null;
            }
            if ($sessionId) {
                $db->insert('cash_payments', [
                    'school_id'           => $schoolId,
                    'payment_id'          => $paymentId,
                    'cashier_id'          => $recordedBy,
                    'cash_session_id'     => $sessionId,
                    'cash_receipt_number' => $cashData['cash_receipt_number'] ?? $receiptInfo['receipt_number'],
                    'received_at'         => date('Y-m-d H:i:s'),
                    'notes'               => $cashData['notes'] ?? null,
                ]);
            }
        }

        // 6. Queue notification to the guardian (independent of posting).
        self::queueReceiptNotification($schoolId, $studentId, $channel, $amount, $receiptInfo['receipt_number']);

        // 7. Audit.
        AuditService::log($schoolId, $recordedBy, 'payment.recorded', 'payments', $paymentId, [
            'channel'     => $channel,
            'amount'      => $amount,
            'ref'         => $reference,
            'ext_ref'     => $extRef,
            'recorded_by' => $recordedBy ? user_name($recordedBy) : 'system',
        ]);

        return [
            'payment_id'     => (int)$paymentId,
            'duplicate'      => false,
            'receipt_id'     => (int)$receiptInfo['id'],
            'receipt_number' => $receiptInfo['receipt_number'],
        ];
    }


    public static function referenceOf(int $paymentId): string
    {
        $p = Database::get()->fetch('SELECT reference FROM payments WHERE id = :id', ['id' => $paymentId]);
        return $p['reference'] ?? '';
    }

    /** Generate a unique, never-reused receipt number. */
    public static function nextReceiptNumber(int $schoolId): string
    {
        $db = Database::get();
        $year = date('Y');
        for ($i = 0; $i < 20; $i++) {
            $seq = (int)$db->scalar(
                "SELECT COALESCE(MAX(CAST(SUBSTR(receipt_number, 12) AS INTEGER)),0)+1
                 FROM receipts WHERE receipt_number LIKE :like",
                ['like' => "RCT-{$year}-%"]
            );
            $number = 'RCT-' . $year . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
            $exists = $db->fetch('SELECT id FROM receipts WHERE receipt_number = :rn', ['rn' => $number]);
            if (!$exists) {
                return $number;
            }
        }
        throw new RuntimeException('Unable to allocate receipt number');
    }

    private static function issueReceipt(int $schoolId, int $paymentId, float $amount, ?int $issuedBy): array
    {
        $number = self::nextReceiptNumber($schoolId);
        $id = Database::get()->insert('receipts', [
            'school_id'      => $schoolId,
            'payment_id'     => $paymentId,
            'receipt_number' => $number,
            'amount'         => $amount,
            'issued_by'      => $issuedBy,
        ]);
        return ['id' => $id, 'receipt_number' => $number];
    }

    private static function queueReceiptNotification(int $schoolId, int $studentId, string $channel, float $amount, string $receiptNo): void
    {
        $guardian = Database::get()->fetch(
            "SELECT g.* FROM guardians g
             JOIN student_guardians sg ON sg.guardian_id = g.id
             WHERE sg.student_id = :sid ORDER BY sg.guardian_id LIMIT 1",
            ['sid' => $studentId]
        );
        $receiver = $guardian['phone'] ?? $guardian['email'] ?? null;
        NotificationService::queue(
            $schoolId,
            $guardian ? 'sms' : 'inapp',
            "Payment confirmed - Receipt {$receiptNo}",
            "Your payment of " . number_format($amount, 2) . " via {$channel} was confirmed. Receipt: {$receiptNo}.",
            null,
            $receiver
        );
    }

    public static function reprintReceipt(int $receiptId): void
    {
        Database::get()->run(
            "UPDATE receipts SET print_count = print_count + 1 WHERE id = :id",
            ['id' => $receiptId]
        );
    }
}

