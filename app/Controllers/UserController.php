<?php
/** UserController - school staff account management (school_admin only). */
final class UserController
{
    public function index(): void
    {
        Auth::requireCan('users.manage');
        $db = Database::get();
        $users = $db->fetchAll(
            "SELECT u.*, GROUP_CONCAT(r.code) AS role_codes
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.school_id = :s
             GROUP BY u.id ORDER BY u.name",
            ['s' => Auth::schoolId()]
        );
        render('users/index', ['title' => 'Staff accounts', 'users' => $users]);
    }

    public function create(): void
    {
        Auth::requireCan('users.manage');
        $roles = Database::get()->fetchAll('SELECT * FROM roles ORDER BY code');
        render('users/create', ['title' => 'Add staff user', 'roles' => $roles]);
    }

    public function store(): void
    {
        Auth::requireCan('users.manage');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $name  = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $roleCode = trim((string)($_POST['role'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || $roleCode === '') {
            flash_set('error', 'Name, valid email, password (min 8 chars) and role are required.');
            redirect('/users/create');
        }
        if ($db->fetch('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
            flash_set('error', 'A user with this email already exists.');
            redirect('/users/create');
        }
        $role = $db->fetch('SELECT * FROM roles WHERE code = :c', ['c' => $roleCode]);
        if (!$role) {
            flash_set('error', 'Unknown role.');
            redirect('/users/create');
        }

        $uid = $db->insert('users', [
            'school_id'     => $schoolId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => trim((string)($_POST['phone'] ?? '')) ?: null,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);
        $db->insert('user_roles', ['user_id' => $uid, 'role_id' => (int)$role['id']]);

        AuditService::log($schoolId, Auth::id(), 'user.created', 'users', $uid, ['email' => $email, 'role' => $roleCode]);
        flash_set('success', "User {$name} created with role '{$roleCode}'.");
        redirect('/users');
    }
}
