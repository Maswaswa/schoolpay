<?php /** Schools list — cross-tenant management. */ ?>
<div class="card" style="margin-bottom:20px;">
  <h2>Schools <a class="btn btn-sm" href="/platform/schools/new" style="float:right;">+ Onboard new school</a></h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Code</th><th>Name</th><th>Status</th>
          <th class="num">Campuses</th><th class="num">Students</th>
          <th class="num">Staff</th><th class="num">Payments</th>
          <th class="num">Collected</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schools as $s): ?>
        <tr>
          <td><strong><?= e($s['code']) ?></strong></td>
          <td><?= e($s['name']) ?></td>
          <td>
            <span class="badge <?= $s['status'] === 'active' ? 'badge-ok' : 'badge-err' ?>">
              <?= e($s['status']) ?>
            </span>
          </td>
          <td class="num"><?= (int)($s['campuses'] ?? 0) ?></td>
          <td class="num"><?= number_format($s['students']) ?></td>
          <td class="num"><?= number_format($s['users']) ?></td>
          <td class="num"><?= number_format($s['payments'] ?? 0) ?></td>
          <td class="num"><?= money((float)($s['collected'] ?? 0)) ?></td>
          <td>
            <form method="post" action="/platform/schools/toggle" class="inline-form" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-sm <?= $s['status'] === 'active' ? 'content-btn' : '' ?>"
                      type="submit" onclick="return confirm('Toggle status of <?= e(addslashes($s['name'])) ?>?');">
                <?= $s['status'] === 'active' ? 'Suspend' : 'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$schools): ?>
        <tr><td colspan="9">No schools registered. <a href="/platform/schools/new">Onboard the first school.</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
