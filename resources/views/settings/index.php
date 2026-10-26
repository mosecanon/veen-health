<?php $this->layout('layouts.app', ['title' => 'Settings & Facility Branding']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Hospital Administration / Configuration</div>
    <h1>Hospital Settings & Security Profile</h1>
    <p class="muted">Manage facility branding, company details, hospital logo, and staff security credentials.</p>
  </div>
</div>

<div class="grid grid-2" style="gap: 24px; align-items: start;">
  <!-- Left Column: Company & Hospital Settings (Admin Only) -->
  <div>
    <?php if ($isAdmin): ?>
      <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--brand);">
        <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
              <svg style="width: 20px; height: 20px; color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              <span>Hospital & Facility Branding</span>
            </h3>
            <div class="muted" style="font-size: 12px; margin-top: 2px;">Company profile, logo, receipts header and regional currency.</div>
          </div>
          <span class="badge badge-brand">ADMIN ONLY</span>
        </div>

        <form method="POST" action="<?= route('settings.company') ?>" enctype="multipart/form-data" style="padding: 20px 22px;">
          <?= csrf_field() ?>

          <!-- Logo Upload & Preview Section -->
          <div style="margin-bottom: 20px; padding: 16px; background: rgba(0,0,0,0.15); border-radius: 8px; border: 1px dashed var(--border); display: flex; gap: 20px; align-items: center;">
            <div style="flex-shrink: 0; text-align: center;">
              <?php if (!empty($tenant['logo'])): ?>
                <img id="logoPreview" src="<?= asset($tenant['logo']) ?>" alt="Hospital Logo" style="width: 72px; height: 72px; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 4px; border: 1px solid var(--border);">
              <?php else: ?>
                <div id="logoPreviewPlaceholder" style="width: 72px; height: 72px; border-radius: 8px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: var(--brand); border: 1px solid var(--border);">
                  <?= strtoupper(substr($tenant['name'] ?? 'V', 0, 1)) ?>
                </div>
                <img id="logoPreview" src="" alt="Hospital Logo" style="width: 72px; height: 72px; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 4px; border: 1px solid var(--border); display: none;">
              <?php endif; ?>
              <div class="dim" style="font-size: 10px; margin-top: 4px;">Active Logo</div>
            </div>

            <div style="flex: 1;">
              <label style="font-weight: 700; font-size: 13px; display: block; margin-bottom: 4px;">Upload Hospital Logo</label>
              <p class="muted" style="margin: 0 0 10px 0; font-size: 11.5px;">Recommended: High-res square or horizontal PNG/JPG with transparent or solid background (Max 4MB). Used on invoices, patient ID cards, and receipts.</p>
              <input type="file" name="logo" id="logoInput" class="input" accept="image/png,image/jpeg,image/webp,image/svg+xml" onchange="previewLogoFile(this)" style="font-size: 12px; padding: 7px 10px;">
            </div>
          </div>

          <div class="form-grid">
            <div class="field full">
              <label>Hospital / Facility Legal Name *</label>
              <input type="text" name="name" class="input" required value="<?= e($tenant['name']) ?>" placeholder="e.g. St. Jude Regional Hospital & Medical Centre">
            </div>

            <div class="field">
              <label>Official Phone Number</label>
              <input type="tel" name="phone" class="input" value="<?= e($tenant['phone']) ?>" placeholder="e.g. +254 700 123 456">
            </div>

            <div class="field">
              <label>Official Inquiries Email</label>
              <input type="email" name="email" class="input" value="<?= e($tenant['email']) ?>" placeholder="e.g. info@hospital.co.ke">
            </div>

            <div class="field full">
              <label>Physical Address & Facility Location</label>
              <input type="text" name="address" class="input" value="<?= e($tenant['address']) ?>" placeholder="e.g. Hospital Road, Medical Plaza 4th Floor, Nairobi">
            </div>

            <div class="field">
              <label>Tax PIN / VAT Registration #</label>
              <input type="text" name="tax_pin" class="input" value="<?= e($tenant['tax_pin']) ?>" placeholder="e.g. P051234567Z">
            </div>

            <div class="field">
              <label>Brand Theme Color</label>
              <div style="display: flex; gap: 8px; align-items: center;">
                <input type="color" id="primaryColorPicker" value="<?= e($tenant['primary_color'] ?: '#14b8a6') ?>" oninput="document.getElementById('primaryColorText').value = this.value" style="width: 44px; height: 42px; padding: 2px; border-radius: 6px; cursor: pointer; border: 1px solid var(--border); background: transparent;">
                <input type="text" id="primaryColorText" name="primary_color" class="input font-mono" value="<?= e($tenant['primary_color'] ?: '#14b8a6') ?>" oninput="document.getElementById('primaryColorPicker').value = this.value">
              </div>
            </div>

            <div class="field">
              <label>Primary Accounting Currency</label>
              <select name="currency" class="input">
                <?php
                  $currencies = ['KES' => 'Kenyan Shilling (KES)', 'USD' => 'US Dollar ($)', 'UGX' => 'Ugandan Shilling (UGX)', 'TZS' => 'Tanzanian Shilling (TZS)', 'NGN' => 'Nigerian Naira (NGN)', 'GHS' => 'Ghanaian Cedi (GHS)', 'RWF' => 'Rwandan Franc (RWF)', 'ZAR' => 'South African Rand (ZAR)', 'EUR' => 'Euro (€)', 'GBP' => 'British Pound (£)'];
                  $currentCur = $tenant['currency'] ?: 'KES';
                ?>
                <?php foreach ($currencies as $code => $label): ?>
                  <option value="<?= $code ?>" <?= $currentCur === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Operating Timezone</label>
              <select name="timezone" class="input">
                <?php
                  $timezones = ['Africa/Nairobi' => 'Africa/Nairobi (EAT +03:00)', 'Africa/Kampala' => 'Africa/Kampala (EAT +03:00)', 'Africa/Dar_es_Salaam' => 'Africa/Dar_es_Salaam (EAT +03:00)', 'Africa/Lagos' => 'Africa/Lagos (WAT +01:00)', 'Africa/Accra' => 'Africa/Accra (GMT +00:00)', 'Africa/Kigali' => 'Africa/Kigali (CAT +02:00)', 'Africa/Johannesburg' => 'Africa/Johannesburg (SAST +02:00)', 'UTC' => 'UTC (Universal Coordinated Time)'];
                  $currentTz = $tenant['timezone'] ?: 'Africa/Nairobi';
                ?>
                <?php foreach ($timezones as $tz => $label): ?>
                  <option value="<?= $tz ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-actions" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary btn-lg">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Save Hospital Branding & Settings</span>
            </button>
          </div>
        </form>
      </div>

      <!-- System Cache & Performance Flush Point -->
      <div class="card" style="margin-bottom: 24px; border-left: 4px solid #f59e0b;">
        <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
              <svg style="width: 20px; height: 20px; color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <span>System Performance & Cache Flush Point</span>
            </h3>
            <div class="muted" style="font-size: 12px; margin-top: 2px;">Purge temporary cached data, Redis buffers, and runtime application memory.</div>
          </div>
          <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-weight: 700;">
            ⚡ <?= strtoupper(\Veen\Core\Cache::driver()) ?> ACTIVE
          </span>
        </div>

        <div style="padding: 20px 22px;">
          <div style="display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            <div style="max-width: 440px;">
              <strong style="display: block; font-size: 13.5px; color: var(--text); margin-bottom: 4px;">
                Reset Application Runtime Cache
              </strong>
              <p class="muted" style="font-size: 12px; margin: 0; line-height: 1.5;">
                Need to clear old thumbnails, refresh updated hospital logos, or force instant synchronization across medical workstations? Flush the cache here safely.
              </p>
            </div>

            <form method="POST" action="<?= route('settings.cache.flush') ?>" onsubmit="return confirm('Flush and reset system cache now? This will immediately synchronize all hospital workstations.');" style="margin: 0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-warning" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 10px 18px; cursor: pointer;">
                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Flush & Reset Cache</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="card" style="padding: 24px; border-left: 4px solid var(--border); margin-bottom: 24px;">
        <h3 style="margin: 0 0 8px 0;">Facility Information</h3>
        <p class="muted" style="margin: 0; font-size: 13px;">You are currently logged into <strong><?= e($tenant['name']) ?></strong> (#<?= $tenant['id'] ?>). Hospital branding and company configuration is managed by the Hospital Administrator.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Column: Personal Profile & Password Security -->
  <div>
    <!-- My Profile Card -->
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #3b82f6;">
      <div class="card-head">
        <div>
          <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
            <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>My Personal Profile</span>
          </h3>
          <div class="muted" style="font-size: 12px; margin-top: 2px;">Your personal name, professional title, and phone contact.</div>
        </div>
        <span class="badge badge-brand"><?= strtoupper(str_replace('_', ' ', $user['role'])) ?></span>
      </div>

      <form method="POST" action="<?= route('settings.profile') ?>" style="padding: 20px 22px;">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field full">
            <label>Full Name *</label>
            <input type="text" name="name" class="input" required value="<?= e($user['name']) ?>">
          </div>

          <div class="field full">
            <label>Email Address <span class="dim" style="font-size: 11px;">(Account Identifier — Locked)</span></label>
            <input type="email" class="input" value="<?= e($user['email']) ?>" disabled readonly style="opacity: 0.7; cursor: not-allowed; background: rgba(0,0,0,0.15);">
            <div class="hint" style="margin-top: 4px; font-size: 11.5px; color: var(--text-dim);">
              🔒 For security and audit logging, staff email addresses are permanent credentials and cannot be edited.
            </div>
          </div>

          <div class="field">
            <label>Professional Designation / Title</label>
            <input type="text" name="title" class="input" value="<?= e($user['title']) ?>" placeholder="e.g. Senior Medical Officer">
          </div>

          <div class="field">
            <label>Personal Mobile Phone</label>
            <input type="tel" name="phone" class="input" value="<?= e($user['phone']) ?>" placeholder="e.g. +254 712 345 678">
          </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
          <button type="submit" class="btn btn-primary">
            <span>Save Profile</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Change Password Card -->
    <div class="card" style="border-left: 4px solid #10b981;">
      <div class="card-head">
        <div>
          <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
            <svg style="width: 20px; height: 20px; color: #10b981;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Change Login Password</span>
          </h3>
          <div class="muted" style="font-size: 12px; margin-top: 2px;">Keep your account secure with a strong password.</div>
        </div>
      </div>

      <form method="POST" action="<?= route('settings.password') ?>" style="padding: 20px 22px;">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field full">
            <label>Current Password *</label>
            <input type="password" name="current_password" class="input" required placeholder="Enter your existing account password">
          </div>

          <div class="field">
            <label>New Password *</label>
            <input type="password" name="password" class="input" required minlength="8" placeholder="Minimum 8 characters">
          </div>

          <div class="field">
            <label>Confirm New Password *</label>
            <input type="password" name="password_confirmation" class="input" required minlength="8" placeholder="Re-type new password">
          </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
          <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">
            <span>Update Password</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function previewLogoFile(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('logoPreview');
      const placeholder = document.getElementById('logoPreviewPlaceholder');
      if (preview) {
        preview.src = e.target.result;
        preview.style.display = 'inline-block';
      }
      if (placeholder) {
        placeholder.style.display = 'none';
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
