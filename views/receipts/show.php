<?php /** Receipt display. */ ?>
<div class="receipt" style="margin:0 auto;">
  <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
    <div>
      <strong style="font-size:18px;"><?= e($receipt['school_name'] ?? 'School Fees IS') ?></strong>
      <div>Receipt</div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:22px;font-weight:800;"><?= e($receipt['receipt_number']) ?></div>
      <div class="hint"><?= e((string)$receipt['confirmed_at']) ?></div>
    </div>
  </div>

  <hr style="border:none;border-top:1px solid #e0e0e0;margin:12px 0;">

  <table style="width:100%;border-collapse:collapse;">
    <tr><td style="padding:4px 0;color:#666;">Student</td><td style="padding:4px 0;font-weight:600;"><?= e($receipt['student_name']) ?></td></tr>
    <tr><td style="padding:4px 0;color:#666;">Student ID</td><td style="padding:4px 0;"><code><?= e($receipt['student_id']) ?></code></td></tr>
    <tr><td style="padding:4px 0;color:#666;">Class</td><td style="padding:4px 0;"><?= e($receipt['class_name'] ?? '—') ?></td></tr>
    <tr><td style="padding:4px 0;color:#666;">Channel</td><td style="padding:4px 0;"><?= e($receipt['channel']) ?></td></tr>
    <?php if (!empty($receipt['provider_ref'])): ?>
    <tr><td style="padding:4px 0;color:#666;">Ref / slip no.</td><td style="padding:4px 0;"><code><?= e($receipt['provider_ref']) ?></code></td></tr>
    <?php endif; ?>
    <tr><td style="padding:4px 0;color:#666;">Recorded by</td><td style="padding:4px 0;"><?= e($receipt['recorded_by_name'] ?? '—') ?></td></tr>
  </table>

  <hr style="border:none;border-top:1px solid #e0e0e0;margin:12px 0;">

  <?php if ($allocations): ?>
  <table style="width:100%;border-collapse:collapse;">
    <tr><th style="text-align:left;padding:4px 0;font-size:12px;color:#888;">Fee item</th><th style="text-align:right;padding:4px 0;font-size:12px;color:#888;">Amount</th></tr>
    <?php foreach ($allocations as $a): ?>
    <tr><td style="padding:3px 0;"><?= e($a['item_name'] ?? '—') ?> <span class="hint"><?= e($a['invoice_no'] ?? '') ?></span></td><td style="text-align:right;padding:3px 0;"><?= money((float)$a['amount']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <hr style="border:none;border-top:1px solid #e0e0e0;margin:10px 0;">
  <?php endif; ?>

  <div style="display:flex;justify-content:space-between;align-items:center;">
    <strong>Amount paid</strong>
    <strong style="font-size:20px;color:#1f5eff;"><?= money((float)$receipt['payment_amount']) ?></strong>
  </div>

  <hr style="border:none;border-top:1px solid #e0e0e0;margin:12px 0;">

  <div style="text-align:center;font-size:12px;color:#888;">This is a machine-generated receipt. Retain for your records.</div>
</div>

<div style="margin-top:20px;text-align:center;">
  <form method="post" action="/receipts/reprint" style="display:inline;">
    <?= csrf_field() ?>
    <input type="hidden" name="receipt_id" value="<?= (int)$receipt['id'] ?>">
    <button class="btn btn-sm" type="submit">Reprint</button>
  </form>
  <a class="btn btn-sm content-btn" href="/payments">Back to payments</a>
</div>