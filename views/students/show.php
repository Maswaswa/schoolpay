<?php /** Student 360: invoices, payments, ledger, guardians. */ ?>
<div class="card">
  <h2><?= e($student['full_name']) ?> <span class="badge"><?= e($student['student_id']) ?></span></h2>
  <p class="hint">
    Class: <strong><?= e($student['class_name'] ?? '—') ?></strong>
    · Reg form no.: <strong><?= e($student['reg_form_number'] ?? '—') ?></strong>
    · Status: <strong><?= e($student['status']) ?></strong>
  </p>
  <?php if (Auth::can('payment.record')): ?>
  <a class="btn btn-sm" href="/payments/record">Record payment</a>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Fee invoices</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Invoice</th><th>Term</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php $anyBalance = 0.0; foreach ($obligations as $o): $bal = (float)$o['billed'] - (float)$o['paid']; $anyBalance += $bal; ?>
        <tr>
          <td><code><?= e($o['invoice_no']) ?></code></td>
          <td><?= e($o['term_name'] ?? '') ?></td>
          <td class="num"><?= money((float)$o['billed']) ?></td>
          <td class="num"><?= money((float)$o['paid']) ?></td>
          <td class="num"><?= money($bal) ?></td>
          <td><span class="badge<?= $bal <= 0 ? ' badge-ok' : ' badge-err' ?>"><?= $bal <= 0 ? 'settled' : 'owing' ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$obligations): ?><tr><td colspan="6">No invoices yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <?php if ($obligations): ?><p class="hint">Total outstanding: <strong><?= money($anyBalance) ?></strong></p><?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Payments</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Reference</th><th>Channel</th><th class="num">Amount</th><th>Recorded by</th><th>Date</th><th>Receipt</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><code><?= e($p['reference']) ?></code></td>
          <td><?= e($p['channel']) ?></td>
          <td class="num"><?= money((float)$p['amount']) ?></td>
          <td><?= e($p['confirmed_by_name'] ?? '—') ?></td>
          <td><?= e((string)$p['confirmed_at']) ?></td>
          <td><?php if (!empty($p['receipt_id'])): ?><a class="btn btn-sm content-btn" href="/receipts/show?id=<?= (int)$p['receipt_id'] ?>"><?= e($p['receipt_number'] ?? 'view') ?></a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="6">No payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h2>Ledger</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
      <tbody>
      <?php foreach ($ledger as $l): ?>
        <tr>
          <td><?= e((string)$l['created_at']) ?></td>
          <td><span class="badge<?= ($l['entry_type'] ?? '') === 'credit' ? ' badge-ok' : ' badge-err' ?>"><?= e($l['entry_type'] ?? '') ?></span></td>
          <td><?= e($l['description'] ?? '') ?></td>
          <td class="num"><?= ($l['entry_type'] ?? '') === 'debit' ? money((float)$l['amount']) : '' ?></td>
          <td class="num"><?= ($l['entry_type'] ?? '') === 'credit' ? money((float)$l['amount']) : '' ?></td>
          <td class="num"><?= money((float)$l['balance_after']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$ledger): ?><tr><td colspan="6">No ledger entries.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h2>Guardians</h2>
  <?php if ($guardians): ?>
  <ul>
    <?php foreach ($guardians as $g): ?>
    <li><strong><?= e($g['name']) ?></strong> — <?= e($g['relationship'] ?? '') ?> · <?= e($g['phone'] ?? 'no phone') ?></li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
  <p class="hint">No guardians linked.</p>
  <?php endif; ?>
</div>
