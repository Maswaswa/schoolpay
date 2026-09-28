<?php /** Create a user for any school or the platform. */ ?>
<div class="card" style="max-width:520px;">
  <h2>Create user</h2>
  <p style="font-size:13px;color:#555;">
    School-level users belong to one school. Leave school empty to create a platform-level account (e.g. support staff who operate across schools).
  </p>
  <form method="post" action="/platform/users/store">
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
      <label for="school_id">School</label>
      <select class="input" id="school_id" name="school_id">
        <option value="">— Platform (no school) —</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= e($s['code']) ?> — <?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
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
    <div class="form-row">
      <label for="password">Password * (min 8 characters)</label>
      <input class="input" id="password" name="password" type="password" required minlength="8">
    </div>
    <button class="btn" type="submit">Create user</button>
    <a class="btn content-btn" href="/platform/users">Cancel</a>
  </form>
</div>