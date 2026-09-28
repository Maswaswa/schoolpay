<?php /** Child detail: invoices + payment history. */ ?>
<div class="card">
  <h2><?= e($student['full_name']) ?> <span class="badge"><?= e($student['student_id']) ?></span></h2>
  <p class="hint">Class: <strong><?= e($student['class_name'] ?? '—') ?></strong> · Status: <strong><?= e($student['status']) ?></strong></p>
  <a class="btn btn-sm content-btn" href="/parent">← My children</a>
</div>

<div class="card">
  <h2>Fee invoices</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Invoice no.</th><th>Term</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($obligations as $o): $bal = round((float)($o['billed'] ?? 0) - (float)($o['paid_amount'] ?? 0), 2); ?>
        <tr>
          <td><code><?= e($o['invoice_no']) ?></code></td>
          <td><?= e($o['term_name'] ?? '') ?></td>
          <td class="num"><?= money((float)($o['billed'] ?? 0)) ?></td>
          <td class="num"><?= money((float)($o['paid_amount'] ?? 0)) ?></td>
          <td class="num"><span class="badge<?= $bal > 0 ? ' badge-err' : ' badge-ok' ?>"><?= money($bal) ?></span></td>
          <td><span class="badge<?= $bal <= 0 ? ' badge-ok' : ' badge-err' ?>"><?= $bal <= 0 ? 'settled' : 'owing' ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$obligations): ?><tr><td colspan="6">No invoices yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h2>Payment history</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Receipt no.</th><th>Date</th><th>Channel</th><th class="num">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?php if (!empty($p['receipt_id'])): ?><a href="/receipts/show?id=<?= (int)$p['receipt_id'] ?>"><code><?= e($p['receipt_number']) ?></code></a><?php else: ?>—<?php endif; ?></td>
          <td><?= e((string)($p['confirmed_at'] ?? '')) ?></td>
          <td><?= e($p['channel'] ?? '') ?></td>
          <td class="num"><?= money((float)($p['amount'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="4">No payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>