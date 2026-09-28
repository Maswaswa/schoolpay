<?php
/**
 * PlatformController - system-owner console (platform_admin only).
 * Cross-tenant: all schools, all users, platform audit, school onboarding.
 */
final class PlatformController
{
    public function index(): void
    {
        Auth::requirePlatform();
        $db = Database::get();

        $stats = [
            'schools'      => (int)$db->scalar("SELECT COUNT(*) FROM schools"),
            'schools_on'   => (int)$db->scalar("SELECT COUNT(*) FROM schools WHERE status = 'active'"),
            'students'     => (int)$db->scalar("SELECT COUNT(*) FROM students WHERE status = 'active'"),
            'users'        => (int)$db->scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'billed'       => (float)($db->scalar("SELECT COALESCE(SUM(total_amount),0) FROM fee_obligations") ?? 0),
            'collected'    => (float)($db->scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'confirmed'") ?? 0),
        ];
        $stats['outstanding'] = round(max($stats['billed'] - $stats['collected'], 0), 2);
        $stats['collected_today'] = (float)($db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='confirmed' AND confirmed_at LIKE :d",
            ['d' => date('Y-m-d') . '%']
        ) ?? 0);

        // Per-school rollup (the "hold all schools records" table)
        $schools = $db->fetchAll("
            SELECT s.id, s.code, s.name, s.status,
              (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.status='active')  AS students,
              (SELECT COUNT(*) FROM users u      WHERE u.school_id = s.id AND u.status='active')   AS users,
              (SELECT COALESCE(SUM(total_amount),0) FROM fee_obligations o WHERE o.school_id = s.id) AS billed,
              (SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.school_id = s.id AND p.status='confirmed') AS collected
            FROM schools s ORDER BY s.name");
        foreach ($schools as &$s) {
            $s['billed'] = (float)$s['billed'];
            $s['collected'] = (float)$s['collected'];
            $s['outstanding'] = round(max($s['billed'] - $s['collected'], 0), 2);
        }
        unset($s);

        $recent = $db->fetchAll("
            SELECT a.*, u.name AS actor_name
            FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.id DESC LIMIT 8");

        render('platform/dashboard', [
            'title' => 'Platform overview', 'stats' => $stats, 'schools' => $schools, 'recent' => $recent,
        ]);
    }

    public function schools(): void
    {
        Auth::requirePlatform();
        $db = Database::get();
        $schools = $db->fetchAll("
            SELECT s.*,
              (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.status='active') AS students,
              (SELECT COUNT(*) FROM users u      WHERE u.school_id = s.id AND u.status='active')  AS users,
              (SELECT COUNT(*) FROM campuses c   WHERE c.school_id = s.id)                        AS campuses,
              (SELECT COUNT(*) FROM payments p   WHERE p.school_id = s.id AND p.status='confirmed') AS payments,
              (SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.school_id = s.id AND p.status='confirmed') AS collected
            FROM schools s ORDER BY s.name");
        render('platform/schools', ['title' => 'Schools', 'schools' => $schools]);
    }

    public function schoolNew(): void
    {
        Auth::requirePlatform();
        render('platform/school_new', ['title' => 'Onboard school']);
    }

    public function schoolStore(): void
    {
        Auth::requirePlatform();
        csrf_check();
        $db = Database::get();

        $code       = strtoupper(trim((string)($_POST['code'] ?? '')));
        $name       = trim((string)($_POST['name'] ?? ''));
        $campusName = trim((string)($_POST['campus'] ?? '')) ?: 'Main Campus';
        $adminName  = trim((string)($_POST['admin_name'] ?? ''));
        $adminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $adminPass  = (string)($_POST['admin_password'] ?? '');
        $yearName   = trim((string)($_POST['year_name'] ?? ''));
        $termName   = trim((string)($_POST['term_name'] ?? ''));

        if ($code === '' || $name === '' || $adminName === '' || $adminEmail === '' || strlen($adminPass) < 8) {
            flash_set('error', 'School code, name, admin name, admin email and a password (min 8 chars) are required.');
            redirect('/platform/schools/new');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Invalid admin email address.');
            redirect('/platform/schools/new');
        }
        if ($db->fetch('SELECT id FROM schools WHERE code = :c', ['c' => $code])) {
            flash_set('error', "School code '{$code}' is already taken.");
            redirect('/platform/schools/new');
        }
        if ($db->fetch('SELECT id FROM users WHERE email = :e', ['e' => $adminEmail])) {
            flash_set('error', 'A user with this admin email already exists.');
            redirect('/platform/schools/new');
        }

        $roleId = fn(string $c): int => (int)$db->scalar('SELECT id FROM roles WHERE code = :c', ['c' => $c]);

        $db->transaction(function () use ($db, $code, $name, $campusName, $adminName, $adminEmail, $adminPass, $yearName, $termName, $roleId) {
            $schoolId = $db->insert('schools', ['code' => $code, 'name' => $name, 'status' => 'active']);
            $db->insert('campuses', ['school_id' => $schoolId, 'name' => $campusName]);

            // School admin + bursar starter accounts (share the admin password)
            $adminId = $db->insert('users', [
                'school_id'     => $schoolId,
                'name'          => $adminName,
                'email'         => $adminEmail,
                'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT),
                'status'        => 'active',
            ]);
            $db->insert('user_roles', ['user_id' => $adminId, 'role_id' => $roleId('school_admin')]);

            $bursarId = $db->insert('users', [
                'school_id'     => $schoolId,
                'name'          => 'Bursar - ' . $name,
                'email'         => 'bursar.' . strtolower($code) . '@school.test',
                'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT),
                'status'        => 'active',
            ]);
            $db->insert('user_roles', ['user_id' => $bursarId, 'role_id' => $roleId('bursar')]);

            // Current academic year + first term so billing can start immediately
            $ayName = $yearName ?: (date('Y') . '/' . ((int)date('Y') + 1));
            $ayId = $db->insert('academic_years', ['school_id' => $schoolId, 'name' => $ayName, 'is_current' => 1]);
            $db->insert('terms', ['school_id' => $schoolId, 'academic_year_id' => $ayId, 'name' => ($termName ?: 'Term 1')]);

            AuditService::log(null, Auth::id(), 'school.onboarded', 'schools', $schoolId,
                ['code' => $code, 'name' => $name, 'admin_email' => $adminEmail]);
        });

        flash_set('success', "School '{$name}' ({$code}) onboarded. Admin + bursar accounts share the password you set.");
        redirect('/platform/schools');
    }

    public function schoolToggle(): void
    {
        Auth::requirePlatform();
        csrf_check();
        $db = Database::get();
        $id = (int)($_POST['id'] ?? 0);
        $school = $db->fetch('SELECT id, name, status FROM schools WHERE id = :id', ['id' => $id]);
        if (!$school) {
            flash_set('error', 'School not found.');
            redirect('/platform/schools');
        }
        $newStatus = $school['status'] === 'active' ? 'suspended' : 'active';
        $db->update('schools', ['status' => $newStatus], 'id = :id', ['id' => $id]);
        AuditService::log(null, Auth::id(), 'school.status_changed', 'schools', $id, ['to' => $newStatus]);
        flash_set('success', $school['name'] . ' is now ' . $newStatus . '.');
        redirect('/platform/schools');
    }

    public function users(): void
    {
        Auth::requirePlatform();
        $db = Database::get();
        $filterSchool = (int)($_GET['school'] ?? 0);
        $filterRole   = trim((string)($_GET['role'] ?? ''));
        $allSchools   = $db->fetchAll('SELECT id, code, name FROM schools ORDER BY name');
        $allRoles     = $db->fetchAll('SELECT id, code FROM roles ORDER BY code');

        $sql = "SELECT u.id, u.email, u.name, u.status, u.school_id,
                       GROUP_CONCAT(r.code) AS role_codes, s.name AS school_name
                FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON r.id = ur.role_id
                LEFT JOIN schools s ON s.id = u.school_id
                WHERE 1=1";
        $params = [];
        if ($filterSchool > 0) {
            $sql .= ' AND u.school_id = :sid';
            $params['sid'] = $filterSchool;
        }
        if ($filterRole !== '') {
            $sql .= ' AND r.code = :rc';
            $params['rc'] = $filterRole;
        }
        $sql .= ' GROUP BY u.id ORDER BY u.name';
        $users = $db->fetchAll($sql, $params);

        render('platform/users', [
            'title' => 'All users', 'users' => $users,
            'allSchools' => $allSchools, 'allRoles' => $allRoles,
            'filterSchool' => $filterSchool, 'filterRole' => $filterRole,
        ]);
    }

    public function userNew(): void
    {
        Auth::requirePlatform();
        $db = Database::get();
        $schools = $db->fetchAll('SELECT id, code, name FROM schools ORDER BY name');
        $roles   = $db->fetchAll('SELECT * FROM roles ORDER BY code');
        render('platform/user_new', ['title' => 'Create user', 'schools' => $schools, 'roles' => $roles]);
    }

    public function userStore(): void
    {
        Auth::requirePlatform();
        csrf_check();
        $db = Database::get();
        $name     = trim((string)($_POST['name'] ?? ''));
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $schoolId = ($_POST['school_id'] ?? '') === '' || (int)($_POST['school_id'] ?? 0) === 0
            ? null : (int)$_POST['school_id'];
        $roleCode = trim((string)($_POST['role'] ?? ''));
        $pass     = (string)($_POST['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || $roleCode === '') {
            flash_set('error', 'Name, valid email, password (min 8 chars) and role are required.');
            redirect('/platform/users/new');
        }
        if ($db->fetch('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
            flash_set('error', 'A user with this email already exists.');
            redirect('/platform/users/new');
        }
        $role = $db->fetch('SELECT id FROM roles WHERE code = :c', ['c' => $roleCode]);
        if (!$role) {
            flash_set('error', 'Unknown role.');
            redirect('/platform/users/new');
        }
        $uid = $db->insert('users', [
            'school_id'     => $schoolId,
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);
        $db->insert('user_roles', ['user_id' => $uid, 'role_id' => (int)$role['id']]);
        AuditService::log($schoolId, Auth::id(), 'user.created', 'users', $uid,
            ['email' => $email, 'school_id' => $schoolId, 'role' => $roleCode]);
        flash_set('success', "User {$name} created with role '{$roleCode}'.");
        redirect('/platform/users');
    }

    public function userToggle(): void
    {
        Auth::requirePlatform();
        csrf_check();
        $db = Database::get();
        $id = (int)($_POST['id'] ?? 0);
        $user = $db->fetch('SELECT id, name, status, email, school_id FROM users WHERE id = :id', ['id' => $id]);
        if (!$user) {
            flash_set('error', 'User not found.');
            redirect('/platform/users');
        }
        if ($user['email'] === 'platform@schoolfees.test') {
            flash_set('error', 'The platform admin account cannot be disabled.');
            redirect('/platform/users');
        }
        $newStatus = $user['status'] === 'active' ? 'disabled' : 'active';
        $db->update('users', ['status' => $newStatus], 'id = :id', ['id' => $id]);
        AuditService::log($user['school_id'], Auth::id(), 'user.status_changed', 'users', $id, ['to' => $newStatus]);
        flash_set('success', $user['name'] . ' set to ' . $newStatus . '.');
        redirect('/platform/users');
    }

    public function audit(): void
    {
        Auth::requirePlatform();
        $db = Database::get();
        $filterSchool = (int)($_GET['school'] ?? 0);
        $filterAction = trim((string)($_GET['action'] ?? ''));
        $allSchools   = $db->fetchAll('SELECT id, code, name FROM schools ORDER BY name');

        $sql = "SELECT a.*, u.name AS actor_name, s.name AS school_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN schools s ON s.id = a.school_id
                WHERE 1=1";
        $params = [];
        if ($filterSchool > 0) {
            $sql .= ' AND a.school_id = :sid';
            $params['sid'] = $filterSchool;
        }
        if ($filterAction !== '') {
            $sql .= ' AND a.action LIKE :act';
            $params['act'] = '%' . $filterAction . '%';
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 300';
        $logs = $db->fetchAll($sql, $params);

        render('platform/audit', [
            'title' => 'Platform audit log', 'logs' => $logs,
            'allSchools' => $allSchools, 'filterSchool' => $filterSchool, 'filterAction' => $filterAction,
        ]);
    }
}