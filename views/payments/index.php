<?php /** Payments log. */ ?>
<div class="card">
  <h2>Recent payments (latest 100)</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Reference</th><th>Student</th><th>Channel</th><th class="num">Amount</th><th>Recorded by</th><th>Date</th><th>Receipt</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><code><?= e($p['reference']) ?></code></td>
          <td><?= e($p['student_name']) ?> <span class="badge"><?= e($p['student_id']) ?></span></td>
          <td><?= e($p['channel']) ?></td>
          <td class="num"><?= money((float)$p['amount']) ?></td>
          <td><?= e($p['recorded_by_name'] ?? '—') ?></td>
          <td><?= e((string)$p['confirmed_at']) ?></td>
          <td><?php if (!empty($p['receipt_id'])): ?><a class="btn btn-sm content-btn" href="/receipts/show?id=<?= (int)$p['receipt_id'] ?>"><?= e($p['receipt_number'] ?? 'view') ?></a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="7">No payments recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
