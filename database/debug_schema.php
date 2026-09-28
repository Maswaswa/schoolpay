<?php
require __DIR__ . '/../app/bootstrap.php';
$sql = file_get_contents(SCHEMA_PATH);
$parts = explode(';', $sql);
foreach ($parts as $i => $stmt) {
    $s = trim($stmt);
    if ($s === '' || str_starts_with($s, '--')) continue;
    try {
        Database::get()->run($s);
        echo "[OK] $i\n";
    } catch (Throwable $e) {
        echo "[ERR] $i : " . $e->getMessage() . "\n  -> " . substr(str_replace("\n", ' ', $s), 0, 100) . "\n";
    }
}
echo "done\n";
