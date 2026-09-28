<?php /** Reconciliation: cash sessions and non-cash channel summaries. */ ?>
<?php if ($discrepancies): ?>
<div class="flash flash-warn" style="border-color:#efdfab;background:#fff8e7;padding:11px 14px;border-radius:8px;margin-bottom:14px;">
  <strong><?= count($discrepancies) ?> discrepancy case(s)</strong> need investigation before they can be closed.
  <a href="/reconciliation?status=open">View open cases</a>
</div>
<?php endif; ?>

<div class="card">
  <h2>Cash sessions</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>Cashier</th><th>Opened</th><th>Closed</th><th class="num">Cash in</th><th class="num">Expected</th><th class="num">Declared</th><th class="num">Diff</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($sessions as $cs): $diff = round((float)($cs['expected_amount'] ?? 0) - (float)($cs['declared_amount'] ?? 0), 2); ?>
        <tr>
          <td><a href="/cash/show?id=<?= (int)$cs['id'] ?>">#<?= (int)$cs['id'] ?></a></td>
          <td><?= e($cs['cashier_name']) ?></td>
          <td><?= e((string)$cs['created_at']) ?></td>
          <td><?= e($cs['closed_at'] ?? '—') ?></td>
          <td class="num"><?= money((float)$cs['cash_total']) ?></td>
          <td class="num"><?= $cs['expected_amount'] !== null ? money((float)$cs['expected_amount']) : '—' ?></td>
          <td class="num"><?= $cs['declared_amount'] !== null ? money((float)$cs['declared_amount']) : '—' ?></td>
          <td class="num<?= $diff != 0 && $cs['declared_amount'] !== null ? ' badge-err' : '' ?>"><?= $cs['declared_amount'] !== null ? money($diff) : '—' ?></td>
          <td><span class="badge<?= ($cs['status'] ?? '') === 'closed' ? ' badge-ok' : (($cs['status'] ?? '') === 'discrepancy' ? ' badge-err' : ' badge-warn') ?>"><?= e($cs['status'] ?? '') ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$sessions): ?><tr><td colspan="9">No cash sessions.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h2>Non-cash collections (bank / mobile money / cheque)</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Channel</th><th class="num">Transactions</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php foreach ($byChannel as $r): ?>
        <tr>
          <td><?= e($r['pay_date']) ?></td>
          <td><?= e($r['channel']) ?></td>
          <td class="num"><?= (int)$r['tx_count'] ?></td>
          <td class="num"><?= money((float)$r['total']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$byChannel): ?><tr><td colspan="4">No non-cash payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>