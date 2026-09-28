<?php /** Record a payment (bursar). Student ID lookup via app.js. */ ?>
<div class="card" style="max-width:640px;">
  <h2>Record payment</h2>
  <?php $sess = $session ?? null; ?>
  <?php if ($sess): ?>
    <p class="hint">Cash session <strong>#<?= (int)$sess['id'] ?></strong> is open — cash payments will be attached to it.</p>
  <?php else: ?>
    <div class="flash flash-info">No open cash session — cash payments require one. Non-cash channels still work.</div>
  <?php endif; ?>

  <form method="post" action="/payments/record">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="student_lookup">Student ID / reg form no. / name *</label>
      <input class="input" id="student_lookup" data-lookup="student" name="student_lookup"
             placeholder="Type a Student ID e.g. KFS-0000238-4" autocomplete="off" required>
      <div data-lookup-result class="hint"></div>
    </div>
    <div class="form-row">
      <label for="channel">Channel *</label>
      <select class="input" id="channel" name="channel" required>
        <?php foreach ($channels as $key => $label): ?>
        <option value="<?= e($key) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="amount">Amount *</label>
      <input class="input" id="amount" name="amount" type="number" step="0.01" min="0.01" required>
    </div>
    <div class="form-row">
      <label for="ext_ref">External reference (slip / txn / cheque no.)</label>
      <input class="input" id="ext_ref" name="ext_ref" placeholder="Required for bank / mobile money / cheque">
      <div class="hint">Duplicate references are rejected — this protects against double recording.</div>
    </div>
    <button class="btn" type="submit">Record payment</button>
    <a class="btn content-btn" href="/payments">Payments log</a>
  </form>
</div>
