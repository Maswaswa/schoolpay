<?php /** Platform overview — holds all schools records. */ ?>
<div class="grid-2" style="margin-bottom:20px;">
  <div class="stat">
    <div class="stat-label">Active schools</div>
    <div class="stat-value"><?= (int)($stats['schools_on']) ?> / <?= (int)($stats['schools']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Total students</div>
    <div class="stat-value"><?= number_format($stats['students']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Collected today</div>
    <div class="stat-value"><?= money($stats['collected_today']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Total collected</div>
    <div class="stat-value"><?= money($stats['collected']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Total billed</div>
    <div class="stat-value"><?= money($stats['billed']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Total outstanding</div>
    <div class="stat-value"><?= money($stats['outstanding']) ?></div>
  </div>
</div>

<div class="card" style="margin-bottom:20px;">
  <h2>Schools — financial overview</h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>School</th><th>Status</th>
          <th class="num">Students</th><th class="num">Staff</th>
          <th class="num">Billed</th><th class="num">Collected</th><th class="num">Outstanding</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schools as $s): ?>
        <tr>
          <td><strong><?= e($s['code']) ?></strong> — <?= e($s['name']) ?></td>
          <td>
            <span class="badge <?= $s['status'] === 'active' ? 'badge-ok' : 'badge-err' ?>">
              <?= e($s['status']) ?>
            </span>
          </td>
          <td class="num"><?= number_format($s['students']) ?></td>
          <td class="num"><?= number_format($s['users']) ?></td>
          <td class="num"><?= money($s['billed']) ?></td>
          <td class="num"><?= money($s['collected']) ?></td>
          <td class="num"><?= money($s['outstanding']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$schools): ?>
        <tr><td colspan="7">No schools registered yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <p style="margin-top:10px;">
    <a class="btn" href="/platform/schools/new">Onboard new school</a>
    <a class="btn content-btn" href="/platform/schools">Manage schools</a>
  </p>
</div>

<div class="card">
  <h2>Recent platform activity</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['created_at'] ?? '') ?></td>
          <td><?= e($r['actor_name'] ?? '—') ?></td>
          <td><code><?= e($r['action'] ?? '') ?></code></td>
          <td style="font-size:12px;color:#555;"><?= e($r['details'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="4">No recent activity.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
