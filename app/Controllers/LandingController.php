<?php
/** LandingController - public marketing pages + role-specific logins.
 *  All methods are unauthenticated (no requireLogin). */

final class LandingController
{
    /** Home: showcase for guests, dashboard for signed-in users. */
    public function home(): void
    {
        if (Auth::user()) {
            $dashFile = APP_PATH . '/Controllers/DashboardController.php';
            if (is_file($dashFile)) {
                require_once $dashFile;
            }
            (new DashboardController())->index();
            return;
        }
        $schoolCount  = (int)Database::get()->scalar('SELECT COUNT(*) FROM schools') ?: 0;
        $studentCount = (int)Database::get()->scalar("SELECT COUNT(*) FROM students WHERE status = 'active'") ?: 0;
        landing_render('landing/home', [
            'title'        => 'Home',
            'schoolCount'  => $schoolCount,
            'studentCount' => $studentCount,
        ]);
    }

    /** Student login page. */
    public function studentLogin(): void
    {
        if (Auth::user()) { redirect('/'); }
        $demoUsers = Database::get()->fetchAll(
            "SELECT u.email, u.name, GROUP_CONCAT(r.code) AS roles
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.email LIKE '%student%' OR r.code IN ('student')
             GROUP BY u.id ORDER BY u.id LIMIT 4"
        );
        landing_render('landing/student-login', [
            'title' => 'Student Login',
            'demoUsers' => $demoUsers,
        ]);
    }

    /** Parent / guardian login page. */
    public function parentLogin(): void
    {
        if (Auth::user()) { redirect('/'); }
        $demoUsers = Database::get()->fetchAll(
            "SELECT u.email, u.name, GROUP_CONCAT(r.code) AS roles
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE r.code = 'parent'
             GROUP BY u.id ORDER BY u.id LIMIT 4"
        );
        landing_render('landing/parent-login', [
            'title' => 'Parent Login',
            'demoUsers' => $demoUsers,
        ]);
    }

    /** School staff / bursar / admin login page. */
    public function staffLogin(): void
    {
        if (Auth::user()) { redirect('/'); }
        $demoUsers = Database::get()->fetchAll(
            "SELECT u.email, u.name, GROUP_CONCAT(r.code) AS roles
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE r.code IN ('bursar','school_admin','director','accountant','auditor')
             GROUP BY u.id ORDER BY u.id LIMIT 8"
        );
        landing_render('landing/staff-login', [
            'title' => 'Staff Login',
            'demoUsers' => $demoUsers,
        ]);
    }

    /** About us page. */
    public function about(): void
    {
        landing_render('landing/about', ['title' => 'About us']);
    }

    /** Unified POST handler for all three login pages. */
    public function authenticate(): void
    {
        csrf_check();
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $back     = trim((string)($_POST['return_to'] ?? '/'));

        $user = Auth::login($email, $password);
        if (!$user) {
            flash_set('error', 'Invalid email or password. Contact the school office if you need help.');
            redirect($back);
        }
        AuditService::log(Auth::schoolId(), Auth::id(), 'auth.login', 'users', (int)$user['id']);

        // Role-aware redirect.
        if (in_array('parent', Auth::roles(), true)) {
            redirect('/parent');
        }
        if (in_array('student', Auth::roles(), true)) {
            // Students have limited read access — send to dashboard.
            redirect('/');
        }
        redirect('/');
    }
}
