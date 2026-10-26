<?php $this->layout('layouts.app', ['title' => $title ?? 'Maternal Antenatal Health Booklet']) ?>

<style>
@media print {
  body * { visibility: hidden; }
  #maternalCardArea, #maternalCardArea * { visibility: visible; }
  #maternalCardArea {
    position: absolute; left: 0; top: 0; width: 100%;
    margin: 0; padding: 15px;
    box-shadow: none !important;
    border: none !important;
    background: #ffffff !important;
    color: #000000 !important;
  }
  .no-print { display: none !important; }
}

.maternal-card-container {
  max-width: 960px;
  margin: 0 auto;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  overflow: hidden;
}

.maternal-header {
  background: linear-gradient(135deg, #059669, #0284c7);
  color: #fff;
  padding: 24px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.maternal-body {
  padding: 28px;
}

.section-head {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--brand);
  margin-top: 24px;
  margin-bottom: 12px;
  border-bottom: 2px solid var(--border);
  padding-bottom: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb">
      <a href="<?= route('clinic.maternal.index') ?>">Maternal Health</a> /
      <a href="<?= route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]) ?>"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></a> /
      Booklet
    </div>
    <h1>Maternal Health & Antenatal Care Booklet</h1>
    <p class="muted">Official Ministry of Health / Facility Antenatal Care longitudinal record booklet.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary" onclick="window.print()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Booklet</span>
    </button>
    <a href="<?= route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]) ?>" class="btn btn-ghost">Back to ANC Chart</a>
  </div>
</div>

<div class="maternal-card-container" id="maternalCardArea">
  <!-- Header -->
  <div class="maternal-header">
    <div>
      <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; opacity: 0.9;">MINISTRY OF HEALTH & REPRODUCTIVE HEALTH</div>
      <h2 style="margin: 4px 0 0; font-size: 24px; font-weight: 800;">MATERNAL ANTENATAL CARE (ANC) BOOKLET</h2>
      <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">Longitudinal Tracking From First Trimester to Delivery</div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.85;">ANC NO.</div>
      <div style="font-size: 20px; font-weight: 800; font-family: monospace;"><?= e($profile->anc_number) ?></div>
      <div style="font-size: 11px; opacity: 0.85;">MRN: <?= e($patient['mrn']) ?></div>
    </div>
  </div>

  <div class="maternal-body">
    <!-- Mother Demographics & Obstetric Summary -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; background: rgba(0,0,0,0.03); padding: 16px; border-radius: 10px; border: 1px solid var(--border);">
      <div>
        <div class="muted" style="font-size: 11px;">MOTHER'S FULL NAME</div>
        <div style="font-weight: 700; font-size: 16px;"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">AGE & PHONE</div>
        <div style="font-weight: 600;">
          <?= $patient['dob'] ? (date('Y') - date('Y', strtotime($patient['dob']))) . ' years' : 'Adult' ?> &bull; <?= e($patient['phone'] ?: 'N/A') ?>
        </div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">LNMP & EDD (DUE DATE)</div>
        <div style="font-weight: 700; color: var(--brand);">
          EDD: <?= date('d M Y', strtotime($profile->edd)) ?>
        </div>
        <div class="muted" style="font-size: 11px;">LNMP: <?= date('d M Y', strtotime($profile->lnmp)) ?></div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">BLOOD GROUP & RHESUS</div>
        <div style="font-weight: 700; font-size: 15px; color: #dc2626;">
          <?= e($profile->blood_group ?: 'Not Tested') ?> <?= e($profile->rhesus_factor ? ucfirst($profile->rhesus_factor) : '') ?>
        </div>
      </div>
    </div>

    <!-- Parity & Risk Classification -->
    <div class="section-head">
      <span>1. Obstetric History & Risk Stratification</span>
      <?php if ($profile->high_risk_pregnancy): ?>
        <span class="badge" style="background: #ef4444; color: #fff; font-size: 11px;">⚠️ HIGH RISK PREGNANCY</span>
      <?php else: ?>
        <span class="badge" style="background: #10b981; color: #fff; font-size: 11px;">Standard Low-Risk</span>
      <?php endif; ?>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 13px;">
      <div>
        <span class="muted">Gravida (Total):</span>
        <strong><?= $profile->gravida ?></strong>
      </div>
      <div>
        <span class="muted">Parity (Past Births):</span>
        <strong><?= $profile->parity ?></strong>
      </div>
      <div>
        <span class="muted">Living Children:</span>
        <strong><?= $profile->living_children ?></strong>
      </div>
      <div>
        <span class="muted">Previous C-Sections:</span>
        <strong><?= $profile->c_sections ?></strong>
      </div>
      <div>
        <span class="muted">Miscarriages / Stillbirths:</span>
        <strong><?= $profile->miscarriages ?> / <?= $profile->stillbirths ?></strong>
      </div>
      <div>
        <span class="muted">Baseline Hb:</span>
        <strong><?= $profile->baseline_haemoglobin_g_dl ? $profile->baseline_haemoglobin_g_dl . ' g/dL' : 'Not recorded' ?></strong>
      </div>
    </div>
    <?php if ($profile->high_risk_pregnancy && $profile->risk_factors): ?>
      <div style="margin-top: 10px; padding: 8px 12px; background: rgba(239, 68, 68, 0.08); border-radius: 6px; font-size: 12px; color: #dc2626;">
        <strong>Risk Factors Identified:</strong> <?= e($profile->risk_factors) ?>
      </div>
    <?php endif; ?>

    <!-- Longitudinal Visits Log -->
    <div class="section-head">
      <span>2. Longitudinal Antenatal Visits (1st Trimester to Present)</span>
    </div>
    <?php if (empty($visits)): ?>
      <p class="muted" style="font-size: 13px;">No antenatal visits recorded yet.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; font-size: 11px;">
          <thead>
            <tr>
              <th>Date & Visit</th>
              <th>Gestation</th>
              <th>Trimester</th>
              <th>BP (mmHg)</th>
              <th>Weight</th>
              <th>Fundal Ht</th>
              <th>Presentation & FHR</th>
              <th>Urine Prot / Glu</th>
              <th>Preventive Care</th>
              <th>Danger Signs</th>
              <th>Doctor / Midwife</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visits as $v): ?>
              <?php
                $isHTN = ($v['systolic_bp'] >= 140 || $v['diastolic_bp'] >= 90);
                $danger = [];
                if ($v['danger_severe_headache']) $danger[] = 'Headache';
                if ($v['danger_blurred_vision']) $danger[] = 'Vision';
                if ($v['danger_epigastric_pain']) $danger[] = 'Epigastric';
                if ($v['danger_vaginal_bleeding']) $danger[] = 'Bleeding';
                if ($v['danger_leaking_liquor']) $danger[] = 'Leaking';
                if ($v['danger_decreased_fetal_movement']) $danger[] = 'Reduced FHR';
              ?>
              <tr>
                <td><strong><?= date('d/m/Y', strtotime($v['visit_date'])) ?></strong> (#<?= $v['visit_number'] ?>)</td>
                <td><strong><?= $v['gestational_age_weeks'] ?> wks</strong></td>
                <td style="text-transform: capitalize;"><?= e($v['trimester']) ?></td>
                <td>
                  <strong style="color: <?= $isHTN ? '#dc2626' : 'inherit' ?>;">
                    <?= $v['systolic_bp'] ?>/<?= $v['diastolic_bp'] ?>
                  </strong>
                </td>
                <td><?= $v['weight_kg'] ?> kg</td>
                <td><?= $v['fundal_height_cm'] ? $v['fundal_height_cm'] . ' cm' : '-' ?></td>
                <td>
                  <?= e($v['fetal_presentation'] ?? '-') ?> &bull;
                  <?= $v['fetal_heart_rate_bpm'] ? $v['fetal_heart_rate_bpm'] . ' bpm' : '-' ?>
                </td>
                <td>
                  Prot: <?= e($v['urine_protein'] ?? 'nil') ?> /
                  Glu: <?= e($v['urine_glucose'] ?? 'nil') ?>
                </td>
                <td>
                  <?php
                    $prev = [];
                    if ($v['ifas_tablets_dispensed']) $prev[] = "IFAS +{$v['ifas_tablets_dispensed']}";
                    if ($v['iptp_sp_dose'] && $v['iptp_sp_dose'] !== 'none') $prev[] = strtoupper($v['iptp_sp_dose']);
                    if ($v['tetanus_toxoid_dose'] && $v['tetanus_toxoid_dose'] !== 'none') $prev[] = strtoupper($v['tetanus_toxoid_dose']);
                    echo !empty($prev) ? implode(', ', $prev) : '-';
                  ?>
                </td>
                <td>
                  <?= !empty($danger) ? '<span style="color: #dc2626; font-weight: 700;">' . implode(', ', $danger) . '</span>' : 'None' ?>
                </td>
                <td><?= date('d/m/y', strtotime($v['visit_date'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Birth Preparedness & Emergency Complication Plan -->
    <div class="section-head">
      <span>3. Individualized Birth Preparedness & Emergency Plan</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px;">
      <div>
        <span class="muted">Chosen Delivery Facility:</span>
        <div style="font-weight: 700;"><?= e($profile->birth_plan_hospital ?: 'Veen Healthcare Maternity Wing') ?></div>
      </div>
      <div>
        <span class="muted">Emergency Transport:</span>
        <div style="font-weight: 700;"><?= e($profile->birth_plan_transport ?: 'Ambulance / Designated vehicle') ?></div>
      </div>
      <div>
        <span class="muted">Designated Blood Donor:</span>
        <div style="font-weight: 700;">
          <?= e($profile->birth_plan_donor ?: 'Hospital Blood Bank') ?>
          <?= !empty($profile->birth_plan_contact_phone) ? ' (' . e($profile->birth_plan_contact_phone) . ')' : '' ?>
        </div>
      </div>
      <div>
        <span class="muted">Emergency Contact Phone:</span>
        <div style="font-weight: 700;"><?= e($profile->birth_plan_contact_phone ?: ($patient['phone'] ?: 'N/A')) ?></div>
      </div>
    </div>

    <!-- Official Stamp & Sign-off footer -->
    <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; border-top: 1px dashed var(--border); padding-top: 24px; font-size: 12px;">
      <div>
        <p class="muted">Official Clinic Stamp & Officer Signature:</p>
        <div style="height: 60px; border-bottom: 1px solid #aaa; width: 80%;"></div>
        <p style="margin-top: 6px;" class="muted">Maternal Child Health (MCH) In-Charge</p>
      </div>
      <div style="text-align: right;">
        <p class="muted">Date Issued:</p>
        <div style="font-weight: 700;"><?= date('d F Y') ?></div>
        <p class="muted" style="margin-top: 4px;">VEEN HEALTHCARE MATERNAL SERVICES</p>
      </div>
    </div>
  </div>
</div>
