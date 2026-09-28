<?php /** Installer landing. */ ?>
<div class="card" style="max-width:560px;margin:40px auto;">
  <h2>Install School Fees IS</h2>
  <?php if ($installed): ?>
    <p>Database detected — <strong><?= (int)$userCount ?></strong> user(s) already exist.</p>
    <p class="hint">Re-running the installer re-checks the schema only; existing data is never duplicated.</p>
  <?php else: ?>
    <p>This will create the SQLite schema and seed demo data (school, terms, fee items, staff and students).</p>
  <?php endif; ?>
  <form method="post" action="/install/run">
    <?= csrf_field() ?>
    <button class="btn" type="submit"><?= $installed ? 'Re-check schema' : 'Install now' ?></button>
  </form>
</div>
