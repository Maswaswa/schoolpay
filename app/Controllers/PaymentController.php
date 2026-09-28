<?php
/** PaymentController - bursar records payments against student obligations. */
final class PaymentController
{
    public function index(): void
    {
        Auth::requireCan('ledger.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $recent = $db->fetchAll(
            "SELECT p.*, s.full_name AS student_name, s.student_id,
                    u.name AS recorded_by_name, r.receipt_number
             FROM payments p
             JOIN students s ON s.id = p.student_id
             LEFT JOIN users u ON u.id = p.confirmed_by
             LEFT JOIN receipts r ON r.payment_id = p.id
             WHERE p.school_id = :s
             ORDER BY p.id DESC LIMIT 100",
            ['s' => $schoolId]
        );
        render('payments/index', ['title' => 'Payments', 'payments' => $recent]);
    }

    public function showRecord(): void
    {
        Auth::requireCan('payment.record');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $session = CashSessionService::openSessionFor((int)Auth::id());
        $channels = PaymentService::CHANNELS;

        render('payments/record', [
            'title' => 'Record payment',
            'channels' => $channels,
            'session' => $session,
        ]);
    }

    public function record(): void
    {
        Auth::requireCan('payment.record');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $studentId = (int)($_POST['student_db_id'] ?? $_POST['student_id'] ?? 0);
        $amount    = (float)($_POST['amount'] ?? 0);
        $channel   = trim((string)($_POST['channel'] ?? ''));
        $extRef    = trim((string)($_POST['ext_ref'] ?? ''));

        if (!$studentId || $amount <= 0 || !isset(PaymentService::CHANNELS[$channel])) {
            flash_set('error', 'Student, amount and channel are required.');
            redirect('/payments/record');
        }

        $student = $db->fetch(
            'SELECT id, full_name FROM students WHERE id = :id AND school_id = :s',
            ['id' => $studentId, 's' => $schoolId]
        );
        if (!$student) {
            flash_set('error', 'Student not found in this school.');
            redirect('/payments/record');
        }

        $cashData = [];
        $activeSession = CashSessionService::openSessionFor((int)Auth::id());
        if ($channel === 'cash') {
            if (!$activeSession) {
                flash_set('error', 'Open a cash session before recording cash payments.');
                redirect('/cash/open');
            }
            $cashData['cash_session_id'] = (int)$activeSession['id'];
            $seq = (int)$db->scalar(
                'SELECT COALESCE(MAX(CAST(cash_receipt_number AS INTEGER)),0)+1 FROM cash_payments WHERE cash_session_id = :sid',
                ['sid' => $activeSession['id']]
            ) ?: 1;
            $cashData['cash_receipt_number'] = 'CR-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        }

        $result = PaymentService::recordPayment(
            $schoolId, $studentId, $channel, $amount, Auth::id(), $extRef ?: null, $cashData
        );

        if ($result['duplicate']) {
            flash_set('warning', "Duplicate reference '{$extRef}' — receipt {$result['receipt_number']} already exists.");
            if ($result['receipt_id']) {
                redirect('/receipts/show?id=' . $result['receipt_id']);
            }
            redirect('/payments/record');
        }

        flash_set('success', "Payment of " . money($amount) . " recorded. Receipt: {$result['receipt_number']}");
        redirect('/receipts/show?id=' . $result['receipt_id']);
    }
}
