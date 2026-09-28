<?php /** Outstanding balances report. */ ?>
<div class="card">
  <h2>Outstanding fees</h2>
  <div class="stat" style="margin-bottom:14px;">
    <div class="stat-label">Total outstanding</div>
    <div class="stat-value"><?= money($totalOutstanding) ?></div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Outstanding</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><code><?= e($s['student_id']) ?></code></td>
          <td><a href="/students/show?id=<?= (int)$s['id'] ?? 0 ?>"><?= e($s['full_name']) ?></a></td>
          <td><?= e($s['class_name'] ?? '—') ?></td>
          <td class="num"><?= money($s['billed']) ?></td>
          <td class="num"><?= money($s['paid']) ?></td>
          <td class="num"><span class="badge<?= $s['outstanding'] > 0 ? ' badge-err' : ' badge-ok' ?>"><?= money($s['outstanding']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="6">No student data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>