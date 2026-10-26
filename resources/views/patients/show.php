<?php $this->layout('layouts.app', ['title' => $patient['first_name'] . ' ' . $patient['last_name']]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('patients.index') ?>">Patients</a> / Chart</div>
    <h1><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
    <p class="muted">MRN: <strong class="mono" style="color: var(--brand);"><?= e($patient['mrn']) ?></strong> • Registered <?= human_date($patient['created_at']) ?></p>
  </div>
  <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
    <form method="POST" action="<?= route('consultations.start') ?>" style="margin: 0;">
      <?= csrf_field() ?>
      <input type="hidden" name="patient_id" value="<?= $patient['uuid'] ?? $patient['id'] ?>">
      <button type="submit" class="btn btn-primary">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span>🩺 Start Consultation</span>
      </button>
    </form>
    <a href="<?= route('patients.edit', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost" title="Edit patient name, phone, demographics">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 15px; height: 15px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
      <span>Edit</span>
    </a>
    <a href="<?= route('patients.card', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
      <span>Print ID Card</span>
    </a>
    <a href="<?= route('queue.kiosk', ['patient_id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
      <span>Queue Ticket</span>
    </a>
    <?php if (!empty($patient['is_child']) || ($patient['dob'] && age_from_dob($patient['dob']) <= 12)): ?>
      <a href="<?= route('clinic.kids.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost" style="color: #0284c7; border-color: rgba(2, 132, 199, 0.4);" title="Open Child Welfare & Growth Chart">
        <span>👶 Kids Clinic</span>
      </a>
    <?php endif; ?>
    <?php if ($patient['sex'] === 'female' && (!$patient['dob'] || (age_from_dob($patient['dob']) >= 12 && age_from_dob($patient['dob']) <= 55))): ?>
      <a href="<?= route('clinic.maternal.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost" style="color: #ec4899; border-color: rgba(236, 72, 153, 0.4);" title="Open Maternal & Antenatal Care Chart">
        <span>🤰 Pregnant / ANC</span>
      </a>
    <?php endif; ?>
    <?php if (in_array(auth()->role ?? '', ['hospital_admin', 'super_admin'], true) || user_can('patients.delete')): ?>
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('deletePatientModal').classList.remove('hidden')" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);" title="Delete Patient Record">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 15px; height: 15px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <span>Delete</span>
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($patient['deleted_at'])): ?>
  <div class="card" style="margin-bottom: 24px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span style="font-size: 24px;">⚠️</span>
      <div>
        <strong style="color: var(--danger); font-size: 15px;">Archived / Soft-Deleted Patient Record</strong>
        <p class="muted" style="margin: 2px 0 0; font-size: 13px;">This patient was removed on <?= human_date($patient['deleted_at']) ?>. Clinical history, visits, lab tests and prescriptions are preserved for regulatory audit compliance.</p>
      </div>
    </div>
    <form method="POST" action="<?= route('patients.restore', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" style="margin: 0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary btn-sm">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span>Restore Patient Record</span>
      </button>
    </form>
  </div>
<?php endif; ?>

<div class="grid grid-3" style="margin-bottom: 24px;">
  <!-- Demographics Card -->
  <div class="card card-pad span-2">
    <h3 style="margin-bottom: 16px;">Patient Profile & Health Details</h3>
    <dl class="dl">
      <dt>Gender</dt>
      <dd style="text-transform: capitalize;"><?= e($patient['sex']) ?></dd>

      <dt>Date of Birth & Age</dt>
      <dd>
        <strong><?= human_date($patient['dob']) ?></strong>
        <?php if ($age = age_from_dob($patient['dob'])): ?>
          <span class="badge badge-brand" style="font-weight: 700; margin-left: 6px;"><?= $age ?> Years Old</span>
        <?php endif; ?>
        <?php if (!empty($patient['is_child'])): ?>
          <span class="badge badge-warning" style="margin-left: 6px;">👶 Child / Dependent</span>
        <?php endif; ?>
      </dd>

      <?php if (!empty($patient['is_child']) && !empty($parent)): ?>
        <dt>Parent / Guardian</dt>
        <dd>
          <a href="<?= route('patients.show', ['id' => $parent['uuid'] ?? $parent['id']]) ?>" style="color: var(--brand); font-weight: 700;">
            <?= e($parent['first_name'] . ' ' . $parent['last_name']) ?> (<?= e($parent['mrn']) ?>)
          </a>
          <span class="dim" style="font-size: 12px; margin-left: 6px;">• <?= ucfirst(e($patient['relationship_to_parent'] ?: 'child')) ?></span>
        </dd>
      <?php endif; ?>

      <?php if (!empty($children)): ?>
        <dt>Registered Dependents</dt>
        <dd>
          <div style="display: flex; flex-direction: column; gap: 4px;">
            <?php foreach ($children as $c): ?>
              <div>
                <a href="<?= route('patients.show', ['id' => $c['uuid'] ?? $c['id']]) ?>" style="color: var(--brand); font-weight: 600;">
                  👶 <?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['mrn']) ?>)
                </a>
                <span class="dim" style="font-size: 11px;">• <?= ucfirst(e($c['relationship_to_parent'] ?: 'child')) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </dd>
      <?php endif; ?>

      <dt>Phone Number</dt>
      <dd class="mono"><?= e($patient['phone'] ?: '—') ?></dd>

      <dt>National ID / Passport</dt>
      <dd class="mono"><?= e($patient['national_id'] ?: '—') ?></dd>

      <dt>Blood Group</dt>
      <dd><?= $patient['blood_group'] ? '<span class="badge badge-brand">' . e($patient['blood_group']) . '</span>' : '—' ?></dd>

      <dt>Known Allergies</dt>
      <dd><?= $patient['allergies'] ? '<span class="badge badge-danger">⚠️ ' . e($patient['allergies']) . '</span>' : '<span class="dim">None recorded</span>' ?></dd>

      <dt>Residential Address</dt>
      <dd><?= e($patient['address'] ?: '—') ?></dd>

      <dt>Emergency Contact</dt>
      <dd><?= e($patient['emergency_contact_name'] ?: '—') ?> <?= $patient['emergency_contact_phone'] ? '(' . e($patient['emergency_contact_phone']) . ')' : '' ?></dd>
    </dl>
  </div>

  <!-- Quick Actions Card -->
  <div class="card card-pad">
    <h3 style="margin-bottom: 14px;">Clinical Actions</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">
      <a href="<?= route('queue.kiosk', ['patient_id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-amber btn-block">
        <span>Send to Triage / Doctor</span>
      </a>
      <a href="<?= route('pharmacy.pos', ['patient_id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-block">
        <span>Dispense / Sell Pharmacy</span>
      </a>
      <div class="divider"></div>
      <div class="dim" style="font-size: 12px;">
        Tenant Isolation: MRN is locked to <?= e(tenant('name')) ?> and cannot collide with other hospitals.
      </div>
    </div>
  </div>
</div>

<?php if (!empty($patient['deleted_at'])): ?>
  <div class="card" style="margin-bottom: 24px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span style="font-size: 24px;">⚠️</span>
      <div>
        <strong style="color: var(--danger); font-size: 15px;">Archived / Soft-Deleted Patient Record</strong>
        <p class="muted" style="margin: 2px 0 0; font-size: 13px;">This patient was removed on <?= human_date($patient['deleted_at']) ?>. Clinical history, visits, lab tests and prescriptions are preserved for regulatory audit compliance.</p>
      </div>
    </div>
    <form method="POST" action="<?= route('patients.restore', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" style="margin: 0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary btn-sm">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span>Restore Patient Record</span>
      </button>
    </form>
  </div>
<?php endif; ?>

<!-- Visit Tickets & Pharmacy Purchases -->
<div class="grid grid-2">
  <!-- Queue History -->
  <div class="card">
    <div class="card-head">
      <h3>Patient Queue Visits</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>Queue</th>
            <th>Room</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tickets)): ?>
            <tr><td colspan="5" class="empty">No queue visits on record.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <tr>
                <td><strong class="mono" style="color: var(--brand);"><?= e($t['code']) ?></strong></td>
                <td><span class="badge" style="background: <?= e($t['queue_color']) ?>20; color: <?= e($t['queue_color']) ?>;"><?= e($t['queue_name']) ?></span></td>
                <td><span class="dim"><?= e($t['service_point_name'] ?: '—') ?></span></td>
                <td><span class="dim"><?= human_date($t['issued_on']) ?></span></td>
                <td><span class="badge badge-<?= $t['status'] === 'completed' ? 'success' : 'muted' ?>"><?= e(ucfirst($t['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pharmacy Purchases -->
  <div class="card">
    <div class="card-head">
      <h3>Pharmacy OTC & Prescription History</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Receipt #</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sales)): ?>
            <tr><td colspan="4" class="empty">No pharmacy purchases on record.</td></tr>
          <?php else: ?>
            <?php foreach ($sales as $s): ?>
              <tr>
                <td><strong class="mono"><?= e($s['receipt_no']) ?></strong></td>
                <td><span class="dim"><?= human_date($s['created_at']) ?></span></td>
                <td><strong style="color: var(--success);"><?= money($s['total_minor']) ?></strong></td>
                <td>
                  <a href="<?= route('pharmacy.receipt', ['id' => $s['uuid'] ?? $s['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost">Receipt</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Password-Protected Patient Deletion Modal -->
<?php if (in_array(auth()->role ?? '', ['hospital_admin', 'super_admin'], true) || user_can('patients.delete')): ?>
<div id="deletePatientModal" class="modal-overlay <?= errors('delete_password') ? '' : 'hidden' ?>" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); border: 1px solid var(--danger);">
    <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px;">⚠️</span>
        <h3 style="margin: 0; font-size: 16px; color: var(--danger);">Confirm Patient Deletion</h3>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('deletePatientModal').classList.add('hidden')" style="padding: 4px 8px;">✕</button>
    </div>
    <form method="POST" action="<?= route('patients.delete', ['id' => $patient['uuid'] ?? $patient['id']]) ?>">
      <?= csrf_field() ?>
      <div class="card-body" style="padding: 20px;">
        <p style="margin: 0 0 14px; font-size: 13.5px; line-height: 1.5;">
          You are about to soft-delete the medical record for:
          <br>
          <strong style="font-size: 15px;"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></strong> 
          (MRN: <span class="mono" style="color: var(--brand);"><?= e($patient['mrn']) ?></span>).
        </p>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #fca5a5;">
          This record will be removed from active clinic searches, queues, and appointments. Please re-enter your account password to authorize this action.
        </div>
        <div class="field">
          <label style="font-weight: 700; font-size: 12px;">Your Password *</label>
          <input type="password" name="password" class="input" required placeholder="Enter your login password" autofocus>
          <?php if ($err = errors('delete_password')): ?>
            <div style="color: #ef4444; font-size: 12px; margin-top: 6px;"><?= e($err) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div style="padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; background: rgba(0,0,0,0.1);">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('deletePatientModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="background: #ef4444; border-color: #ef4444; color: #fff;">Confirm & Delete Patient</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
