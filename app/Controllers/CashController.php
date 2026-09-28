<?php
/** CashController - cashier session management (open/close/view). */
final class CashController
{
    public function index(): void
    {
        Auth::requireCan('cash.session');
        $db = Database::get();
        $session = CashSessionService::openSessionFor((int)Auth::id());

        $recent = $db->fetchAll(
            "SELECT cs.*,
                    COALESCE((SELECT SUM(p.amount) FROM cash_payments cp JOIN payments p ON p.id = cp.payment_id
                              WHERE cp.cash_session_id = cs.id),0) AS cash_total,
                    (cs.opening_amount + COALESCE((SELECT SUM(p.amount) FROM cash_payments cp
                              JOIN payments p ON p.id = cp.payment_id WHERE cp.cash_session_id = cs.id),0))
                       AS expected_amount,
                    u.name AS cashier_name
             FROM cash_sessions cs
             JOIN users u ON u.id = cs.cashier_id
             WHERE cs.cashier_id = :uid ORDER BY cs.id DESC LIMIT 20",
            ['uid' => Auth::id()]
        );
        render('cash/index', ['title' => 'Cash sessions', 'session' => $session, 'recent' => $recent]);
    }

    public function showOpen(): void
    {
        Auth::requireCan('cash.session');
        $session = CashSessionService::openSessionFor((int)Auth::id());
        if ($session) {
            flash_set('info', 'You already have an open session (opened ' . date('d M Y H:i', strtotime((string)$session['created_at'])) . ').');
            redirect('/cash');
        }
        render('cash/open', ['title' => 'Open session']);
    }

    public function open(): void
    {
        Auth::requireCan('cash.session');
        csrf_check();
        $active = CashSessionService::openSessionFor((int)Auth::id());
        if ($active) {
            flash_set('warning', 'A session is already open.');
            redirect('/cash');
        }
        $opening = (float)($_POST['opening_amount'] ?? 0);
        CashSessionService::open((int)Auth::schoolId(), null, (int)Auth::id(), $opening);
        flash_set('success', 'Cash session opened. Opening float: ' . money($opening));
        redirect('/cash');
    }

    public function showClose(): void
    {
        Auth::requireCan('cash.session');
        $session = CashSessionService::openSessionFor((int)Auth::id());
        if (!$session) {
            flash_set('error', 'No open session found.');
            redirect('/cash');
        }
        $payments = CashSessionService::paymentsIn((int)$session['id']);
        $expected = CashSessionService::expectedAmount((int)$session['id']);
        render('cash/close', ['title' => 'Close session', 'session' => $session, 'payments' => $payments, 'expected' => $expected]);
    }

    public function close(): void
    {
        Auth::requireCan('cash.session');
        csrf_check();
        $session = CashSessionService::openSessionFor((int)Auth::id());
        if (!$session) {
            flash_set('error', 'No open session.');
            redirect('/cash');
        }
        $declared = (float)($_POST['declared_amount'] ?? 0);
        $notes    = trim((string)($_POST['notes'] ?? ''));
        $result = CashSessionService::close((int)$session['id'], $declared);

        AuditService::log((int)Auth::schoolId(), Auth::id(), 'cash.session.close', 'cash_sessions', (int)$session['id'], [
            'expected' => $result['expected'] ?? 0, 'declared' => $declared,
            'difference' => $result['difference'] ?? 0, 'status' => $result['status'] ?? 'closed',
            'notes' => $notes,
        ]);

        flash_set('info', 'Session closed. Expected: ' . money($result['expected'] ?? 0) . ', Declared: ' . money($declared));
        redirect('/cash');
    }

    public function show(): void
    {
        Auth::requireCan('cash.session');
        $sid = (int)($_GET['id'] ?? 0);
        $db = Database::get();
        $session = $db->fetch(
            "SELECT cs.*, u.name AS cashier_name FROM cash_sessions cs JOIN users u ON u.id = cs.cashier_id WHERE cs.id = :id",
            ['id' => $sid]
        );
        if (!$session) { abort(404, 'Session not found.'); }
        $payments = CashSessionService::paymentsIn($sid);
        $expected = CashSessionService::expectedAmount($sid);
        render('cash/session', ['title' => 'Cash session', 'session' => $session, 'payments' => $payments, 'expected' => $expected]);
    }
}
