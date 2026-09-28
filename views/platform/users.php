<?php /** All users across all schools. */ ?>
<div class="card" style="margin-bottom:20px;">
  <h2>All users
    <a class="btn btn-sm" href="/platform/users/new" style="float:right;">+ Add user</a>
  </h2>

  <form method="get" class="inline-form" style="margin-bottom:14px;gap:8px;flex-wrap:wrap;">
    <select class="input" name="school" onchange="this.form.submit()">
      <option value="">All schools</option>
      <?php foreach ($allSchools as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $filterSchool == $s['id'] ? 'selected' : '' ?>>
        <?= e($s['code']) ?> — <?= e($s['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="role" onchange="this.form.submit()">
      <option value="">All roles</option>
      <?php foreach ($allRoles as $r): ?>
      <option value="<?= e($r['code']) ?>" <?= $filterRole === $r['code'] ? 'selected' : '' ?>>
        <?= e($r['code']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterSchool > 0 || $filterRole !== ''): ?>
    <a class="btn btn-sm content-btn" href="/platform/users">Clear filters</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>School</th><th>Role(s)</th>
          <th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <?php
          $roles = $u['role_codes'] ? explode(',', $u['role_codes']) : [];
          $isPlatformAdmin = $u['email'] === 'platform@schoolfees.test';
        ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td>
            <?php if ($u['school_id']): ?>
              <?= e($u['school_name'] ?: '—') ?>
            <?php else: ?>
              <em style="color:#888;">Platform</em>
            <?php endif; ?>
          </td>
          <td>
            <?php foreach ($roles as $r): ?>
              <span class="badge"><?= e(trim($r)) ?></span>
            <?php endforeach; ?>
          </td>
          <td>
            <span class="badge <?= $u['status'] === 'active' ? 'badge-ok' : 'badge-err' ?>">
              <?= e($u['status']) ?>
            </span>
          </td>
          <td>
            <?php if (!$isPlatformAdmin): ?>
            <form method="post" action="/platform/users/toggle" class="inline-form" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm <?= $u['status'] === 'active' ? 'content-btn' : '' ?>"
                      type="submit" onclick="return confirm('Toggle status of <?= e(addslashes($u['name'])) ?>?');">
                <?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <?php else: ?>
            <span style="color:#888;font-size:12px;">(protected)</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
        <tr><td colspan="6">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>