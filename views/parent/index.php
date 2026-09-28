<?php /** Parent portal: linked children and balances. */ ?>
<div class="card">
  <h2>My children</h2>
  <?php if ($children): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th class="num">Outstanding</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($children as $c): ?>
        <tr>
          <td><code><?= e($c['student_id']) ?></code></td>
          <td><?= e($c['full_name']) ?></td>
          <td><?= e($c['class_name'] ?? '—') ?></td>
          <td class="num"><span class="badge<?= $c['outstanding'] > 0 ? ' badge-err' : ' badge-ok' ?>"><?= money($c['outstanding']) ?></span></td>
          <td><a class="btn btn-sm content-btn" href="/parent/child?id=<?= (int)$c['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p class="hint">No children are linked to your account. Contact the school office to link a student to your parent account.</p>
  <?php endif; ?>
</div>