<?php $this->layout('layouts.app', ['title' => $title]) ?>

<style>
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
  box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.85);
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
    <div class="breadcrumb">
      <a href="<?= route('dashboard') ?>">Dashboard</a> / 
      <a href="<?= route('clinic.ccc.index') ?>">HIV Clinic (CCC)</a> / 
      <?= e($profile->ccc_number) ?>
    </div>
    <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
      <h1 style="margin: 0;"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> &mdash; <?= e($profile->ccc_number) ?></h1>
      <?php if ($profile->pmtct_active): ?>
        <span class="badge badge-primary" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">🤱 PMTCT Active</span>
      <?php endif; ?>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openVisitModal()">
      <span>+ Record Encounter / ARV Refill</span>
    </button>
    <a href="<?= route('clinic.ccc.card', ['id' => $profile->uuid ?? $profile->id]) ?>" class="btn btn-secondary">
      <span>🪪 Print CCC Passport</span>
    </a>
  </div>
</div>

<!-- Key Vitals & CCC Metrics -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid <?= $profile->viral_load_suppressed ? '#10b981' : '#ef4444' ?>;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Viral Load</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: <?= $profile->viral_load_suppressed ? '#10b981' : '#ef4444' ?>; margin-top: 4px;">
      <?= $profile->current_viral_load !== null ? number_format($profile->current_viral_load) . ' c/mL' : 'Pending' ?>
    </div>
    <div style="margin-top: 6px;">
      <?php if ($profile->viral_load_suppressed): ?>
        <span class="badge badge-success">✓ Suppressed (&lt;1,000 c/mL)</span>
      <?php else: ?>
        <span class="badge badge-danger">⚠️ Unsuppressed / High VL</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #3b82f6;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Immunological CD4</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #3b82f6; margin-top: 4px;">
      <?= $profile->current_cd4 ? number_format($profile->current_cd4) : 'Pending' ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Baseline: <?= $profile->baseline_cd4 ? number_format($profile->baseline_cd4) . ' cells/µL' : 'N/A' ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #8b5cf6;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Current ARV Regimen</div>
    <div class="mono" style="font-size: 20px; font-weight: 800; color: #8b5cf6; margin-top: 4px;">
      <?= e($profile->current_regimen) ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      <?= ucwords(str_replace('_', ' ', e($profile->line_of_treatment))) ?> &bull; <?= strtoupper(str_replace('_', ' ', e($profile->current_who_stage))) ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid var(--brand);">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Client Retention</div>
    <div style="font-size: 22px; font-weight: 800; color: var(--brand); margin-top: 4px;">
      <?= strtoupper(str_replace('_', ' ', e($profile->status))) ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Enrolled: <?= date('d M Y', strtotime($profile->enrollment_date)) ?>
    </div>
  </div>
</div>

<div class="grid grid-3" style="gap: 24px; margin-bottom: 24px;">
  <!-- Demographics & Clinical Profile Summary -->
  <div class="card card-pad">
    <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 14px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      Patient Demographics
    </h3>

    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Full Name</div>
        <div style="font-weight: 700; font-size: 15px; color: var(--text);">
          <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?>
        </div>
      </div>

      <div class="grid grid-2" style="gap: 10px;">
        <div>
          <div class="dim" style="font-size: 11px; text-transform: uppercase;">MRN</div>
          <div class="mono" style="font-weight: 700;"><?= e($patient['mrn']) ?></div>
        </div>
        <div>
          <div class="dim" style="font-size: 11px; text-transform: uppercase;">Sex / Age</div>
          <div style="font-weight: 600;"><?= ucfirst(e($patient['sex'])) ?> / <?= $patient['dob'] ? age_from_dob($patient['dob']) . ' yrs' : 'N/A' ?></div>
        </div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Phone Number</div>
        <div style="font-weight: 600;"><?= e($patient['phone'] ?: 'No Phone Recorded') ?></div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Entry Point</div>
        <div style="font-weight: 600;"><?= strtoupper(str_replace('_', ' ', e($profile->entry_point))) ?></div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">ART Start Date</div>
        <div style="font-weight: 600;"><?= $profile->art_start_date ? date('d M Y', strtotime($profile->art_start_date)) : 'N/A' ?></div>
      </div>

      <?php if (!empty($profile->treatment_buddy_name)): ?>
        <div style="border-top: 1px solid var(--border); padding-top: 8px; margin-top: 4px;">
          <div class="dim" style="font-size: 11px; text-transform: uppercase; color: var(--brand);">Treatment Supporter (Buddy)</div>
          <div style="font-weight: 700;"><?= e($profile->treatment_buddy_name) ?></div>
          <div class="muted" style="font-size: 12px;"><?= e($profile->treatment_buddy_phone ?: 'No phone provided') ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Longitudinal Encounters & Refills Table -->
  <div class="card card-pad" style="grid-column: span 2;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      <h3 style="font-size: 14px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
        Clinical Encounters & ARV Refill History (<?= count($visits) ?>)
      </h3>
      <button type="button" class="btn btn-sm btn-primary" onclick="openVisitModal()">+ New Visit</button>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" style="font-size: 12.5px;">
        <thead>
          <tr>
            <th>Visit Date</th>
            <th>Weight & BMI</th>
            <th>WHO Stage</th>
            <th>Adherence %</th>
            <th>Regimen & MMD</th>
            <th>Viral Load</th>
            <th>CD4</th>
            <th>Next Refill</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($visits)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 24px; color: var(--text-muted);">
                No clinical encounters recorded yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($visits as $v): ?>
              <tr>
                <td>
                  <strong style="color: var(--text);"><?= date('d M Y', strtotime($v['visit_date'])) ?></strong>
                  <div class="muted" style="font-size: 11px;">Dr. <?= e($v['clinician_name'] ?? 'Clinician') ?></div>
                </td>
                <td>
                  <div><?= $v['weight_kg'] ? e($v['weight_kg']) . ' kg' : 'N/A' ?></div>
                  <div class="muted" style="font-size: 11px;"><?= $v['bmi'] ? 'BMI: ' . e($v['bmi']) : '' ?></div>
                </td>
                <td>
                  <span class="badge badge-secondary"><?= strtoupper(str_replace('_', ' ', e($v['who_stage']))) ?></span>
                </td>
                <td>
                  <?php if ($v['adherence_pill_count_percent'] !== null): ?>
                    <strong style="color: <?= $v['adherence_pill_count_percent'] >= 95 ? '#10b981' : '#f59e0b' ?>;">
                      <?= e($v['adherence_pill_count_percent']) ?>%
                    </strong>
                  <?php else: ?>
                    <span class="muted">--</span>
                  <?php endif; ?>
                </td>
                <td>
                  <strong class="mono" style="color: #8b5cf6;"><?= e($v['regimen_dispensed']) ?></strong>
                  <div class="muted" style="font-size: 11px;"><?= e($v['months_dispensed']) ?> Mo. Supply</div>
                </td>
                <td>
                  <?php if ($v['viral_load_copies_ml'] !== null): ?>
                    <strong class="mono" style="color: <?= $v['viral_load_copies_ml'] < 1000 ? '#10b981' : '#ef4444' ?>;">
                      <?= number_format($v['viral_load_copies_ml']) ?> c/mL
                    </strong>
                  <?php else: ?>
                    <span class="muted">--</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= $v['cd4_count'] ? number_format($v['cd4_count']) : '--' ?>
                </td>
                <td>
                  <?php if (!empty($v['next_appointment_date'])): ?>
                    <strong class="mono"><?= date('d M Y', strtotime($v['next_appointment_date'])) ?></strong>
                  <?php else: ?>
                    <span class="muted">N/A</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if (!empty($v['clinical_notes'])): ?>
                <tr>
                  <td colspan="8" style="background: var(--surface-2); font-size: 11.5px; padding: 6px 12px; color: var(--text-muted); font-style: italic;">
                    Notes: <?= e($v['clinical_notes']) ?>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 100% OPAQUE FOLLOW-UP VISIT MODAL          -->
<!-- ========================================== -->
<div id="visitModal" class="ccc-modal-backdrop">
  <div class="ccc-modal-content" style="max-width: 780px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 18px;">
      <div>
        <h3 style="margin: 0; font-size: 17px; font-weight: 800;">Record Follow-up Encounter & ARV Refill</h3>
        <div class="muted" style="font-size: 12px; margin-top: 2px;">Client: <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> (<?= e($profile->ccc_number) ?>)</div>
      </div>
      <button type="button" class="btn btn-ghost" onclick="closeVisitModal()">&times;</button>
    </div>

    <form method="POST" action="<?= route('clinic.ccc.visit.store', ['id' => $profile->uuid ?? $profile->id]) ?>">
      <?= csrf_field() ?>

      <div class="ccc-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 10px; color: var(--brand); font-weight: 700;">1. Encounter Vitals & Staging</h4>
        
        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Visit Date *</label>
            <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div>
            <label class="form-label">Weight (kg)</label>
            <input type="number" step="0.1" name="weight_kg" class="form-control" placeholder="e.g. 64.5">
          </div>

          <div>
            <label class="form-label">Height (cm)</label>
            <input type="number" step="0.1" name="height_cm" class="form-control" placeholder="e.g. 170.0">
          </div>
        </div>

        <div class="grid grid-3" style="gap: 12px;">
          <div>
            <label class="form-label">WHO Clinical Stage</label>
            <select name="who_stage" class="form-control">
              <option value="stage_1" <?= $profile->current_who_stage === 'stage_1' ? 'selected' : '' ?>>Stage 1</option>
              <option value="stage_2" <?= $profile->current_who_stage === 'stage_2' ? 'selected' : '' ?>>Stage 2</option>
              <option value="stage_3" <?= $profile->current_who_stage === 'stage_3' ? 'selected' : '' ?>>Stage 3</option>
              <option value="stage_4" <?= $profile->current_who_stage === 'stage_4' ? 'selected' : '' ?>>Stage 4</option>
            </select>
          </div>

          <div>
            <label class="form-label">Pill Count Adherence (%)</label>
            <input type="number" step="0.1" name="adherence_pill_count_percent" class="form-control" value="98.0" placeholder="e.g. 98.0">
          </div>

          <div style="display: flex; align-items: flex-end; padding-bottom: 6px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" name="tb_screened_this_visit" value="1" checked>
              <span>TB Symptom Screened (Neg)</span>
            </label>
          </div>
        </div>
      </div>

      <div class="ccc-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 10px; color: var(--brand); font-weight: 700;">2. Laboratory Markers & ARV Multi-Month Refill</h4>
        
        <div class="grid grid-2" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Viral Load Test Result (copies/mL)</label>
            <input type="number" name="viral_load_copies_ml" class="form-control" placeholder="Leave blank if not drawn this visit">
          </div>

          <div>
            <label class="form-label">CD4 Count (cells/µL)</label>
            <input type="number" name="cd4_count" class="form-control" placeholder="Leave blank if not drawn this visit">
          </div>
        </div>

        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Regimen Dispensed *</label>
            <input type="text" name="regimen_dispensed" class="form-control" value="<?= e($profile->current_regimen) ?>" required>
          </div>

          <div>
            <label class="form-label">Multi-Month Dispensing (MMD)</label>
            <select name="months_dispensed" class="form-control" onchange="autoCalculateNextAppt(this.value)">
              <option value="1">1 Month Supply</option>
              <option value="3" selected>3 Months Supply (Standard)</option>
              <option value="6">6 Months Supply (Fast Track MMD)</option>
            </select>
          </div>

          <div>
            <label class="form-label">Next Refill Appointment *</label>
            <input type="date" name="next_appointment_date" id="nextApptDateInput" class="form-control" value="<?= date('Y-m-d', strtotime('+3 months')) ?>" required>
          </div>
        </div>

        <div>
          <label class="form-label">Clinical Encounter Notes</label>
          <textarea name="clinical_notes" class="form-control" rows="2" placeholder="Document any side effects, adherence challenges, or clinical findings..."></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
        <button type="button" class="btn btn-secondary" onclick="closeVisitModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Visit & Refill</button>
      </div>
    </form>
  </div>
</div>

<script>
function openVisitModal() {
  document.getElementById('visitModal').style.display = 'flex';
}
function closeVisitModal() {
  document.getElementById('visitModal').style.display = 'none';
}

function autoCalculateNextAppt(months) {
  const m = parseInt(months, 10) || 3;
  const d = new Date();
  d.setMonth(d.getMonth() + m);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  document.getElementById('nextApptDateInput').value = `${yyyy}-${mm}-${dd}`;
}
</script>
