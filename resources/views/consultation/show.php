<?php $this->layout('layouts.app', ['title' => 'Consultation — ' . $enc['first_name'] . ' ' . $enc['last_name']]) ?>

<!-- Patient Header Banner -->
<div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, rgba(20, 184, 166, 0.08), rgba(59, 130, 246, 0.04)); border-left: 5px solid var(--brand);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <h2 style="margin: 0; font-size: 22px;"><?= e($enc['first_name'] . ' ' . $enc['last_name']) ?></h2>
        <?php if ($enc['ticket_number']): ?>
          <span class="badge badge-brand mono" style="font-size: 14px; font-weight: 700;">Ticket #<?= e($enc['ticket_number']) ?></span>
        <?php endif; ?>
        <span class="badge badge-<?= $enc['status'] === 'completed' ? 'success' : 'info' ?>">
          <?= ucfirst(str_replace('_', ' ', $enc['status'])) ?>
        </span>
      </div>
      <div style="margin-top: 6px; font-size: 13px; color: var(--muted); display: flex; gap: 16px; flex-wrap: wrap;">
        <span>MRN: <strong class="mono text-brand"><?= e($enc['mrn']) ?></strong></span>
        <span>Sex: <strong><?= ucfirst($enc['sex']) ?></strong></span>
        <span>Age: <strong><?= age_from_dob($enc['dob']) ? age_from_dob($enc['dob']) . ' yrs' : 'N/A' ?></strong> (<?= e($enc['dob']) ?>)</span>
        <span>Blood: <strong><?= e($enc['blood_group'] ?: 'Not recorded') ?></strong></span>
        <span>Allergies: <strong style="color: #ef4444;"><?= e($enc['allergies'] ?: 'NKDA (None reported)') ?></strong></span>
        <span>Payer: <strong><?= e($enc['insurance_provider'] ? $enc['insurance_provider'] . ' (' . $enc['insurance_number'] . ')' : 'Cash / Self Pay') ?></strong></span>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 10px;">
      <div style="text-align: right;">
        <?= barcode_data_uri($enc['mrn'], 120, 32) ? '<img src="' . barcode_data_uri($enc['mrn'], 120, 32) . '" alt="MRN Barcode" style="max-height: 32px;">' : '' ?>
        <div class="mono dim" style="font-size: 10px;"><?= e($enc['mrn']) ?></div>
      </div>
      <a href="<?= route('pharmacy.pos', ['patient_id' => $enc['patient_uuid'] ?? $enc['patient_id']]) ?>" class="btn btn-sm btn-primary" style="background: var(--brand-2); border-color: var(--brand-2); display: inline-flex; align-items: center; gap: 6px;" target="_blank" title="Open Pharmacy POS pre-linked to this patient">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Dispense at Pharmacy</span>
      </a>
      <a href="<?= route('patients.show', ['id' => $enc['patient_uuid'] ?? $enc['patient_id']]) ?>" class="btn btn-ghost btn-sm" target="_blank">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        <span>Full Patient Record</span>
      </a>
    </div>
  </div>
</div>

<div class="grid grid-2" style="margin-bottom: 24px; gap: 20px;">
  <!-- Left Column: Triage & Vitals -->
  <div class="card">
    <div class="card-head">
      <h3>1. Clinical Triage & Vital Signs</h3>
      <span class="badge badge-brand"><?= strtoupper($enc['triage_category']) ?></span>
    </div>

    <form method="POST" action="<?= route('consultations.vitals', ['id' => $enc['uuid'] ?? $enc['id']]) ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field">
          <label>Blood Pressure (Systolic/Diastolic)</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <input type="number" name="systolic_bp" class="input" placeholder="120" value="<?= e($enc['systolic_bp']) ?>">
            <span class="muted">/</span>
            <input type="number" name="diastolic_bp" class="input" placeholder="80" value="<?= e($enc['diastolic_bp']) ?>">
            <span class="muted" style="font-size: 11px;">mmHg</span>
          </div>
        </div>

        <div class="field">
          <label>Heart / Pulse Rate</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <input type="number" name="pulse_rate" class="input" placeholder="72" value="<?= e($enc['pulse_rate']) ?>">
            <span class="muted" style="font-size: 11px;">bpm</span>
          </div>
        </div>

        <div class="field">
          <label>Body Temperature</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <input type="number" step="0.1" name="temperature_c" class="input" placeholder="36.8" value="<?= e($enc['temperature_c']) ?>">
            <span class="muted" style="font-size: 11px;">°C</span>
          </div>
        </div>

        <div class="field">
          <label>Oxygen Saturation (SpO2)</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <input type="number" name="spo2_percent" class="input" placeholder="98" value="<?= e($enc['spo2_percent']) ?>">
            <span class="muted" style="font-size: 11px;">%</span>
          </div>
        </div>

        <div class="field">
          <label>Respiratory Rate</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <input type="number" name="respiratory_rate" class="input" placeholder="18" value="<?= e($enc['respiratory_rate']) ?>">
            <span class="muted" style="font-size: 11px;">breaths/min</span>
          </div>
        </div>

        <div class="field">
          <label>Triage Priority Classification</label>
          <select name="triage_category" class="input">
            <option value="standard" <?= $enc['triage_category'] === 'standard' ? 'selected' : '' ?>>Standard (Green)</option>
            <option value="urgent" <?= $enc['triage_category'] === 'urgent' ? 'selected' : '' ?>>Urgent (Yellow)</option>
            <option value="very_urgent" <?= $enc['triage_category'] === 'very_urgent' ? 'selected' : '' ?>>Very Urgent (Orange)</option>
            <option value="emergency" <?= $enc['triage_category'] === 'emergency' ? 'selected' : '' ?>>Resuscitation / Emergency (Red)</option>
            <option value="non_urgent" <?= $enc['triage_category'] === 'non_urgent' ? 'selected' : '' ?>>Non-Urgent (Blue)</option>
          </select>
        </div>

        <div class="field">
          <label>Weight (kg)</label>
          <input type="number" step="0.1" id="v_weight" name="weight_kg" class="input" placeholder="70.5" value="<?= e($enc['weight_kg']) ?>" oninput="calculateBmi()">
        </div>

        <div class="field">
          <label>Height (cm)</label>
          <input type="number" step="0.1" id="v_height" name="height_cm" class="input" placeholder="175" value="<?= e($enc['height_cm']) ?>" oninput="calculateBmi()">
        </div>

        <div class="field full" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
          <div>
            <span class="dim" style="font-size: 12px;">Calculated Body Mass Index (BMI):</span>
            <div id="bmi_display" style="font-size: 20px; font-weight: 800; color: var(--brand);">
              <?= $enc['bmi'] ? $enc['bmi'] . ' kg/m²' : '--' ?>
            </div>
          </div>
          <button type="submit" class="btn btn-sm btn-primary">
            <span>Save Vital Signs</span>
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Right Column: Doctor Examination & Notes -->
  <div class="card">
    <div class="card-head">
      <h3>2. Clinical Notes & Diagnoses</h3>
    </div>

    <form method="POST" action="<?= route('consultations.notes', ['id' => $enc['uuid'] ?? $enc['id']]) ?>">
      <?= csrf_field() ?>
      <div class="field" style="margin-bottom: 12px;">
        <label>Presenting Complaints & History of Illness</label>
        <textarea name="presenting_complaints" class="input" rows="3" placeholder="Chief complaint, duration, onset, severity..."><?= e($enc['presenting_complaints']) ?></textarea>
      </div>

      <div class="field" style="margin-bottom: 12px;">
        <label>Physical Examination & System Review</label>
        <textarea name="physical_examination" class="input" rows="2" placeholder="General condition, chest, abdomen, CNS observations..."><?= e($enc['physical_examination']) ?></textarea>
      </div>

      <div class="grid grid-2" style="gap: 12px; margin-bottom: 12px;">
        <div class="field">
          <label>Working / Provisional Diagnosis</label>
          <input type="text" name="working_diagnosis" class="input" placeholder="e.g. Acute Bronchitis" value="<?= e($enc['working_diagnosis']) ?>">
        </div>
        <div class="field">
          <label>Confirmed Diagnosis (with ICD-10 if applicable)</label>
          <input type="text" name="confirmed_diagnosis" class="input" placeholder="e.g. J20.9 Bronchitis" value="<?= e($enc['confirmed_diagnosis']) ?>">
        </div>
      </div>

      <div class="field" style="margin-bottom: 16px;">
        <label>Treatment Plan & Doctor's Instructions</label>
        <textarea name="treatment_plan" class="input" rows="2" placeholder="Clinical management plan, dietary advice, follow-up instructions..."><?= e($enc['treatment_plan']) ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary">
          <span>Save Clinical Assessment</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Section 3: Diagnostic Tests & Lab Workstation -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head">
    <div>
      <h3 style="margin: 0;">3. Laboratory & Diagnostic Orders</h3>
      <p class="muted" style="margin: 0; font-size: 13px;">Direct integration with LIMS and radiology. Results flow back into this EMR record in real time.</p>
    </div>
    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('newLabOrderModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Order Diagnostic Test</span>
    </button>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Test Name</th>
          <th>Category</th>
          <th>Specimen / Ref Range</th>
          <th>Result Value</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="6" class="dim" style="text-align: center; padding: 20px;">
              No diagnostic tests ordered for this encounter yet. Click "Order Diagnostic Test" above.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="mono font-bold"><?= e($o['order_number']) ?></td>
              <td><strong><?= e($o['test_name']) ?></strong></td>
              <td><span class="badge badge-brand"><?= strtoupper(e($o['test_category'])) ?></span></td>
              <td class="dim" style="font-size: 12px;"><?= e($o['reference_range'] ?: 'Standard') ?></td>
              <td>
                <?php if ($o['result_value']): ?>
                  <strong style="font-size: 15px; color: <?= $o['is_abnormal'] ? '#ef4444' : 'var(--brand)' ?>;">
                    <?= e($o['result_value']) ?> <?= e($o['unit']) ?>
                  </strong>
                  <?php if ($o['is_abnormal']): ?>
                    <span class="badge badge-danger" style="margin-left: 6px;">ABNORMAL</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="dim" style="font-size: 12px;">Awaiting Lab Processing</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= $o['result_status'] === 'verified' ? 'success' : 'warning' ?>">
                  <?= ucfirst($o['result_status'] ?: 'ordered') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Section 4: Electronic Prescriptions & Pharmacy Dispense -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0;">4. Electronic Prescriptions (Rx)</h3>
      <p class="muted" style="margin: 0; font-size: 13px;">Prescriptions are automatically queued to the hospital pharmacy for dispensing.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <a href="<?= route('pharmacy.pos', ['patient_id' => $enc['patient_uuid'] ?? $enc['patient_id']]) ?>" class="btn btn-sm btn-ghost" style="color: var(--brand-2); font-weight: 700; border-color: rgba(245, 158, 11, 0.4);" target="_blank" title="Open Pharmacy POS linked directly to this patient">
        <span>💊 Open POS for this Patient</span>
      </a>
      <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('newRxModal').classList.remove('hidden')">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Prescribe Medication</span>
      </button>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Rx #</th>
          <th>Medication</th>
          <th>Dosage & Frequency</th>
          <th>Duration</th>
          <th>Quantity</th>
          <th>Instructions</th>
          <th>Dispense Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($prescriptions)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 20px;">
              No medications prescribed yet. Click "Prescribe Medication" above.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($prescriptions as $rx): ?>
            <tr>
              <td class="mono font-bold"><?= e($rx['rx_number']) ?></td>
              <td><strong><?= e($rx['drug_name']) ?></strong></td>
              <td><?= e($rx['dose']) ?> • <?= e($rx['frequency']) ?></td>
              <td><?= $rx['duration_days'] ?> days</td>
              <td><span class="badge badge-info"><?= $rx['quantity_prescribed'] ?> units</span></td>
              <td class="dim" style="font-size: 12px;"><?= e($rx['instructions']) ?></td>
              <td>
                <span class="badge badge-<?= $rx['item_status'] === 'dispensed' ? 'success' : 'warning' ?>">
                  <?= ucfirst(str_replace('_', ' ', $rx['item_status'] ?: 'pending')) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Section 5: Prior Visits & Longitudinal Medical Records History -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid #6366f1;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
        <svg style="width: 20px; height: 20px; color: #6366f1;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Patient Prior Visit History & Clinical Records</span>
        <span class="badge <?= count($pastVisits) > 0 ? 'badge-brand' : 'badge-ghost' ?>"><?= count($pastVisits) ?> Previous Visits</span>
      </h3>
      <p class="muted" style="margin: 2px 0 0; font-size: 13px;">Full chronological longitudinal history of previous treatments, diagnoses, doctor notes, and past prescriptions.</p>
    </div>
  </div>

  <div style="padding: 16px 22px;">
    <?php if (empty($pastVisits)): ?>
      <div class="dim" style="text-align: center; padding: 24px;">
        ℹ️ No prior consultation encounters recorded for this patient yet. This is their initial visit on file.
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php foreach ($pastVisits as $idx => $pv): ?>
          <details class="card" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); padding: 0; border-radius: 8px;" <?= $idx === 0 ? 'open' : '' ?>>
            <summary style="padding: 14px 18px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <span class="badge badge-brand" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($pv['created_at'])) ?></span>
                <strong style="font-size: 14px;">Visit #<?= $pv['id'] ?> — <?= e($pv['working_diagnosis'] ?: ($pv['presenting_complaints'] ?: 'General Consultation')) ?></strong>
                <span class="dim" style="font-size: 12px;">Dr. <?= e($pv['doctor_name'] ?: 'Attending Doctor') ?></span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <?php if (!empty($pv['confirmed_diagnosis'])): ?>
                  <span class="badge badge-success" style="font-size: 10px;">Dx: <?= e($pv['confirmed_diagnosis']) ?></span>
                <?php endif; ?>
                <?php if ($pv['rx_count'] > 0): ?>
                  <span class="badge badge-info" style="font-size: 10px;">💊 <?= $pv['rx_count'] ?> Rx Prescribed</span>
                <?php endif; ?>
                <?php if ($pv['lab_orders_count'] > 0): ?>
                  <span class="badge badge-warning" style="font-size: 10px;">🧪 <?= $pv['lab_orders_count'] ?> Labs</span>
                <?php endif; ?>
                <a href="<?= route('consultations.show', ['id' => $pv['uuid'] ?? $pv['id']]) ?>" class="btn btn-sm btn-ghost" style="font-size: 11px; padding: 2px 8px;" target="_blank">View Visit ↗</a>
              </div>
            </summary>

            <div style="padding: 14px 18px; border-top: 1px solid var(--border); font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
              <!-- Complaints & Notes -->
              <div class="grid grid-2" style="gap: 16px;">
                <div>
                  <label class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Presenting Complaints / History</label>
                  <div style="margin-top: 4px;"><?= nl2br(e($pv['presenting_complaints'] ?: ($pv['history_of_illness'] ?: 'No complaints logged.'))) ?></div>
                </div>
                <div>
                  <label class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Clinical Notes & Treatment Plan</label>
                  <div style="margin-top: 4px;"><?= nl2br(e($pv['doctor_notes'] ?: ($pv['treatment_plan'] ?: 'No treatment notes logged.'))) ?></div>
                </div>
              </div>

              <!-- Past Vitals -->
              <?php if ($pv['systolic_bp'] || $pv['temperature_c'] || $pv['pulse_rate'] || $pv['bmi']): ?>
                <div style="background: rgba(0, 0, 0, 0.15); padding: 8px 12px; border-radius: 6px; display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px;">
                  <?php if ($pv['systolic_bp'] && $pv['diastolic_bp']): ?>
                    <span>BP: <strong><?= $pv['systolic_bp'] ?>/<?= $pv['diastolic_bp'] ?> mmHg</strong></span>
                  <?php endif; ?>
                  <?php if ($pv['temperature_c']): ?>
                    <span>Temp: <strong><?= $pv['temperature_c'] ?> °C</strong></span>
                  <?php endif; ?>
                  <?php if ($pv['pulse_rate']): ?>
                    <span>Pulse: <strong><?= $pv['pulse_rate'] ?> bpm</strong></span>
                  <?php endif; ?>
                  <?php if ($pv['bmi']): ?>
                    <span>BMI: <strong><?= $pv['bmi'] ?></strong> (<?= $pv['weight_kg'] ?> kg)</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Prescribed Drugs in this past visit -->
              <?php if (!empty($pv['prescriptions'])): ?>
                <div>
                  <label class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--brand-2);">Medications Prescribed on this Visit:</label>
                  <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                    <?php foreach ($pv['prescriptions'] as $prx): ?>
                      <div style="padding: 6px 10px; background: rgba(245, 158, 11, 0.06); border-radius: 4px; border-left: 3px solid var(--brand-2); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                          <strong><?= e($prx['medicine_name']) ?></strong>
                          <span class="dim" style="margin-left: 6px;"><?= e($prx['dosage']) ?> • <?= e($prx['frequency']) ?> • <?= $prx['duration_days'] ?> days</span>
                          <?php if (!empty($prx['instructions'])): ?>
                            <span class="dim" style="font-style: italic; margin-left: 6px;">(<?= e($prx['instructions']) ?>)</span>
                          <?php endif; ?>
                        </div>
                        <span class="badge badge-info" style="font-size: 10px;"><?= $prx['qty_prescribed'] ?> units</span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Lab Tests from this past visit -->
              <?php if (!empty($pv['lab_results'])): ?>
                <div>
                  <label class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Laboratory Findings on this Visit:</label>
                  <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                    <?php foreach ($pv['lab_results'] as $plr): ?>
                      <div style="padding: 6px 10px; background: rgba(0,0,0,0.1); border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                          <strong><?= e($plr['test_name']) ?></strong>: 
                          <span style="color: <?= $plr['is_abnormal'] ? '#ef4444' : 'var(--brand)' ?>; font-weight: 700;">
                            <?= e($plr['observed_value'] ?: 'Result recorded') ?> <?= e($plr['unit']) ?>
                          </span>
                          <?php if ($plr['reference_range']): ?>
                            <span class="dim mono" style="font-size: 10px; margin-left: 6px;">(Ref: <?= e($plr['reference_range']) ?>)</span>
                          <?php endif; ?>
                        </div>
                        <?php if ($plr['is_abnormal']): ?>
                          <span class="badge badge-danger" style="font-size: 9px;">ABNORMAL</span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Encounter Conclusion Bar -->
<div class="card" style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px;">
  <div>
    <strong>Conclude Consultation Encounter</strong>
    <p class="muted" style="margin: 2px 0 0; font-size: 13px;">Finalizes clinical notes, closes active queue ticket, and routes patient to pharmacy or cashier.</p>
  </div>
  <form method="POST" action="<?= route('consultations.complete', ['id' => $enc['uuid'] ?? $enc['id']]) ?>" style="margin: 0;">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Conclude this consultation encounter?')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>Finalize & Conclude Consultation</span>
    </button>
  </form>
</div>

<!-- Modal: Order Diagnostic Test -->
<div id="newLabOrderModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Order Diagnostic Test</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newLabOrderModal').classList.add('hidden')">&times;</button>
    </div>
    <form method="POST" action="<?= route('consultations.lab.order', ['id' => $enc['uuid'] ?? $enc['id']]) ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Select Test from Catalog *</label>
          <select name="test_id" class="input" required>
            <?php foreach ($testCatalog as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['code']) ?> — <?= e($t['name']) ?> (<?= strtoupper($t['category']) ?> • <?= format_minor($t['price_minor'], tenant('currency')) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Clinical Priority</label>
          <select name="priority" class="input">
            <option value="routine">Routine</option>
            <option value="urgent">Urgent</option>
            <option value="stat">STAT / Immediate</option>
          </select>
        </div>
        <div class="field full">
          <label>Clinical Indication / Notes for Lab</label>
          <input type="text" name="clinical_notes" class="input" placeholder="e.g. Rule out bacterial infection, check electrolyte imbalance">
        </div>
      </div>
      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newLabOrderModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Test Request</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Prescribe Medication -->
<div id="newRxModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 560px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Electronic Prescription (Rx)</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newRxModal').classList.add('hidden')">&times;</button>
    </div>
    <form method="POST" action="<?= route('consultations.rx.add', ['id' => $enc['uuid'] ?? $enc['id']]) ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Choose Hospital Medication *</label>
          <select name="product_id" class="input" onchange="onSelectProduct(this)">
            <option value="">-- Choose from Pharmacy Formulary --</option>
            <?php foreach ($pharmacyProducts as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= e($pr['name']) ?> (<?= e($pr['strength'] ?: $pr['form']) ?>) • Stock: <?= $pr['stock_qty'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field full">
          <label>Or Enter Custom Drug Name</label>
          <input type="text" id="custom_drug_name" name="drug_name" class="input" placeholder="e.g. Amoxicillin 500mg capsules">
        </div>
        <div class="field">
          <label>Dose</label>
          <input type="text" name="dose" class="input" value="1 tablet / capsule">
        </div>
        <div class="field">
          <label>Frequency</label>
          <select name="frequency" class="input">
            <option value="OD (Once daily)">OD (Once daily)</option>
            <option value="BD (Twice daily)">BD (Twice daily)</option>
            <option value="TDS (3x daily)" selected>TDS (3x daily)</option>
            <option value="QDS (4x daily)">QDS (4x daily)</option>
            <option value="PRN (As needed)">PRN (As needed)</option>
            <option value="Stat (Immediately)">Stat (Immediately)</option>
          </select>
        </div>
        <div class="field">
          <label>Duration (Days)</label>
          <input type="number" name="duration_days" class="input" value="5" min="1" max="90">
        </div>
        <div class="field">
          <label>Dispense Quantity</label>
          <input type="number" name="quantity_prescribed" class="input" value="15" min="1">
        </div>
        <div class="field full">
          <label>Usage Instructions for Patient</label>
          <input type="text" name="instructions" class="input" value="Take orally after meals with water. Complete full course.">
        </div>
      </div>
      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newRxModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Transmit Rx to Pharmacy</button>
      </div>
    </form>
  </div>
</div>

<script>
function calculateBmi() {
  const w = parseFloat(document.getElementById('v_weight').value);
  const h = parseFloat(document.getElementById('v_height').value);
  const disp = document.getElementById('bmi_display');
  if (w > 0 && h > 0) {
    const hm = h / 100.0;
    const bmi = (w / (hm * hm)).toFixed(1);
    disp.textContent = bmi + ' kg/m²';
  } else {
    disp.textContent = '--';
  }
}

function onSelectProduct(sel) {
  const custom = document.getElementById('custom_drug_name');
  if (sel.value) {
    custom.value = sel.options[sel.selectedIndex].text.split('•')[0].trim();
  }
}
</script>