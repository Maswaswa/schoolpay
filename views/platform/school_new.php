<?php /** Onboard a new school. */ ?>
<div class="card" style="max-width:560px;">
  <h2>Onboard a new school</h2>
  <p style="font-size:13px;color:#555;margin-bottom:16px;">
    This creates the school, a main campus, the current academic year, first term, and two starter staff accounts (school admin + bursar). All receive the same initial password you set below.
  </p>
  <form method="post" action="/platform/schools/store">
    <?= csrf_field() ?>

    <div style="border-bottom:1px solid #ddd;padding-bottom:12px;margin-bottom:14px;">
      <strong style="display:block;margin-bottom:10px;">School details</strong>
      <div class="form-row">
        <label for="code">School code * (3–6 letters, uppercase, unique)</label>
        <input class="input" id="code" name="code" maxlength="6" required
               placeholder="e.g. STANFORD" value="<?= old('code') ?>" style="text-transform:uppercase;">
      </div>
      <div class="form-row">
        <label for="name">School name *</label>
        <input class="input" id="name" name="name" required
               placeholder="e.g. St. Stanford Primary School" value="<?= old('name') ?>">
      </div>
      <div class="form-row">
        <label for="campus">Main campus name</label>
        <input class="input" id="campus" name="campus"
               placeholder="Main Campus" value="<?= old('campus') ?>">
      </div>
    </div>

    <div style="border-bottom:1px solid #ddd;padding-bottom:12px;margin-bottom:14px;">
      <strong style="display:block;margin-bottom:10px;">School admin account (login credentials)</strong>
      <div class="form-row">
        <label for="admin_name">Admin full name *</label>
        <input class="input" id="admin_name" name="admin_name" required
               placeholder="e.g. Headmaster Joseph Opiyo" value="<?= old('admin_name') ?>">
      </div>
      <div class="form-row">
        <label for="admin_email">Admin email * (unique, used to log in)</label>
        <input class="input" id="admin_email" name="admin_email" type="email" required
               placeholder="admin@schoolname.test" value="<?= old('admin_email') ?>">
      </div>
      <div class="form-row">
        <label for="admin_password">Initial password * (min 8 characters — all staff share this)</label>
        <input class="input" id="admin_password" name="admin_password" type="password" required minlength="8"
               placeholder="Set a strong initial password">
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <strong style="display:block;margin-bottom:10px;">Academic calendar (optional — defaults provided if left blank)</strong>
      <div class="form-row">
        <label for="year_name">Academic year name</label>
        <input class="input" id="year_name" name="year_name"
               placeholder="<?= e(date('Y')) ?>/<?= e((int)date('Y')+1) ?>" value="<?= old('year_name') ?>">
      </div>
      <div class="form-row">
        <label for="term_name">First term name</label>
        <input class="input" id="term_name" name="term_name"
               placeholder="Term 1" value="<?= old('term_name', 'Term 1') ?>">
      </div>
    </div>

    <button class="btn" type="submit">Onboard school</button>
    <a class="btn content-btn" href="/platform/schools">Cancel</a>
  </form>
</div>