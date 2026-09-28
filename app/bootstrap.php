<?php
/**
 * bootstrap - configuration, autoloading, session, and global helpers.
 * Run from public/index.php.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/views');
define('DB_PATH', BASE_PATH . '/database/schoolpay.sqlite');
define('SCHEMA_PATH', BASE_PATH . '/database/schema.sql');
define('APP_NAME', 'School Fees Information System');

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Lightweight PSR-4 style autoloader for global classes ----
spl_autoload_register(function (string $class): void {
    $dirs = [APP_PATH, APP_PATH . '/Models', APP_PATH . '/Services'];
    foreach ($dirs as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// ---- Database ----
Database::connect('sqlite:' . DB_PATH);

// Activate schema if the database is empty / missing.
require_once __DIR__ . '/Services/StudentIdService.php';
// (schema is applied by the installer controller if tables are missing)

// ---- Helpers ----
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function money(float $value): string
{
    return number_format($value, 2);
}

function csrf_field(): string
{
    $_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
    return '<input type="hidden" name="_token" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_token'] ?? '';
    $stored = $_SESSION['csrf'] ?? '';
    if (!$stored || !hash_equals($stored, (string)$sent)) {
        abort(403, 'Invalid security token. Please go back and retry.');
    }
}

function flash_set(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string
{
    return $_SESSION['flash'][$key] ?? null;
}

function flash_drain(string $key): void
{
    unset($_SESSION['flash'][$key]);
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function user_name(?int $id): string
{
    if (!$id) return 'System';
    $u = Database::get()->fetch('SELECT name FROM users WHERE id = :id', ['id' => $id]);
    return $u['name'] ?? 'Unknown';
}

function school_name(?int $schoolId): string
{
    if (!$schoolId) return 'Platform';
    $s = Database::get()->fetch('SELECT name FROM schools WHERE id = :id', ['id' => $schoolId]);
    return $s['name'] ?? 'Unknown';
}

/**
 * Render a view inside the shared layout.
 */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require VIEW_PATH . '/layout.php';
}

/**
 * Render a view inside the public landing layout (no sidebar).
 */
function landing_render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require VIEW_PATH . '/landing.php';
}

/**
 * Render an error page (self-contained, no layout dependency).
 */
function abort(int $code, string $message): void
{
    http_response_code($code);
    $error = ['code' => $code, 'message' => $message];
    $appName = APP_NAME;
    require VIEW_PATH . '/error.php';
    exit;
}

/**
 * Validate that a query returned exactly the user's school (tenant isolation).
 * Simplest enforcement: every controller scopes queries by Auth::schoolId().
 */
function assert_school(int $schoolId): void
{
    if (Auth::hasRole('platform_admin')) {
        return; // platform admins may operate across tenants (their own school_id is NULL)
    }
    $own = Auth::schoolId();
    if ($own === null || $own !== $schoolId) {
        AuditService::log($schoolId, Auth::id(), 'tenant.denied', 'schools', $schoolId);
        abort(403, 'Cross-tenant access denied.');
    }
}
