<?php $this->layout('layouts.app', ['title' => 'OPD & Clinical Consultations']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Outpatient Services / Consultation & Triage</div>
    <h1>Clinical Consultations & Triage Queue</h1>
    <p class="muted">Physician examination, digital triage vitals, clinical diagnostic orders, and electronic prescriptions.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newConsultModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>🩺 Start New Consultation</span>
    </button>
    <a href="<?= route('queue.index') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
      <span>Queue Desk</span>
    </a>
  </div>
</div>

<!-- Active Patients Waiting for Doctor / In Consultation -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head">
    <h3>Active Clinical Worklist (<?= count($encounters) ?> Patients Waiting / In-Consultation)</h3>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Triage Priority</th>
          <th>Ticket / MRN</th>
          <th>Patient Name</th>
          <th>Demographics</th>
          <th>Vitals Summary</th>
          <th>Encounter Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($encounters)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 36px;">
              No patients currently in consultation queue. Click "🩺 Start New Consultation" above to begin consulting any patient.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($encounters as $e): ?>
            <tr>
              <td>
                <?php
                  $triageBadge = match($e['triage_category']) {
                    'emergency' => 'danger',
                    'very_urgent' => 'danger',
                    'urgent' => 'warning',
                    default => 'brand'
                  };
                ?>
                <span class="badge badge-<?= $triageBadge ?>" style="font-weight: 700; text-transform: uppercase;">
                  <?= e($e['triage_category']) ?>
                </span>
              </td>
              <td>
                <?php if ($e['ticket_number']): ?>
                  <span class="badge badge-brand mono" style="font-size: 13px; font-weight: 700;"><?= e($e['ticket_number']) ?></span>
                <?php endif; ?>
                <div class="mono dim" style="font-size: 11px; margin-top: 2px;">MRN: <?= e($e['mrn']) ?></div>
              </td>
              <td>
                <strong style="font-size: 15px;"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></strong>
                <div class="dim" style="font-size: 12px;"><?= e($e['phone'] ?: 'No phone recorded') ?></div>
              </td>
              <td>
                <span><?= ucfirst($e['sex']) ?> • <?= age_from_dob($e['dob']) ? age_from_dob($e['dob']) . ' yrs' : 'Age N/A' ?></span>
              </td>
              <td>
                <?php if ($e['systolic_bp'] || $e['temperature_c'] || $e['pulse_rate']): ?>
                  <span class="mono" style="font-size: 12px;">
                    BP: <strong><?= $e['systolic_bp'] ?>/<?= $e['diastolic_bp'] ?></strong> •
                    HR: <strong><?= $e['pulse_rate'] ?></strong> •
                    T: <strong><?= $e['temperature_c'] ?>°C</strong>
                    <?php if ($e['bmi']): ?> • BMI: <strong><?= $e['bmi'] ?></strong><?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="dim" style="font-size: 12px;">Vitals not recorded yet</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= $e['status'] === 'triaged' ? 'info' : ($e['status'] === 'consulting' ? 'brand' : 'ghost') ?>">
                  <?= ucfirst(str_replace('_', ' ', $e['status'])) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="<?= route('consultations.show', ['id' => $e['uuid'] ?? $e['id']]) ?>" class="btn btn-sm btn-primary">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  <span>Open Doctor EMR</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recently Completed Consultations -->
<div class="card">
  <div class="card-head">
    <h3>Recently Completed Consultations</h3>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>MRN</th>
          <th>Patient Name</th>
          <th>Doctor</th>
          <th>Diagnosis</th>
          <th>Date Concluded</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentCompleted)): ?>
          <tr>
            <td colspan="6" class="dim" style="text-align: center; padding: 24px;">No completed consultations yet today.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentCompleted as $rc): ?>
            <tr>
              <td class="mono font-bold"><?= e($rc['mrn']) ?></td>
              <td><strong><?= e($rc['first_name'] . ' ' . $rc['last_name']) ?></strong></td>
              <td><?= e($rc['doctor_name'] ?: 'Attending Physician') ?></td>
              <td><?= e($rc['confirmed_diagnosis'] ?: ($rc['working_diagnosis'] ?: 'Routine OPD Consultation')) ?></td>
              <td class="dim" style="font-size: 12px;"><?= date('d M Y H:i', strtotime($rc['updated_at'])) ?></td>
              <td style="text-align: right;">
                <a href="<?= route('consultations.show', ['id' => $rc['uuid'] ?? $rc['id']]) ?>" class="btn btn-sm btn-ghost">
                  <span>View Record</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Start New Patient Consultation -->
<div id="newConsultModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 580px; padding: 26px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Start Patient Consultation</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newConsultModal').classList.add('hidden')">&times;</button>
    </div>

    <!-- Mode Selector Tabs: Existing Search vs Quick Register -->
    <div style="display: flex; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <button type="button" id="tabSearchBtn" class="btn btn-sm btn-primary" onclick="switchConsultTab('search')">
        🔍 Find Existing Patient
      </button>
      <button type="button" id="tabQuickRegBtn" class="btn btn-sm btn-ghost" onclick="switchConsultTab('quickReg')">
        ➕ Register New Patient
      </button>
    </div>

    <!-- Search Section -->
    <div id="consultSearchSection">
      <form method="POST" action="<?= route('consultations.start') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field full">
            <label style="display: flex; justify-content: space-between;">
              <span>Search Patient (Name, ID Number, Phone, or MRN) *</span>
              <a href="javascript:void(0)" onclick="switchConsultTab('quickReg')" style="font-size: 11px; color: var(--brand);">Not found? Register here</a>
            </label>
            <input type="text" id="consult_search_input" class="input" placeholder="Click to view recent patients or type name, ID, phone..." onfocus="searchConsultPatient(this.value)" onclick="searchConsultPatient(this.value)" oninput="searchConsultPatient(this.value)" autofocus autocomplete="off">
            <input type="hidden" id="consult_patient_id" name="patient_id" required>
            <div id="consult_search_results" style="margin-top: 8px; max-height: 200px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; background: var(--surface);"></div>
          </div>

          <div id="pickedPatientCard" class="field full" style="display: none; padding: 14px; background: rgba(20, 184, 166, 0.08); border: 1px solid var(--brand); border-radius: 8px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--brand);">Selected Patient for EMR Desk:</div>
            <strong id="pickedName" style="font-size: 17px;"></strong>
            <div id="pickedMeta" class="dim mono" style="font-size: 12px; margin-top: 4px;"></div>
          </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
          <button type="button" class="btn btn-ghost" onclick="document.getElementById('newConsultModal').classList.add('hidden')">Cancel</button>
          <button type="submit" id="btnLaunchConsult" class="btn btn-primary" disabled>
            <span>Launch Doctor EMR Desk &rarr;</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Quick Register Section (Without leaving page) -->
    <div id="consultQuickRegSection" style="display: none;">
      <form id="quickRegForm" onsubmit="submitQuickPatient(event)">
        <?= csrf_field() ?>

        <!-- Patient Type Selection (Adult vs Child) -->
        <div style="margin-bottom: 14px; background: rgba(255,255,255,0.03); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
          <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Patient Classification *</label>
          <div style="display: flex; gap: 20px; align-items: center;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600;">
              <input type="radio" name="patient_type_toggle" id="pt_adult" value="adult" checked onchange="togglePatientType(this.value)">
              <span>🧑 Adult Patient</span>
            </label>
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--brand);">
              <input type="radio" name="patient_type_toggle" id="pt_child" value="child" onchange="togglePatientType(this.value)">
              <span>👶 Child / Minor (No National ID required)</span>
            </label>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>First Name *</label>
            <input type="text" id="qr_first_name" name="first_name" class="input" required placeholder="e.g. Samuel">
          </div>
          <div class="field">
            <label>Last Name *</label>
            <input type="text" id="qr_last_name" name="last_name" class="input" required placeholder="e.g. Mwangi">
          </div>
          <div class="field">
            <label>Gender *</label>
            <select id="qr_sex" name="sex" class="input" required>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="field">
            <label>Phone Number * <span class="dim font-normal" id="qr_phone_hint">(Parent/Guardian if minor)</span></label>
            <input type="text" id="qr_phone" name="phone" class="input" required placeholder="e.g. +254 712 345 678">
          </div>
          <div class="field">
            <label>National ID / Passport # <span class="dim font-normal" id="qr_id_optional_label">(Optional)</span></label>
            <input type="text" id="qr_national_id" name="national_id" class="input" placeholder="e.g. 29384756 (optional)">
          </div>
          <div class="field" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 6px;">
            <div>
              <label>Date of Birth</label>
              <input type="date" id="qr_dob" name="dob" class="input" oninput="document.getElementById('qr_age').value = window.calculateAge(this.value)">
            </div>
            <div>
              <label>Age (Yrs)</label>
              <input type="number" id="qr_age" class="input" placeholder="Yrs" min="0" max="130" oninput="if(this.value && !document.getElementById('qr_dob').value) document.getElementById('qr_dob').value = window.estimateDobFromAge(this.value)" style="text-align: center;">
            </div>
          </div>
          <div class="field full" style="grid-column: 1 / -1; margin-top: 4px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" id="qr_is_child" name="is_child" value="1" onchange="document.getElementById(this.checked ? 'pt_child' : 'pt_adult').checked = true; togglePatientType(this.checked ? 'child' : 'adult');" style="width: 16px; height: 16px;">
              <span>👶 Register as Child / Minor (using Parent's contact/phone number)</span>
            </label>
          </div>
        </div>

        <div id="quickRegAlert" style="margin-top: 12px; display: none; font-size: 12px;"></div>

        <div class="form-actions" style="margin-top: 20px;">
          <button type="button" class="btn btn-ghost" onclick="switchConsultTab('search')">Back to Search</button>
          <button type="submit" id="btnSubmitQuickPatient" class="btn btn-primary">
            <span>Register & Start Consultation &rarr;</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let consultTimer;
function switchConsultTab(tab) {
  const searchSection = document.getElementById('consultSearchSection');
  const regSection = document.getElementById('consultQuickRegSection');
  const searchBtn = document.getElementById('tabSearchBtn');
  const regBtn = document.getElementById('tabQuickRegBtn');

  if (tab === 'search') {
    searchSection.style.display = 'block';
    regSection.style.display = 'none';
    searchBtn.className = 'btn btn-sm btn-primary';
    regBtn.className = 'btn btn-sm btn-ghost';
    document.getElementById('consult_search_input').focus();
  } else {
    searchSection.style.display = 'none';
    regSection.style.display = 'block';
    searchBtn.className = 'btn btn-sm btn-ghost';
    regBtn.className = 'btn btn-sm btn-primary';
    document.getElementById('qr_first_name').focus();
  }
}

function searchConsultPatient(q) {
  clearTimeout(consultTimer);
  const container = document.getElementById('consult_search_results');
  consultTimer = setTimeout(async () => {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || '/patients/search';
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const pts = await res.json();
      if (!pts || !pts.length) {
        container.style.display = 'block';
        container.innerHTML = `
          <div style="padding: 12px; text-align: center;">
            <div class="dim" style="font-size: 13px;">No matching patient found${q ? ' for "' + q + '"' : ''}.</div>
            <button type="button" class="btn btn-sm btn-ghost" style="margin-top: 6px;" onclick="switchConsultTab('quickReg')">
              ➕ Register as New Patient
            </button>
          </div>`;
        return;
      }
      container.style.display = 'block';
      container.innerHTML = pts.map(p => `
        <div style="padding: 10px 14px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickConsultPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.mrn}', '${p.sex}', '${p.phone || ''}', '${p.national_id || ''}', ${p.age || 'null'})">
          <div>
            <strong style="font-size: 14px;">${p.first_name} ${p.last_name}</strong>
            <div class="dim mono" style="font-size: 11px; margin-top: 2px;">
              MRN: <span style="color: var(--brand); font-weight: 700;">${p.mrn}</span>
              ${p.national_id ? ' • ID: ' + p.national_id : ''}
              ${p.age ? ' • ' + p.age + ' yrs' : ''}
              ${p.phone ? ' • Tel: ' + p.phone : ''}
            </div>
          </div>
          <span class="badge badge-brand">+ Select</span>
        </div>
      `).join('');
    } catch (e) {
      console.error(e);
    }
  }, 50);
}

function pickConsultPatient(id, name, mrn, sex, phone, nationalId) {
  document.getElementById('consult_patient_id').value = id;
  document.getElementById('consult_search_input').value = name + ' (' + mrn + ')';
  document.getElementById('consult_search_results').style.display = 'none';
  document.getElementById('pickedName').textContent = name;
  document.getElementById('pickedMeta').textContent = 'MRN: ' + mrn + (nationalId ? ' • Nat ID: ' + nationalId : '') + ' • ' + (sex || '') + (phone ? ' • Phone: ' + phone : '');
  document.getElementById('pickedPatientCard').style.display = 'block';
  document.getElementById('btnLaunchConsult').disabled = false;
}

function togglePatientType(type) {
  const isChild = (type === 'child');
  const childCb = document.getElementById('qr_is_child');
  if (childCb) childCb.checked = isChild;
  const idLabel = document.getElementById('qr_id_optional_label');
  const idInput = document.getElementById('qr_national_id');
  const phoneHint = document.getElementById('qr_phone_hint');
  if (isChild) {
    if (idLabel) idLabel.textContent = '(Not required for child/minor)';
    if (idInput) {
      idInput.placeholder = 'Child / Minor has no ID (Optional)';
      idInput.required = false;
    }
    if (phoneHint) phoneHint.textContent = '(Parent/Guardian phone)';
  } else {
    if (idLabel) idLabel.textContent = '(Optional)';
    if (idInput) {
      idInput.placeholder = 'e.g. 29384756 (optional)';
    }
    if (phoneHint) phoneHint.textContent = '(Patient or Contact)';
  }
}

async function submitQuickPatient(e) {
  e.preventDefault();
  const alertBox = document.getElementById('quickRegAlert');
  const btn = document.getElementById('btnSubmitQuickPatient');
  btn.disabled = true;
  btn.innerHTML = 'Registering...';
  alertBox.style.display = 'none';

  const isChild = document.getElementById('pt_child')?.checked || document.getElementById('qr_is_child')?.checked ? 1 : 0;
  const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || '';

  const payload = {
    _token: csrfToken,
    first_name: document.getElementById('qr_first_name').value.trim(),
    last_name: document.getElementById('qr_last_name').value.trim(),
    sex: document.getElementById('qr_sex').value,
    phone: document.getElementById('qr_phone').value.trim(),
    national_id: document.getElementById('qr_national_id').value.trim(),
    dob: document.getElementById('qr_dob').value,
    is_child: isChild,
  };

  try {
    const storeUrl = window.PATIENTS_QUICK_STORE_URL || '/patients/quick-store';
    const res = await fetch(storeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (res.ok && data.success && data.patient) {
      // Pick newly created patient
      switchConsultTab('search');
      pickConsultPatient(data.patient.id, data.patient.full_name, data.patient.mrn, data.patient.sex, data.patient.phone, data.patient.national_id);
    } else {
      alertBox.className = 'badge badge-danger';
      alertBox.style.display = 'block';
      alertBox.textContent = data.message || (res.status === 419 ? 'Your session token was refreshed. Please try again.' : 'Failed to create patient record.');
    }
  } catch (err) {
    alertBox.className = 'badge badge-danger';
    alertBox.style.display = 'block';
    alertBox.textContent = 'Server connection error during patient registration.';
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Register & Start Consultation &rarr;';
  }
}
</script>