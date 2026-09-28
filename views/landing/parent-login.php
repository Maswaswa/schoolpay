<?php /** Parent / guardian login - public landing area. $demoUsers passed in. */ ?>

<section class="section">
  <div class="container login-shell">
    <div class="login-card">
      <h1><span class="badge-icon">👪</span> Parent login</h1>
      <p class="sub">Guardians sign in with the email address registered with the school bursar.</p>

      <form method="post" action="/auth/landing">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="/parent-login">
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" required autofocus
                 value="<?= old('email') ?>" placeholder="guardian@example.com">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button class="btn btn-block" type="submit">Sign in as parent</button>
      </form>

      <div class="login-switch">
        Not a guardian?
        <div class="login-switch-links">
          <a href="/student-login">Student login</a>
          <a href="/staff-login">Staff login</a>
        </div>
      </div>

      <div class="demo-accounts">
        <strong>What you'll see inside</strong><br>
        Your children, their outstanding balances, payment history and printable receipts —
        plus alerts whenever a new fee invoice is raised.
      </div>

      <?php if (!empty($demoUsers)): ?>
      <div class="demo-accounts">
        <strong>Demo parent accounts</strong><br>
        <?php foreach ($demoUsers as $u): ?>
          <code><?= e($u['email']) ?></code> — <?= e($u['name']) ?><br>
        <?php endforeach; ?>
        Default demo password: <code>password123</code>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
