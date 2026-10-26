<?php $this->layout('layouts.app', ['title' => $title ?? 'Child Health Passport']) ?>

<style>
@media print {
  body * { visibility: hidden; }
  #childPassportArea, #childPassportArea * { visibility: visible; }
  #childPassportArea {
    position: absolute; left: 0; top: 0; width: 100%;
    margin: 0; padding: 15px;
    box-shadow: none !important;
    border: none !important;
    background: #ffffff !important;
    color: #000000 !important;
  }
  .no-print { display: none !important; }
  .page-break { page-break-before: always; }
}

.passport-container {
  max-width: 900px;
  margin: 0 auto;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  overflow: hidden;
}

.passport-header {
  background: linear-gradient(135deg, #0284c7, #0d9488);
  color: #fff;
  padding: 24px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.passport-body {
  padding: 28px;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--brand);
  margin-top: 24px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 2px solid var(--border);
  padding-bottom: 6px;
}
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb">
      <a href="<?= route('clinic.kids.index') ?>">Kids Clinic</a> /
      <a href="<?= route('clinic.kids.show', ['id' => $patient->uuid ?? $patient->id]) ?>"><?= e($patient->first_name . ' ' . $patient->last_name) ?></a> /
      Passport
    </div>
    <h1>Child Health Passport & Growth Record</h1>
    <p class="muted">Official Child Welfare Clinic (CWC) comprehensive health, growth, and immunization passport.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary" onclick="window.print()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Health Passport</span>
    </button>
    <a href="<?= route('clinic.kids.show', ['id' => $patient->uuid ?? $patient->id]) ?>" class="btn btn-ghost">Back to Child Chart</a>
  </div>
</div>

<div class="passport-container" id="childPassportArea">
  <!-- Header -->
  <div class="passport-header">
    <div>
      <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; opacity: 0.9;">MINISTRY OF HEALTH & CHILD WELFARE</div>
      <h2 style="margin: 4px 0 0; font-size: 24px; font-weight: 800;">CHILD HEALTH & IMMUNIZATION PASSPORT</h2>
      <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">Under-5 Clinic & Longitudinal Growth Record</div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.85;">MRN #</div>
      <div style="font-size: 18px; font-weight: 800; font-family: monospace;"><?= e($patient->mrn) ?></div>
      <div style="font-size: 11px; opacity: 0.85;">UUID: <?= substr($patient->uuid ?? '', 0, 8) ?>...</div>
    </div>
  </div>

  <div class="passport-body">
    <!-- Child & Parent Info -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; background: rgba(0,0,0,0.03); padding: 16px; border-radius: 10px; border: 1px solid var(--border);">
      <div>
        <div class="muted" style="font-size: 11px;">CHILD'S FULL NAME</div>
        <div style="font-weight: 700; font-size: 15px;"><?= e($patient->first_name . ' ' . $patient->last_name) ?></div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">DATE OF BIRTH & SEX</div>
        <div style="font-weight: 600;">
          <?= $patient->dob ? date('d M Y', strtotime($patient->dob)) : 'N/A' ?> (<?= strtoupper($patient->sex ?? 'Unknown') ?>)
        </div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">BLOOD GROUP & RH</div>
        <div style="font-weight: 700; color: #dc2626;"><?= e($profile['blood_group'] ?? 'Not Tested') ?></div>
      </div>
      <div>
        <div class="muted" style="font-size: 11px;">PARENT / GUARDIAN</div>
        <div style="font-weight: 600;">
          <?php if ($parent): ?>
            <?= e($parent['first_name'] . ' ' . $parent['last_name']) ?> (<?= e($parent['phone'] ?? 'No phone') ?>)
          <?php else: ?>
            <?= e($patient->phone ?: 'Primary Guardian') ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Birth History -->
    <div class="section-title">
      <span>1. Birth History & Perinatal Record</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 13px;">
      <div>
        <span class="muted">Birth Weight:</span>
        <strong><?= $profile && $profile['birth_weight_kg'] ? number_format((float)$profile['birth_weight_kg'], 2) . ' kg' : 'N/A' ?></strong>
      </div>
      <div>
        <span class="muted">Birth Length:</span>
        <strong><?= $profile && $profile['birth_length_cm'] ? $profile['birth_length_cm'] . ' cm' : 'N/A' ?></strong>
      </div>
      <div>
        <span class="muted">Head Circumference:</span>
        <strong><?= $profile && $profile['birth_head_circ_cm'] ? $profile['birth_head_circ_cm'] . ' cm' : 'N/A' ?></strong>
      </div>
      <div>
        <span class="muted">Gestational Age:</span>
        <strong><?= $profile && $profile['gestational_age_weeks'] ? $profile['gestational_age_weeks'] . ' weeks' : 'Term' ?></strong>
      </div>
      <div>
        <span class="muted">Delivery Mode:</span>
        <strong style="text-transform: capitalize;"><?= e($profile['delivery_mode'] ?? 'SVD') ?></strong>
      </div>
      <div>
        <span class="muted">APGAR Score:</span>
        <strong><?= e($profile['apgar_score_1min'] ?? '-') ?> (1 min) / <?= e($profile['apgar_score_5min'] ?? '-') ?> (5 min)</strong>
      </div>
      <div>
        <span class="muted">Feeding Method:</span>
        <strong style="text-transform: capitalize;"><?= e($profile['feeding_method'] ?? 'Exclusive Breastfeeding') ?></strong>
      </div>
      <div>
        <span class="muted">Place of Birth:</span>
        <strong><?= e($profile['place_of_birth'] ?? 'Health Facility') ?></strong>
      </div>
    </div>

    <!-- Longitudinal Vitals & Growth Log -->
    <div class="section-title">
      <span>2. Longitudinal Growth & Clinical Vitals History</span>
    </div>
    <?php if (empty($visits)): ?>
      <p class="muted" style="font-size: 13px;">No visit records logged yet.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; font-size: 12px;">
          <thead>
            <tr>
              <th>Date</th>
              <th>Age</th>
              <th>Weight</th>
              <th>Height</th>
              <th>Head Circ</th>
              <th>MUAC & Nutrition</th>
              <th>Vitals (T / P / SpO2)</th>
              <th>Development Milestones</th>
              <th>Next Due</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visits as $v): ?>
              <tr>
                <td><strong><?= date('d/m/Y', strtotime($v['visit_date'])) ?></strong></td>
                <td>
                  <?php if ($v['age_months'] !== null): ?>
                    <?= $v['age_months'] ?> mo
                  <?php elseif ($v['age_days'] !== null): ?>
                    <?= $v['age_days'] ?> d
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td><strong><?= $v['weight_kg'] ?> kg</strong></td>
                <td><?= $v['height_cm'] ? $v['height_cm'] . ' cm' : '-' ?></td>
                <td><?= $v['head_circumference_cm'] ? $v['head_circumference_cm'] . ' cm' : '-' ?></td>
                <td>
                  <?php if ($v['muac_cm']): ?>
                    <?= $v['muac_cm'] ?> cm
                  <?php endif; ?>
                  <?php
                    $nutClass = match($v['nutritional_status']) {
                      'sam' => 'badge-danger',
                      'mam' => 'badge-warning',
                      'overweight' => 'badge-info',
                      default => 'badge-success'
                    };
                  ?>
                  <span class="badge <?= $nutClass ?>" style="text-transform: uppercase; font-size: 10px;"><?= $v['nutritional_status'] ?? 'normal' ?></span>
                </td>
                <td>
                  <?= $v['temperature_c'] ? $v['temperature_c'] . '°C' : '-' ?> /
                  <?= $v['pulse_bpm'] ? $v['pulse_bpm'] . ' bpm' : '-' ?> /
                  <?= $v['spo2_percent'] ? $v['spo2_percent'] . '%' : '-' ?>
                </td>
                <td style="max-width: 160px;"><?= e($v['developmental_milestones'] ?? '-') ?></td>
                <td><?= $v['next_visit_date'] ? date('d/m/y', strtotime($v['next_visit_date'])) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Immunization Registry -->
    <div class="section-title">
      <span>3. Official Immunization & Vaccination Registry</span>
    </div>
    <div style="overflow-x: auto;">
      <table class="table" style="width: 100%; font-size: 12px;">
        <thead>
          <tr>
            <th>Target Age</th>
            <th>Vaccine</th>
            <th>Dose</th>
            <th>Status</th>
            <th>Date Administered</th>
            <th>Batch / Lot #</th>
            <th>Administered By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($immunizations as $im): ?>
            <tr>
              <td><span class="badge" style="background: rgba(0,0,0,0.06);"><?= e($im['target_age_description']) ?></span></td>
              <td><strong><?= e($im['vaccine_name']) ?></strong> <span class="muted">(<?= e($im['vaccine_code']) ?>)</span></td>
              <td>Dose <?= e($im['dose_number']) ?></td>
              <td>
                <?php if ($im['status'] === 'given'): ?>
                  <span class="badge badge-success">✓ Given</span>
                <?php elseif ($im['status'] === 'overdue'): ?>
                  <span class="badge badge-danger">Overdue</span>
                <?php else: ?>
                  <span class="badge badge-warning">Due</span>
                <?php endif; ?>
              </td>
              <td><?= $im['date_administered'] ? date('d/m/Y', strtotime($im['date_administered'])) : '-' ?></td>
              <td><code><?= e($im['batch_number'] ?? '-') ?></code></td>
              <td><?= e($im['administered_by_name'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Official Stamp & Sign-off footer -->
    <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; border-top: 1px dashed var(--border); padding-top: 24px; font-size: 12px;">
      <div>
        <p class="muted">Clinic Stamp & Signature:</p>
        <div style="height: 60px; border-bottom: 1px solid #aaa; width: 80%;"></div>
        <p style="margin-top: 6px;" class="muted">Clinician / Maternal-Child Health Officer</p>
      </div>
      <div style="text-align: right;">
        <p class="muted">Card Issue Date:</p>
        <div style="font-weight: 700;"><?= date('d F Y') ?></div>
        <p class="muted" style="margin-top: 4px;">VEEN HEALTHCARE MATERNAL & CHILD HEALTH SYSTEM</p>
      </div>
    </div>
  </div>
</div>
