<?php $this->layout('layouts.app', ['title' => 'Platform Administration']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Platform / Veen Africa Multi-Tenant</div>
    <h1>Hospital SaaS Fleet Management</h1>
    <p class="muted">Manage hospitals, tenant isolation IDs, country configurations, dynamic plans, and cloud onboarding.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('platform.plans') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      <span>Manage Plans</span>
    </a>
    <button type="button" class="btn btn-primary btn-lg" onclick="openOnboardModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Onboard New Hospital</span>
    </button>
  </div>
</div>

<!-- Fleet Overview Stats -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    </div>
    <div class="label">Total Hospitals (Tenants)</div>
    <div class="value"><?= $metrics['total_tenants'] ?></div>
    <div class="delta"><?= $metrics['active_tenants'] ?> active • <?= $metrics['trial_tenants'] ?> in trial</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <div class="label">Cross-Tenant Patients</div>
    <div class="value"><?= number_format($metrics['total_patients']) ?></div>
    <div class="delta">Strictly isolated by tenant_id</div>
  </div>

  <div class="stat" style="--accent: #f59e0b;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Countries Active</div>
    <div class="value"><?= count(array_unique(array_column($tenants, 'country_code'))) ?></div>
    <div class="delta">Multi-currency & localized VAT</div>
  </div>

  <div class="stat" style="--accent: #8b5cf6;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
    </div>
    <div class="label">SaaS Security</div>
    <div class="value">Active</div>
    <div class="delta">BelongsToTenant Global Scope Enforced</div>
  </div>
</div>

<!-- Tenants Table -->
<div class="card">
  <div class="card-head">
    <h3>Registered Hospital Workspaces</h3>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tenant ID</th>
          <th>Hospital Name</th>
          <th>Country / Currency</th>
          <th>Plan & Cycle</th>
          <th>Patients</th>
          <th>Users / Facilities</th>
          <th>Status</th>
          <th style="text-align: right;">Workspace Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tenants as $t): ?>
          <tr style="<?= ($activeTenantId ?? null) == $t['id'] ? 'background: rgba(20, 184, 166, 0.08);' : '' ?>">
            <td>
              <strong class="mono" style="color: var(--brand);">#<?= $t['id'] ?></strong>
              <?php if (($activeTenantId ?? null) == $t['id']): ?>
                <span class="badge badge-brand" style="margin-left: 4px; font-size: 10px;">CURRENT</span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="font-size: 15px;"><?= e($t['name']) ?></strong>
              <div class="dim mono" style="font-size: 11px;">Slug: <strong><?= e($t['slug']) ?></strong></div>
            </td>
            <td>
              <span class="badge badge-brand"><?= e($t['country_code']) ?></span>
              <span class="mono" style="margin-left: 4px;"><?= e($t['currency']) ?></span>
            </td>
            <td>
              <span class="badge badge-info"><?= e($t['plan_name'] ?: 'Standard') ?></span>
              <div class="dim" style="font-size: 11px; margin-top: 2px;">
                Cycle: <strong style="text-transform: capitalize;"><?= str_replace('_', ' ', $t['billing_cycle'] ?? 'free_trial') ?></strong>
                <?php if (!empty($t['subscription_expires_at'])): ?>
                  <br>Exp: <?= e($t['subscription_expires_at']) ?>
                <?php elseif (!empty($t['trial_ends_at'])): ?>
                  <br>Trial ends: <?= e($t['trial_ends_at']) ?>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong class="mono"><?= number_format($t['patient_count']) ?></strong>
            </td>
            <td>
              <span class="muted"><?= $t['user_count'] ?> users • <?= $t['branch_count'] ?> branch</span>
            </td>
            <td>
              <span class="badge badge-<?= $t['status'] === 'active' ? 'success' : ($t['status'] === 'trial' ? 'warning' : 'danger') ?>">
                <?= ucfirst($t['status']) ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 6px; align-items: center;">
                <button type="button" class="btn btn-sm btn-ghost" onclick="openTenantModulesModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)" title="Configure enabled modules & feature flags for this hospital">
                  ⚙ Modules
                </button>
                <form method="POST" action="<?= route('platform.switch_tenant', ['id' => $t['uuid'] ?? $t['id']]) ?>" style="margin: 0;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-primary" title="Enter and manage this hospital workspace">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span>Enter Workspace</span>
                  </button>
                </form>
                <form method="POST" action="<?= route('platform.tenants.toggle', ['id' => $t['uuid'] ?? $t['id']]) ?>" style="margin: 0;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-ghost">
                    <?= $t['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Onboard Modal -->
<?php 
  $formErrors = errors(); 
  $flashError = flash('error');
  $hasOnboardErrors = !empty($formErrors) || !empty($flashError);
?>
<div id="newTenantModal" class="<?= $hasOnboardErrors ? '' : 'hidden' ?>" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 650px; padding: 28px; max-height: 92vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Onboard New Hospital</h3>
      <button type="button" class="icon-btn" onclick="closeOnboardModal()">&times;</button>
    </div>

    <?php if ($hasOnboardErrors): ?>
      <div class="alert alert-danger" style="margin-bottom: 18px; padding: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: 8px;">
        <strong style="color: #ef4444;">Please correct the following errors:</strong>
        <?php if (!empty($flashError)): ?>
          <div style="font-size: 13px; margin-top: 4px; color: #fca5a5; font-weight: 600;"><?= e($flashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($formErrors)): ?>
          <ul style="margin: 6px 0 0 18px; font-size: 13px;">
            <?php foreach ($formErrors as $field => $fieldErrors): ?>
              <?php foreach ((array)$fieldErrors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= route('platform.tenants.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label for="h_name">Hospital / Clinic Name *</label>
          <input type="text" id="h_name" name="name" class="input" required value="<?= e(old('name')) ?>" placeholder="e.g. Nairobi Metropolitan Hospital" oninput="autoGenerateSlug(this.value)">
        </div>

        <div class="field">
          <label for="h_slug">Tenant Subdomain / Slug *</label>
          <input type="text" id="h_slug" name="slug" class="input" required value="<?= e(old('slug')) ?>" placeholder="e.g. nairobi-metropolitan">
          <span class="dim" style="font-size: 11px;">Autogenerated from clinic name. Alphanumeric and hyphens only.</span>
        </div>

        <div class="field">
          <label for="country_code">Country *</label>
          <select id="country_code" name="country_code" class="input" required>
            <?php foreach ($countries as $c): ?>
              <option value="<?= e($c['code']) ?>" <?= old('country_code', 'KE') === $c['code'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?> (<?= e($c['currency']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="plan_id">Subscription Plan Tier *</label>
          <select id="plan_id" name="plan_id" class="input" required>
            <?php foreach ($plans as $p): ?>
              <option value="<?= $p['id'] ?>" <?= old('plan_id') == $p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?> ($<?= number_format($p['price_usd_cents'] / 100, 2) ?>/mo)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="billing_cycle">Billing Cycle & Model *</label>
          <select id="billing_cycle" name="billing_cycle" class="input" required onchange="toggleCycleFields(this.value)">
            <option value="free_trial" <?= old('billing_cycle', 'free_trial') === 'free_trial' ? 'selected' : '' ?>>Free Trial</option>
            <option value="monthly" <?= old('billing_cycle') === 'monthly' ? 'selected' : '' ?>>Per Month (Monthly)</option>
            <option value="yearly" <?= old('billing_cycle') === 'yearly' ? 'selected' : '' ?>>Per Year (Annual)</option>
            <option value="one_time" <?= old('billing_cycle') === 'one_time' ? 'selected' : '' ?>>One-Time Lifetime License</option>
          </select>
        </div>

        <div class="field" id="trialDaysField">
          <label for="trial_days">Trial Duration (Days)</label>
          <input type="number" id="trial_days" name="trial_days" class="input" value="<?= e(old('trial_days', '14')) ?>" min="1" max="365">
        </div>

        <div class="field">
          <label for="h_email">Hospital Contact Email</label>
          <input type="email" id="h_email" name="email" class="input" value="<?= e(old('email')) ?>" placeholder="info@hospital.com">
        </div>

        <div class="divider full"></div>
        <div class="full" style="font-size: 13px; font-weight: 700; color: var(--brand);">HOSPITAL ADMINISTRATOR ACCOUNT</div>

        <div class="field full">
          <label for="admin_name">Admin Full Name *</label>
          <input type="text" id="admin_name" name="admin_name" class="input" required value="<?= e(old('admin_name')) ?>" placeholder="e.g. Dr. Jane Smith">
        </div>

        <div class="field">
          <label for="admin_email">Admin Email (Used for login) *</label>
          <input type="email" id="admin_email" name="admin_email" class="input" required value="<?= e(old('admin_email')) ?>" placeholder="admin@hospital.com">
        </div>

        <div class="field">
          <label for="admin_password">Initial Password *</label>
          <div class="password-field-wrap">
            <input type="password" id="admin_password" name="admin_password" class="input" required value="<?= e(old('admin_password', 'Password@123')) ?>">
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('admin_password', this)" title="Show/Hide Password" aria-label="Toggle password visibility">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="closeOnboardModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Provision Tenant Workspace</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Configure Hospital Modules & Pay-Per-Feature -->
<div id="tenantModulesModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h3 style="margin: 0;" id="modTitle">Configure Hospital Modules</h3>
        <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Enable or restrict platform modules based on this hospital's billing subscription tier.</p>
      </div>
      <button type="button" class="icon-btn" onclick="document.getElementById('tenantModulesModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="tenantModulesForm" method="POST" action="">
      <?= csrf_field() ?>
      <div style="display: flex; flex-direction: column; gap: 12px; margin: 16px 0;">
        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🩺 Doctor Clinical EMR & Consultations</strong>
            <div class="dim" style="font-size: 11px;">OPD clinical notes, ICD-10 diagnoses, vitals, prescriptions</div>
          </div>
          <input type="checkbox" name="module_consultations" id="mod_consultations" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>💊 Pharmacy OTC POS & FEFO Inventory</strong>
            <div class="dim" style="font-size: 11px;">Over-the-counter sales, cash register, batch expiry tracking</div>
          </div>
          <input type="checkbox" name="module_pharmacy" id="mod_pharmacy" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🔬 Laboratory LIMS Diagnostic System</strong>
            <div class="dim" style="font-size: 11px;">Sample collection, technician workbench, verification</div>
          </div>
          <input type="checkbox" name="module_lab" id="mod_lab" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🏨 Inpatient Wards & Visual Bed Board (IPD)</strong>
            <div class="dim" style="font-size: 11px;">Bed occupancy, daily rate billing, transfers, discharges</div>
          </div>
          <input type="checkbox" name="module_inpatient" id="mod_inpatient" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>💳 Invoicing, Cashier Desk & Insurance Claims</strong>
            <div class="dim" style="font-size: 11px;">Cash/M-Pesa payment recording, claim adjudication</div>
          </div>
          <input type="checkbox" name="module_billing" id="mod_billing" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>📣 Patient Queue & Voice Audio Calling</strong>
            <div class="dim" style="font-size: 11px;">Kiosk ticketing, service point recall, digital TV displays</div>
          </div>
          <input type="checkbox" name="module_queue" id="mod_queue" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>📅 Appointments & Calendar Reservations</strong>
            <div class="dim" style="font-size: 11px;">Patient scheduling, daily arrival reminder alerts</div>
          </div>
          <input type="checkbox" name="module_appointments" id="mod_appointments" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>📊 Comprehensive Reports & Financial Year</strong>
            <div class="dim" style="font-size: 11px;">Fiscal year auditing, department volume, pharmacy margin</div>
          </div>
          <input type="checkbox" name="module_reports" id="mod_reports" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>👶 Clinic for Kids & Child Welfare (CWC)</strong>
            <div class="dim" style="font-size: 11px;">EPI vaccine schedules, developmental milestones, MUAC & growth charts</div>
          </div>
          <input type="checkbox" name="module_clinic_kids" id="mod_clinic_kids" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🤰 Pregnant Women Clinic (ANC & Maternal Health)</strong>
            <div class="dim" style="font-size: 11px;">Gravida/parity, trimester visits, PMTCT, birth plans & delivery log</div>
          </div>
          <input type="checkbox" name="module_clinic_maternal" id="mod_clinic_maternal" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🎗️ HIV Comprehensive Care Centre (CCC)</strong>
            <div class="dim" style="font-size: 11px;">ART regimens, CD4 / Viral Load suppression cascades, MMD refills & passports</div>
          </div>
          <input type="checkbox" name="module_clinic_ccc" id="mod_clinic_ccc" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🩻 Radiology & Diagnostic Imaging (MRI, CT, X-Ray)</strong>
            <div class="dim" style="font-size: 11px;">Inpatient MRI requisition, pre-scan safety checklist, porter transport & report verification</div>
          </div>
          <input type="checkbox" name="module_radiology" id="mod_radiology" value="1" style="width: 18px; height: 18px;">
        </label>

        <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
          <div>
            <strong>🕊️ Mortuary & Deceased Registry</strong>
            <div class="dim" style="font-size: 11px;">Inpatient & external deceased intake, cold room storage, pathology autopsy & release permits</div>
          </div>
          <input type="checkbox" name="module_mortuary" id="mod_mortuary" value="1" style="width: 18px; height: 18px;">
        </label>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('tenantModulesModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Active Modules</button>
      </div>
    </form>
  </div>
</div>

<script>
function openOnboardModal() {
  document.getElementById('newTenantModal').classList.remove('hidden');
}

function closeOnboardModal() {
  document.getElementById('newTenantModal').classList.add('hidden');
}

function autoGenerateSlug(name) {
  const slugInput = document.getElementById('h_slug');
  const slug = name.toString().toLowerCase().trim()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9 -]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
  slugInput.value = slug;
}

function toggleCycleFields(cycle) {
  const field = document.getElementById('trialDaysField');
  if (cycle === 'free_trial') {
    field.style.display = 'block';
  } else {
    field.style.display = 'none';
  }
}

function openTenantModulesModal(t) {
  document.getElementById('modTitle').textContent = 'Modules for ' + t.name;
  const baseUrl = (window.APP_BASE || '').replace(/\/$/, '');
  const tenantKey = t.uuid || t.id;
  document.getElementById('tenantModulesForm').action = baseUrl + '/platform/tenants/' + encodeURIComponent(tenantKey) + '/modules';

  let settings = {};
  try {
    if (typeof t.settings === 'string') {
      settings = JSON.parse(t.settings || '{}');
    } else if (typeof t.settings === 'object' && t.settings !== null) {
      settings = t.settings;
    }
  } catch (e) {}

  const mods = settings.modules || null;
  // If no settings exist yet, default all to true
  const isDefaultAll = (mods === null || typeof mods !== 'object');

  const keys = ['consultations', 'pharmacy', 'lab', 'inpatient', 'billing', 'queue', 'appointments', 'reports', 'clinic_kids', 'clinic_maternal', 'clinic_ccc', 'radiology', 'mortuary'];
  keys.forEach(k => {
    const el = document.getElementById('mod_' + k);
    if (el) {
      el.checked = isDefaultAll ? true : Boolean(mods[k]);
    }
  });

  document.getElementById('tenantModulesModal').classList.remove('hidden');
}
</script>
