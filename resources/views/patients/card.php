<?php $this->layout('layouts.app', ['title' => 'Patient Identification Card']) ?>

<style>
@media print {
  body * { visibility: hidden; }
  #patientIdCardArea, #patientIdCardArea * { visibility: visible; }
  #patientIdCardArea {
    position: absolute; left: 50%; top: 40px;
    transform: translateX(-50%);
    width: 360px;
    box-shadow: none !important;
    border: 2px solid #000 !important;
    background: #ffffff !important;
    color: #000000 !important;
  }
  .no-print { display: none !important; }
}

.id-card {
  width: 100%;
  max-width: 420px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  position: relative;
}

.id-card-header {
  background: linear-gradient(135deg, var(--brand), #0d9488);
  color: #fff;
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.id-card-body {
  padding: 22px;
}

.id-avatar {
  width: 84px;
  height: 96px;
  background: rgba(255,255,255,0.05);
  border: 2px dashed var(--border);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  font-size: 11px;
  text-align: center;
  padding: 4px;
}
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb"><a href="<?= route('patients.index') ?>">Patients</a> / <a href="<?= route('patients.show', ['id' => $patient['id']]) ?>"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></a> / ID Card</div>
    <h1>Official Patient Identification Card</h1>
    <p class="muted">Standardized medical identification card for registration, admissions, and pharmacy verification.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary" onclick="window.print()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print ID Card</span>
    </button>
    <a href="<?= route('patients.show', ['id' => $patient['id']]) ?>" class="btn btn-ghost">Back to Patient Chart</a>
  </div>
</div>

<div style="display: flex; justify-content: center; margin-top: 30px;">
  <div class="id-card" id="patientIdCardArea">
    <!-- Header -->
    <div class="id-card-header">
      <div>
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; opacity: 0.85;">PATIENT MEDICAL IDENTITY</div>
        <div style="font-size: 18px; font-weight: 800;"><?= e(tenant('name', 'Veen Hospital')) ?></div>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 10px; background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 999px; font-weight: 700;">ACTIVE</span>
      </div>
    </div>

    <!-- Body -->
    <div class="id-card-body">
      <div style="display: flex; gap: 18px; margin-bottom: 16px;">
        <!-- Avatar / Photo placeholder -->
        <div class="id-avatar">
          <svg style="width: 32px; height: 32px; margin-bottom: 4px; opacity: 0.5;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <span>PHOTO</span>
        </div>

        <!-- Demographics -->
        <div style="flex: 1;">
          <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text);">
            <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?>
          </h2>
          <?php if (!empty($patient['other_names'])): ?>
            <div class="dim" style="font-size: 13px;"><?= e($patient['other_names']) ?></div>
          <?php endif; ?>

          <div style="margin-top: 8px; font-size: 12px; line-height: 1.6;">
            <div><span class="dim">MRN:</span> <strong class="mono" style="color: var(--brand);"><?= e($patient['mrn']) ?></strong></div>
            <div><span class="dim">National ID / Passport:</span> <strong><?= e($patient['national_id'] ?: 'N/A') ?></strong></div>
            <div><span class="dim">Phone:</span> <strong><?= e($patient['phone'] ?: 'N/A') ?></strong></div>
          </div>
        </div>
      </div>

      <!-- Attributes Grid -->
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: rgba(255,255,255,0.02); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 11px; margin-bottom: 16px;">
        <div>
          <div class="dim" style="text-transform: uppercase;">Sex</div>
          <div style="font-weight: 700; text-transform: uppercase;"><?= e($patient['sex']) ?></div>
        </div>
        <div>
          <div class="dim" style="text-transform: uppercase;">Birth Date</div>
          <div style="font-weight: 700;"><?= $patient['dob'] ? date('d M Y', strtotime($patient['dob'])) : '—' ?></div>
        </div>
        <div>
          <div class="dim" style="text-transform: uppercase;">Blood Group</div>
          <div style="font-weight: 800; color: #ef4444;"><?= e($patient['blood_group'] ?: '—') ?></div>
        </div>
      </div>

      <?php if (!empty($patient['allergies'])): ?>
        <div style="margin-bottom: 14px; padding: 6px 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; font-size: 11px; color: #ef4444;">
          <strong>⚠ CRITICAL ALLERGIES:</strong> <?= e($patient['allergies']) ?>
        </div>
      <?php endif; ?>

      <!-- Barcode Area -->
      <div style="text-align: center; border-top: 1px dashed var(--border); padding-top: 14px;">
        <?= barcode_data_uri($patient['mrn'], 180, 42) ? '<img src="' . barcode_data_uri($patient['mrn'], 180, 42) . '" alt="MRN Barcode" style="max-height: 42px;">' : '' ?>
        <div class="mono dim" style="font-size: 12px; letter-spacing: 2px; margin-top: 4px;"><?= e($patient['mrn']) ?></div>
      </div>

      <!-- Footer Info -->
      <div style="margin-top: 10px; text-align: center; font-size: 10px; color: var(--muted);">
        Emergency Contact: <?= e($patient['next_of_kin_name'] ?: 'N/A') ?> (<?= e($patient['next_of_kin_phone'] ?: 'N/A') ?>) • <?= e(tenant('phone')) ?>
      </div>
    </div>
  </div>
</div>
