<?php /** Platform-wide audit log. */ ?>
<div class="card">
  <h2>Platform audit log</h2>
  <form method="get" class="inline-form" style="margin-bottom:14px;gap:8px;flex-wrap:wrap;">
    <select class="input" name="school" onchange="this.form.submit()">
      <option value="">All schools</option>
      <?php foreach ($allSchools as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $filterSchool == $s['id'] ? 'selected' : '' ?>>
        <?= e($s['code']) ?> — <?= e($s['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <input class="input" type="text" name="action" placeholder="Filter by action (e.g. payment)"
           value="<?= e($filterAction) ?>">
    <button class="btn btn-sm" type="submit">Filter</button>
    <?php if ($filterSchool > 0 || $filterAction !== ''): ?>
    <a class="btn btn-sm content-btn" href="/platform/audit">Clear</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>When</th><th>School</th><th>User</th><th>Action</th><th>Entity</th><th>Record ID</th><th>Details</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= e($log['created_at'] ?? '') ?></td>
          <td><?= e($log['school_name'] ?? 'Platform') ?></td>
          <td><?= e($log['actor_name'] ?? '—') ?></td>
          <td><code><?= e($log['action'] ?? '') ?></code></td>
          <td><?= e($log['entity'] ?? '') ?></td>
          <td class="num"><?= (int)($log['entity_id'] ?? 0) ?></td>
          <td style="font-size:12px;color:#555;"><?= e($log['details'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="7">No audit logs found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>