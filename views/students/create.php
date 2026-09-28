<?php /** Register a student; optional invoice from a published fee structure. */ ?>
<div class="card" style="max-width:640px;">
  <h2>Register student</h2>
  <form method="post" action="/students/store">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="full_name">Full name *</label>
      <input class="input" id="full_name" name="full_name" required value="<?= old('full_name') ?>">
    </div>
    <div class="form-row">
      <label for="reg_form_number">Registration form no.</label>
      <input class="input" id="reg_form_number" name="reg_form_number" value="<?= old('reg_form_number') ?>">
      <div class="hint">Optional official registration number — also searchable at the counter.</div>
    </div>
    <div class="form-row">
      <label for="class_id">Class *</label>
      <select class="input" id="class_id" name="class_id" required>
        <option value="">— select —</option>
        <?php foreach ($classes as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="fee_structure_id">Invoice from fee structure (optional)</label>
      <select class="input" id="fee_structure_id" name="fee_structure_id">
        <option value="">— none —</option>
        <?php foreach ($feeStructures as $fs): ?>
        <option value="<?= (int)$fs['id'] ?>"><?= e($fs['name'] . ' — ' . $fs['term_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="hint">If selected, a term invoice is generated immediately from the structure's fee lines.</div>
    </div>
    <button class="btn" type="submit">Register student</button>
    <a class="btn content-btn" href="/students">Cancel</a>
  </form>
</div>
