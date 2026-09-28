<?php
/** Public landing layout — marketing shell with top nav + footer, no sidebar. */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$user = Auth::user();
$flashOk   = flash_get('success'); flash_drain('success');
$flashErr  = flash_get('error');   flash_drain('error');
$flashInfo = flash_get('info');    flash_drain('info');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Welcome') ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/landing.css">
<?php if (defined('EXTRA_CSS')): ?><link rel="stylesheet" href="<?= e(EXTRA_CSS) ?>"><?php endif; ?>
</head>
<body class="public-body">
  <header class="site-header">
    <div class="container site-nav">
      <a class="brand" href="/">
        <span class="brand-mark">SF</span>
        <span class="brand-name">School Fees IS</span>
      </a>
      <nav class="site-links" id="siteLinks">
        <a href="/" class="<?= $path === '/' ? 'active' : '' ?>">Home</a>
        <a href="/about" class="<?= $path === '/about' ? 'active' : '' ?>">About us</a>
        <?php if ($user): ?>
          <a href="/">My dashboard</a>
          <form method="post" action="/logout" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Log out</button></form>
        <?php else: ?>
          <a href="/student-login" class="<?= $path === '/student-login' ? 'active' : '' ?>">Student login</a>
          <a href="/parent-login" class="<?= $path === '/parent-login' ? 'active' : '' ?>">Parent login</a>
          <a href="/staff-login" class="<?= $path === '/staff-login' ? 'active' : '' ?>">Staff login</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="site-main">
    <?php if ($flashOk): ?><div class="container"><div class="flash flash-ok"><?= e($flashOk) ?></div></div><?php endif; ?>
    <?php if ($flashErr): ?><div class="container"><div class="flash flash-err"><?= e($flashErr) ?></div></div><?php endif; ?>
    <?php if ($flashInfo): ?><div class="container"><div class="flash flash-info"><?= e($flashInfo) ?></div></div><?php endif; ?>
    <?php $flashWarn = flash_get('warning'); flash_drain('warning'); if ($flashWarn): ?><div class="container"><div class="flash flash-info"><?= e($flashWarn) ?></div></div><?php endif; ?>

    <?php require VIEW_PATH . '/' . $view . '.php'; ?>
  </main>

  <footer class="site-footer">
    <div class="container site-footer-inner">
      <div>© <?= date('Y') ?> <?= e(APP_NAME) ?> — payments are recorded by the school bursar; no third-party provider is involved.</div>
      <nav class="site-footer-links">
        <a href="/about">About</a>
        <a href="/student-login">Students</a>
        <a href="/parent-login">Parents</a>
        <a href="/staff-login">Staff</a>
      </nav>
    </div>
  </footer>
</body>
</html>
