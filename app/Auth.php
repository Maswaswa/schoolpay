<?php
/**
 * Auth - session-based authentication + role-based access control (RBAC).
 * Roles and their permitted operations follow the design document §4/§21.
 */
final class Auth
{
    private const PERMISSIONS = [
        'platform_admin' => ['*'],
        'director'       => ['reports.view', 'audit.view', 'adjust.approve', 'students.view', 'receipt.view', 'ledger.view', 'recon.view'],
        'school_admin'   => ['users.manage', 'students.manage', 'fees.manage', 'billing.manage', 'reports.view', 'school.manage', 'receipt.view', 'children.view'],
        'bursar'         => ['students.manage', 'fees.manage', 'billing.manage', 'payment.record', 'cash.session', 'recon.manage', 'recon.view', 'adjust.request', 'adjust.approve', 'reports.view', 'ledger.view', 'receipt.view', 'receipt.reprint'],
        'accountant'     => ['students.view', 'recon.manage', 'recon.view', 'reports.view', 'ledger.view', 'receipt.view', 'adjust.request', 'adjust.approve'],
        'cashier'        => ['students.view', 'receipt.view', 'receipt.reprint', 'ledger.view'],
        'teacher'        => ['students.view'],
        'parent'         => ['children.view', 'receipt.view'],
        'auditor'        => ['reports.view', 'audit.view', 'ledger.view', 'receipt.view', 'recon.view'],
        'support'        => ['students.view', 'recon.view', 'recon.manage', 'receipt.view'],
    ];

    public static function login(string $email, string $password): ?array
    {
        $user = Database::get()->fetch('SELECT * FROM users WHERE email = :e AND status = :s', ['e' => $email, 's' => 'active']);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        Database::get()->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        $_SESSION['user_id'] = (int)$user['id'];
        return self::user();
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = Database::get()->fetch('SELECT * FROM users WHERE id = :id', ['id' => $_SESSION['user_id']]);
        if (!$user) {
            return null;
        }
        $user['roles'] = array_column(Database::get()->fetchAll(
            "SELECT r.code FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :uid",
            ['uid' => $user['id']]
        ), 'code');
        return $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function schoolId(): ?int
    {
        $u = self::user();
        if (!$u || $u['school_id'] === null || $u['school_id'] === '') {
            return null;
        }
        return (int)$u['school_id'];
    }

    public static function roles(): array
    {
        $u = self::user();
        return $u['roles'] ?? [];
    }

    public static function hasRole(string $code): bool
    {
        return in_array($code, self::roles(), true);
    }

    public static function can(string $permission): bool
    {
        foreach (self::roles() as $role) {
            $perms = self::PERMISSIONS[$role] ?? [];
            if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
                return true;
            }
        }
        return false;
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            redirect('/'); // guests see the public landing / login chooser
        }
    }

    public static function requireCan(string $permission): void
    {
        self::requireLogin();
        if (!self::can($permission)) {
            http_response_code(403);
            abort(403, 'You are not authorized to perform this action.');
        }
    }

    /**
     * Gate platform-only routes. Throws 403 for any non-platform-admin.
     */
    public static function requirePlatform(): void
    {
        self::requireLogin();
        if (!self::hasRole('platform_admin')) {
            http_response_code(403);
            abort(403, 'Platform administrators only.');
        }
    }

    public static function permissionList(): array
    {
        return self::PERMISSIONS;
    }
}
