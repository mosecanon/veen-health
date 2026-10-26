<?php $this->layout('layouts.app', ['title' => 'Appointments & Patient Reservations']) ?>

<?php
$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
$nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
$nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;

$firstDayTimestamp = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = (int) date('t', $firstDayTimestamp);
$startDayOfWeek = (int) date('w', $firstDayTimestamp); // 0 (Sun) to 6 (Sat)
?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Appointments & Calendar</div>
    <h1>Patient Appointments & Scheduling</h1>
    <p class="muted">Doctor consultations, follow-up reservations, and automated clinical arrival reminders.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary" onclick="document.getElementById('newAppointmentModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Book New Appointment</span>
    </button>
  </div>
</div>

<!-- Today's Upcoming Arrivals Reminder Banner -->
<?php if (!empty($todayAppointments)): ?>
  <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--brand); background: rgba(20, 184, 166, 0.04);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px;">🔔</span>
        <div>
          <h3 style="margin: 0; font-size: 16px;">Today's Patient Schedule (<?= date('d M Y') ?>)</h3>
          <div class="dim" style="font-size: 12px;"><?= count($todayAppointments) ?> reservation(s) booked for today.</div>
        </div>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table" style="margin: 0;">
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient Name & MRN</th>
            <th>Attending Doctor</th>
            <th>Type</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($todayAppointments as $ta): ?>
            <tr>
              <td class="mono font-bold" style="color: var(--brand); font-size: 14px;"><?= date('H:i', strtotime($ta['start_time'])) ?></td>
              <td>
                <strong style="font-size: 14px;"><?= e($ta['first_name'] . ' ' . $ta['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($ta['mrn']) ?> • Tel: <?= e($ta['phone'] ?: 'N/A') ?></div>
              </td>
              <td><?= e($ta['doctor_name'] ?: 'Any Available Doctor') ?></td>
              <td><span class="badge badge-brand"><?= strtoupper(str_replace('_', ' ', $ta['type'])) ?></span></td>
              <td>
                <span class="badge badge-<?= $ta['status'] === 'confirmed' ? 'success' : ($ta['status'] === 'completed' ? 'info' : ($ta['status'] === 'cancelled' ? 'danger' : 'warning')) ?>">
                  <?= ucfirst($ta['status']) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <?php if ($ta['status'] === 'scheduled'): ?>
                    <form method="POST" action="<?= route('appointments.status', ['id' => $ta['id']]) ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="confirmed">
                      <button type="submit" class="btn btn-sm btn-ghost" title="Confirm Arrival">Confirm</button>
                    </form>
                  <?php endif; ?>

                  <form method="POST" action="<?= route('consultations.start') ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="patient_id" value="<?= $ta['patient_uuid'] ?? $ta['patient_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Start Consultation</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Month Calendar View -->
<div class="card" style="margin-bottom: 24px;">
  <!-- Month Nav Header -->
  <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid var(--border);">
    <h2 style="margin: 0; font-size: 20px;">
      <?= $monthNames[$currentMonth] ?> <?= $currentYear ?>
    </h2>
    <div style="display: flex; gap: 8px;">
      <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-sm btn-ghost">&larr; Previous</a>
      <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-sm btn-ghost">Current Month</a>
      <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-sm btn-ghost">Next &rarr;</a>
    </div>
  </div>

  <!-- Calendar Grid -->
  <div style="padding: 18px 24px;">
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; font-weight: 700; font-size: 12px; text-transform: uppercase; margin-bottom: 10px; color: var(--muted);">
      <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px;">
      <!-- Empty offset cells -->
      <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
        <div style="min-height: 90px; background: rgba(255,255,255,0.01); border: 1px dashed var(--border); border-radius: 8px; opacity: 0.3;"></div>
      <?php endfor; ?>

      <!-- Days of Month -->
      <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
        <?php
          $dayStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
          $dayApps = $calendarDays[$dayStr] ?? [];
          $isToday = ($dayStr === $todayDate);
        ?>
        <div style="min-height: 96px; padding: 8px; background: <?= $isToday ? 'rgba(20, 184, 166, 0.08)' : 'var(--surface)' ?>; border: 1px solid <?= $isToday ? 'var(--brand)' : 'var(--border)' ?>; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: <?= $isToday ? '800' : '600' ?>; font-size: 13px; color: <?= $isToday ? 'var(--brand)' : 'inherit' ?>;">
              <?= $day ?> <?= $isToday ? '<span class="badge badge-brand" style="font-size: 9px; padding: 1px 4px;">TODAY</span>' : '' ?>
            </span>
            <?php if (!empty($dayApps)): ?>
              <span class="badge badge-brand" style="font-size: 10px;"><?= count($dayApps) ?></span>
            <?php endif; ?>
          </div>

          <div style="margin-top: 6px; font-size: 11px; overflow: hidden; display: flex; flex-direction: column; gap: 4px;">
            <?php foreach (array_slice($dayApps, 0, 2) as $da): ?>
              <div style="white-space: nowrap; text-overflow: ellipsis; overflow: hidden; background: rgba(255,255,255,0.03); padding: 2px 4px; border-radius: 4px;" title="<?= e($da['first_name'] . ' ' . $da['last_name']) ?> (<?= date('H:i', strtotime($da['start_time'])) ?>)">
                <span class="mono" style="color: var(--brand);"><?= date('H:i', strtotime($da['start_time'])) ?></span> <?= e($da['last_name']) ?>
              </div>
            <?php endforeach; ?>
            <?php if (count($dayApps) > 2): ?>
              <span class="dim" style="font-size: 10px;">+<?= count($dayApps) - 2 ?> more</span>
            <?php endif; ?>
          </div>

          <div style="margin-top: 4px; text-align: right;">
            <button type="button" class="btn btn-sm btn-ghost" style="padding: 1px 6px; font-size: 10px;" onclick="openScheduleForDate('<?= $dayStr ?>')">
              + Book
            </button>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- All Monthly Bookings Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <h3 style="margin: 0;">All Appointments for <?= $monthNames[$currentMonth] ?> <?= $currentYear ?> (<?= count($appointments) ?>)</h3>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('appointmentsTable', 'appointments_schedule')" title="Export appointments to Excel / CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 260px;">
        <input type="text" class="input" placeholder="🔍 Search appointments..." onkeyup="filterTable(this, 'appointmentsTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="appointmentsTable">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Patient Name & MRN</th>
          <th>National ID & Phone</th>
          <th>Doctor</th>
          <th>Type & Notes</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="7" class="dim" style="text-align: center; padding: 32px;">No appointments recorded for this month.</td></tr>
        <?php else: ?>
          <?php foreach ($appointments as $a): ?>
            <tr>
              <td>
                <strong class="mono" style="color: var(--brand);"><?= date('d M Y', strtotime($a['appointment_date'])) ?></strong>
                <div class="dim mono" style="font-size: 12px;"><?= date('H:i', strtotime($a['start_time'])) ?></div>
              </td>
              <td>
                <strong style="font-size: 14px;"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($a['mrn']) ?></div>
              </td>
              <td>
                <div>ID: <strong><?= e($a['national_id'] ?: 'N/A') ?></strong></div>
                <div class="dim" style="font-size: 11px;">Tel: <?= e($a['phone'] ?: 'N/A') ?></div>
              </td>
              <td>
                <strong><?= e($a['doctor_name'] ?: 'Unassigned') ?></strong>
                <div class="dim" style="font-size: 11px;"><?= e($a['doctor_title'] ?: 'Medical Officer') ?></div>
              </td>
              <td>
                <span class="badge badge-brand"><?= strtoupper(str_replace('_', ' ', $a['type'])) ?></span>
                <?php if ($a['notes']): ?>
                  <div class="dim" style="font-size: 11px; margin-top: 2px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($a['notes']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= $a['status'] === 'confirmed' ? 'success' : ($a['status'] === 'completed' ? 'info' : ($a['status'] === 'cancelled' ? 'danger' : 'warning')) ?>">
                  <?= ucfirst($a['status']) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 4px;">
                  <?php if ($a['status'] !== 'completed' && $a['status'] !== 'cancelled'): ?>
                    <form method="POST" action="<?= route('appointments.status', ['id' => $a['id']]) ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="completed">
                      <button type="submit" class="btn btn-sm btn-ghost" title="Mark Completed">✓ Done</button>
                    </form>
                    <form method="POST" action="<?= route('appointments.status', ['id' => $a['id']]) ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="cancelled">
                      <button type="submit" class="btn btn-sm btn-ghost" style="color: #ef4444;" title="Cancel Booking">&times;</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Schedule New Appointment -->
<div id="newAppointmentModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Schedule Patient Appointment</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newAppointmentModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('appointments.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Find Patient (Search Name, National ID, Phone, MRN) *</label>
          <input type="text" id="app_patient_search" class="input" placeholder="Click to view recent patients or search name, ID, phone..." onfocus="searchAppPatient(this.value)" onclick="searchAppPatient(this.value)" oninput="searchAppPatient(this.value)" autocomplete="off">
          <input type="hidden" id="app_patient_id" name="patient_id" required>
          <div id="app_patient_results" style="margin-top: 6px; max-height: 180px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; background: var(--surface);"></div>
        </div>

        <div id="appPickedCard" class="field full" style="display: none; padding: 10px; background: rgba(20, 184, 166, 0.08); border: 1px solid var(--brand); border-radius: 8px;">
          <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--brand);">Selected Patient:</div>
          <strong id="appPickedName" style="font-size: 15px;"></strong>
          <div id="appPickedMeta" class="dim mono" style="font-size: 12px;"></div>
        </div>

        <div class="field full">
          <label>Attending Doctor / Specialist</label>
          <select name="doctor_user_id" class="input">
            <option value="">-- Any Available Doctor / General Pool --</option>
            <?php foreach ($doctors as $doc): ?>
              <option value="<?= $doc['id'] ?>"><?= e($doc['name']) ?> (<?= e($doc['title'] ?: 'Doctor') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Appointment Date *</label>
          <input type="date" id="app_date_input" name="appointment_date" class="input" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="field">
          <label>Start Time *</label>
          <input type="time" name="start_time" class="input" required value="09:00">
        </div>

        <div class="field">
          <label>Appointment Type *</label>
          <select name="type" class="input" required>
            <option value="consultation" selected>Consultation</option>
            <option value="follow_up">Follow-Up Review</option>
            <option value="review">Lab / Imaging Review</option>
            <option value="procedure">Minor Procedure</option>
            <option value="meeting">Patient Counseling / Meeting</option>
          </select>
        </div>

        <div class="field">
          <label>Subject / Title</label>
          <input type="text" name="title" class="input" value="Routine Clinical Consultation">
        </div>

        <div class="field full">
          <label>Clinical Notes / Reason for Reservation</label>
          <textarea name="notes" class="input" rows="2" placeholder="e.g. Post-discharge checkup and blood pressure monitoring"></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newAppointmentModal').classList.add('hidden')">Cancel</button>
        <button type="submit" id="btnSubmitApp" class="btn btn-primary" disabled>Schedule Reservation</button>
      </div>
    </form>
  </div>
</div>

<script>
let appSearchTimer;
function searchAppPatient(q) {
  clearTimeout(appSearchTimer);
  const container = document.getElementById('app_patient_results');
  appSearchTimer = setTimeout(async () => {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || '/patients/search';
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const pts = await res.json();
      if (!pts || !pts.length) {
        container.style.display = 'block';
        container.innerHTML = '<div class="dim" style="padding: 10px; font-size: 12px; text-align: center;">No matching patient found.</div>';
        return;
      }
      container.style.display = 'block';
      container.innerHTML = pts.map(p => `
        <div style="padding: 8px 12px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickAppPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.mrn}', '${p.national_id || ''}', '${p.phone || ''}', ${p.age || 'null'})">
          <div>
            <strong>${p.first_name} ${p.last_name}</strong>
            <div class="dim mono" style="font-size: 11px;">MRN: ${p.mrn} ${p.national_id ? ' • ID: ' + p.national_id : ''} ${p.age ? ' • ' + p.age + ' yrs' : ''} ${p.phone ? ' • ' + p.phone : ''}</div>
          </div>
          <span class="badge badge-brand">+ Pick</span>
        </div>
      `).join('');
    } catch (e) {
      console.error(e);
    }
  }, 50);
}

function pickAppPatient(id, name, mrn, nationalId, phone, age) {
  document.getElementById('app_patient_id').value = id;
  document.getElementById('app_patient_search').value = `${name} (${mrn})${age ? ' - ' + age + ' yrs' : ''}`;
  document.getElementById('app_patient_results').style.display = 'none';
  document.getElementById('appPickedName').textContent = name;
  document.getElementById('appPickedMeta').textContent = 'MRN: ' + mrn + (nationalId ? ' • ID: ' + nationalId : '') + (age ? ' • ' + age + ' yrs' : '') + (phone ? ' • Tel: ' + phone : '');
  document.getElementById('appPickedCard').style.display = 'block';
  document.getElementById('btnSubmitApp').disabled = false;
}

function openScheduleForDate(dateStr) {
  document.getElementById('app_date_input').value = dateStr;
  document.getElementById('newAppointmentModal').classList.remove('hidden');
}
</script>
