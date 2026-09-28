<?php /** Single cash session detail. */ ?>
<div class="card">
  <h2>Cash session #<?= (int)$session['id'] ?> — <?= e($session['cashier_name'] ?? '') ?></h2>
  <p class="hint">
    Status: <strong><?= e($session['status']) ?></strong>
    · Opened: <?= e((string)$session['created_at']) ?>
    · Closed: <?= e($session['closed_at'] ?? '—') ?>
  </p>
  <div class="grid-2">
    <div class="stat"><div class="stat-label">Opening float</div><div class="stat-value"><?= money((float)$session['opening_amount']) ?></div></div>
    <div class="stat"><div class="stat-label">Expected cash</div><div class="stat-value"><?= money($expected) ?></div></div>
    <?php if ($session['declared_amount'] !== null): ?>
    <div class="stat"><div class="stat-label">Declared</div><div class="stat-value"><?= money((float)$session['declared_amount']) ?></div></div>
    <div class="stat"><div class="stat-label">Difference</div><div class="stat-value"><?= money((float)$session['difference_amount']) ?></div></div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Cash payments in this session</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cash receipt</th><th>Reference</th><th class="num">Amount</th><th>Receipt</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><code><?= e($p['cash_receipt_number'] ?? '—') ?></code></td>
          <td><code><?= e($p['reference']) ?></code></td>
          <td class="num"><?= money((float)$p['amount']) ?></td>
          <td><?php if (!empty($p['receipt_id'])): ?><a href="/receipts/show?id=<?= (int)$p['receipt_id'] ?>"><code><?= e($p['receipt_number'] ?? '') ?></code></a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="4">No cash payments.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
