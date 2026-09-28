<?php /** Self-contained error page (no layout dependency). */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Error <?= (int)$error['code'] ?> — <?= e($appName) ?></title>
<style>
  body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:#f4f6fb; color:#1c2333;
         display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .card { background:#fff; border:1px solid #e3e7f0; border-radius:12px; padding:40px; max-width:520px; text-align:center;
          box-shadow:0 8px 30px rgba(20,30,60,.08); }
  .code { font-size:56px; font-weight:800; color:#c0392b; margin:0; }
  h1 { font-size:20px; margin:8px 0 12px; }
  p { color:#5b6478; line-height:1.6; }
  a.btn { display:inline-block; margin-top:18px; padding:10px 22px; background:#1f5eff; color:#fff; border-radius:8px;
          text-decoration:none; font-weight:600; }
</style>
</head>
<body>
  <div class="card">
    <p class="code"><?= (int)$error['code'] ?></p>
    <h1><?= e($error['message']) ?></h1>
    <p>If you believe this is a mistake, contact the school bursar or your system administrator.</p>
    <a class="btn" href="/">Back to dashboard</a>
  </div>
</body>
</html>
