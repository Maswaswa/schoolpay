<?php /** School staff login - public landing area. $demoUsers passed in. */ ?>

<section class="section">
  <div class="container login-shell">
    <div class="login-card">
      <h1><span class="badge-icon">🏫</span> Staff login</h1>
      <p class="sub">For bursars, school admins, accountants, directors and auditors.</p>

      <form method="post" action="/auth/landing">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="/staff-login">
        <div class="field">
          <label for="email">Staff email</label>
          <input type="email" id="email" name="email" required autofocus
                 value="<?= old('email') ?>" placeholder="bursar@school.ac.ug">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button class="btn btn-block" type="submit">Sign in to staff area</button>
      </form>

      <div class="login-switch">
        Not a staff member?
        <div class="login-switch-links">
          <a href="/student-login">Student login</a>
          <a href="/parent-login">Parent login</a>
        </div>
      </div>

      <?php if (!empty($demoUsers)): ?>
      <div class="demo-accounts">
        <strong>Demo staff accounts</strong><br>
        <?php foreach ($demoUsers as $u): ?>
          <code><?= e($u['email']) ?></code> — <?= e($u['name']) ?> (<?= e($u['roles']) ?>)<br>
        <?php endforeach; ?>
        Default demo password: <code>password123</code>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
