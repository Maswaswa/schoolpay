<?php /** Role-aware dashboard. */ ?>
<?php if ($isBursar): ?>
<div class="grid-2">
  <div class="stat">
    <div class="stat-label">Collected today</div>
    <div class="stat-value"><?= money($stats['collected_today'] ?? 0) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Collected this term</div>
    <div class="stat-value"><?= money($stats['collected_term'] ?? 0) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Billed this term</div>
    <div class="stat-value"><?= money($stats['billed_term'] ?? 0) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">Outstanding</div>
    <div class="stat-value"><?= money($stats['outstanding'] ?? 0) ?></div>
  </div>
</div>

<?php $os = $stats['open_session'] ?? null; ?>
<div class="card" style="margin-top:16px;">
  <h2>Cash session</h2>
  <?php if ($os): ?>
    <p>Session <strong>#<?= (int)$os['id'] ?></strong> is open (opening float <?= money((float)$os['opening_amount']) ?>).
       Expected cash so far: <strong><?= money(CashSessionService::expectedAmount((int)$os['id'])) ?></strong>.</p>
    <a class="btn btn-sm" href="/cash/close">Close session</a>
  <?php else: ?>
    <p>No cash session is open for you.</p>
    <a class="btn btn-sm" href="/cash/open">Open a session</a>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Quick actions</h2>
  <a class="btn btn-sm" href="/payments/record">Record a payment</a>
  <a class="btn btn-sm content-btn" href="/payments">Payments log</a>
  <a class="btn btn-sm content-btn" href="/fees/billing">Invoice a term</a>
</div>
<?php else: ?>
<div class="card">
  <h2>Welcome</h2>
  <p>Use the sidebar to navigate the areas available to your role.</p>
</div>
<?php endif; ?>
