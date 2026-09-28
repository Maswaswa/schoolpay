<?php /** Fee items: list + create form. */ ?>
<div class="grid-2">
<div class="card">
  <h2>New fee item</h2>
  <form method="post" action="/fees/items/store">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="code">Code *</label>
      <input class="input" id="code" name="code" placeholder="e.g. TUI-1" required value="<?= old('code') ?>">
    </div>
    <div class="form-row">
      <label for="name">Name *</label>
      <input class="input" id="name" name="name" required value="<?= old('name') ?>">
    </div>
    <div class="form-row">
      <label for="category">Category</label>
      <select class="input" id="category" name="category">
        <option value="tuition">Tuition</option>
        <option value="meals">Meals</option>
        <option value="transport">Transport</option>
        <option value="other">Other</option>
      </select>
    </div>
    <button class="btn" type="submit">Create fee item</button>
  </form>
</div>

<div class="card">
  <h2><?= count($items) ?> fee item(s)</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Code</th><th>Name</th><th>Category</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><code><?= e($it['code']) ?></code></td><td><?= e($it['name']) ?></td><td><?= e($it['category']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="3">No fee items yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
