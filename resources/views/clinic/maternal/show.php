<?php $this->layout('layouts.app', ['title' => $title ?? 'Antenatal Care Record']) ?>

<style>
.badge-danger { background: #ef4444; color: #fff; }
.badge-warning { background: #f59e0b; color: #fff; }
.badge-success { background: #10b981; color: #fff; }
.badge-info { background: #3b82f6; color: #fff; }

.tab-btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  background: transparent;
  border: 1px solid transparent;
  color: var(--muted);
  transition: all 0.2s ease;
}
.tab-btn:hover {
  background: rgba(0,0,0,0.04);
  color: var(--text);
}
.tab-btn.active {
  background: var(--brand);
  color: #fff;
}

.danger-tag {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  background: rgba(239, 68, 68, 0.15);
  color: #dc2626;
  margin: 2px;
}

/* Maternal Clinic 100% Opaque Modal System (Zero bleed-through) */
.maternal-modal-backdrop {
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

.maternal-modal-content {
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

[data-theme="light"] .maternal-modal-content {
  background: #ffffff !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  border-color: rgba(15, 23, 42, 0.15);
  box-shadow: 0 25px 65px -15px rgba(15, 23, 42, 0.35);
}

.maternal-modal-section {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.03);
}

[data-theme="light"] .maternal-modal-section {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb">
      <a href="<?= route('clinic.maternal.index') ?>">Maternal Health (ANC)</a> /
      <?= e($profile['first_name'] . ' ' . $profile['last_name']) ?>
    </div>
    <h1><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?> &bull; ANC Longitudinal Record</h1>
    <p class="muted">
      ANC # <?= e($profile['anc_number']) ?> &bull; MRN: <?= e($profile['mrn']) ?> &bull;
      Enrolled since <?= date('d M Y', strtotime($profile['created_at'])) ?>
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openRecordVisitModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Log ANC Visit</span>
    </button>
    <a href="<?= route('clinic.maternal.card', ['id' => $profile['uuid'] ?? $profile['id']]) ?>" class="btn btn-ghost" target="_blank">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Booklet</span>
    </a>
    <button type="button" class="btn btn-ghost" onclick="openEditProfileModal()">Edit Baseline</button>
    <button type="button" class="btn btn-ghost" onclick="openBirthPlanModal()">Birth Plan (BPCR)</button>
    <?php if ($profile['status'] === 'active'): ?>
      <button type="button" class="btn btn-ghost" style="color: #7c3aed;" onclick="openConcludeModal()">Conclude Delivery</button>
    <?php endif; ?>
    <a href="<?= route('patients.show', ['id' => $profile['patient_uuid'] ?? $profile['patient_id']]) ?>" class="btn btn-ghost">Patient Chart</a>
  </div>
</div>

<!-- High Risk Alert Banner (If Applicable) -->
<?php if ($profile['high_risk_pregnancy']): ?>
  <div style="background: rgba(239, 68, 68, 0.1); border-left: 5px solid #ef4444; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span style="font-size: 24px;">⚠️</span>
      <div>
        <div style="font-weight: 800; color: #dc2626; font-size: 14px;">HIGH-RISK PREGNANCY ALERT</div>
        <div style="font-size: 12px; color: var(--text);">
          <?= e($profile['risk_factors'] ?: 'Mother requires specialized obstetric surveillance, frequent blood pressure checks, and hospital-based delivery.') ?>
        </div>
      </div>
    </div>
    <span class="badge badge-danger">High Risk</span>
  </div>
<?php endif; ?>

<!-- Top Profile & Pregnancy Header Card -->
<div class="card" style="padding: 22px; margin-bottom: 24px;">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
    <!-- Column 1: Gestational Timeline -->
    <div style="border-right: 1px solid var(--border); padding-right: 12px;">
      <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">CURRENT GESTATION</div>
      <div style="font-size: 22px; font-weight: 800; color: var(--brand); margin-top: 4px;">
        <?= e($profile['gestational_age']) ?>
      </div>
      <div style="margin-top: 6px;">
        <?php
          $tb = match($profile['current_trimester']) {
            'first'  => '<span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb; font-weight: 700;">1st Trimester (1-13 wks)</span>',
            'second' => '<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-weight: 700;">2nd Trimester (14-27 wks)</span>',
            default  => '<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #059669; font-weight: 700;">3rd Trimester (28-40+ wks)</span>',
          };
          echo $tb;
        ?>
      </div>
      <div class="muted" style="font-size: 11px; margin-top: 8px;">
        <strong>LNMP:</strong> <?= date('d M Y', strtotime($profile['lnmp'])) ?><br>
        <strong>EDD:</strong> <?= date('d M Y', strtotime($profile['edd'])) ?>
        (<?= $profile['days_to_edd'] > 0 ? "in {$profile['days_to_edd']} days" : 'Due' ?>)
      </div>
    </div>

    <!-- Column 2: Obstetric Parity -->
    <div style="border-right: 1px solid var(--border); padding-right: 12px;">
      <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">OBSTETRIC HISTORY</div>
      <div style="font-size: 20px; font-weight: 800; margin-top: 4px;">
        G<?= $profile['gravida'] ?> P<?= $profile['parity'] ?>
      </div>
      <div style="font-size: 12px; margin-top: 6px; line-height: 1.6;">
        <span>Living Children: <strong><?= $profile['living_children'] ?></strong></span><br>
        <span>Previous C-Sections: <strong><?= $profile['c_sections'] ?></strong></span><br>
        <span>Miscarriages / Stillbirths: <strong><?= $profile['miscarriages'] ?> / <?= $profile['stillbirths'] ?></strong></span>
      </div>
    </div>

    <!-- Column 3: Baseline Labs & Blood Group -->
    <div style="border-right: 1px solid var(--border); padding-right: 12px;">
      <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">BASELINE CLINICAL LABS</div>
      <div style="margin-top: 6px; font-size: 13px; line-height: 1.6;">
        <div>Blood Group & Rh: <strong><?= e($profile['blood_group'] ?? '-') ?> <?= e($profile['rhesus_factor'] ? ucfirst($profile['rhesus_factor']) : '-') ?></strong></div>
        <div>Baseline Hb: <strong><?= $profile['baseline_haemoglobin_g_dl'] ? $profile['baseline_haemoglobin_g_dl'] . ' g/dL' : 'Not recorded' ?></strong></div>
        <div>Syphilis (VDRL): <strong><?= e($profile['syphilis_vdrl_status'] ?? 'Not tested') ?></strong></div>
        <div>HIV / PMTCT: <strong><?= e($profile['hiv_status'] ?? 'Not tested') ?></strong></div>
        <div>Hepatitis B: <strong><?= e($profile['hepatitis_b_status'] ?? 'Not tested') ?></strong></div>
      </div>
    </div>

    <!-- Column 4: Preventive Care Summary -->
    <div>
      <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">PREVENTIVE INTERVENTIONS</div>
      <div style="margin-top: 6px; font-size: 12px; line-height: 1.7;">
        <div>💊 <strong>IFAS Tablets:</strong> <?= (int)$totalIfas ?> tablets dispensed</div>
        <div>🦟 <strong>Malaria IPTp-SP:</strong> <?= count($iptpDoses) > 0 ? implode(', ', $iptpDoses) : 'None given yet' ?></div>
        <div>💉 <strong>Tetanus Toxoid (TT):</strong> <?= count($ttDoses) > 0 ? implode(', ', $ttDoses) : 'Pending' ?></div>
        <div>🪱 <strong>Deworming:</strong> <?= $dewormed ? '✓ Completed' : 'Pending' ?></div>
        <div>🛏️ <strong>ITN Bed Net:</strong> <?= $netGiven ? '✓ Issued' : 'Pending' ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Trimester Navigation Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
  <button type="button" class="tab-btn active" onclick="switchTrimesterTab('all')">
    All Longitudinal Visits (<?= count($visits) ?>)
  </button>
  <button type="button" class="tab-btn" onclick="switchTrimesterTab('first')">
    1st Trimester (<?= count($visitsByTrimester['first']) ?>)
  </button>
  <button type="button" class="tab-btn" onclick="switchTrimesterTab('second')">
    2nd Trimester (<?= count($visitsByTrimester['second']) ?>)
  </button>
  <button type="button" class="tab-btn" onclick="switchTrimesterTab('third')">
    3rd Trimester (<?= count($visitsByTrimester['third']) ?>)
  </button>
</div>

<!-- Longitudinal Visits Record Table -->
<div class="card" style="margin-bottom: 24px;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0; font-size: 16px; font-weight: 700;" id="visitsTableTitle">Longitudinal Antenatal Visit History (1st Trimester to Present)</h3>
      <p class="muted" style="margin: 4px 0 0; font-size: 12px;">Detailed tracking of gestational age, maternal vitals, fundal height, FHR, labs, and danger signs</p>
    </div>
    <button type="button" class="btn btn-sm btn-primary" onclick="openRecordVisitModal()">+ Add Visit Record</button>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="width: 100%; font-size: 12px;">
      <thead>
        <tr>
          <th>Date & Visit #</th>
          <th>Gest. Age</th>
          <th>Trimester</th>
          <th>BP (mmHg)</th>
          <th>Weight & Gain</th>
          <th>Fundal Ht</th>
          <th>FHR & Presentation</th>
          <th>Urine & Hb</th>
          <th>Preventive Care</th>
          <th>Danger Signs</th>
          <th>Clinician</th>
        </tr>
      </thead>
      <tbody id="visitsTableBody">
        <?php if (empty($visits)): ?>
          <tr>
            <td colspan="11" style="text-align: center; padding: 40px;" class="muted">
              <div style="font-size: 28px; margin-bottom: 6px;">🩺</div>
              <div style="font-weight: 700;">No ANC visits recorded yet</div>
              <div style="font-size: 12px; margin-top: 4px;">Click "+ Add Visit Record" above to record the 1st trimester initial contact.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($visits as $v): ?>
            <?php
              $isHTN = ($v['systolic_bp'] >= 140 || $v['diastolic_bp'] >= 90);
              $hasProtein = in_array($v['urine_protein'], ['1_plus', '2_plus', '3_plus', '1+', '2+', '3+', '4+'], true);
              $dangerList = !empty($v['danger_signs_screened']) ? array_filter(array_map('trim', explode(',', $v['danger_signs_screened']))) : [];
            ?>
            <tr class="visit-row" data-trimester="<?= e($v['trimester']) ?>">
              <td>
                <div style="font-weight: 700;"><?= date('d/m/Y', strtotime($v['visit_date'])) ?></div>
                <div class="muted" style="font-size: 11px;">Visit #<?= $v['visit_number'] ?></div>
              </td>
              <td>
                <strong><?= $v['gestational_age_weeks'] ?> wks</strong>
              </td>
              <td>
                <?php
                  $tBadge = match($v['trimester']) {
                    'first'  => '<span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">1st Tri</span>',
                    'second' => '<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">2nd Tri</span>',
                    default  => '<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #059669;">3rd Tri</span>',
                  };
                  echo $tBadge;
                ?>
              </td>
              <td>
                <span style="font-weight: 700; color: <?= $isHTN ? '#ef4444' : 'inherit' ?>;">
                  <?= $v['systolic_bp'] ?>/<?= $v['diastolic_bp'] ?>
                </span>
                <?php if ($isHTN && $hasProtein): ?>
                  <span class="badge badge-danger" title="Elevated BP + Proteinuria: Suspect Pre-Eclampsia" style="font-size: 9px;">PRE-ECLAMPSIA ALERT</span>
                <?php elseif ($isHTN): ?>
                  <span class="badge badge-warning" style="font-size: 9px;">HTN</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= $v['weight_kg'] ?> kg</strong>
                <?php if ($v['weight_gain_kg'] !== null): ?>
                  <div class="muted" style="font-size: 10px;"><?= $v['weight_gain_kg'] >= 0 ? '+' : '' ?><?= $v['weight_gain_kg'] ?> kg</div>
                <?php endif; ?>
              </td>
              <td>
                <?= $v['fundal_height_cm'] ? $v['fundal_height_cm'] . ' cm' : '-' ?>
              </td>
              <td>
                <div>FHR: <strong><?= $v['fetal_heart_rate_bpm'] ? $v['fetal_heart_rate_bpm'] . ' bpm' : '-' ?></strong></div>
                <div class="muted" style="font-size: 11px; text-transform: capitalize;">
                  <?= e($v['fetal_presentation'] ?? '-') ?> &bull; <?= e($v['fetal_movement'] ?? '-') ?>
                </div>
              </td>
              <td>
                <?php
                  $protStr = match($v['urine_protein']) {
                    '1_plus', '1+' => '1+',
                    '2_plus', '2+' => '2+',
                    '3_plus', '3+' => '3+',
                    'trace'        => 'Trace',
                    default        => 'Nil',
                  };
                ?>
                <div>U-Prot: <strong style="color: <?= $hasProtein ? '#ef4444' : 'inherit' ?>;"><?= $protStr ?></strong></div>
                <div class="muted" style="font-size: 10px;">
                  Hb: <?= $v['haemoglobin_g_dl'] ? $v['haemoglobin_g_dl'] . 'g/dL' : '-' ?> &bull; U-Gluc: <?= e($v['urine_glucose'] ?? '-') ?>
                </div>
              </td>
              <td>
                <?php if ($v['ifas_tablets_dispensed'] > 0): ?>
                  <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #059669; font-size: 10px;">IFAS +<?= $v['ifas_tablets_dispensed'] ?></span>
                <?php endif; ?>
                <?php if ($v['iptp_sp_dose'] && $v['iptp_sp_dose'] !== 'none'): ?>
                  <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #2563eb; font-size: 10px;">IPTp <?= strtoupper($v['iptp_sp_dose']) ?></span>
                <?php endif; ?>
                <?php if ($v['tetanus_toxoid_dose'] && $v['tetanus_toxoid_dose'] !== 'none'): ?>
                  <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: #7c3aed; font-size: 10px;">TT <?= strtoupper($v['tetanus_toxoid_dose']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($dangerList)): ?>
                  <?php foreach ($dangerList as $ds): ?>
                    <span class="danger-tag">⚠️ <?= e($ds) ?></span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="muted" style="font-size: 11px;">None</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="muted"><?= e($v['doctor_name'] ?? 'Attending Clinician') ?></span>
                <?php if ($v['next_visit_date']): ?>
                  <div class="muted" style="font-size: 10px;">Next: <?= date('d/m/y', strtotime($v['next_visit_date'])) ?></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Birth Preparedness & Complication Readiness (BPCR) Card -->
<div class="card" style="padding: 20px; margin-bottom: 24px; border-left: 4px solid #10b981;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
    <div>
      <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Birth Preparedness & Complication Readiness Plan (BPCR)</h3>
      <p class="muted" style="margin: 4px 0 0; font-size: 12px;">Individualized plan for safe delivery, emergency transport, and designated blood donor</p>
    </div>
    <button type="button" class="btn btn-sm btn-ghost" onclick="openBirthPlanModal()">Edit Plan</button>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; font-size: 13px;">
    <div>
      <span class="muted">Designated Delivery Facility:</span>
      <div style="font-weight: 700;"><?= e($profile['birth_plan_hospital'] ?: 'Veen Healthcare Maternity Wing') ?></div>
    </div>
    <div>
      <span class="muted">Emergency Transport:</span>
      <div style="font-weight: 700;">
        <?= e($profile['birth_plan_transport'] ?: 'Private / Ambulance on standby') ?>
      </div>
    </div>
    <div>
      <span class="muted">Designated Blood Donor:</span>
      <div style="font-weight: 700;">
        <?= e($profile['birth_plan_donor'] ?: 'Hospital Blood Bank') ?>
        <?php if (!empty($profile['birth_plan_contact_phone'])): ?>
          (<?= e($profile['birth_plan_contact_phone']) ?>)
        <?php endif; ?>
      </div>
    </div>
    <div>
      <span class="muted">Emergency Contact Phone:</span>
      <div style="font-weight: 700;"><?= e($profile['birth_plan_contact_phone'] ?: ($profile['phone'] ?: 'N/A')) ?></div>
    </div>
  </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Record ANC Visit Modal (100% Opaque) -->
<div id="recordVisitModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeRecordVisitModal()">
  <div class="maternal-modal-content" style="max-width: 820px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">🩺</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Log Antenatal Care (ANC) Visit</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Maternal vitals, obstetric measurements, pre-eclampsia screening, and preventive care</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeRecordVisitModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.maternal.visit.store', ['id' => $profile['uuid'] ?? $profile['id']]) ?>">
      <?= csrf_field() ?>

      <!-- Visit Date & Number -->
      <div class="maternal-modal-section" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
        <div class="form-group">
          <label class="label" style="font-weight: 700; font-size: 11.5px;">Visit Date *</label>
          <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" class="input" required onchange="calculateGestationAtVisit(this.value)" />
        </div>
        <div class="form-group">
          <label class="label" style="font-weight: 700; font-size: 11.5px;">Visit Number *</label>
          <input type="number" name="visit_number" value="<?= count($visits) + 1 ?>" min="1" class="input" required />
        </div>
        <div class="form-group">
          <label class="label" style="font-weight: 700; font-size: 11.5px;">Gestational Age (wks)</label>
          <input type="number" step="0.1" name="gestational_age_weeks" id="visit_gest_weeks" value="<?= $profile['gestational_weeks'] ?>" class="input" required />
        </div>
      </div>

      <!-- Maternal Vitals: BP, Weight, Temp, Pulse -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          1. Maternal Vitals & Pre-Eclampsia Screening
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Systolic BP (mmHg) *</label>
            <input type="number" name="systolic_bp" id="systolic_bp" class="input" placeholder="e.g. 120" required onchange="checkPreEclampsiaAlert()" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Diastolic BP (mmHg) *</label>
            <input type="number" name="diastolic_bp" id="diastolic_bp" class="input" placeholder="e.g. 80" required onchange="checkPreEclampsiaAlert()" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Weight (kg) *</label>
            <input type="number" step="0.1" name="weight_kg" class="input" placeholder="e.g. 64.5" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Pulse Rate (bpm)</label>
            <input type="number" name="pulse_bpm" class="input" placeholder="e.g. 78" />
          </div>
        </div>

        <div id="bpWarning" style="display: none; margin-top: 10px; padding: 8px 12px; background: rgba(239, 68, 68, 0.12); border-left: 3px solid #ef4444; border-radius: 6px; font-size: 11px; color: #dc2626; font-weight: 700;">
          ⚠️ BP ≥ 140/90 mmHg: High Blood Pressure detected! Screen for proteinuria and pre-eclampsia symptoms.
        </div>
      </div>

      <!-- Obstetric Examination: Fundal Height, Lie, Presentation, FHR -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          2. Obstetric & Fetal Examination
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Fundal Height (cm)</label>
            <input type="number" step="0.5" name="fundal_height_cm" class="input" placeholder="e.g. 26" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Fetal Heart Rate (bpm)</label>
            <input type="number" name="fetal_heart_rate_bpm" class="input" placeholder="120-160" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Presentation</label>
            <select name="fetal_presentation" class="input">
              <option value="cephalic">Cephalic (Vertex)</option>
              <option value="breech">Breech</option>
              <option value="transverse">Transverse</option>
              <option value="undetermined">Undetermined</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Fetal Movements</label>
            <select name="fetal_movement" class="input">
              <option value="present">Present (Normal)</option>
              <option value="reduced">Reduced</option>
              <option value="absent">Absent</option>
              <option value="not_felt_yet">Not Felt Yet (&lt;18 wks)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Lab Quick Tests: Urine Protein, Urine Glucose, Hb, Oedema -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          3. Urinalysis, Haemoglobin & Oedema
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Urine Protein / Albumin</label>
            <select name="urine_protein" class="input">
              <option value="nil">Nil / Negative</option>
              <option value="trace">Trace</option>
              <option value="1+">1+ (30 mg/dL)</option>
              <option value="2+">2+ (100 mg/dL)</option>
              <option value="3+">3+ (300 mg/dL)</option>
              <option value="4+">4+ (1000 mg/dL)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Urine Glucose</label>
            <select name="urine_glucose" class="input">
              <option value="nil">Nil / Negative</option>
              <option value="trace">Trace</option>
              <option value="1+">1+</option>
              <option value="2+">2+</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Haemoglobin (g/dL)</label>
            <input type="number" step="0.1" name="haemoglobin_g_dl" class="input" placeholder="e.g. 11.8" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Dependent Oedema</label>
            <select name="oedema" class="input">
              <option value="none">None</option>
              <option value="mild">Mild (Pedal +)</option>
              <option value="moderate">Moderate (Pre-tibial ++)</option>
              <option value="severe">Severe (Generalized +++)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Preventive Interventions -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          4. Preventive Interventions Dispensed
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">IFAS (Iron+Folate) Tablets</label>
            <input type="number" name="ifas_tablets_dispensed" value="30" min="0" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Malaria IPTp-SP Dose</label>
            <select name="iptp_sp_dose" class="input">
              <option value="none">None this visit</option>
              <option value="sp1">IPTp-1 (From 14 wks)</option>
              <option value="sp2">IPTp-2</option>
              <option value="sp3">IPTp-3</option>
              <option value="sp4">IPTp-4</option>
              <option value="sp5">IPTp-5</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Tetanus Toxoid (TT / Td) Dose</label>
            <select name="tetanus_toxoid_dose" class="input">
              <option value="none">None this visit</option>
              <option value="tt1">TT1 (First contact)</option>
              <option value="tt2">TT2 (+4 weeks)</option>
              <option value="tt3">TT3 (+6 months)</option>
              <option value="tt4">TT4 (+1 year)</option>
              <option value="tt5">TT5 (+1 year)</option>
            </select>
          </div>
        </div>
        <div style="display: flex; gap: 20px; margin-top: 12px; font-size: 12px; flex-wrap: wrap;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="deworming_given" value="1" />
            <span>Deworming Administered (Mebendazole / Albendazole)</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="mosquito_net_given" value="1" />
            <span>Insecticide-Treated Mosquito Net Issued</span>
          </label>
        </div>
      </div>

      <!-- Obstetric Danger Signs Screening -->
      <div style="border: 1px solid rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.05); border-radius: 12px; padding: 16px; margin-bottom: 18px;">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: #dc2626;">
          5. Obstetric Danger Signs Screening (Check if present)
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 12px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_severe_headache" value="1" />
            <span>Severe Persistent Headache</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_blurred_vision" value="1" />
            <span>Blurred / Disturbed Vision</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_epigastric_pain" value="1" />
            <span>Severe Epigastric Pain</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_vaginal_bleeding" value="1" />
            <span>Vaginal Bleeding</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_leaking_liquor" value="1" />
            <span>Drainage of Liquor / PROM</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="danger_decreased_fetal_movement" value="1" />
            <span>Reduced Fetal Movement</span>
          </label>
        </div>
      </div>

      <!-- Clinical Notes & Next Appointment -->
      <div class="maternal-modal-section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Clinical Notes / Treatment Plan</label>
          <input type="text" name="clinical_notes" placeholder="Enter findings, prescriptions, and instructions..." class="input" />
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Next Appointment Date</label>
          <input type="date" name="next_visit_date" class="input" min="<?= date('Y-m-d') ?>" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
        <button type="button" class="btn btn-ghost" onclick="closeRecordVisitModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save ANC Visit Record</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Edit Baseline Profile Modal (100% Opaque) -->
<div id="editProfileModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeEditProfileModal()">
  <div class="maternal-modal-content" style="max-width: 640px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">📋</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Edit Baseline Maternal Record</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Update pregnancy milestones, obstetric history, and risk factors</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditProfileModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.maternal.profile.update', ['id' => $profile['uuid'] ?? $profile['id']]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div class="form-group">
          <label class="label" style="font-size: 11px; font-weight: 700;">LNMP *</label>
          <input type="date" name="lnmp" value="<?= $profile['lnmp'] ?>" class="input" required />
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px; font-weight: 700;">EDD (Estimated Delivery Date)</label>
          <input type="date" name="edd" value="<?= $profile['edd'] ?>" class="input" />
        </div>
      </div>

      <div class="maternal-modal-section" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Gravida</label>
          <input type="number" name="gravida" value="<?= $profile['gravida'] ?>" min="1" class="input" required />
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Parity</label>
          <input type="number" name="parity" value="<?= $profile['parity'] ?>" min="0" class="input" required />
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Living Children</label>
          <input type="number" name="living_children" value="<?= $profile['living_children'] ?>" min="0" class="input" />
        </div>
      </div>

      <div class="maternal-modal-section" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Blood Group</label>
          <select name="blood_group" class="input">
            <option value="">--</option>
            <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
              <option value="<?= $bg ?>" <?= ($profile['blood_group'] === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Rhesus Factor</label>
          <select name="rhesus_factor" class="input">
            <option value="positive" <?= ($profile['rhesus_factor'] === 'positive') ? 'selected' : '' ?>>Rh +</option>
            <option value="negative" <?= ($profile['rhesus_factor'] === 'negative') ? 'selected' : '' ?>>Rh -</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">Baseline Hb (g/dL)</label>
          <input type="number" step="0.1" name="baseline_haemoglobin_g_dl" value="<?= $profile['baseline_haemoglobin_g_dl'] ?>" class="input" />
        </div>
      </div>

      <div style="margin-bottom: 16px; padding: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px;">
        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; cursor: pointer; color: #dc2626;">
          <input type="checkbox" name="high_risk_pregnancy" value="1" <?= $profile['high_risk_pregnancy'] ? 'checked' : '' ?> />
          <span>High-Risk Pregnancy Flag</span>
        </label>
        <div style="margin-top: 8px;">
          <input type="text" name="risk_factors" value="<?= e($profile['risk_factors']) ?>" placeholder="Risk factors and warnings..." class="input" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closeEditProfileModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Edit Birth Preparedness Plan Modal (100% Opaque) -->
<div id="birthPlanModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeBirthPlanModal()">
  <div class="maternal-modal-content" style="max-width: 640px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">🎯</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Birth Preparedness & Complication Readiness Plan</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">BPCR plan: delivery facility, transport, designated blood donor, and postnatal counseling</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeBirthPlanModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.maternal.birth_plan.update', ['id' => $profile['uuid'] ?? $profile['id']]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section">
        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Chosen Delivery Facility</label>
          <input type="text" name="birth_plan_hospital" value="<?= e($profile['birth_plan_hospital'] ?? $profile['chosen_delivery_facility'] ?? '') ?>" class="input" placeholder="e.g. Veen Healthcare Maternity Wing" />
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Emergency Transport Arrangement</label>
          <input type="text" name="birth_plan_transport" value="<?= e($profile['birth_plan_transport'] ?? $profile['transport_arrangement'] ?? '') ?>" class="input" placeholder="e.g. Facility ambulance / Taxi contact: 07..." />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Designated Blood Donor Name</label>
            <input type="text" name="birth_plan_donor" value="<?= e($profile['birth_plan_donor'] ?? $profile['designated_blood_donor_name'] ?? '') ?>" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Donor Phone #</label>
            <input type="text" name="birth_plan_contact_phone" value="<?= e($profile['birth_plan_contact_phone'] ?? $profile['designated_blood_donor_phone'] ?? '') ?>" class="input" />
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Postpartum Family Planning Method Chosen</label>
          <input type="text" name="postpartum_fp_counseling" value="<?= e($profile['postpartum_fp_counseling']) ?>" class="input" placeholder="e.g. PPIUD / Implants / DMPA / Exclusive Breastfeeding" />
        </div>

        <div style="margin-top: 8px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px;">
            <input type="checkbox" name="emergency_funds_arranged" value="1" <?= $profile['emergency_funds_arranged'] ? 'checked' : '' ?> />
            <span>Emergency delivery funds verified and set aside</span>
          </label>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closeBirthPlanModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- 4. Conclude Delivery Modal (100% Opaque) -->
<div id="concludeDeliveryModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeConcludeModal()">
  <div class="maternal-modal-content" style="max-width: 540px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">👶</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Conclude Pregnancy Episode</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Record delivery date, outcome, and newborn clinical notes</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeConcludeModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.maternal.delivery.conclude', ['id' => $profile['uuid'] ?? $profile['id']]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section">
        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Delivery / Conclusion Date *</label>
          <input type="date" name="delivery_date" value="<?= date('Y-m-d') ?>" class="input" required />
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Outcome Status *</label>
          <select name="status" class="input" required>
            <option value="delivered">Successfully Delivered (Live Birth)</option>
            <option value="referred">Referred to Tertiary Care</option>
            <option value="miscarriage">Pregnancy Loss / Miscarriage</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 8px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">Delivery Outcome Notes / Newborn details</label>
          <textarea name="delivery_notes" rows="3" class="input" placeholder="e.g. SVD delivered live male infant, birth weight 3.2 kg, APGAR 9/10, mother and baby well."></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closeConcludeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Mark as Concluded</button>
      </div>
    </form>
  </div>
</div>

<script>
var lnmpStr = "<?= $profile['lnmp'] ?>";

function switchTrimesterTab(trimester) {
  var buttons = document.querySelectorAll('.tab-btn');
  buttons.forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');

  var rows = document.querySelectorAll('.visit-row');
  var title = document.getElementById('visitsTableTitle');

  if (trimester === 'all') {
    rows.forEach(r => r.style.display = '');
    title.textContent = 'Longitudinal Antenatal Visit History (All Trimesters)';
  } else if (trimester === 'first') {
    rows.forEach(r => r.style.display = r.getAttribute('data-trimester') === 'first' ? '' : 'none');
    title.textContent = '1st Trimester Visits (1 to 13 Weeks)';
  } else if (trimester === 'second') {
    rows.forEach(r => r.style.display = r.getAttribute('data-trimester') === 'second' ? '' : 'none');
    title.textContent = '2nd Trimester Visits (14 to 27 Weeks)';
  } else if (trimester === 'third') {
    rows.forEach(r => r.style.display = r.getAttribute('data-trimester') === 'third' ? '' : 'none');
    title.textContent = '3rd Trimester Visits (28 to 40+ Weeks)';
  }
}

function openRecordVisitModal() {
  var m = document.getElementById('recordVisitModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeRecordVisitModal() {
  var m = document.getElementById('recordVisitModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function openEditProfileModal() {
  var m = document.getElementById('editProfileModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeEditProfileModal() {
  var m = document.getElementById('editProfileModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function openBirthPlanModal() {
  var m = document.getElementById('birthPlanModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeBirthPlanModal() {
  var m = document.getElementById('birthPlanModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function openConcludeModal() {
  var m = document.getElementById('concludeDeliveryModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeConcludeModal() {
  var m = document.getElementById('concludeDeliveryModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeRecordVisitModal();
    closeEditProfileModal();
    closeBirthPlanModal();
    closeConcludeModal();
  }
});

function checkPreEclampsiaAlert() {
  var sys = parseInt(document.getElementById('systolic_bp').value || 0, 10);
  var dia = parseInt(document.getElementById('diastolic_bp').value || 0, 10);
  var warn = document.getElementById('bpWarning');
  if (warn) {
    if (sys >= 140 || dia >= 90) {
      warn.style.display = 'block';
    } else {
      warn.style.display = 'none';
    }
  }
}

function calculateGestationAtVisit(visitDateVal) {
  if (!visitDateVal || !lnmpStr) return;
  var dVisit = new Date(visitDateVal);
  var dLnmp = new Date(lnmpStr);
  var diff = Math.floor((dVisit.getTime() - dLnmp.getTime()) / 86400000);
  if (diff >= 0) {
    var wks = (diff / 7).toFixed(1);
    var el = document.getElementById('visit_gest_weeks');
    if (el) el.value = wks;
  }
}
</script>
