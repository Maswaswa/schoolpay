<?php /** Fee structures: list + builder. */ ?>
<div class="card">
  <h2>New fee structure</h2>
  <form method="post" action="/fees/structures/store">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="term_id">Term *</label>
      <select class="input" id="term_id" name="term_id" required>
        <option value="">— select term —</option>
        <?php foreach ($terms as $t): ?>
        <option value="<?= (int)$t['id'] ?>"><?= e(($t['name'] ?? '') . ' — ' . ($t['year_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="name">Structure name *</label>
      <input class="input" id="name" name="name" required placeholder="e.g. Term 1 standard fees">
    </div>
    <div class="form-row">
      <label>Fee lines *</label>
      <div class="table-wrap">
        <table class="table" id="structureLines">
          <thead><tr><th>Fee item</th><th class="num">Amount</th></tr></thead>
          <tbody>
          <?php foreach ($feeItems as $i => $fi): ?>
            <tr>
              <td>
                <input type="hidden" name="fee_item_id[<?= $i ?>]" value="<?= (int)$fi['id'] ?>">
                <?= e($fi['name']) ?> <span class="badge"><?= e($fi['code']) ?></span>
              </td>
              <td class="num"><input class="input num" style="text-align:right;" type="number" step="0.01" min="0" name="amount[<?= $i ?>]" placeholder="0.00"></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="hint">Enter an amount for each fee line that applies to this structure. Zero/blank lines are skipped.</div>
    </div>
    <button class="btn" type="submit">Publish structure</button>
  </form>
</div>

<div class="card">
  <h2><?= count($structures) ?> structure(s)</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Name</th><th>Term</th><th class="num">Total</th><th>Status</th><th>Created by</th></tr></thead>
      <tbody>
      <?php foreach ($structures as $fs): ?>
        <tr>
          <td><?= e($fs['name']) ?></td>
          <td><?= e($fs['term_name'] ?? '') ?></td>
          <td class="num"><?= money((float)$fs['total_amount']) ?></td>
          <td><span class="badge<?= ($fs['status'] ?? '') === 'published' ? ' badge-ok' : ' badge-warn' ?>"><?= e($fs['status'] ?? '') ?></span></td>
          <td><?= e($fs['created_by_name'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$structures): ?><tr><td colspan="5">No fee structures yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
