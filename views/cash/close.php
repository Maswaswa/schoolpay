<?php /** Close a cash session: declare physical cash. */ ?>
<div class="card" style="max-width:560px;">
  <h2>Close cash session #<?= (int)$session['id'] ?></h2>
  <p class="hint">Opened <?= e((string)$session['created_at']) ?> · float <?= money((float)$session['opening_amount']) ?></p>

  <div class="stat" style="margin-bottom:14px;">
    <div class="stat-label">Expected cash (cash payments recorded in this session)</div>
    <div class="stat-value"><?= money($expected) ?></div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cash receipt</th><th>Reference</th><th>Student</th><th class="num">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><code><?= e($p['cash_receipt_number'] ?? '—') ?></code></td>
          <td><code><?= e($p['reference']) ?></code></td>
          <td></td>
          <td class="num"><?= money((float)$p['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="4">No cash payments in this session.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <form method="post" action="/cash/close" style="margin-top:14px;">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="declared_amount">Declared physical cash *</label>
      <input class="input" id="declared_amount" name="declared_amount" type="number" step="0.01" min="0" required>
      <div class="hint">Any difference between expected and declared opens a reconciliation case.</div>
    </div>
    <div class="form-row">
      <label for="notes">Notes</label>
      <input class="input" id="notes" name="notes" placeholder="Optional explanation for differences">
    </div>
    <button class="btn" type="submit" data-confirm="Close this cash session?">Close session</button>
    <a class="btn content-btn" href="/cash">Cancel</a>
  </form>
</div>
