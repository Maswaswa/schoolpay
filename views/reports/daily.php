<?php /** Daily collection report. */ ?>
<div class="card">
  <h2>Daily collections — <?= e($date) ?></h2>
  <form method="get" class="inline-form" style="margin-bottom:14px;">
    <input type="date" name="date" value="<?= e($date) ?>">
    <button class="btn btn-sm" type="submit">Go</button>
  </form>

  <div class="grid-2">
    <div class="stat"><div class="stat-label">Grand total</div><div class="stat-value"><?= money($grandTotal) ?></div></div>
    <div class="stat"><div class="stat-label">Transactions</div><div class="stat-value"><?= (int)$txCount ?></div></div>
  </div>
</div>

<div class="card">
  <h3>By channel</h3>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Channel</th><th class="num">Transactions</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php foreach ($totals as $t): ?>
        <tr><td><?= e($t['channel']) ?></td><td class="num"><?= (int)$t['tx_count'] ?></td><td class="num"><?= money((float)$t['total']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$totals): ?><tr><td colspan="3">No transactions on this date.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h3>By cashier</h3>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cashier</th><th class="num">Transactions</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php foreach ($cashiers as $c): ?>
        <tr><td><?= e($c['cashier_name']) ?></td><td class="num"><?= (int)$c['tx_count'] ?></td><td class="num"><?= money((float)$c['total']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$cashiers): ?><tr><td colspan="3">No transactions on this date.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>