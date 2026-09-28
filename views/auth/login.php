<?php /** Login (self-contained inside layout). */ ?>
<div class="card" style="max-width:460px;margin:40px auto;">
  <h2>Log in</h2>
  <form method="post" action="/login">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="email">Email</label>
      <input class="input" type="email" id="email" name="email" required autofocus value="<?= old('email') ?>">
    </div>
    <div class="form-row">
      <label for="password">Password</label>
      <input class="input" type="password" id="password" name="password" required>
    </div>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>

  <div class="demo-accounts">
    <strong>Demo accounts</strong> (password: <code>password123</code>)
    <table class="table" style="margin-top:8px;">
      <?php foreach ($demoUsers as $u): ?>
      <tr>
        <td><code><?= e($u['email']) ?></code></td>
        <td><?= e($u['name']) ?></td>
        <td><span class="badge"><?= e($u['roles']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p style="margin:8px 0 0;">Prefer a themed sign-in?
      <a href="/student-login">Student</a> ·
      <a href="/parent-login">Parent</a> ·
      <a href="/staff-login">Staff</a>
      · <a href="/about">About this system</a>
    </p>
  </div>
</div>
