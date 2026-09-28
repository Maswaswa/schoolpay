<?php
/** DashboardController - role-aware landing page. */
final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        // Platform admins use the dedicated console instead of the school dashboard
        if (Auth::hasRole('platform_admin')) {
            redirect('/platform');
        }

        $db = Database::get();
        $schoolId = Auth::schoolId();
        $user = Auth::user();
        $today = date('Y-m-d');

        $stats = [];
        $isBursar = Auth::can('payment.record');

        if ($schoolId && (Auth::can('students.manage') || Auth::can('students.view'))) {
            $stats['students'] = (int)$db->scalar(
                "SELECT COUNT(*) FROM students WHERE school_id = :s AND status='active'",
                ['s' => $schoolId]
            );
        }

        if ($isBursar && $schoolId) {
            $stats['collected_today'] = (float)($db->scalar(
                "SELECT COALESCE(SUM(amount),0) FROM payments
                 WHERE school_id = :s AND status='confirmed' AND confirmed_at LIKE :d",
                ['s' => $schoolId, 'd' => $today . '%']
            ) ?? 0);
            $stats['collected_term'] = (float)($db->scalar(
                "SELECT COALESCE(SUM(amount),0) FROM payments WHERE school_id = :s AND status='confirmed'",
                ['s' => $schoolId]
            ) ?? 0);
            $stats['billed_term'] = (float)($db->scalar(
                "SELECT COALESCE(SUM(total_amount),0) FROM fee_obligations WHERE school_id = :s",
                ['s' => $schoolId]
            ) ?? 0);
            $stats['outstanding'] = round($stats['billed_term'] - min($stats['billed_term'], $stats['collected_term']), 2);

            $openSession = CashSessionService::openSessionFor((int)$user['id']);
            $stats['open_session'] = $openSession;
        }

        if (Auth::can('recon.view') && $schoolId) {
            $stats['open_cases'] = (int)$db->scalar(
                "SELECT COUNT(*) FROM reconciliation_cases WHERE school_id = :s AND status='open'",
                ['s' => $schoolId]
            );
        }

        if (Auth::can('children.view')) {
            redirect('/parent');
        }

        render('dashboard/index', ['title' => 'Dashboard', 'stats' => $stats, 'isBursar' => $isBursar]);
    }

    public function processNotifications(): void
    {
        Auth::requireLogin();
        csrf_check();
        $sent = NotificationService::processQueue(50);
        flash_set('success', "Notification queue processed — {$sent} message(s) sent.");
        redirect('/');
    }
}
