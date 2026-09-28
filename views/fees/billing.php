<?php /** Term billing run. */ ?>
<div class="card">
  <h2>Invoice a term</h2>
  <p class="hint">Select a published fee structure — every active student without an invoice for that term is billed the structure's lines. Students already invoiced are skipped.</p>
  <form method="post" action="/fees/billing/run" data-confirm="Invoice every active student from this structure?">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="fee_structure_id">Fee structure *</label>
      <select class="input" id="fee_structure_id" name="fee_structure_id" required>
        <option value="">— select —</option>
        <?php foreach ($structures as $fs): ?>
        <option value="<?= (int)$fs['id'] ?>">
          <?= e($fs['name'] . ' — ' . ($fs['term_name'] ?? '') . ' · ' . money((float)$fs['total_amount'])) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Run billing</button>
  </form>
</div>

<div class="card">
  <h2>Structures &amp; invoice counts</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Structure</th><th>Term</th><th class="num">Total per student</th><th class="num">Invoices in term</th></tr></thead>
      <tbody>
      <?php foreach ($structures as $fs): ?>
        <tr>
          <td><?= e($fs['name']) ?></td>
          <td><?= e($fs['term_name'] ?? '') ?></td>
          <td class="num"><?= money((float)$fs['total_amount']) ?></td>
          <td class="num"><?= (int)$fs['invoices_count'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$structures): ?><tr><td colspan="4">No structures yet — create one under Fee structures.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
