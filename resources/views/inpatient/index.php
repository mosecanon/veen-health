<?php $this->layout('layouts.app', ['title' => 'Inpatient Wards & Beds (IPD)']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Inpatient Department / Wards & Bed Board</div>
    <h1>Inpatient Wards & Real-Time Bed Board</h1>
    <p class="muted">Live bed occupancy, inpatient admissions, patient transfers, and discharge management.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-ghost" onclick="document.getElementById('newWardModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      <span>➕ Add New Ward & Beds</span>
    </button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('admitModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Admit Patient to Bed</span>
    </button>
  </div>
</div>

<!-- Bed KPIs -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m0 0h16m-16 0V4a2 2 0 012-2h12a2 2 0 012 2v13m0 0v3m-16 0h16M7 10h4m-4 4h8"/></svg>
    </div>
    <div class="label">Total Beds</div>
    <div class="value"><?= $stats['total_beds'] ?></div>
    <div class="delta"><?= count($wards) ?> Hospital Wards</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Available Beds</div>
    <div class="value"><?= $stats['available_beds'] ?></div>
    <div class="delta">Ready for immediate admission</div>
  </div>

  <div class="stat" style="--accent: #ef4444;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Occupied Beds</div>
    <div class="value"><?= $stats['occupied_beds'] ?></div>
    <div class="delta">Active admitted patients</div>
  </div>

  <div class="stat" style="--accent: #f59e0b;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
    </div>
    <div class="label">Occupancy Rate</div>
    <div class="value"><?= $stats['occupancy_rate'] ?>%</div>
    <div class="delta">Ward capacity utilization</div>
  </div>
</div>

<!-- Fast Inpatient & Ward Locator Search Bar -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--brand); padding: 18px 24px;">
  <div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center;">
    <div style="flex: 1; min-width: 280px;">
      <h3 style="margin: 0 0 6px 0; display: flex; align-items: center; gap: 8px;">
        <svg style="width: 20px; height: 20px; color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <span>Fast Inpatient Ward & Bed Locator</span>
      </h3>
      <p class="muted" style="margin: 0; font-size: 13px;">Instant search to locate which ward and bed any admitted patient is currently in.</p>
    </div>
    <div style="flex: 1.5; min-width: 320px;">
      <input type="text" id="wardPatientLocator" class="input" placeholder="🔍 Search patient by Name, MRN, Ward name, Bed #, or Diagnosis..." oninput="filterWardPatientLocator(this.value)" onkeyup="filterWardPatientLocator(this.value)" onclick="filterWardPatientLocator(this.value)" autocomplete="off" style="font-size: 14px; padding: 10px 14px;">
    </div>
  </div>
  <div id="wardLocatorResults" style="display: none; margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--border);"></div>
</div>

<!-- Active Admissions Table (Visible Prominently at the Top) -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0;">Currently Admitted Patients (<?= count($activeAdmissions) ?>)</h3>
      <span class="muted" style="font-size: 12px;">Complete register of all patients staying in hospital wards.</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <input type="text" id="admissionsSearchInput" class="input" placeholder="🔍 Filter admissions table..." oninput="tableSearch('admissionsTable', this.value)" onkeyup="tableSearch('admissionsTable', this.value)" style="width: 240px; padding: 6px 12px; font-size: 13px;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('admissionsTable', 'inpatient_admissions')">
        <span>📥 Export CSV</span>
      </button>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="admissionsTable">
      <thead>
        <tr>
          <th>Admission #</th>
          <th>Patient Name & MRN</th>
          <th>Ward & Bed</th>
          <th>Admitting Diagnosis</th>
          <th>Admitted On</th>
          <th>Doctor</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($activeAdmissions)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 32px;">
              No patients currently admitted in wards.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($activeAdmissions as $adm): ?>
            <tr>
              <td class="mono font-bold"><?= e($adm['admission_no']) ?></td>
              <td>
                <strong style="font-size: 15px;"><?= e($adm['first_name'] . ' ' . $adm['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($adm['mrn']) ?> • <?= ucfirst($adm['sex']) ?></div>
              </td>
              <td>
                <span class="badge badge-brand"><?= e($adm['ward_name']) ?></span>
                <strong class="mono" style="margin-left: 4px;"><?= e($adm['bed_number']) ?></strong>
              </td>
              <td><?= e($adm['admission_reason']) ?></td>
              <td class="dim" style="font-size: 12px;"><?= date('d M Y H:i', strtotime($adm['admission_date'])) ?></td>
              <td><?= e($adm['doctor_name'] ?: 'Attending Physician') ?></td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= route('radiology.index') ?>?inpatient=<?= e($adm['id']) ?>" class="btn btn-sm btn-ghost" style="color: #8b5cf6;" title="Order MRI or diagnostic imaging scan">
                    🩻 Order MRI / Scan
                  </a>
                  <button type="button" class="btn btn-sm btn-ghost" onclick="openDischargeModal(<?= htmlspecialchars(json_encode($adm), ENT_QUOTES, 'UTF-8') ?>)">
                    <span>Discharge</span>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Visual Bed Board By Ward (Collapsible) -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
        <span>Visual Bed Board & Ward Occupancy</span>
        <span class="badge badge-brand" style="font-size: 11px;"><?= count($wards) ?> Wards</span>
      </h3>
      <div class="muted" style="font-size: 12px; margin-top: 2px;">Real-time headcount and physical bed cards across each ward.</div>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <div style="display: flex; gap: 12px; font-size: 12px; margin-right: 8px;">
        <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span> Available</span>
        <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span> Occupied</span>
      </div>
      <button type="button" class="btn btn-sm btn-ghost" id="toggleWardsBtn" onclick="toggleWardsCollapse()" style="font-weight: 600;">
        <span>▲ Collapse Wards</span>
      </button>
    </div>
  </div>

  <div id="wardsCollapseContent" style="display: flex; flex-direction: column; gap: 24px; padding: 12px 0;">
    <?php foreach ($wards as $w): ?>
      <?php 
        $bedsInWard = $wardBeds[$w['id']] ?? []; 
        $wStat = $wardStats[$w['id']] ?? ['total_beds' => count($bedsInWard), 'occupied_beds' => 0, 'available_beds' => count($bedsInWard), 'occupancy_rate' => 0];
      ?>
      <div class="ward-block" data-ward-name="<?= strtolower(e($w['name'])) ?>" data-ward-code="<?= strtolower(e($w['code'])) ?>">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid var(--border); padding-bottom: 8px; gap: 12px;">
          <div>
            <strong style="font-size: 16px;"><?= e($w['name']) ?></strong>
            <span class="dim mono" style="font-size: 11px; margin-left: 6px;">(Code: <?= e($w['code']) ?> • Floor <?= $w['floor_number'] ?>)</span>
            <span class="muted" style="font-size: 12px; margin-left: 10px;">
              Rate: <strong><?= format_minor($bedsInWard[0]['daily_rate_minor'] ?? 0, tenant('currency')) ?></strong> / day
            </span>
          </div>
          <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge <?= $wStat['occupied_beds'] > 0 ? 'badge-brand' : 'badge-ghost' ?>" style="font-size: 12px; padding: 4px 10px;">
              👥 <strong><?= $wStat['occupied_beds'] ?></strong> Admitted Patients / <?= $wStat['total_beds'] ?> Total Beds
            </span>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 100px; height: 8px; background: rgba(0,0,0,0.06); border-radius: 4px; overflow: hidden;">
                <div style="width: <?= $wStat['occupancy_rate'] ?>%; height: 100%; background: <?= $wStat['occupancy_rate'] >= 90 ? '#ef4444' : ($wStat['occupancy_rate'] >= 50 ? '#f59e0b' : '#10b981') ?>; transition: width 0.3s ease;"></div>
              </div>
              <span class="dim mono" style="font-size: 11px; font-weight: 600;"><?= $wStat['occupancy_rate'] ?>%</span>
            </div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
          <?php foreach ($bedsInWard as $bed): ?>
            <?php $isOccupied = $bed['status'] === 'occupied'; ?>
            <div class="bed-card" data-bed-number="<?= strtolower(e($bed['bed_number'])) ?>" data-patient-name="<?= strtolower(e(($bed['first_name'] ?? '') . ' ' . ($bed['last_name'] ?? ''))) ?>" data-patient-mrn="<?= strtolower(e($bed['mrn'] ?? '')) ?>" style="border: 2px solid <?= $isOccupied ? '#ef4444' : '#10b981' ?>; background: <?= $isOccupied ? 'rgba(239, 68, 68, 0.04)' : 'rgba(16, 185, 129, 0.04)' ?>; border-radius: 8px; padding: 12px;">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong class="mono" style="font-size: 14px;"><?= e($bed['bed_number']) ?></strong>
                <span class="badge badge-<?= $isOccupied ? 'danger' : 'success' ?>" style="font-size: 10px;">
                  <?= strtoupper($bed['status']) ?>
                </span>
              </div>

              <?php if ($isOccupied): ?>
                <div style="margin-top: 8px;">
                  <strong style="font-size: 13px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= e($bed['first_name'] . ' ' . $bed['last_name']) ?>
                  </strong>
                  <div class="dim mono" style="font-size: 10px;">MRN: <?= e($bed['mrn']) ?></div>
                  <div class="dim" style="font-size: 10px; margin-top: 2px;">
                    Adm: <?= date('d M H:i', strtotime($bed['admission_date'])) ?>
                  </div>
                </div>
              <?php else: ?>
                <div style="margin-top: 14px; font-size: 11px; color: #10b981; font-weight: 600;">
                  Ready for Admission
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal: Admit Patient to Bed -->
<div id="admitModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Inpatient Bed Admission</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('admitModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('inpatient.admit') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Select Patient *</label>
          <input type="text" id="patient_search_input" class="input" placeholder="Click to view recent patients or search name, MRN, phone..." onfocus="searchInpatient(this.value)" onclick="searchInpatient(this.value)" oninput="searchInpatient(this.value)" autocomplete="off">
          <input type="hidden" id="admit_patient_id" name="patient_id" required>
          <div id="inpatient_search_results" style="margin-top: 6px; max-height: 180px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; background: var(--surface);"></div>
        </div>

        <div class="field full">
          <label>Select Available Bed *</label>
          <select name="bed_id" class="input" required>
            <?php if (empty($availableBeds)): ?>
              <option value="">No beds currently available</option>
            <?php else: ?>
              <?php foreach ($availableBeds as $ab): ?>
                <option value="<?= $ab['id'] ?>">Bed <?= e($ab['bed_number']) ?> — <?= e($ab['ward_name']) ?> (<?= format_minor($ab['daily_rate_minor'], tenant('currency')) ?>/day)</option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="field full">
          <label>Admitting Diagnosis / Indication *</label>
          <input type="text" name="admitting_diagnosis" class="input" required placeholder="e.g. Severe Pneumonia, Post-Op Care">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('admitModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Admit to Bed</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Discharge Patient -->
<div id="dischargeModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;" id="dischargeTitle">Discharge Inpatient</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('dischargeModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="dischargeForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Discharge Diagnosis *</label>
          <input type="text" id="dc_diagnosis" name="discharge_diagnosis" class="input" required>
        </div>

        <div class="field full">
          <label>Discharge Summary & Instructions *</label>
          <textarea name="discharge_summary" class="input" rows="3" required placeholder="Condition at discharge, take-home medication, follow-up date..."></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('dischargeModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Authorize Discharge</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add New Ward & Beds -->
<div id="newWardModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Add New Hospital Ward & Beds</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newWardModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('inpatient.wards.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Ward Name *</label>
          <input type="text" name="name" class="input" required placeholder="e.g. St. Luke Surgical Wing">
        </div>

        <div class="field">
          <label>Ward Code *</label>
          <input type="text" name="code" class="input" required placeholder="e.g. SURG-A">
        </div>

        <div class="field">
          <label>Floor Number *</label>
          <input type="number" name="floor_number" class="input" value="1" min="0" required>
        </div>

        <div class="field full">
          <label>Ward Category *</label>
          <select name="type" class="input" required>
            <option value="general_male">General Male Ward</option>
            <option value="general_female">General Female Ward</option>
            <option value="pediatric">Pediatric Ward</option>
            <option value="maternity">Maternity Ward</option>
            <option value="surgical" selected>Surgical Ward</option>
            <option value="icu">Intensive Care Unit (ICU)</option>
            <option value="hcu">High Care Unit (HCU)</option>
            <option value="private">Private VIP Room</option>
          </select>
        </div>

        <div class="field">
          <label>Bed Price per Day (<?= tenant('currency') ?>) *</label>
          <input type="number" step="0.01" name="price_per_day" class="input" required placeholder="e.g. 2500.00">
        </div>

        <div class="field">
          <label>Initial Bed Count *</label>
          <input type="number" name="number_of_beds" class="input" value="6" min="1" max="50" required>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newWardModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Ward & Provision Beds</button>
      </div>
    </form>
  </div>
</div>

<script>
let searchTimeout;
function searchInpatient(q) {
  clearTimeout(searchTimeout);
  const container = document.getElementById('inpatient_search_results');
  searchTimeout = setTimeout(async () => {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || '/patients/search';
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const pts = await res.json();
      if (!pts || !pts.length) {
        container.style.display = 'block';
        container.innerHTML = '<div class="dim" style="font-size: 12px; padding: 10px; text-align: center;">No matching patients found.</div>';
        return;
      }
      container.style.display = 'block';
      container.innerHTML = pts.map(p => `
        <div style="padding: 10px 12px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.mrn}', '${p.national_id || ''}', '${p.phone || ''}', ${p.age || 'null'})">
          <div>
            <strong style="font-size: 14px;">${p.first_name} ${p.last_name}</strong>
            <div class="dim mono" style="font-size: 11px;">
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

function pickPatient(id, name, mrn, nationalId, phone, age) {
  document.getElementById('admit_patient_id').value = id;
  document.getElementById('patient_search_input').value = `${name} (${mrn})${age ? ' - ' + age + ' yrs' : ''}`;
  document.getElementById('inpatient_search_results').style.display = 'none';
}

function openDischargeModal(adm) {
  document.getElementById('dischargeTitle').textContent = 'Discharge: ' + adm.first_name + ' ' + adm.last_name;
  document.getElementById('dc_diagnosis').value = adm.admission_reason || '';
  const baseUrl = (window.APP_BASE || '').replace(/\/$/, '');
  const admKey = adm.uuid || adm.id;
  document.getElementById('dischargeForm').action = baseUrl + '/inpatient/admissions/' + encodeURIComponent(admKey) + '/discharge';
  document.getElementById('dischargeModal').classList.remove('hidden');
}

const activeAdmissionsData = <?= json_encode($activeAdmissions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function filterWardPatientLocator(query) {
  const q = (query || '').trim().toLowerCase();
  const resContainer = document.getElementById('wardLocatorResults');
  
  // Also filter the table
  const searchInput = document.getElementById('admissionsSearchInput');
  if (searchInput) {
    searchInput.value = query;
  }
  tableSearch('admissionsTable', query);

  if (!q) {
    resContainer.style.display = 'none';
    resContainer.innerHTML = '';
    return;
  }

  const matches = activeAdmissionsData.filter(adm => {
    const fullName = ((adm.first_name || '') + ' ' + (adm.last_name || '')).toLowerCase();
    const mrn = (adm.mrn || '').toLowerCase();
    const ward = (adm.ward_name || '').toLowerCase();
    const bed = (adm.bed_number || '').toLowerCase();
    const reason = (adm.admission_reason || '').toLowerCase();
    return fullName.includes(q) || mrn.includes(q) || ward.includes(q) || bed.includes(q) || reason.includes(q);
  });

  resContainer.style.display = 'block';

  if (!matches.length) {
    resContainer.innerHTML = `<div class="dim" style="font-size: 13px; text-align: center; padding: 10px;">🔍 No admitted patient found matching "<strong>${query}</strong>" in any ward.</div>`;
    return;
  }

  resContainer.innerHTML = `
    <div style="font-size: 12px; font-weight: 700; color: var(--brand); margin-bottom: 8px;">
      FOUND ${matches.length} ADMITTED PATIENT${matches.length > 1 ? 'S' : ''}:
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px;">
      ${matches.map(m => `
        <div style="background: rgba(14, 116, 144, 0.06); border: 1px solid rgba(14, 116, 144, 0.2); border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <strong style="font-size: 14px; display: block;">${m.first_name} ${m.last_name}</strong>
            <div style="font-size: 11px; margin-top: 2px;">
              <span class="mono dim">MRN: ${m.mrn}</span> • 
              <span class="badge badge-brand" style="font-size: 10px;">${m.ward_name}</span>
              <strong class="mono" style="margin-left: 4px;">Bed ${m.bed_number}</strong>
            </div>
            ${m.admission_reason ? `<div class="dim" style="font-size: 11px; margin-top: 2px;">Reason: ${m.admission_reason}</div>` : ''}
          </div>
          <button type="button" class="btn btn-sm btn-ghost" onclick="focusBedCard('${m.bed_number}')" title="Highlight bed in visual board">
            📍 Bed View
          </button>
        </div>
      `).join('')}
    </div>
  `;
}

function focusBedCard(bedNumber) {
  const cards = document.querySelectorAll('.bed-card');
  let target = null;
  cards.forEach(c => {
    if ((c.dataset.bedNumber || '').toLowerCase() === (bedNumber || '').toLowerCase()) {
      target = c;
    }
  });

  if (target) {
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    target.style.boxShadow = '0 0 0 4px #0e7490';
    target.style.transition = 'box-shadow 0.3s ease';
    setTimeout(() => {
      target.style.boxShadow = '';
    }, 2500);
  }
}

function toggleWardsCollapse() {
  const content = document.getElementById('wardsCollapseContent');
  const btn = document.getElementById('toggleWardsBtn');
  if (!content) return;
  const isCollapsed = content.style.display === 'none';
  if (isCollapsed) {
    content.style.display = 'flex';
    btn.innerHTML = '<span>▲ Collapse Wards</span>';
    localStorage.setItem('inpatient_wards_collapsed', 'false');
  } else {
    content.style.display = 'none';
    btn.innerHTML = '<span>▼ Expand Wards Grid</span>';
    localStorage.setItem('inpatient_wards_collapsed', 'true');
  }
}

// Restore saved collapse preference
document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('inpatient_wards_collapsed') === 'true') {
    const content = document.getElementById('wardsCollapseContent');
    const btn = document.getElementById('toggleWardsBtn');
    if (content && btn) {
      content.style.display = 'none';
      btn.innerHTML = '<span>▼ Expand Wards Grid</span>';
    }
  }
});
</script>