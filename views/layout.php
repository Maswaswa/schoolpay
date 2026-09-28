<?php
/** Shared layout shell. Views are partials required at the content mark. */
$user    = Auth::user();
$path    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$flashOk  = flash_get('success'); flash_drain('success');
$flashErr = flash_get('error');   flash_drain('error');
$flashInfo= flash_get('info');    flash_drain('info');

$can = fn(string $p): bool => Auth::can($p);
$isBursar = $user && ($can('payment.record'));

$navGroups = [];
$navGroups[] = ['label' => 'Overview', 'items' => [
    ['/' => 'Dashboard'],
]];
if ($can('students.manage') || $can('students.view')) {
    $navGroups[] = ['label' => 'Students', 'items' => [
        ['/students' => 'Students'],
    ]];
}
if ($can('fees.manage')) {
    $navGroups[] = ['label' => 'Fees & Billing', 'items' => [
        ['/fees/items' => 'Fee items'],
        ['/fees/structures' => 'Fee structures'],
        ['/fees/billing' => 'Invoice a term'],
    ]];
}
if ($isBursar) {
    $navGroups[] = ['label' => 'Collections', 'items' => [
        ['/payments/record' => 'Record payment'],
        ['/payments' => 'Payments log'],
        ['/cash' => 'Cash sessions'],
    ]];
}
if ($can('recon.view')) {
    $navGroups[] = ['label' => 'Controls', 'items' => [
        ['/reconciliation' => 'Reconciliation'],
        ['/reports/daily' => 'Daily collections'],
        ['/reports/outstanding' => 'Outstanding balances'],
    ]];
}
if ($can('users.manage')) {
    $navGroups[] = ['label' => 'Administration', 'items' => [
        ['/users' => 'Staff & roles'],
        ['/audit' => 'Audit log'],
    ]];
} elseif ($can('audit.view')) {
    $navGroups[] = ['label' => 'Administration', 'items' => [
        ['/audit' => 'Audit log'],
    ]];
}
// ---- Platform console (platform_admin only) ----
if ($user && Auth::hasRole('platform_admin')) {
    $navGroups[] = ['label' => 'Platform', 'items' => [
        ['/platform' => 'Overview'],
        ['/platform/schools' => 'Schools'],
        ['/platform/schools/new' => 'Onboard school'],
        ['/platform/users' => 'All users'],
        ['/platform/audit' => 'Platform audit'],
    ]];
}
if ($can('children.view')) {
    $navGroups[] = ['label' => 'My family', 'items' => [
        ['/parent' => 'My children'],
    ]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? APP_NAME) ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<?php if (defined('EXTRA_CSS')): ?><link rel="stylesheet" href="<?= e(EXTRA_CSS) ?>"><?php endif; ?>
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark">SF</span>
      <span class="brand-name">School Fees IS</span>
    </div>
    <?php if ($user): ?>
    <div class="user-chip">
      <strong><?= e($user['name']) ?></strong>
      <small><?= e(implode(', ', Auth::roles())) ?> · <?= e(school_name($user['school_id'] ? (int)$user['school_id'] : null)) ?></small>
    </div>
    <?php endif; ?>
    <nav>
      <?php foreach ($navGroups as $group): ?>
        <div class="nav-group">
          <div class="nav-label"><?= e($group['label']) ?></div>
          <?php foreach ($group['items'] as $item): ?>
            <?php $href = (string)array_key_first($item); $label = (string)$item[$href]; ?>
            <a class="nav-link<?= $path === $href ? ' active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <?php if ($user): ?>
    <form method="post" action="/logout" class="logout-form">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-block" type="submit">Log out</button>
    </form>
    <?php endif; ?>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="hamburger" id="navToggle" type="button" aria-label="Toggle navigation">☰</button>
      <span class="topbar-title"><?= e($title ?? APP_NAME) ?></span>
      <?php if ($user && $can('reports.view')): ?>
      <form method="post" action="/notifications/process" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-ghost" type="submit" title="Try delivering queued SMS/email notifications">Send queued alerts</button>
      </form>
      <?php endif; ?>
    </header>

    <main class="content">
      <?php if ($flashOk): ?><div class="flash flash-ok"><?= e($flashOk) ?></div><?php endif; ?>
      <?php if ($flashErr): ?><div class="flash flash-err"><?= e($flashErr) ?></div><?php endif; ?>
      <?php if ($flashInfo): ?><div class="flash flash-info"><?= e($flashInfo) ?></div><?php endif; ?>
      <?php $flashWarn = flash_get('warning'); flash_drain('warning'); if ($flashWarn): ?><div class="flash flash-info"><?= e($flashWarn) ?></div><?php endif; ?>

      <?php require VIEW_PATH . '/' . $view . '.php'; ?>
    </main>

    <footer class="footer">
      <small>Mode: <strong>Bursar-only collections</strong> — payments are recorded directly by the school bursar; no third-party payment provider is involved.</small>
    </footer>
  </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
