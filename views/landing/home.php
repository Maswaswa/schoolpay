<?php /** Landing page - public showcase. $schoolCount / $studentCount passed in. */ ?>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="hero-kicker">School fees, finally under control</span>
      <h1>Track, bill and collect school fees — with every shilling accounted for.</h1>
      <p class="hero-sub">
        <?= e(APP_NAME) ?> gives schools one place to register students, build fee structures,
        invoice each term, record bursar-collected payments and reconcile cash — with receipts,
        SMS/email alerts and audit trails built in.
      </p>
      <div class="hero-actions">
        <a class="btn-hero" href="/staff-login">Staff login</a>
        <a class="btn-hero-ghost" href="/parent-login">Parent portal</a>
      </div>
    </div>
    <aside class="hero-panel">
      <ul class="hero-points">
        <li>Bursar-only collections — no third-party payment provider</li>
        <li>Instant printable receipts with reprint control</li>
        <li>Fee structures invoiced to a whole term in one run</li>
        <li>Daily cash sessions with open/close reconciliation</li>
        <li>Automatic SMS &amp; email balance notifications</li>
        <li>Full audit log of every sensitive action</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Pick your entrance</h2>
      <p>Three doors, one system. Each role signs in through its own page and lands straight in the tools it is allowed to use.</p>
    </div>
    <div class="audience-grid">
      <div class="audience-card">
        <div class="audience-icon">🎓</div>
        <h3>Students</h3>
        <p>Check your fee statement, see what has been paid and what is outstanding for the term.</p>
        <a class="btn" href="/student-login">Student login</a>
      </div>
      <div class="audience-card">
        <div class="audience-icon">👪</div>
        <h3>Parents &amp; guardians</h3>
        <p>Follow all your children in one place, download receipts and get alerts when fees are due.</p>
        <a class="btn" href="/parent-login">Parent login</a>
      </div>
      <div class="audience-card">
        <div class="audience-icon">🏫</div>
        <h3>School staff</h3>
        <p>Bursars, admins and accountants manage billing, record payments and close the books daily.</p>
        <a class="btn" href="/staff-login">Staff login</a>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Everything a school fees office needs</h2>
      <p>Built for the way Ugandan schools actually collect fees — cash at the bursar's desk, receipts on the spot, books that balance.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-icon">🧾</div>
        <h3>Fees &amp; billing</h3>
        <p>Define fee items, assemble term structures per class, and invoice every student in one billing run.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💵</div>
        <h3>Bursar collections</h3>
        <p>Payments are recorded directly by the bursar — no gateways, no middlemen, no hidden charges.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🧮</div>
        <h3>Cash sessions</h3>
        <p>Open a session with a float, record payments against it, and close with an automatic variance check.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📱</div>
        <h3>Parent portal</h3>
        <p>Guardians log in to see balances per child, payment history and printable receipts.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Reports &amp; reconciliation</h3>
        <p>Daily collection reports, outstanding balances lists and ledger-vs-cash reconciliation views.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Roles &amp; audit trail</h3>
        <p>Nine granular roles from bursar to auditor, and every sensitive action is written to an audit log.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>How a term runs on Schoolpay</h2>
      <p>From registration to a balanced cash book in five steps.</p>
    </div>
    <div class="steps">
      <div class="step"><h3>Register students</h3><p>Enrol learners with auto-generated student IDs and guardian contacts.</p></div>
      <div class="step"><h3>Build the fee structure</h3><p>Combine tuition, meals, uniform and other items into a per-class term structure.</p></div>
      <div class="step"><h3>Invoice the term</h3><p>One billing run raises invoices for every active student in the selected classes.</p></div>
      <div class="step"><h3>Collect &amp; receipt</h3><p>The bursar records each payment and prints an official receipt instantly.</p></div>
      <div class="step"><h3>Reconcile &amp; report</h3><p>Close the cash session, reconcile the ledger and send SMS/email reminders.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band">
      <div>
        <h2>Ready to see it for yourself?</h2>
        <p>Staff, parents and students each have their own way in — pick yours.</p>
      </div>
      <div class="hero-actions">
        <a class="btn-hero" href="/staff-login">Staff login</a>
        <a class="btn-hero-ghost" href="/about">Learn more</a>
      </div>
    </div>
  </div>
</section>
