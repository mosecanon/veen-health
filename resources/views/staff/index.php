<?php $this->layout('layouts.app', ['title' => 'Hospital Staff & Employees Directory']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Hospital Administration / Human Resources</div>
    <h1>Staff & Employee Directory</h1>
    <p class="muted">Manage doctors, clinical specialists, nurses, pharmacists, laboratory technologists, and cashier accounts.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('staff.roles') ?>" class="btn btn-ghost btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      <span>Custom Roles & Permissions</span>
    </a>
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newStaffModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      <span>Add New Employee / Doctor</span>
    </button>
  </div>
</div>

<!-- Role Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
  <a href="<?= route('staff.index') ?>" class="btn btn-sm <?= empty($currentRole) ? 'btn-primary' : 'btn-ghost' ?>">
    All Staff (<?= count($staff) ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=doctor" class="btn btn-sm <?= $currentRole === 'doctor' ? 'btn-primary' : 'btn-ghost' ?>">
    🩺 Doctors & Specialists (<?= $counts['doctor'] ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=nurse" class="btn btn-sm <?= $currentRole === 'nurse' ? 'btn-primary' : 'btn-ghost' ?>">
    💉 Triage & Ward Nurses (<?= $counts['nurse'] ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=pharmacist" class="btn btn-sm <?= $currentRole === 'pharmacist' ? 'btn-primary' : 'btn-ghost' ?>">
    💊 Pharmacists (<?= $counts['pharmacist'] ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=lab_tech" class="btn btn-sm <?= $currentRole === 'lab_tech' ? 'btn-primary' : 'btn-ghost' ?>">
    🧪 Lab Technologists (<?= $counts['lab_tech'] ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=cashier" class="btn btn-sm <?= $currentRole === 'cashier' ? 'btn-primary' : 'btn-ghost' ?>">
    💳 Cashiers & Billing (<?= $counts['cashier'] ?>)
  </a>
  <a href="<?= route('staff.index') ?>?role=hospital_admin" class="btn btn-sm <?= $currentRole === 'hospital_admin' ? 'btn-primary' : 'btn-ghost' ?>">
    👑 Administrators (<?= $counts['hospital_admin'] ?>)
  </a>
</div>

<!-- Staff Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <h3 style="margin: 0;">Registered Employees (<?= count($staff) ?>)</h3>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('staffTable', 'hospital_staff_directory')" title="Export staff list as CSV / Excel">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 320px;">
        <input type="text" class="input" placeholder="🔍 Search employee name, title, role..." onkeyup="filterTable(this, 'staffTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="staffTable">
      <thead>
        <tr>
          <th>Employee Name</th>
          <th>Designation / Title</th>
          <th>Role</th>
          <th>Contact & Login Email</th>
          <th>Facility / Branch</th>
          <th>Status</th>
          <th style="text-align: right;">Account Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($staff)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 36px;">
              No staff members found matching this filter. Click "Add New Employee / Doctor" above.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($staff as $u): ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <div class="avatar" style="width: 36px; height: 36px; font-size: 13px;">
                    <?= initials($u['name']) ?>
                  </div>
                  <div>
                    <strong style="font-size: 15px;"><?= e($u['name']) ?></strong>
                    <div class="dim mono" style="font-size: 11px;">ID: #EMP-<?= str_pad((string)$u['id'], 4, '0', STR_PAD_LEFT) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <strong><?= e($u['title'] ?: 'Staff Member') ?></strong>
              </td>
              <td>
                <?php
                  $badge = match ($u['role']) {
                    'doctor' => 'brand',
                    'nurse' => 'info',
                    'pharmacist' => 'success',
                    'lab_tech' => 'warning',
                    'cashier' => 'purple',
                    'hospital_admin' => 'danger',
                    default => 'ghost',
                  };
                ?>
                <span class="badge badge-<?= $badge ?>" style="font-weight: 700; text-transform: uppercase;">
                  <?= e(str_replace('_', ' ', $u['role'])) ?>
                </span>
              </td>
              <td>
                <div class="mono" style="font-size: 13px; font-weight: 600; color: var(--text);"><?= e($u['email']) ?></div>
                <div class="dim" style="font-size: 11px;"><?= e($u['phone'] ?: 'No phone recorded') ?></div>
              </td>
              <td>
                <span><?= e($u['branch_name'] ?: 'Main Facility') ?></span>
              </td>
              <td>
                <span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                  <?= $u['is_active'] ? 'Active' : 'Suspended' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <button type="button" class="btn btn-sm btn-ghost" onclick="openEditStaffModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" title="Edit employee details">
                    <span>✏️ Edit</span>
                  </button>
                  <form method="POST" action="<?= route('staff.reset_password', ['id' => $u['uuid'] ?? $u['id']]) ?>" style="margin: 0;" onsubmit="return confirm('Reset password for <?= addslashes($u['name']) ?> to Password@123?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost" title="Reset password to Password@123">
                      <span>Reset Pass</span>
                    </button>
                  </form>
                  <?php if ($u['id'] != auth()->id): ?>
                    <form method="POST" action="<?= route('staff.toggle', ['id' => $u['uuid'] ?? $u['id']]) ?>" style="margin: 0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-ghost">
                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                      </button>
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

<!-- Modal: Add New Employee -->
<div id="newStaffModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 600px; padding: 28px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
      <h3 style="margin: 0;">Add New Hospital Employee / Doctor</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newStaffModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('staff.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Employee Full Name *</label>
          <input type="text" name="name" class="input" required placeholder="e.g. Dr. Samuel Waweru or Sister Grace Mwende">
        </div>

        <div class="field">
          <label>Staff Role & Dynamic Permissions *</label>
          <select name="role" class="input" required onchange="onRoleSelect(this.value)">
            <?php if (!empty($roles)): ?>
              <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['slug']) ?>" <?= $r['slug'] === 'doctor' ? 'selected' : '' ?>>
                  <?= e($r['name']) ?> (<?= $r['is_system'] ? 'Standard' : 'Custom' ?>)
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="doctor" selected>Doctor / Physician / Specialist</option>
              <option value="nurse">Nurse (Triage / Ward / Inpatient)</option>
              <option value="pharmacist">Pharmacist / Dispenser</option>
              <option value="lab_tech">Laboratory Technologist</option>
              <option value="cashier">Cashier / Billing Officer</option>
              <option value="hospital_admin">Hospital Administrator</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label>Professional Title / Designation</label>
          <input type="text" id="staff_title" name="title" class="input" placeholder="e.g. Consultant Pediatrician">
        </div>

        <div class="field full">
          <label>Login Email Address (Used to Sign In) *</label>
          <input type="email" name="email" class="input" required placeholder="samuel@hospital.com">
        </div>

        <div class="field">
          <label>Initial Login Password *</label>
          <input type="password" name="password" class="input" required value="Password@123">
        </div>

        <div class="field">
          <label>Phone Number</label>
          <input type="text" name="phone" class="input" placeholder="+254 712 345 678">
        </div>

        <div class="field full">
          <label>Facility / Branch Location</label>
          <select name="branch_id" class="input">
            <?php foreach ($branches as $br): ?>
              <option value="<?= $br['id'] ?>"><?= e($br['name']) ?> (<?= e($br['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full" style="margin-top: 4px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="can_access_all_branches" value="1" style="width: 16px; height: 16px;">
            <span style="font-weight: 600;">🌐 Multi-Branch Privilege: Can view and switch across all hospital branches</span>
          </label>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newStaffModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>Create Employee Account</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Existing Employee -->
<div id="editStaffModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 600px; padding: 28px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
      <h3 style="margin: 0;" id="editStaffModalTitle">Edit Employee Details</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('editStaffModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" id="editStaffForm" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Employee Full Name *</label>
          <input type="text" id="edit_staff_name" name="name" class="input" required>
        </div>

        <div class="field">
          <label>Email Address <span class="dim">(Account Identifier - Locked)</span></label>
          <input type="email" id="edit_staff_email" class="input" disabled readonly style="opacity: 0.7; cursor: not-allowed;">
        </div>

        <div class="field">
          <label>Phone Number</label>
          <input type="tel" id="edit_staff_phone" name="phone" class="input" placeholder="e.g. +254 712 345 678">
        </div>

        <div class="field">
          <label>Staff Role & Dynamic Permissions *</label>
          <select id="edit_staff_role" name="role" class="input" required>
            <?php if (!empty($roles)): ?>
              <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['slug']) ?>"><?= e($r['name']) ?> (<?= $r['is_system'] ? 'Standard' : 'Custom' ?>)</option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="doctor">Doctor / Physician / Specialist</option>
              <option value="nurse">Nurse (Triage / Ward / Inpatient)</option>
              <option value="pharmacist">Pharmacist / Dispenser</option>
              <option value="lab_tech">Laboratory Technologist</option>
              <option value="cashier">Cashier / Billing Officer</option>
              <option value="hospital_admin">Hospital Administrator</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label>Professional Title / Designation</label>
          <input type="text" id="edit_staff_title" name="title" class="input" placeholder="e.g. Consultant Pediatrician">
        </div>

        <div class="field full">
          <label>Facility / Branch Location</label>
          <select id="edit_staff_branch_id" name="branch_id" class="input">
            <?php foreach ($branches as $br): ?>
              <option value="<?= $br['id'] ?>"><?= e($br['name']) ?> (<?= e($br['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full" style="margin-top: 4px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" id="edit_staff_can_access_all_branches" name="can_access_all_branches" value="1" style="width: 16px; height: 16px;">
            <span style="font-weight: 600;">🌐 Multi-Branch Privilege: Can view and switch across all hospital branches</span>
          </label>
        </div>

        <div class="field full" style="margin-top: 8px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" id="edit_staff_is_active" name="is_active" value="1">
            <span style="font-weight: 600;">Account is Active & Permitted to Login</span>
          </label>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('editStaffModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Save Changes</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function onRoleSelect(role) {
  const t = document.getElementById('staff_title');
  if (!t.value || t.value.startsWith('Consultant') || t.value.startsWith('Staff') || t.value.startsWith('Pharmacist') || t.value.startsWith('Lab') || t.value.startsWith('Cashier')) {
    t.value = role === 'doctor' ? 'Consultant Physician' :
              role === 'nurse' ? 'Staff Nurse (Triage & Ward)' :
              role === 'pharmacist' ? 'Chief Pharmacist' :
              role === 'lab_tech' ? 'Medical Laboratory Scientist' :
              role === 'cashier' ? 'Cashier & Financial Accounts' :
              role === 'receptionist' ? 'Front Desk Officer' : 'Administrator';
  }
}

function openEditStaffModal(user) {
  document.getElementById('editStaffModalTitle').textContent = 'Edit: ' + user.name;
  const baseUrl = (window.APP_BASE || '').replace(/\/$/, '');
  const userKey = user.uuid || user.id;
  document.getElementById('editStaffForm').action = baseUrl + '/staff/' + encodeURIComponent(userKey);
  document.getElementById('edit_staff_name').value = user.name || '';
  document.getElementById('edit_staff_email').value = user.email || '';
  document.getElementById('edit_staff_phone').value = user.phone || '';
  document.getElementById('edit_staff_title').value = user.title || '';
  document.getElementById('edit_staff_role').value = user.role || 'doctor';
  if (document.getElementById('edit_staff_branch_id')) {
    document.getElementById('edit_staff_branch_id').value = user.branch_id || '';
  }
  document.getElementById('edit_staff_can_access_all_branches').checked = !!Number(user.can_access_all_branches);
  document.getElementById('edit_staff_is_active').checked = !!Number(user.is_active);

  document.getElementById('editStaffModal').classList.remove('hidden');
}
</script>