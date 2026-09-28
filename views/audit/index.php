<?php /** Audit trail. */ ?>
<div class="card">
  <h2>Audit trail</h2>
  <form method="get" class="inline-form" style="margin-bottom:14px;">
    <input class="input" type="text" name="action" placeholder="Filter by action (e.g. payment)" value="<?= e($action) ?>">
    <button class="btn btn-sm" type="submit">Filter</button>
    <?php if ($action): ?><a class="btn btn-sm content-btn" href="/audit">Clear</a><?php endif; ?>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>Resource</th><th>Record ID</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= e((string)($log['created_at'] ?? '')) ?></td>
          <td><?= e($log['actor_name'] ?? '—') ?></td>
          <td><code><?= e($log['action'] ?? '') ?></code></td>
          <td><?= e($log['resource_type'] ?? '') ?></td>
          <td class="num"><?= (int)($log['resource_id'] ?? 0) ?></td>
          <td style="font-size:12px;color:#555;"><?= e($log['details'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="6">No audit logs found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>