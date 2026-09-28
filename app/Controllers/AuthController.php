<?php
/** AuthController - login/logout. */
final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::user()) {
            redirect('/');
        }
        $users = Database::get()->fetchAll(
            "SELECT u.email, u.name, GROUP_CONCAT(r.code) AS roles
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             GROUP BY u.id ORDER BY u.id LIMIT 12"
        );
        render('auth/login', ['title' => 'Log in', 'demoUsers' => $users]);
    }

    public function login(): void
    {
        csrf_check();
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $user = Auth::login($email, $password);
        if (!$user) {
            flash_set('error', 'Invalid email or password.');
            redirect('/login');
        }
        AuditService::log(Auth::schoolId(), Auth::id(), 'auth.login', 'users', (int)$user['id']);
        redirect('/');
    }

    public function logout(): void
    {
        csrf_check();
        AuditService::log(Auth::schoolId(), Auth::id(), 'auth.logout', 'users', Auth::id());
        Auth::logout();
        redirect('/');
    }
}
