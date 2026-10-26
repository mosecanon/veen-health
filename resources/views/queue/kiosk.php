<?php $this->layout('layouts.app', ['title' => 'Issue Queue Ticket']) ?>

<style>
  @media print {
    /* Hide everything on the page */
    body * {
      visibility: hidden !important;
    }
    /* Only display the ticket slip */
    #printSlip, #printSlip * {
      visibility: visible !important;
    }
    #printSlip {
      position: absolute !important;
      left: 0 !important;
      top: 0 !important;
      width: 80mm !important;
      margin: 0 !important;
      padding: 10px !important;
      box-shadow: none !important;
      border: none !important;
    }
  }
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('queue.index') ?>">Queue</a> / Dispenser</div>
    <h1>Ticket Dispenser & Patient Check-in</h1>
    <p class="muted">Issue queue tickets, record patient details into MPI, and print 80mm barcode slips.</p>
  </div>
</div>

<div class="grid grid-3" style="align-items: start;">
  <!-- Left 2 Cols: Department Queues Selector Form -->
  <div class="card card-pad span-2 no-print">
    <h3 style="margin-bottom: 16px;">1. Select Destination Department</h3>

    <form method="POST" action="<?= route('queue.tickets.issue') ?>" id="ticketForm">
      <?= csrf_field() ?>

      <!-- Big Touch Cards for Queues -->
      <div class="grid grid-2" style="margin-bottom: 22px;">
        <?php foreach ($queues as $i => $q): ?>
          <label style="cursor: pointer;">
            <input type="radio" name="queue_id" value="<?= $q['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> style="display: none;" onchange="updateQueueSelection(this, '<?= e($q['color']) ?>')">
            <div class="queue-choice" id="qc-<?= $q['id'] ?>" style="border: 2px solid <?= $i === 0 ? e($q['color']) : 'var(--border)' ?>; background: <?= $i === 0 ? e($q['color']) . '15' : 'var(--surface-2)' ?>; border-radius: var(--radius); padding: 18px; transition: all .2s;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="font-size: 16px; font-family: var(--font-display);"><?= e($q['name']) ?></strong>
                <span class="badge" style="background: <?= e($q['color']) ?>; color: #fff; font-weight: 700;"><?= e($q['prefix']) ?></span>
              </div>
              <div class="muted" style="font-size: 12.5px;">
                <?= $q['waiting_count'] ?> patients currently in line
              </div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Patient Record Mode Tabs -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">2. Patient Identification & Details</h3>
        <div class="tabs">
          <button type="button" class="active" id="tabQuickBtn" onclick="switchPatientMode('quick')">Quick Name</button>
          <button type="button" id="tabSearchBtn" onclick="switchPatientMode('search')">Search Existing (MRN)</button>
          <button type="button" id="tabFullBtn" onclick="switchPatientMode('full')">+ Register New Patient</button>
        </div>
      </div>

      <!-- Mode A: Quick Walk-in Name -->
      <div id="patientModeQuick" style="margin-bottom: 20px;">
        <div class="form-grid">
          <div class="field full">
            <label for="customer_name">Patient / Walk-in Name</label>
            <input type="text" id="customer_name" name="customer_name" class="input" placeholder="e.g. Grace Wambui (or leave blank for Walk-in)">
          </div>
        </div>
      </div>

      <!-- Mode B: Search Existing Patient -->
      <div id="patientModeSearch" style="display: none; margin-bottom: 20px;">
        <div class="field" style="position: relative;">
          <label for="patientSearchInput">Search Patient by MRN, Name or Phone</label>
          <div class="input-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="patientSearchInput" class="input" placeholder="Type at least 2 letters (e.g. Grace, 0712, NAI)..." oninput="searchPatientAutocomplete(this.value)">
          </div>
          <div id="patientSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--surface-solid); border: 1px solid var(--border-strong); border-radius: 8px; z-index: 50; max-height: 220px; overflow-y: auto; box-shadow: var(--shadow);"></div>
        </div>
        <div id="selectedPatientBadge" style="display: none; margin-top: 10px; padding: 12px; border-radius: 8px; background: rgba(20, 184, 166, 0.12); border: 1px solid rgba(20, 184, 166, 0.3);">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <strong id="spName" style="color: var(--brand);"></strong>
              <div class="dim mono" id="spDetails" style="font-size: 12px;"></div>
            </div>
            <button type="button" class="btn btn-sm btn-ghost" onclick="clearSelectedPatient()">&times; Clear</button>
          </div>
        </div>
        <input type="hidden" name="patient_id" id="selectedPatientId" value="">
      </div>

      <!-- Mode C: Full Patient Registration Right from Kiosk -->
      <div id="patientModeFull" style="display: none; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; margin-bottom: 20px;">
        <div style="font-size: 13px; font-weight: 700; color: var(--brand); margin-bottom: 12px;">
          📋 Complete Patient Registration (Auto-generates MRN)
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="reg_first_name">First Name *</label>
            <input type="text" id="reg_first_name" name="first_name" class="input" placeholder="e.g. John">
          </div>
          <div class="field">
            <label for="reg_last_name">Last / Family Name *</label>
            <input type="text" id="reg_last_name" name="last_name" class="input" placeholder="e.g. Mwangi">
          </div>
          <div class="field">
            <label for="reg_phone">Phone Number</label>
            <input type="text" id="reg_phone" name="phone" class="input" placeholder="e.g. +254 712 345678">
          </div>
          <div class="field">
            <label for="reg_sex">Biological Sex *</label>
            <select id="reg_sex" name="sex" class="input">
              <option value="female">Female</option>
              <option value="male">Male</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="field">
            <label for="reg_dob">Date of Birth</label>
            <input type="date" id="reg_dob" name="dob" class="input">
          </div>
          <div class="field">
            <label for="reg_national_id">National ID / Passport</label>
            <input type="text" id="reg_national_id" name="national_id" class="input" placeholder="e.g. 29384751">
          </div>
          <div class="field full">
            <label for="reg_allergies">Known Allergies / Medical Alerts</label>
            <input type="text" id="reg_allergies" name="allergies" class="input" placeholder="e.g. Penicillin allergy, Asthma">
          </div>
        </div>
      </div>

      <!-- Priority & Triage Level -->
      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="field">
          <label for="priority">Priority / Triage Classification</label>
          <select id="priority" name="priority" class="input">
            <option value="0" selected>Normal Queue</option>
            <option value="1">Priority (Elderly, Maternity, Disability, Child)</option>
            <option value="2">🚨 Emergency (Immediate Calling)</option>
          </select>
        </div>

        <div class="field">
          <label for="notes">Clinical Reason / Chief Complaint</label>
          <input type="text" id="notes" name="notes" class="input" placeholder="e.g. Routine consultation, High fever, Prescription refill">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg btn-block" style="font-size: 16px; padding: 16px;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <span>Generate & Issue Ticket</span>
      </button>
    </form>
  </div>

  <!-- Right Col: Ticket Preview / Printable Slip -->
  <div class="card card-pad">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;" class="no-print">
      <h3 style="margin: 0;">Ticket Slip Preview</h3>
      <?php if ($recentTicket): ?>
        <a href="<?= route('queue.tickets.print', ['id' => $recentTicket['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost">
          ↗ Open Pure Slip
        </a>
      <?php endif; ?>
    </div>

    <?php if ($recentTicket): ?>
      <div id="printSlip" style="background: #ffffff; color: #111827; border-radius: 12px; padding: 22px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
        <div style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #111827;">
          <?= e(tenant('name')) ?>
        </div>
        <div style="font-size: 12px; color: #4b5563; font-weight: 600; margin-top: 2px;">
          <?= e($recentTicket['queue_name']) ?>
        </div>
        <div style="border-top: 1px dashed #d1d5db; margin: 12px 0;"></div>

        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280;">Your Ticket Number</div>
        <div class="mono" style="font-size: 46px; font-weight: 900; color: #0f172a; line-height: 1.05; margin: 6px 0;">
          <?= e($recentTicket['code']) ?>
        </div>

        <?php if ($recentTicket['first_name']): ?>
          <div style="font-size: 13px; font-weight: 700; color: #1f2937;">
            <?= e($recentTicket['first_name'] . ' ' . $recentTicket['last_name']) ?>
          </div>
          <div class="mono" style="font-size: 11px; color: #4b5563;">MRN: <?= e($recentTicket['mrn']) ?></div>
        <?php elseif ($recentTicket['customer_name']): ?>
          <div style="font-size: 13px; font-weight: 700; color: #1f2937;">
            <?= e($recentTicket['customer_name']) ?>
          </div>
        <?php endif; ?>

        <?php if ((int) $recentTicket['priority'] === 2): ?>
          <div style="display: inline-block; background: #fee2e2; color: #dc2626; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 99px; margin-top: 6px;">EMERGENCY</div>
        <?php elseif ((int) $recentTicket['priority'] === 1): ?>
          <div style="display: inline-block; background: #fef3c7; color: #d97706; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 99px; margin-top: 6px;">PRIORITY</div>
        <?php endif; ?>

        <!-- Code128 Barcode Generator Output -->
        <div style="margin: 10px 0;">
          <?= barcode($recentTicket['code'], 36, 2, false) ?>
        </div>

        <div style="border-top: 1px dashed #d1d5db; margin: 12px 0;"></div>
        <div style="font-size: 11px; color: #4b5563; line-height: 1.35;">
          Please take a seat in the waiting hall.<br>Watch the TV screen and listen for audio calling.
        </div>
        <div style="font-size: 10px; color: #9ca3af; margin-top: 8px;">
          <?= date('d M Y • H:i:s', strtotime($recentTicket['created_at'])) ?>
          <br>Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline; font-weight: 600;">Veen Africa</a>
        </div>
      </div>

      <div class="no-print" style="margin-top: 14px; display: flex; flex-direction: column; gap: 8px;">
        <button type="button" class="btn btn-primary btn-block btn-lg" onclick="printPureTicket(<?= $recentTicket['id'] ?>)">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Print Ticket Only (80mm)</span>
        </button>
      </div>
    <?php else: ?>
      <div class="empty no-print">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <p>No ticket recently issued.<br>Select a department on the left and click <strong>Generate & Issue Ticket</strong>.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  function updateQueueSelection(input, color) {
    document.querySelectorAll('.queue-choice').forEach(el => {
      el.style.borderColor = 'var(--border)';
      el.style.background = 'var(--surface-2)';
    });
    const target = document.getElementById('qc-' + input.value);
    if (target) {
      target.style.borderColor = color;
      target.style.background = color + '15';
    }
  }

  function switchPatientMode(mode) {
    document.getElementById('patientModeQuick').style.display = mode === 'quick' ? 'block' : 'none';
    document.getElementById('patientModeSearch').style.display = mode === 'search' ? 'block' : 'none';
    document.getElementById('patientModeFull').style.display = mode === 'full' ? 'block' : 'none';

    document.getElementById('tabQuickBtn').classList.toggle('active', mode === 'quick');
    document.getElementById('tabSearchBtn').classList.toggle('active', mode === 'search');
    document.getElementById('tabFullBtn').classList.toggle('active', mode === 'full');
  }

  let searchTimeout = null;
  function searchPatientAutocomplete(query) {
    clearTimeout(searchTimeout);
    const box = document.getElementById('patientSearchResults');
    if (!query || query.trim().length < 2) {
      box.style.display = 'none';
      return;
    }
    searchTimeout = setTimeout(async () => {
      try {
        const res = await fetch(`<?= route('patients.search') ?>?q=${encodeURIComponent(query.trim())}`);
        const data = await res.json();
        if (data.length === 0) {
          box.innerHTML = '<div style="padding:10px; font-size:12px; color:var(--text-muted);">No patients found.</div>';
          box.style.display = 'block';
          return;
        }
        box.innerHTML = data.map(p => `
          <div style="padding:10px 14px; border-bottom:1px solid var(--border); cursor:pointer; font-size:13px;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='transparent'" onclick="selectPatient(${p.id}, '${escapeHtml(p.first_name + ' ' + p.last_name)}', '${p.mrn}', '${p.phone || ''}')">
            <strong>${escapeHtml(p.first_name + ' ' + p.last_name)}</strong>
            <span class="badge badge-brand mono" style="font-size:11px; margin-left:6px;">${p.mrn}</span>
            <span class="dim" style="font-size:11.5px; margin-left:8px;">${p.phone || ''}</span>
          </div>
        `).join('');
        box.style.display = 'block';
      } catch (e) {
        console.error(e);
      }
    }, 200);
  }

  function selectPatient(id, name, mrn, phone) {
    document.getElementById('selectedPatientId').value = id;
    document.getElementById('spName').innerText = name;
    document.getElementById('spDetails').innerText = `MRN: ${mrn} • Phone: ${phone || 'N/A'}`;
    document.getElementById('selectedPatientBadge').style.display = 'block';
    document.getElementById('patientSearchResults').style.display = 'none';
    document.getElementById('patientSearchInput').value = name;
  }

  function clearSelectedPatient() {
    document.getElementById('selectedPatientId').value = '';
    document.getElementById('selectedPatientBadge').style.display = 'none';
    document.getElementById('patientSearchInput').value = '';
  }

  function printPureTicket(id) {
    // Open dedicated pure ticket slip window formatted for 80mm
    const w = 360;
    const h = 550;
    const left = (screen.width / 2) - (w / 2);
    const top = (screen.height / 2) - (h / 2);
    const win = window.open(`<?= url('queue/tickets') ?>/${id}/print`, 'printTicketSlip', `width=${w},height=${h},top=${top},left=${left},toolbar=0,scrollbars=1,status=0`);
    if (win) win.focus();
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[m]);
  }
</script>
