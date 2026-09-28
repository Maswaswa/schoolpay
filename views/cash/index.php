<?php /** Cash sessions overview. */ ?>
<div class="card">
  <h2>Your cash session</h2>
  <?php if ($session): ?>
    <p>Session <strong>#<?= (int)$session['id'] ?></strong> opened <?= e((string)$session['created_at']) ?>
       with float <strong><?= money((float)$session['opening_amount']) ?></strong>.</p>
    <p>Expected cash now: <strong><?= money(CashSessionService::expectedAmount((int)$session['id'])) ?></strong></p>
    <a class="btn btn-sm" href="/cash/close">Close session</a>
    <a class="btn btn-sm content-btn" href="/cash/show?id=<?= (int)$session['id'] ?>">View session</a>
  <?php else: ?>
    <p>No open session.</p>
    <a class="btn btn-sm" href="/cash/open">Open a session</a>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Your recent sessions</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>Opened</th><th>Closed</th><th class="num">Opening</th><th class="num">Cash in</th><th class="num">Expected</th><th class="num">Declared</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $cs): ?>
        <tr>
          <td><a href="/cash/show?id=<?= (int)$cs['id'] ?>">#<?= (int)$cs['id'] ?></a></td>
          <td><?= e((string)$cs['created_at']) ?></td>
          <td><?= e($cs['closed_at'] ?? '—') ?></td>
          <td class="num"><?= money((float)$cs['opening_amount']) ?></td>
          <td class="num"><?= money((float)$cs['cash_total']) ?></td>
          <td class="num"><?= $cs['expected_amount'] !== null ? money((float)$cs['expected_amount']) : '—' ?></td>
          <td class="num"><?= $cs['declared_amount'] !== null ? money((float)$cs['declared_amount']) : '—' ?></td>
          <td><span class="badge<?= ($cs['status'] ?? '') === 'closed' ? ' badge-ok' : (($cs['status'] ?? '') === 'discrepancy' ? ' badge-err' : ' badge-warn') ?>"><?= e($cs['status'] ?? '') ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="8">No sessions yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
