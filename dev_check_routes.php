<?php
/** dev: verify every route's Controller@method exists. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('APP_PATH', 'c:/Users/maswa/OneDrive/Desktop/schoolpay/mvp/app');
$src = file_get_contents('c:/Users/maswa/OneDrive/Desktop/schoolpay/mvp/public/index.php');
preg_match_all("/'(\/[a-z\/]+)'\s+=>\s+'([A-Za-z]+)@([A-Za-z]+)'/", $src, $m, PREG_SET_ORDER);
$missing = [];
$loaded = [];
foreach ($m as $r) {
    $ctrl = $r[2]; $method = $r[3];
    if (!isset($loaded[$ctrl])) {
        $f = APP_PATH . '/Controllers/' . $ctrl . '.php';
        if (!is_file($f)) { $missing[] = "$ctrl (file missing)"; continue; }
        require_once $f;
        $loaded[$ctrl] = true;
    }
    if (!method_exists($ctrl, $method)) { $missing[] = "$ctrl@$method"; }
}
echo count($m) . " routes checked\n";
echo $missing ? "MISSING:\n" . implode("\n", $missing) : "All route methods exist\n";
