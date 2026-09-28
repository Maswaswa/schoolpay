<?php /** Open a cash session. */ ?>
<div class="card" style="max-width:480px;">
  <h2>Open cash session</h2>
  <form method="post" action="/cash/open">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="opening_amount">Opening float</label>
      <input class="input" id="opening_amount" name="opening_amount" type="number" step="0.01" min="0" value="0">
      <div class="hint">Cash physically in the drawer when the session starts.</div>
    </div>
    <button class="btn" type="submit">Open session</button>
  </form>
</div>
