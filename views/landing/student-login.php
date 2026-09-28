<?php /** Student login - public landing area. $demoUsers passed in. */ ?>

<section class="section">
  <div class="container login-shell">
    <div class="login-card">
      <h1><span class="badge-icon">🎓</span> Student login</h1>
      <p class="sub">Sign in with the school email address on your admission letter.</p>

      <form method="post" action="/auth/landing">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="/student-login">
        <div class="field">
          <label for="email">School email</label>
          <input type="email" id="email" name="email" required autofocus
                 value="<?= old('email') ?>" placeholder="student@school.ac.ug">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button class="btn btn-block" type="submit">Sign in as student</button>
      </form>

      <div class="login-switch">
        Not a student?
        <div class="login-switch-links">
          <a href="/parent-login">Parent login</a>
          <a href="/staff-login">Staff login</a>
        </div>
      </div>

      <?php if (!empty($demoUsers)): ?>
      <div class="demo-accounts">
        <strong>Demo student accounts</strong><br>
        <?php foreach ($demoUsers as $u): ?>
          <code><?= e($u['email']) ?></code> — <?= e($u['name']) ?><br>
        <?php endforeach; ?>
        Demo password: <code>password123</code>
      </div>
      <?php else: ?>
      <div class="demo-accounts">
        Student portal accounts are issued by the school office.
        Guardians can follow fees for their children via the <a href="/parent-login">parent login</a>.
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
