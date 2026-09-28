<?php
/**
 * Front controller + router (plain PHP, no framework).
 * Route map: 'PATH' => 'Controller@method'
 *
 * MVP constraint: the bursar is the ONLY role that can accept payments.
 * There is no payment provider, gateway, callback or intermediary —
 * therefore there is no /payment/callback, /webhook or provider route.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

// ---- Global extra stylesheet (loaded by both layouts after their base CSS) ----
define('EXTRA_CSS', '/assets/css/custom.css');

// ---- Serve static assets when using the PHP built-in server ----
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        return false; // let the built-in server stream css/js/images
    }
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/');
if ($path === '') {
    $path = '/';
}

// ---- Ensure the database is installed before anything else ----
$installed = false;
try {
    $installed = (bool)Database::get()->scalar(
        "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'"
    );
} catch (Throwable $e) {
    $installed = false;
}
$isInstallRoute = str_starts_with($path, '/install');
if (!$installed && !$isInstallRoute) {
    redirect('/install');
}

$routes = [
    'GET'  => [
        '/'                        => 'LandingController@home',
        '/install'                 => 'InstallController@index',
        '/login'                   => 'AuthController@showLogin',
        '/logout'                  => 'AuthController@logout',
        '/student-login'           => 'LandingController@studentLogin',
        '/parent-login'            => 'LandingController@parentLogin',
        '/staff-login'             => 'LandingController@staffLogin',
        '/about'                   => 'LandingController@about',
        '/students'                => 'StudentController@index',
        '/students/create'         => 'StudentController@create',
        '/students/show'           => 'StudentController@show',
        '/fees/items'              => 'FeeController@items',
        '/fees/structures'         => 'FeeController@structures',
        '/fees/structures/create'  => 'FeeController@createStructure',
        '/fees/billing'            => 'FeeController@billing',
        '/payments'                => 'PaymentController@index',
        '/payments/record'         => 'PaymentController@showRecord',
        '/cash'                    => 'CashController@index',
        '/cash/open'               => 'CashController@showOpen',
        '/cash/close'              => 'CashController@showClose',
        '/cash/show'               => 'CashController@show',
        '/receipts/show'           => 'ReceiptController@show',
        '/reconciliation'          => 'ReconciliationController@index',
        '/reports/daily'           => 'ReportController@daily',
        '/reports/outstanding'     => 'ReportController@outstanding',
        '/users'                   => 'UserController@index',
        '/users/create'            => 'UserController@create',
        '/audit'                   => 'AuditController@index',
        '/parent'                  => 'ParentController@index',
        '/parent/child'            => 'ParentController@child',
        '/lookup/student'          => 'StudentController@lookup',
        '/platform'                => 'PlatformController@index',
        '/platform/schools'        => 'PlatformController@schools',
        '/platform/schools/new'    => 'PlatformController@schoolNew',
        '/platform/users'          => 'PlatformController@users',
        '/platform/users/new'      => 'PlatformController@userNew',
        '/platform/audit'          => 'PlatformController@audit',
    ],
    'POST' => [
        '/install/run'             => 'InstallController@run',
        '/login'                   => 'AuthController@login',
        '/auth/landing'            => 'LandingController@authenticate',
        '/logout'                  => 'AuthController@logout',
        '/students/store'          => 'StudentController@store',
        '/fees/items/store'        => 'FeeController@storeItem',
        '/fees/structures/store'   => 'FeeController@storeStructure',
        '/fees/billing/run'        => 'FeeController@runBilling',
        '/payments/record'         => 'PaymentController@record',
        '/cash/open'               => 'CashController@open',
        '/cash/close'              => 'CashController@close',
        '/receipts/reprint'        => 'ReceiptController@reprint',
        '/users/store'             => 'UserController@store',
        '/platform/schools/store'  => 'PlatformController@schoolStore',
        '/platform/schools/toggle' => 'PlatformController@schoolToggle',
        '/platform/users/store'    => 'PlatformController@userStore',
        '/platform/users/toggle'   => 'PlatformController@userToggle',
        '/notifications/process'   => 'DashboardController@processNotifications',
    ],
];

$method = $_SERVER['REQUEST_METHOD'];
$route  = $routes[$method][$path] ?? null;

if ($route === null) {
    abort(404, "Page not found: {$path}");
}

[$controller, $action] = explode('@', $route);
$controllerFile  = APP_PATH . '/Controllers/' . $controller . '.php';

if (!is_file($controllerFile)) {
    abort(500, "Controller file missing: {$controller}");
}

require_once $controllerFile;

if (!class_exists($controller) || !method_exists($controller, $action)) {
    abort(500, "Invalid controller action: {$route}");
}

(new $controller())->{$action}();
