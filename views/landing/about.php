<?php /** About us - public landing area. */ ?>

<section class="about-hero">
  <div class="container">
    <h1>About <?= e(APP_NAME) ?></h1>
    <p>
      We build straightforward, honest software for school fees administration — designed for the
      schools that collect fees in cash at the bursar's desk and need every shilling accounted for.
    </p>
  </div>
</section>

<section class="section" style="padding-top: 10px;">
  <div class="container">
    <div class="section-head">
      <h2>Why we exist</h2>
      <p>
        Fee collection in many schools runs on paper receipts, hand-written ledgers and a shoebox of
        records. Disputes are hard to settle, balances are guessed, and audits take weeks. We replace
        that with a single, auditable system that works the way the bursar already works.
      </p>
    </div>
    <div class="value-grid">
      <div class="value-card">
        <h3>🎯 Honest by design</h3>
        <p>Every payment, adjustment and session close is logged. Nine granular roles mean the right
           people see the right numbers — and the auditor sees everything.</p>
      </div>
      <div class="value-card">
        <h3>🤝 Bursar-first, not fintech-first</h3>
        <p>We deliberately involve no third-party payment provider. Money moves the way it always has —
           cash at the desk — but the records move instantly and reconcile themselves.</p>
      </div>
      <div class="value-card">
        <h3>👨‍👩‍👧 Families included</h3>
        <p>Parents and students get their own portal: balances per child, receipts to print at home and
           SMS/email alerts when fees are due.</p>
      </div>
      <div class="value-card">
        <h3>⚡ Small, fast, dependable</h3>
        <p>Plain PHP and SQLite keep the system light enough for any school computer — no cloud
           dependency, no monthly gateway fees, data stays with the school.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>What the system covers</h2>
      <p>One product, five connected areas.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-icon">🎓</div>
        <h3>Students &amp; families</h3>
        <p>Registration with auto-generated student IDs, guardian links and class placement.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🧾</div>
        <h3>Fees &amp; invoicing</h3>
        <p>Fee items, per-class term structures and bulk billing runs for the whole term.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💵</div>
        <h3>Collections</h3>
        <p>Bursar-recorded payments, instant receipts and reprint controls.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🧮</div>
        <h3>Controls</h3>
        <p>Cash sessions with open/close variance checks, daily reports and reconciliation.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Oversight</h3>
        <p>Role-based access and a complete audit log of sensitive actions.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band">
      <div>
        <h2>Questions? Come and see it work.</h2>
        <p>Log in with a demo account or contact the school office for a walkthrough.</p>
      </div>
      <div class="hero-actions">
        <a class="btn-hero" href="/staff-login">Staff login</a>
        <a class="btn-hero-ghost" href="/">Back to home</a>
      </div>
    </div>
  </div>
</section>
