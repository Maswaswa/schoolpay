<?php /** Add staff user. */ ?>
<div class="card" style="max-width:480px;">
  <h2>Add staff user</h2>
  <form method="post" action="/users/store">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="name">Full name *</label>
      <input class="input" id="name" name="name" required value="<?= old('name') ?>">
    </div>
    <div class="form-row">
      <label for="email">Email *</label>
      <input class="input" id="email" name="email" type="email" required value="<?= old('email') ?>">
    </div>
    <div class="form-row">
      <label for="phone">Phone</label>
      <input class="input" id="phone" name="phone" value="<?= old('phone') ?>">
    </div>
    <div class="form-row">
      <label for="password">Password * (min 8 characters)</label>
      <input class="input" id="password" name="password" type="password" required minlength="8">
    </div>
    <div class="form-row">
      <label for="role">Role *</label>
      <select class="input" id="role" name="role" required>
        <option value="">— select —</option>
        <?php foreach ($roles as $r): ?>
        <option value="<?= e($r['code']) ?>"><?= e($r['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Create user</button>
    <a class="btn content-btn" href="/users">Cancel</a>
  </form>
</div>