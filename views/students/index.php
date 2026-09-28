<?php /** Students list + search. */ ?>
<div class="card">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <form method="get" action="/students" class="inline-form" style="flex:1;">
      <input class="input" type="search" name="q" placeholder="Search name, Student ID or reg form no…" value="<?= e($q) ?>">
    </form>
    <?php if (Auth::can('students.manage')): ?><a class="btn btn-sm" href="/students/create">+ Register student</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <h2><?= count($students) ?> student(s)</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <th>Student ID</th><th>Name</th><th>Class</th><th>Reg form no.</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><code><?= e($s['student_id']) ?></code></td>
          <td><a href="/students/show?id=<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></a></td>
          <td><?= e($s['class_name'] ?? '—') ?></td>
          <td><?= e($s['reg_form_number'] ?? '—') ?></td>
          <td class="num"><?= money((float)$s['total_billed']) ?></td>
          <td class="num"><?= money((float)$s['total_paid']) ?></td>
          <td class="num"><span class="badge<?= $s['_balance'] > 0 ? ' badge-err' : ' badge-ok' ?>"><?= money($s['_balance']) ?></span></td>
          <td><a class="btn btn-sm content-btn" href="/students/show?id=<?= (int)$s['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="8">No students found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
