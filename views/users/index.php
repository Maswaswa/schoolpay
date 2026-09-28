<?php /** Staff accounts. */ ?>
<div class="card">
  <h2>Staff accounts</h2>
  <div style="margin-bottom:14px;">
    <a class="btn btn-sm" href="/users/create">+ Add staff user</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role(s)</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><code><?= e($u['email']) ?></code></td>
          <td><?= e($u['phone'] ?? '—') ?></td>
          <td><?php foreach (explode(',', $u['role_codes'] ?? '') as $rc): if ($rc): ?><span class="badge"><?= e(trim($rc)) ?></span><?php endif; endforeach; ?></td>
          <td><span class="badge<?= ($u['status'] ?? '') === 'active' ? ' badge-ok' : ' badge-err' ?>"><?= e($u['status'] ?? '') ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?><tr><td colspan="5">No staff accounts.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>