<?php $this->layout('layouts.app', ['title' => 'Child Chart: ' . $patient['first_name'] . ' ' . $patient['last_name']]) ?>

<?php
  $dob = !empty($patient['dob']) ? new DateTime($patient['dob']) : null;
  $now = new DateTime();
  $ageString = 'Unknown';
  if ($dob) {
    $diff = $now->diff($dob);
    $ageString = $diff->y > 0 ? "{$diff->y} yrs, {$diff->m} mos" : "{$diff->m} months ({$diff->d} days)";
  }

  $latestVisit = !empty($visits) ? end($visits) : null;
?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('clinic.kids.index') ?>">Kids Clinic</a> / Child Chart</div>
    <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
      <h1 style="margin: 0;"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
      <span class="badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7; font-weight: 700; font-size: 13px;">👶 Child Patient</span>
      <?php if (!empty($patient['blood_group'])): ?>
        <span class="badge badge-brand"><?= e($patient['blood_group']) ?></span>
      <?php endif; ?>
    </div>
    <p class="muted" style="margin-top: 4px;">
      MRN: <strong class="mono" style="color: var(--brand);"><?= e($patient['mrn']) ?></strong>
      • Born: <strong><?= human_date($patient['dob']) ?></strong> (<?= $ageString ?>)
      • Sex: <strong style="text-transform: capitalize;"><?= e($patient['sex']) ?></strong>
      <?php if ($parent): ?>
        • Parent/Guardian: <strong><?= e($parent['first_name'] . ' ' . $parent['last_name']) ?></strong> (<span class="mono"><?= e($parent['phone']) ?></span>)
      <?php endif; ?>
    </p>
  </div>

  <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newChildVisitModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Record Clinic Visit & Vitals</span>
    </button>
    <a href="<?= route('clinic.kids.card', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost" target="_blank" title="Print Child Health Passport">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Health Passport</span>
    </a>
    <button type="button" class="btn btn-ghost" onclick="document.getElementById('editBirthProfileModal').classList.remove('hidden')">
      <span>Edit Birth Record</span>
    </button>
    <a href="<?= route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost">
      <span>General Chart</span>
    </a>
  </div>
</div>

<!-- Child Summary Cards -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid #0284c7;">
    <div class="dim" style="font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Current Weight</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #0284c7; margin-top: 4px;">
      <?= $latestVisit ? $latestVisit['weight_kg'] . ' kg' : ($profile['birth_weight_kg'] ? $profile['birth_weight_kg'] . ' kg (Birth)' : '—') ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      <?php if ($profile['birth_weight_kg'] && $latestVisit): ?>
        Gain: +<?= round($latestVisit['weight_kg'] - $profile['birth_weight_kg'], 2) ?> kg since birth
      <?php else: ?>
        Birth Wt: <?= $profile['birth_weight_kg'] ? $profile['birth_weight_kg'] . ' kg' : 'Not recorded' ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #10b981;">
    <div class="dim" style="font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Length / Height</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #10b981; margin-top: 4px;">
      <?= $latestVisit && $latestVisit['height_cm'] ? $latestVisit['height_cm'] . ' cm' : ($profile['birth_length_cm'] ? $profile['birth_length_cm'] . ' cm (Birth)' : '—') ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Head Circ: <?= $latestVisit && $latestVisit['head_circumference_cm'] ? $latestVisit['head_circumference_cm'] . ' cm' : ($profile['birth_head_circ_cm'] ? $profile['birth_head_circ_cm'] . ' cm' : '—') ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid <?= ($latestVisit && in_array($latestVisit['nutritional_status'], ['sam', 'mam'])) ? '#ef4444' : '#14b8a6' ?>;">
    <div class="dim" style="font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Nutritional Status</div>
    <div style="font-size: 22px; font-weight: 800; margin-top: 4px;">
      <?php if ($latestVisit): ?>
        <?php if ($latestVisit['nutritional_status'] === 'sam'): ?>
          <span style="color: #ef4444;">SAM (Severe)</span>
        <?php elseif ($latestVisit['nutritional_status'] === 'mam'): ?>
          <span style="color: #f59e0b;">MAM (Moderate)</span>
        <?php elseif ($latestVisit['nutritional_status'] === 'overweight'): ?>
          <span style="color: #a855f7;">Overweight</span>
        <?php else: ?>
          <span style="color: #10b981;">Normal Growth</span>
        <?php endif; ?>
      <?php else: ?>
        <span class="dim">Pending Visit</span>
      <?php endif; ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      MUAC: <?= $latestVisit && $latestVisit['muac_cm'] ? $latestVisit['muac_cm'] . ' cm' : 'Not recorded' ?>
    </div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #6366f1;">
    <div class="dim" style="font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Immunization Progress</div>
    <?php
      $givenCount = count(array_filter($immunizations, fn($i) => $i['status'] === 'given'));
      $totalCount = count($immunizations);
      $pct = $totalCount ? round(($givenCount / $totalCount) * 100) : 0;
    ?>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #6366f1; margin-top: 4px;">
      <?= $givenCount ?> / <?= $totalCount ?>
      <span style="font-size: 14px; font-weight: 600; color: var(--muted);">(<?= $pct ?>%)</span>
    </div>
    <div style="height: 6px; background: rgba(0,0,0,0.1); border-radius: 3px; margin-top: 8px; overflow: hidden;">
      <div style="width: <?= $pct ?>%; height: 100%; background: #6366f1; border-radius: 3px;"></div>
    </div>
  </div>
</div>

<!-- Birth Record & Baseline Profile Card -->
<div class="card card-pad" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
    <h3 style="margin: 0; font-size: 16px;">Birth History & Perinatal Profile</h3>
    <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('editBirthProfileModal').classList.remove('hidden')">Edit Profile</button>
  </div>

  <div class="grid grid-4" style="gap: 16px; font-size: 13.5px;">
    <div>
      <div class="dim" style="font-size: 11.5px;">Birth Weight</div>
      <strong><?= $profile['birth_weight_kg'] ? $profile['birth_weight_kg'] . ' kg' : '—' ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Birth Length</div>
      <strong><?= $profile['birth_length_cm'] ? $profile['birth_length_cm'] . ' cm' : '—' ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Head Circumference</div>
      <strong><?= $profile['birth_head_circ_cm'] ? $profile['birth_head_circ_cm'] . ' cm' : '—' ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Gestational Age at Birth</div>
      <strong><?= $profile['gestational_age_weeks'] ? $profile['gestational_age_weeks'] . ' weeks' : '—' ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Mode of Delivery</div>
      <strong style="text-transform: uppercase;"><?= e(str_replace('_', ' ', $profile['delivery_mode'] ?? 'SVD')) ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">APGAR Score (1m / 5m)</div>
      <strong><?= ($profile['apgar_score_1min'] !== null ? $profile['apgar_score_1min'] : '—') . ' / ' . ($profile['apgar_score_5min'] !== null ? $profile['apgar_score_5min'] : '—') ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Feeding Method</div>
      <strong style="text-transform: capitalize;"><?= e(str_replace('_', ' ', $profile['feeding_method'] ?? 'Exclusive Breastfeeding')) ?></strong>
    </div>
    <div>
      <div class="dim" style="font-size: 11.5px;">Place of Birth</div>
      <strong><?= e($profile['place_of_birth'] ?: '—') ?></strong>
    </div>
  </div>

  <?php if (!empty($profile['birth_complications']) || !empty($profile['special_needs_or_allergies'])): ?>
    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--border); display: flex; gap: 20px; font-size: 13px;">
      <?php if (!empty($profile['birth_complications'])): ?>
        <div><strong class="dim">Birth Complications:</strong> <span style="color: #ef4444;"><?= e($profile['birth_complications']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($profile['special_needs_or_allergies'])): ?>
        <div><strong class="dim">Allergies / Special Needs:</strong> <span style="color: #f59e0b;"><?= e($profile['special_needs_or_allergies']) ?></span></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Longitudinal Growth & Vitals Progression -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0;">Longitudinal Growth & Vitals Tracking (<?= count($visits) ?> Recorded Visits)</h3>
      <p class="muted" style="margin: 2px 0 0; font-size: 13px;">Complete clinic visit history, weight-for-age, height, MUAC, vitals, and developmental milestones.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('newChildVisitModal').classList.remove('hidden')">
      <span>+ Record Visit</span>
    </button>
  </div>

  <?php if (!empty($chartData['labels'])): ?>
    <div style="padding: 16px 20px; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border);">
      <div style="font-weight: 700; font-size: 13px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
        <span>📈 Growth Curve Visualizer (Weight in kg over time)</span>
        <span class="dim" style="font-size: 11px;">Points: <?= count($chartData['labels']) ?></span>
      </div>
      <div style="display: flex; align-items: flex-end; gap: 14px; height: 120px; padding: 10px 0; border-bottom: 2px solid var(--border); overflow-x: auto;">
        <?php
          $maxWeight = max($chartData['weights'] ?: [10]);
          $maxWeight = max(10, $maxWeight * 1.15);
        ?>
        <?php foreach ($chartData['labels'] as $idx => $label): ?>
          <?php
            $w = $chartData['weights'][$idx];
            $heightPct = round(($w / $maxWeight) * 100);
          ?>
          <div style="display: flex; flex-direction: column; align-items: center; min-width: 65px; height: 100%; justify-content: flex-end;">
            <span class="mono" style="font-size: 11px; font-weight: 700; color: #0284c7; margin-bottom: 4px;"><?= $w ?>kg</span>
            <div style="width: 24px; height: <?= max(8, $heightPct) ?>%; background: linear-gradient(180deg, #0284c7, #38bdf8); border-radius: 4px 4px 0 0;"></div>
            <span class="dim" style="font-size: 10px; margin-top: 6px; white-space: nowrap;"><?= $label ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Visit Date</th>
          <th>Age</th>
          <th>Weight (kg)</th>
          <th>Height (cm)</th>
          <th>Head Circ</th>
          <th>MUAC</th>
          <th>Vitals (Temp / Pulse / SpO2)</th>
          <th>Milestones & Clinical Review</th>
          <th>Next Review</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($visits)): ?>
          <tr>
            <td colspan="9" class="empty" style="text-align: center; padding: 24px;">
              No visits recorded yet. Click <strong>"+ Record Clinic Visit & Vitals"</strong> above to log the child's first visit.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($visits as $v): ?>
            <tr>
              <td>
                <strong><?= date('d M Y', strtotime($v['visit_date'])) ?></strong>
                <div class="dim" style="font-size: 11px;">By: <?= e($v['doctor_name'] ?? 'Attending Clinician') ?></div>
              </td>
              <td>
                <span class="badge badge-ghost"><?= $v['age_months'] ?> months</span>
              </td>
              <td>
                <strong class="mono" style="color: #0284c7; font-size: 15px;"><?= $v['weight_kg'] ?> kg</strong>
              </td>
              <td>
                <?= $v['height_cm'] ? $v['height_cm'] . ' cm' : '—' ?>
              </td>
              <td>
                <?= $v['head_circumference_cm'] ? $v['head_circumference_cm'] . ' cm' : '—' ?>
              </td>
              <td>
                <?php if ($v['muac_cm']): ?>
                  <strong><?= $v['muac_cm'] ?> cm</strong>
                  <div>
                    <?php if ($v['nutritional_status'] === 'sam'): ?>
                      <span class="badge badge-danger" style="font-size: 10px;">SAM</span>
                    <?php elseif ($v['nutritional_status'] === 'mam'): ?>
                      <span class="badge badge-warning" style="font-size: 10px;">MAM</span>
                    <?php else: ?>
                      <span class="badge badge-success" style="font-size: 10px;">Normal</span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="dim">—</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-size: 12px; line-height: 1.4;">
                  <?php if ($v['temperature_c']): ?>
                    <span>🌡️ <?= $v['temperature_c'] ?>°C</span>
                  <?php endif; ?>
                  <?php if ($v['pulse_rate']): ?>
                    <span style="margin-left: 4px;">❤️ <?= $v['pulse_rate'] ?> bpm</span>
                  <?php endif; ?>
                  <?php if ($v['spo2_percent']): ?>
                    <span style="margin-left: 4px;">💨 <?= $v['spo2_percent'] ?>%</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php if ($v['developmental_milestones']): ?>
                  <div style="font-size: 12px; color: var(--brand); margin-bottom: 2px;">
                    <strong>Milestones:</strong> <?= e($v['developmental_milestones']) ?>
                  </div>
                <?php endif; ?>
                <?php if ($v['diagnoses']): ?>
                  <div style="font-size: 12px;"><strong>Dx:</strong> <?= e($v['diagnoses']) ?></div>
                <?php endif; ?>
                <?php if ($v['clinical_notes']): ?>
                  <div class="dim" style="font-size: 11.5px;"><?= e($v['clinical_notes']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($v['next_visit_date']): ?>
                  <span class="mono" style="font-size: 12px;"><?= human_date($v['next_visit_date']) ?></span>
                <?php else: ?>
                  <span class="dim">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Child Immunization Passport / Vaccine Schedule -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0;">💉 Child Immunization Passport & Schedule (EPI / WHO Standard)</h3>
      <p class="muted" style="margin: 2px 0 0; font-size: 13px;">Tracks all compulsory childhood vaccines, doses, lot numbers, and adverse reactions.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <span class="badge badge-success"><?= $givenCount ?> Given</span>
      <span class="badge badge-warning"><?= $totalCount - $givenCount ?> Pending</span>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Target Age</th>
          <th>Vaccine Code & Name</th>
          <th>Dose</th>
          <th>Status</th>
          <th>Date Administered</th>
          <th>Batch / Lot #</th>
          <th>Administered By</th>
          <th>Adverse Events / Notes</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($immunizations as $im): ?>
          <tr>
            <td>
              <strong style="color: var(--brand);"><?= e($im['target_age_description']) ?></strong>
            </td>
            <td>
              <strong><?= e($im['vaccine_name']) ?></strong>
              <div class="mono dim" style="font-size: 11px;"><?= e($im['vaccine_code']) ?></div>
            </td>
            <td>
              <span class="mono">Dose <?= $im['dose_number'] ?></span>
            </td>
            <td>
              <?php if ($im['status'] === 'given'): ?>
                <span class="badge badge-success">✔ Given</span>
              <?php elseif ($im['status'] === 'overdue'): ?>
                <span class="badge badge-danger">Overdue</span>
              <?php elseif ($im['status'] === 'missed'): ?>
                <span class="badge badge-danger">Missed</span>
              <?php elseif ($im['status'] === 'exempted'): ?>
                <span class="badge badge-ghost">Exempted</span>
              <?php else: ?>
                <span class="badge badge-warning">Due</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($im['administered_date']): ?>
                <strong class="mono"><?= date('d M Y', strtotime($im['administered_date'])) ?></strong>
              <?php else: ?>
                <span class="dim">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $im['batch_number'] ? '<span class="mono">' . e($im['batch_number']) . '</span>' : '<span class="dim">—</span>' ?>
            </td>
            <td>
              <?= e($im['administered_by'] ?: '—') ?>
            </td>
            <td>
              <?php if ($im['adverse_reaction']): ?>
                <span class="badge badge-danger" style="font-size: 10.5px;">AEFI: <?= e($im['adverse_reaction']) ?></span>
              <?php endif; ?>
              <?php if ($im['notes']): ?>
                <span class="dim" style="font-size: 11px;"><?= e($im['notes']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-ghost" onclick="openImmunizationModal(<?= htmlspecialchars(json_encode($im), ENT_QUOTES) ?>)">
                <?= $im['status'] === 'given' ? 'Edit' : 'Administer' ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Record New Child Visit -->
<div id="newChildVisitModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <h3 style="margin: 0;">Record Pediatric Clinic Visit & Vitals</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('newChildVisitModal').classList.add('hidden')">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.kids.visits.store', ['id' => $patient['uuid'] ?? $patient['id']]) ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label>Visit Date *</label>
          <input type="date" name="visit_date" class="input" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="field">
          <label>Child Weight (kg) *</label>
          <input type="number" step="0.01" name="weight_kg" class="input" placeholder="e.g. 8.4" required autofocus>
        </div>

        <div class="field">
          <label>Height / Length (cm)</label>
          <input type="number" step="0.1" name="height_cm" class="input" placeholder="e.g. 72.5">
        </div>

        <div class="field">
          <label>Head Circumference (cm)</label>
          <input type="number" step="0.1" name="head_circumference_cm" class="input" placeholder="e.g. 44.0">
        </div>

        <div class="field">
          <label>MUAC (cm) - Mid-Upper Arm</label>
          <input type="number" step="0.1" name="muac_cm" class="input" placeholder="e.g. 13.5 (Normal ≥12.5)">
        </div>

        <div class="field">
          <label>Nutritional Classification</label>
          <select name="nutritional_status" class="input">
            <option value="">Auto-classify from MUAC</option>
            <option value="normal">Normal / Adequate Growth (MUAC ≥ 12.5 cm)</option>
            <option value="mam">MAM - Moderate Acute Malnutrition (11.5 - 12.4 cm)</option>
            <option value="sam">SAM - Severe Acute Malnutrition (< 11.5 cm)</option>
            <option value="overweight">Overweight</option>
          </select>
        </div>

        <div class="field">
          <label>Body Temperature (°C)</label>
          <input type="number" step="0.1" name="temperature_c" class="input" placeholder="36.8">
        </div>

        <div class="field">
          <label>Pulse Rate (bpm)</label>
          <input type="number" name="pulse_rate" class="input" placeholder="110">
        </div>

        <div class="field">
          <label>Respiratory Rate (bpm)</label>
          <input type="number" name="respiratory_rate" class="input" placeholder="28">
        </div>

        <div class="field">
          <label>Oxygen Saturation (SpO2 %)</label>
          <input type="number" name="spo2_percent" class="input" placeholder="99">
        </div>

        <div class="field full">
          <label>Developmental Milestones Achieved</label>
          <input type="text" name="developmental_milestones" class="input" placeholder="e.g. Holds head steady, rolls over, babbles, responds to name, pincher grasp">
        </div>

        <div class="field full">
          <label>Dietary & Feeding History</label>
          <input type="text" name="diet_and_feeding_notes" class="input" placeholder="e.g. Breastfeeding 6x daily, porridge with egg and milk introduced">
        </div>

        <div class="field full">
          <label>Clinical Diagnoses (if ill)</label>
          <input type="text" name="diagnoses" class="input" placeholder="e.g. Acute upper respiratory infection, well-baby routine review">
        </div>

        <div class="field full">
          <label>Treatments / Supplements Given</label>
          <input type="text" name="treatment_given" class="input" placeholder="e.g. Zinc sulfate + ORS, Vitamin A 100,000 IU, Paracetamol syrup">
        </div>

        <div class="field full">
          <label>Physician / Nurse Clinical Notes</label>
          <textarea name="clinical_notes" class="input" rows="2" placeholder="Clinical impression, counseling on infant feeding, maternal education"></textarea>
        </div>

        <div class="field">
          <label>Next Scheduled Clinic Visit</label>
          <input type="date" name="next_visit_date" class="input" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
        </div>
      </div>

      <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newChildVisitModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Visit Record</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Administer / Update Vaccine -->
<div id="updateImmunizationModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <div>
        <h3 style="margin: 0;" id="immModalTitle">Administer Vaccine</h3>
        <p class="muted" style="margin: 2px 0 0; font-size: 12.5px;" id="immModalSubtitle">Record vaccine dose</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('updateImmunizationModal').classList.add('hidden')">✕</button>
    </div>

    <form id="immForm" method="POST" action="">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field full">
          <label>Status *</label>
          <select name="status" id="imm_status" class="input" required>
            <option value="given">✔ Given / Administered</option>
            <option value="due">Due</option>
            <option value="overdue">Overdue</option>
            <option value="missed">Missed</option>
            <option value="exempted">Medically Exempted</option>
          </select>
        </div>

        <div class="field">
          <label>Date Administered</label>
          <input type="date" name="administered_date" id="imm_date" class="input" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="field">
          <label>Vaccine Batch / Lot #</label>
          <input type="text" name="batch_number" id="imm_batch" class="input" placeholder="e.g. B-99482A">
        </div>

        <div class="field full">
          <label>Administered By (Healthcare Worker)</label>
          <input type="text" name="administered_by" id="imm_by" class="input" value="<?= e(auth()->name ?? 'Clinic Nurse') ?>">
        </div>

        <div class="field full">
          <label>Adverse Events (AEFI - if any)</label>
          <input type="text" name="adverse_reaction" id="imm_aefi" class="input" placeholder="e.g. Mild fever, localized swelling at injection site">
        </div>

        <div class="field full">
          <label>Notes / Next Due Date</label>
          <input type="text" name="notes" id="imm_notes" class="input" placeholder="e.g. Next dose scheduled at 10 weeks">
        </div>
      </div>

      <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('updateImmunizationModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Vaccine Record</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Birth Profile -->
<div id="editBirthProfileModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <h3 style="margin: 0;">Update Birth History & Perinatal Record</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('editBirthProfileModal').classList.add('hidden')">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.kids.profile.update', ['id' => $profile['uuid'] ?? $profile['id']]) ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label>Birth Weight (kg)</label>
          <input type="number" step="0.01" name="birth_weight_kg" class="input" value="<?= e($profile['birth_weight_kg']) ?>">
        </div>

        <div class="field">
          <label>Birth Length (cm)</label>
          <input type="number" step="0.1" name="birth_length_cm" class="input" value="<?= e($profile['birth_length_cm']) ?>">
        </div>

        <div class="field">
          <label>Head Circumference at Birth (cm)</label>
          <input type="number" step="0.1" name="birth_head_circ_cm" class="input" value="<?= e($profile['birth_head_circ_cm']) ?>">
        </div>

        <div class="field">
          <label>Gestational Age at Birth (weeks)</label>
          <input type="number" name="gestational_age_weeks" class="input" value="<?= e($profile['gestational_age_weeks']) ?>">
        </div>

        <div class="field">
          <label>Mode of Delivery</label>
          <select name="delivery_mode" class="input">
            <option value="svd" <?= ($profile['delivery_mode'] ?? '') === 'svd' ? 'selected' : '' ?>>Spontaneous Vertex Delivery (SVD)</option>
            <option value="c_section" <?= ($profile['delivery_mode'] ?? '') === 'c_section' ? 'selected' : '' ?>>Caesarean Section (C-Section)</option>
            <option value="assisted" <?= ($profile['delivery_mode'] ?? '') === 'assisted' ? 'selected' : '' ?>>Assisted Delivery</option>
            <option value="breech" <?= ($profile['delivery_mode'] ?? '') === 'breech' ? 'selected' : '' ?>>Breech Delivery</option>
          </select>
        </div>

        <div class="field">
          <label>Feeding Method</label>
          <select name="feeding_method" class="input">
            <option value="exclusive_breastfeeding" <?= ($profile['feeding_method'] ?? '') === 'exclusive_breastfeeding' ? 'selected' : '' ?>>Exclusive Breastfeeding (EBF)</option>
            <option value="mixed" <?= ($profile['feeding_method'] ?? '') === 'mixed' ? 'selected' : '' ?>>Mixed Feeding</option>
            <option value="formula" <?= ($profile['feeding_method'] ?? '') === 'formula' ? 'selected' : '' ?>>Infant Formula</option>
            <option value="weaned" <?= ($profile['feeding_method'] ?? '') === 'weaned' ? 'selected' : '' ?>>Complementary / Weaned</option>
          </select>
        </div>

        <div class="field">
          <label>APGAR Score (1m / 5m)</label>
          <div style="display: flex; gap: 8px;">
            <input type="number" name="apgar_score_1min" class="input" placeholder="1m" value="<?= e($profile['apgar_score_1min']) ?>">
            <input type="number" name="apgar_score_5min" class="input" placeholder="5m" value="<?= e($profile['apgar_score_5min']) ?>">
          </div>
        </div>

        <div class="field">
          <label>Blood Group</label>
          <input type="text" name="blood_group" class="input" value="<?= e($profile['blood_group']) ?>" placeholder="e.g. O+">
        </div>

        <div class="field full">
          <label>Place of Birth / Facility</label>
          <input type="text" name="place_of_birth" class="input" value="<?= e($profile['place_of_birth']) ?>">
        </div>

        <div class="field full">
          <label>Birth Complications</label>
          <textarea name="birth_complications" class="input" rows="2"><?= e($profile['birth_complications']) ?></textarea>
        </div>

        <div class="field full">
          <label>Allergies & Special Needs</label>
          <input type="text" name="special_needs_or_allergies" class="input" value="<?= e($profile['special_needs_or_allergies']) ?>">
        </div>
      </div>

      <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('editBirthProfileModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Birth Details</button>
      </div>
    </form>
  </div>
</div>

<script>
function openImmunizationModal(imm) {
  document.getElementById('immModalTitle').textContent = imm.vaccine_name;
  document.getElementById('immModalSubtitle').textContent = imm.target_age_description + ' • Dose ' + imm.dose_number;
  document.getElementById('imm_status').value = imm.status || 'given';
  document.getElementById('imm_date').value = imm.administered_date || new Date().toISOString().split('T')[0];
  document.getElementById('imm_batch').value = imm.batch_number || '';
  if (imm.administered_by) {
    document.getElementById('imm_by').value = imm.administered_by;
  }
  document.getElementById('imm_aefi').value = imm.adverse_reaction || '';
  document.getElementById('imm_notes').value = imm.notes || '';
  
  const form = document.getElementById('immForm');
  form.action = '/clinic/kids/immunizations/' + (imm.uuid || imm.id);
  
  document.getElementById('updateImmunizationModal').classList.remove('hidden');
}
</script>
