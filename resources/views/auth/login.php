<?php $this->layout('layouts.guest', ['title' => 'Sign In — Veen Health']) ?>

<div class="auth-wrap">
  <!-- Left Brand Hero -->
  <div class="auth-hero">
    <div>
      <div class="brand-hero-header" style="margin-bottom: 32px;">
        <img src="<?= asset('img/logo-white.png') ?>" alt="Veen Health - Better Care. Smarter Hospitals." class="logo-dark-mode" style="max-height: 68px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 16px rgba(20, 184, 166, 0.25));">
        <img src="<?= asset('img/logo.png') ?>" alt="Veen Health - Better Care. Smarter Hospitals." class="logo-light-mode" style="max-height: 68px; width: auto; object-fit: contain; transform:scale(2.0)">
      </div>
      <h1 style="margin-top: 10px; font-size: 38px; font-weight: 800; line-height: 1.15; letter-spacing: -0.02em;">
        Transforming How Healthcare <span class="grad">Does Business.</span>
      </h1>
      <p class="muted" style="max-width: 520px; font-size: 15px; margin-top: 16px; line-height: 1.6;">
        Veen Health bridges clinical excellence with financial intelligence. From paperless EMR and high-speed FEFO pharmacy POS to automated queue audio-calling and multi-branch operations, we empower medical institutions to operate with unmatched speed, transparency, and profitability.
      </p>
    </div>

    <div class="auth-features">
      <div class="auth-feature">
        <strong>⚡ Zero Revenue Leakage</strong>
        <span>Unified OPD/IPD invoicing, automated insurance claims, and split mobile money / cash reconciliation.</span>
      </div>
      <div class="auth-feature">
        <strong>💊 Smart FEFO Pharmacy POS</strong>
        <span>Real-time batch tracking, wholesale stock valuation, barcode dispensing, and automated reorder alerts.</span>
      </div>
      <div class="auth-feature">
        <strong>🩺 Longitudinal Clinical EMR</strong>
        <span>Multi-encounter consultation records, lab test orders with LIMS tracking, and paperless digital prescriptions.</span>
      </div>
      <div class="auth-feature">
        <strong>📢 Synchronized Patient Flow</strong>
        <span>Automated multi-room audio calling, priority triage, and live waiting room TV monitors to slash wait times.</span>
      </div>
    </div>

    <div class="dim" style="font-size: 12.5px; display: flex; align-items: center; gap: 8px;">
      <span>Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: var(--brand); font-weight: 700; text-decoration: none;">Veen Africa</a></span>
      <span>•</span>
      <span>© <?= date('Y') ?> All rights reserved.</span>
    </div>
  </div>

  <!-- Right Login Panel -->
  <div class="auth-panel">
    <div class="auth-card">
      <div class="auth-brand-badge" style="margin-bottom: 24px;">
        <img src="<?= asset('img/logo-white.png') ?>" alt="Veen Health" class="logo-dark-mode" style="max-height: 48px; width: auto; object-fit: contain;">
        <img src="<?= asset('img/logo.png') ?>" alt="Veen Health" class="logo-light-mode" style="max-height: 48px; width: auto; object-fit: contain;">
      </div>

      <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 6px;">Sign In to Workspace</h2>
      <p class="muted" style="margin-bottom: 24px; font-size: 14px;">Enter your medical or administrative credentials to proceed.</p>

      <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><?= e($msg) ?></div>
      <?php endif; ?>
      <?php if ($msg = flash('info')): ?>
        <div class="alert alert-info"><?= e($msg) ?></div>
      <?php endif; ?>
      <?php if ($err = errors('email')): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= route('login.post') ?>">
        <?= csrf_field() ?>

        <div class="field" style="margin-bottom: 18px;">
          <label for="email">Work Email</label>
          <input type="email" id="email" name="email" class="input <?= errors('email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required autofocus placeholder="name@hospital.com">
        </div>

        <div class="field" style="margin-bottom: 24px;">
          <label for="password">Password</label>
          <div style="position: relative; display: flex; align-items: center;">
            <input type="password" id="password" name="password" class="input" value="" required placeholder="Enter your password" style="padding-right: 44px; width: 100%;">
            <button type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; display: flex; align-items: center;" title="Show or hide password">
              <svg id="eyeIconOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg id="eyeIconClosed" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 20px; height: 20px; display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="font-weight: 600;">
          <span>Sign In to Workspace</span>
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      <!-- Quick Role Access for Evaluation & Staging 
      <details class="demo-creds" style="margin-top: 24px; border: 1px dashed var(--border); border-radius: var(--radius-sm); padding: 12px 14px; background: rgba(255, 255, 255, 0.02);">
        <summary style="cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; user-select: none;">
          <span style="display: flex; align-items: center; gap: 6px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px; color: var(--brand);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Role Showcase & Quick Access</span>
          </span>
          <span class="dim" style="font-size: 11px;">1-Click Fill &darr;</span>
        </summary>
        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 12px;">
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left; background: rgba(20, 184, 166, 0.08); border: 1px solid var(--brand);" onclick="fillCreds('achieng@nairobicare.co.ke', 'Password@123')">
            🩺 <strong>Doctor: Dr. Achieng Otieno</strong> &nbsp;<span class="dim">(achieng@nairobicare.co.ke)</span>
          </button>
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left;" onclick="fillCreds('admin@nairobicare.co.ke', 'Password@123')">
            🏥 <strong>Hospital Admin: Nairobi Care</strong> &nbsp;<span class="dim">(admin@nairobicare.co.ke)</span>
          </button>
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left;" onclick="fillCreds('brian@nairobicare.co.ke', 'Password@123')">
            💊 <strong>Pharmacy POS: Brian</strong> &nbsp;<span class="dim">(brian@nairobicare.co.ke)</span>
          </button>
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left;" onclick="fillCreds('peter@nairobicare.co.ke', 'Password@123')">
            🧪 <strong>LIMS Lab Tech: Peter</strong> &nbsp;<span class="dim">(peter@nairobicare.co.ke)</span>
          </button>
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left;" onclick="fillCreds('mercy@nairobicare.co.ke', 'Password@123')">
            💳 <strong>Cashier & Billing: Mercy</strong> &nbsp;<span class="dim">(mercy@nairobicare.co.ke)</span>
          </button>
          <button type="button" class="btn btn-sm btn-ghost" style="justify-content: flex-start; text-align: left;" onclick="fillCreds('admin@veen.africa', 'Password@123')">
            ⚡ <strong>Veen SaaS Super Admin</strong> &nbsp;<span class="dim">(admin@veen.africa)</span>
          </button>
        </div>
      </details>-->
    </div>

    <!-- Right Panel Footer (visible on mobile & desktop) -->
    <div style="margin-top: 20px; text-align: center; font-size: 12.5px; color: var(--text-muted);">
      Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: var(--brand); font-weight: 700; text-decoration: none;">Veen Africa</a>
    </div>
  </div>
</div>

<script>
  function fillCreds(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
  }

  function togglePasswordVisibility() {
    const input = document.getElementById('password');
    const open = document.getElementById('eyeIconOpen');
    const closed = document.getElementById('eyeIconClosed');
    if (input.type === 'password') {
      input.type = 'text';
      open.style.display = 'none';
      closed.style.display = 'inline';
    } else {
      input.type = 'password';
      open.style.display = 'inline';
      closed.style.display = 'none';
    }
  }
</script>
