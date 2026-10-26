<?php $this->layout('layouts.app', ['title' => 'Comprehensive Care Centre (CCC) - HIV Clinic']) ?>

<style>
/* Sticky Action Column pinned to the right edge - 100% Opaque (Never Transparent) */
.sticky-actions-col {
  position: sticky;
  right: 0;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  box-shadow: -8px 0 16px rgba(0, 0, 0, 0.25);
  z-index: 10;
  text-align: right;
  white-space: nowrap;
}
th.sticky-actions-col {
  z-index: 11;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
}
.table tbody tr:hover .sticky-actions-col {
  background: #1f2937 !important;
  background-color: #1f2937 !important;
  box-shadow: -8px 0 18px rgba(0, 0, 0, 0.35);
}
[data-theme="light"] .sticky-actions-col,
[data-theme="light"] th.sticky-actions-col {
  background: #ffffff !important;
  background-color: #ffffff !important;
  box-shadow: -8px 0 16px rgba(15, 23, 42, 0.08);
}
[data-theme="light"] .table tbody tr:hover .sticky-actions-col {
  background: #f1f5f9 !important;
  background-color: #f1f5f9 !important;
  box-shadow: -8px 0 18px rgba(15, 23, 42, 0.12);
}

.ccc-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow-y: auto;
}

.ccc-modal-content {
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  color: var(--text, #f9fafb);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
  border-radius: 16px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px;
  box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.08);
  position: relative;
  isolation: isolate;
}

[data-theme="light"] .ccc-modal-content {
  background: #ffffff !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  border-color: rgba(15, 23, 42, 0.15);
  box-shadow: 0 25px 65px -15px rgba(15, 23, 42, 0.35);
}

.ccc-modal-section {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.03);
}

[data-theme="light"] .ccc-modal-section {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Specialized Clinics / HIV Care</div>
    <h1>🎗️ Comprehensive Care Centre (CCC)</h1>
    <p class="muted">Longitudinal HIV chronic care, viral load suppression monitoring, Multi-Month Dispensing (MMD), and PMTCT linkage.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openEnrollCccModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Enroll CCC Client</span>
    </button>
  </div>
</div>

<!-- Stats KPIs -->
<div class="grid grid-5" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid var(--brand);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Active on ART</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: var(--brand); margin-top: 4px;"><?= number_format($stats['activeClients']) ?></div>
      </div>
      <div style="background: rgba(20, 184, 166, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">💊</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Receiving Antiretrovirals</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #10b981;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">VL Suppression Rate</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #10b981; margin-top: 4px;"><?= $stats['suppressionRate'] ?>%</div>
      </div>
      <div style="background: rgba(16, 185, 129, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📉</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">VL &lt; 1,000 copies/mL</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #3b82f6;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Suppressed Clients</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #3b82f6; margin-top: 4px;"><?= number_format($stats['suppressedClients']) ?></div>
      </div>
      <div style="background: rgba(59, 130, 246, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🎯</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Undetectable / Suppressed</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #ec4899;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">PMTCT Mothers</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #ec4899; margin-top: 4px;"><?= number_format($stats['pmtctCount']) ?></div>
      </div>
      <div style="background: rgba(236, 72, 153, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🤱</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Maternal HIV Prevention</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #f59e0b;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">2nd / 3rd Line</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #f59e0b; margin-top: 4px;"><?= number_format($stats['secondLineCount']) ?></div>
      </div>
      <div style="background: rgba(245, 158, 11, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🔬</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Switched / Regimen Change</div>
  </div>
</div>

<!-- Filters -->
<div class="card card-pad" style="margin-bottom: 20px;">
  <form method="GET" action="<?= route('clinic.ccc.index') ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
    <div style="flex: 2; min-width: 220px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Search CCC Registry</label>
      <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="CCC #, client name, MRN, phone, regimen...">
    </div>

    <div style="flex: 1; min-width: 140px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Retention Status</label>
      <select name="status" class="form-control">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Retention States</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active on Care</option>
        <option value="transferred_out" <?= $status === 'transferred_out' ? 'selected' : '' ?>>Transferred Out</option>
        <option value="lost_to_followup" <?= $status === 'lost_to_followup' ? 'selected' : '' ?>>Lost to Follow-up (LTFU)</option>
        <option value="deceased" <?= $status === 'deceased' ? 'selected' : '' ?>>Deceased</option>
      </select>
    </div>

    <div style="flex: 1; min-width: 140px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Viral Suppression</label>
      <select name="suppression" class="form-control">
        <option value="all" <?= $suppression === 'all' ? 'selected' : '' ?>>All Viral Loads</option>
        <option value="suppressed" <?= $suppression === 'suppressed' ? 'selected' : '' ?>>✓ Suppressed (&lt; 1,000 c/mL)</option>
        <option value="unsuppressed" <?= $suppression === 'unsuppressed' ? 'selected' : '' ?>>⚠️ Unsuppressed (&ge; 1,000 c/mL)</option>
      </select>
    </div>

    <div style="flex: 1; min-width: 120px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">PMTCT Linkage</label>
      <select name="pmtct" class="form-control">
        <option value="all" <?= $pmtct === 'all' ? 'selected' : '' ?>>All Clients</option>
        <option value="1" <?= $pmtct === '1' ? 'selected' : '' ?>>PMTCT Mothers Only</option>
      </select>
    </div>

    <div style="display: flex; gap: 8px;">
      <button type="submit" class="btn btn-secondary">Filter</button>
      <?php if ($q !== '' || $status !== 'active' || $suppression !== 'all' || $pmtct !== 'all'): ?>
        <a href="<?= route('clinic.ccc.index') ?>" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Table Card -->
<div class="card">
  <!-- Top Horizontal Scroll Helper -->
  <div id="cccTopScroll" style="overflow-x: auto; overflow-y: hidden; height: 16px; border-bottom: 1px solid var(--border); background: var(--surface-2, rgba(255,255,255,0.02));">
    <div id="cccTopScrollInner" style="height: 1px;"></div>
  </div>

  <div id="cccTableWrapper" style="overflow-x: auto; width: 100%;">
    <table class="table" style="margin-bottom: 0; min-width: 1150px;">
      <thead>
        <tr>
          <th>CCC # & Status</th>
          <th>Patient Name & MRN</th>
          <th>Sex / Age</th>
          <th>WHO Stage & Line</th>
          <th>Current ARV Regimen</th>
          <th>Viral Load (copies/mL)</th>
          <th>CD4 Count</th>
          <th>PMTCT Link</th>
          <th>Next Refill Appt</th>
          <th class="sticky-actions-col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
          <tr>
            <td colspan="10" style="text-align: center; padding: 48px; color: var(--text-muted);">
              <div style="font-size: 32px; margin-bottom: 8px;">🎗️</div>
              <div style="font-size: 15px; font-weight: 600;">No CCC clients match your filter criteria</div>
              <p class="muted" style="margin-top: 4px; font-size: 13px;">Enroll a patient into the HIV clinic using the button above.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td>
                <div class="mono" style="font-weight: 700; color: var(--text); font-size: 13.5px;"><?= e($c['ccc_number']) ?></div>
                <div style="margin-top: 3px;">
                  <?php if ($c['status'] === 'active'): ?>
                    <span class="badge badge-success">Active on Care</span>
                  <?php elseif ($c['status'] === 'transferred_out'): ?>
                    <span class="badge badge-info">Transferred Out</span>
                  <?php elseif ($c['status'] === 'lost_to_followup'): ?>
                    <span class="badge badge-warning">LTFU</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Deceased</span>
                  <?php endif; ?>
                </div>
              </td>

              <td>
                <div style="font-weight: 700; color: var(--brand); font-size: 13.5px;">
                  <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                </div>
                <div class="muted mono" style="font-size: 11px; margin-top: 2px;">
                  MRN: <?= e($c['mrn']) ?> &bull; Phone: <?= e($c['phone'] ?: 'N/A') ?>
                </div>
              </td>

              <td>
                <div style="font-size: 13px; font-weight: 600;">
                  <?= ucfirst(e($c['sex'])) ?>
                </div>
                <div class="muted" style="font-size: 11px;">
                  <?= $c['dob'] ? age_from_dob($c['dob']) . ' yrs' : 'N/A' ?>
                </div>
              </td>

              <td>
                <span class="badge badge-secondary"><?= strtoupper(str_replace('_', ' ', e($c['current_who_stage']))) ?></span>
                <div class="muted" style="font-size: 11px; margin-top: 3px;">
                  <?= ucwords(str_replace('_', ' ', e($c['line_of_treatment']))) ?>
                </div>
              </td>

              <td>
                <div style="font-weight: 700; color: #8b5cf6; font-size: 13px; font-family: monospace;">
                  <?= e($c['current_regimen']) ?>
                </div>
                <div class="muted" style="font-size: 11px;">
                  ART Started: <?= $c['art_start_date'] ? date('M Y', strtotime($c['art_start_date'])) : 'N/A' ?>
                </div>
              </td>

              <td>
                <?php if ($c['current_viral_load'] !== null): ?>
                  <div class="mono" style="font-weight: 700; font-size: 13px;">
                    <?= number_format($c['current_viral_load']) ?> c/mL
                  </div>
                  <div style="margin-top: 3px;">
                    <?php if ($c['viral_load_suppressed']): ?>
                      <span class="badge badge-success">✓ Suppressed</span>
                    <?php else: ?>
                      <span class="badge badge-danger">⚠️ Unsuppressed</span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-secondary">Pending Baseline</span>
                <?php endif; ?>
              </td>

              <td>
                <div class="mono" style="font-weight: 600; font-size: 13px;">
                  <?= $c['current_cd4'] ? number_format($c['current_cd4']) . ' cells/µL' : 'Not Done' ?>
                </div>
              </td>

              <td>
                <?php if ($c['pmtct_active']): ?>
                  <span class="badge badge-primary" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">🤱 PMTCT Active</span>
                <?php else: ?>
                  <span class="muted" style="font-size: 12px;">--</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if (!empty($c['next_appointment_date'])): ?>
                  <div class="mono" style="font-size: 12.5px; font-weight: 600;">
                    <?= date('d M Y', strtotime($c['next_appointment_date'])) ?>
                  </div>
                  <?php
                    $diff = (strtotime($c['next_appointment_date']) - time()) / 86400;
                    if ($diff < 0) {
                        echo '<span class="badge badge-danger" style="margin-top: 2px;">Overdue</span>';
                    } elseif ($diff <= 7) {
                        echo '<span class="badge badge-warning" style="margin-top: 2px;">Due Soon</span>';
                    }
                  ?>
                <?php else: ?>
                  <span class="muted" style="font-size: 11px;">Not scheduled</span>
                <?php endif; ?>
              </td>

              <td class="sticky-actions-col">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= route('clinic.ccc.show', ['id' => $c['uuid'] ?? $c['id']]) ?>" class="btn btn-sm btn-secondary">
                    View Care Plan
                  </a>
                  <a href="<?= route('clinic.ccc.card', ['id' => $c['uuid'] ?? $c['id']]) ?>" class="btn btn-sm btn-ghost" title="Print appointment passport">
                    🪪 Card
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Bar (at least 20 records per page) -->
  <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-top: 1px solid var(--border); font-size: 13px;">
    <div class="muted">
      Showing <?= min($total, ($page - 1) * $perPage + 1) ?> to <?= min($total, $page * $perPage) ?> of <?= number_format($total) ?> clients
      (<?= $perPage ?> per page)
    </div>
    <div style="display: flex; gap: 6px;">
      <?php if ($page > 1): ?>
        <a href="<?= route('clinic.ccc.index', array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-sm btn-secondary">← Previous</a>
      <?php endif; ?>
      <?php if ($page < $lastPage): ?>
        <a href="<?= route('clinic.ccc.index', array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-sm btn-secondary">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 100% OPAQUE ENROLLMENT MODAL               -->
<!-- ========================================== -->
<div id="enrollCccModal" class="ccc-modal-backdrop">
  <div class="ccc-modal-content" style="max-width: 820px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
      <div>
        <h3 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
          <span>🎗️</span> Enroll Patient into HIV Clinic (CCC)
        </h3>
        <p class="muted" style="margin: 4px 0 0 0; font-size: 12.5px;">Register patient for chronic antiretroviral therapy (ART) and adherence monitoring.</p>
      </div>
      <button type="button" class="btn btn-ghost" onclick="closeEnrollCccModal()" style="font-size: 18px; line-height: 1;">&times;</button>
    </div>

    <form method="POST" action="<?= route('clinic.ccc.store') ?>">
      <?= csrf_field() ?>

      <!-- Patient Selection -->
      <div class="ccc-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; color: var(--brand); font-weight: 700;">1. Patient Identification</h4>
        
        <div style="margin-bottom: 10px;">
          <input type="text" id="cccCandidateSearchInput" class="form-control" placeholder="🔍 Quick search client candidate by name or MRN..." oninput="filterCccCandidateSelect(this.value)" style="margin-bottom: 8px; font-size: 13px;">
          <label class="form-label">Select Patient to Enroll *</label>
          <select name="patient_id" id="cccPatientSelect" class="form-control" required size="5" style="height: auto;">
            <option value="">-- Choose patient --</option>
            <?php foreach ($candidatePatients as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= e($p['first_name'] . ' ' . $p['last_name']) ?> (MRN: <?= e($p['mrn']) ?>) &mdash; <?= ucfirst(e($p['sex'])) ?> &bull; <?= $p['phone'] ?: 'No Phone' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Enrollment & Clinical Baseline -->
      <div class="ccc-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; color: var(--brand); font-weight: 700;">2. Clinical Baseline & Regimen</h4>
        
        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Enrollment Date *</label>
            <input type="date" name="enrollment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div>
            <label class="form-label">Entry Point *</label>
            <select name="entry_point" class="form-control">
              <option value="opd" selected>Outpatient Department (OPD)</option>
              <option value="inpatient">Inpatient Ward</option>
              <option value="pmtct">Maternal / PMTCT ANC</option>
              <option value="vct">Voluntary Counseling & Testing (VCT)</option>
              <option value="transfer_in">Transfer In from Other Facility</option>
            </select>
          </div>

          <div>
            <label class="form-label">Baseline WHO Stage *</label>
            <select name="who_stage_baseline" class="form-control">
              <option value="stage_1" selected>Stage 1 (Asymptomatic / PGL)</option>
              <option value="stage_2">Stage 2 (Mild Symptoms)</option>
              <option value="stage_3">Stage 3 (Advanced Symptoms)</option>
              <option value="stage_4">Stage 4 (Severe / AIDS Defining)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">ART Start Date</label>
            <input type="date" name="art_start_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>

          <div>
            <label class="form-label">ARV Regimen Protocol *</label>
            <input type="text" name="current_regimen" class="form-control" value="TDF+3TC+DTG" list="regimenSuggestions" required>
            <datalist id="regimenSuggestions">
              <option value="TDF+3TC+DTG">TDF+3TC+DTG (Preferred 1st Line Adult)</option>
              <option value="ABC+3TC+DTG">ABC+3TC+DTG (Alternative 1st Line)</option>
              <option value="TDF+3TC+EFV">TDF+3TC+EFV (Alternative 1st Line)</option>
              <option value="AZT+3TC+ATV/r">AZT+3TC+ATV/r (2nd Line)</option>
              <option value="TDF+3TC+LPV/r">TDF+3TC+LPV/r (2nd Line)</option>
            </datalist>
          </div>

          <div>
            <label class="form-label">Treatment Line</label>
            <select name="line_of_treatment" class="form-control">
              <option value="first_line" selected>1st Line Regimen</option>
              <option value="second_line">2nd Line Regimen</option>
              <option value="third_line">3rd Line / Salvage</option>
            </select>
          </div>
        </div>

        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Baseline CD4 Count (cells/µL)</label>
            <input type="number" name="baseline_cd4" class="form-control" placeholder="e.g. 450">
          </div>

          <div>
            <label class="form-label">Baseline Viral Load (copies/mL)</label>
            <input type="number" name="baseline_viral_load" class="form-control" placeholder="e.g. 50 (or 0 if undetectable)">
          </div>

          <div>
            <label class="form-label">Initial ARV Refill (MMD)</label>
            <select name="months_dispensed" class="form-control">
              <option value="1">1 Month Supply</option>
              <option value="3" selected>3 Months (Standard MMD)</option>
              <option value="6">6 Months (Stable MMD)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-2" style="gap: 12px; margin-top: 10px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
            <input type="checkbox" name="pmtct_active" value="1">
            <strong style="color: #ec4899;">Pregnant / Lactating Mother (PMTCT Linkage)</strong>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
            <input type="checkbox" name="tb_screened_this_visit" value="1" checked>
            <span>TB Symptom Screening Conducted (Negative)</span>
          </label>
        </div>
      </div>

      <!-- Treatment Buddy / Supporter -->
      <div class="ccc-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; color: var(--brand); font-weight: 700;">3. Adherence Treatment Buddy / Supporter</h4>
        
        <div class="grid grid-2" style="gap: 12px;">
          <div>
            <label class="form-label">Treatment Supporter Full Name</label>
            <input type="text" name="treatment_buddy_name" class="form-control" placeholder="e.g. Sister, Spouse, Caregiver">
          </div>

          <div>
            <label class="form-label">Supporter Phone Number</label>
            <input type="text" name="treatment_buddy_phone" class="form-control" placeholder="e.g. +254 700 000 000">
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="closeEnrollCccModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="padding: 9px 24px;">Enroll Client in CCC</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEnrollCccModal() {
  document.getElementById('enrollCccModal').style.display = 'flex';
}
function closeEnrollCccModal() {
  document.getElementById('enrollCccModal').style.display = 'none';
}

function filterCccCandidateSelect(query) {
  const q = query.toLowerCase().trim();
  const select = document.getElementById('cccPatientSelect');
  if (!select) return;
  const options = select.options;
  for (let i = 0; i < options.length; i++) {
    const text = options[i].text.toLowerCase();
    if (!q || text.indexOf(q) !== -1 || options[i].value === '') {
      options[i].style.display = '';
    } else {
      options[i].style.display = 'none';
    }
  }
}

// Dual Scrollbar Sync
document.addEventListener('DOMContentLoaded', function() {
  const topScroll = document.getElementById('cccTopScroll');
  const topScrollInner = document.getElementById('cccTopScrollInner');
  const tableWrapper = document.getElementById('cccTableWrapper');

  if (topScroll && topScrollInner && tableWrapper) {
    function syncWidth() {
      topScrollInner.style.width = tableWrapper.scrollWidth + 'px';
    }
    syncWidth();
    window.addEventListener('resize', syncWidth);

    topScroll.addEventListener('scroll', function() {
      tableWrapper.scrollLeft = topScroll.scrollLeft;
    });

    tableWrapper.addEventListener('scroll', function() {
      topScroll.scrollLeft = tableWrapper.scrollLeft;
    });
  }
});
</script>
