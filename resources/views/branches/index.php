<?php $this->layout('layouts.app', ['title' => 'Branches & Multi-Facility Locations']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('settings.index') ?>">Settings</a> / Branches</div>
    <h1>Hospital Branches & Satellite Facilities</h1>
    <p class="muted">Manage distributed clinics, satellite outpatient centres, and multi-branch operations for <?= e(tenant('name')) ?>.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if (user_can_all_branches()): ?>
      <form method="POST" action="<?= route('branches.switch', ['id' => 0]) ?>" style="margin: 0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-ghost" title="View consolidated data across all hospital branches">
          🌐 View All Branches
        </button>
      </form>
    <?php endif; ?>
    <button type="button" class="btn btn-primary" onclick="openAddBranchModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Add New Branch</span>
    </button>
  </div>
</div>

<!-- Branch Overview Stats -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    </div>
    <div class="label">Total Facilities</div>
    <div class="value"><?= count($branches) ?></div>
    <div class="delta">Operating branches</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Active Branches</div>
    <div class="value"><?= count(array_filter($branches, fn($b) => !empty($b['is_active']))) ?></div>
    <div class="delta">Available for staffing & visits</div>
  </div>

  <div class="stat" style="--accent: #3b82f6;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </div>
    <div class="label">Assigned Staff</div>
    <div class="value"><?= array_sum(array_column($branches, 'staff_count')) ?></div>
    <div class="delta">Across all branch units</div>
  </div>

  <div class="stat" style="--accent: #f59e0b;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <div class="label">Patient Registrations</div>
    <div class="value"><?= array_sum(array_column($branches, 'patient_count')) ?></div>
    <div class="delta">Total registered patients</div>
  </div>
</div>

<!-- Branches Table -->
<div class="card">
  <div class="card-head">
    <h3>Hospital Facilities & Locations</h3>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Branch Code</th>
          <th>Branch / Facility Name</th>
          <th>Contact & Address</th>
          <th>Stationed Staff</th>
          <th>Patients Seen</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($branches as $b): ?>
          <?php $isCurrent = ($activeBranchId ?? null) == $b['id']; ?>
          <tr style="<?= $isCurrent ? 'background: rgba(20, 184, 166, 0.08);' : '' ?>">
            <td>
              <strong class="mono" style="font-size: 14px; color: var(--brand);"><?= e($b['code']) ?></strong>
              <?php if (!empty($b['is_main'])): ?>
                <span class="badge badge-brand" style="margin-left: 6px; font-size: 10px;">MAIN HQ</span>
              <?php endif; ?>
              <?php if ($isCurrent): ?>
                <span class="badge badge-success" style="margin-left: 6px; font-size: 10px;">VIEWING</span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="font-size: 15px;"><?= e($b['name']) ?></strong>
              <div class="dim mono" style="font-size: 11px;">UUID: <?= e(substr($b['uuid'] ?? (string)$b['id'], 0, 13)) ?>...</div>
            </td>
            <td>
              <div><?= e($b['phone'] ?: '—') ?></div>
              <div class="dim" style="font-size: 11px;"><?= e($b['address'] ?: 'No address specified') ?></div>
            </td>
            <td>
              <span class="badge badge-info"><?= (int) $b['staff_count'] ?> Employees</span>
            </td>
            <td>
              <strong class="mono"><?= number_format($b['patient_count']) ?></strong>
            </td>
            <td>
              <span class="badge badge-<?= !empty($b['is_active']) ? 'success' : 'danger' ?>">
                <?= !empty($b['is_active']) ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 6px; align-items: center;">
                <?php if (user_can_all_branches()): ?>
                  <form method="POST" action="<?= route('branches.switch', ['id' => $b['uuid'] ?? $b['id']]) ?>" style="margin: 0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= $isCurrent ? 'btn-ghost' : 'btn-primary' ?>" title="Switch into this branch workspace">
                      <?= $isCurrent ? 'Active View' : 'Switch Branch' ?>
                    </button>
                  </form>
                <?php endif; ?>

                <button type="button" class="btn btn-sm btn-ghost" onclick="openEditBranchModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)">
                  Edit
                </button>

                <?php if (empty($b['is_main'])): ?>
                  <form method="POST" action="<?= route('branches.toggle', ['id' => $b['uuid'] ?? $b['id']]) ?>" style="margin: 0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost">
                      <?= !empty($b['is_active']) ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New Branch -->
<div id="addBranchModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Add New Hospital Branch</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('addBranchModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('branches.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label for="b_name">Branch / Facility Name *</label>
          <input type="text" id="b_name" name="name" class="input" required placeholder="e.g. Westlands Outpatient Medical Centre">
        </div>

        <div class="field">
          <label for="b_code">Branch Code *</label>
          <input type="text" id="b_code" name="code" class="input" required placeholder="e.g. WEST" style="text-transform: uppercase;">
          <span class="dim" style="font-size: 11px;">Short alphanumeric code (2-8 chars)</span>
        </div>

        <div class="field">
          <label for="b_phone">Branch Contact Phone</label>
          <input type="text" id="b_phone" name="phone" class="input" placeholder="e.g. +254 712 345 678">
        </div>

        <div class="field full">
          <label for="b_address">Physical Address / Location</label>
          <input type="text" id="b_address" name="address" class="input" placeholder="e.g. Parklands Plaza, 2nd Floor, Suite 204">
        </div>

        <div class="field full" style="margin-top: 4px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="is_main" value="1" style="width: 16px; height: 16px;">
            <span>Set as Primary Main Hospital Facility</span>
          </label>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 22px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('addBranchModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Hospital Branch</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Branch -->
<div id="editBranchModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Edit Branch Details</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('editBranchModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="editBranchForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label for="eb_name">Branch / Facility Name *</label>
          <input type="text" id="eb_name" name="name" class="input" required>
        </div>

        <div class="field">
          <label for="eb_code">Branch Code *</label>
          <input type="text" id="eb_code" name="code" class="input" required style="text-transform: uppercase;">
        </div>

        <div class="field">
          <label for="eb_phone">Branch Contact Phone</label>
          <input type="text" id="eb_phone" name="phone" class="input">
        </div>

        <div class="field full">
          <label for="eb_address">Physical Address</label>
          <input type="text" id="eb_address" name="address" class="input">
        </div>

        <div class="field full" style="display: flex; gap: 24px; margin-top: 6px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" id="eb_is_main" name="is_main" value="1" style="width: 16px; height: 16px;">
            <span>Primary Main Hospital Facility</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" id="eb_is_active" name="is_active" value="1" style="width: 16px; height: 16px;">
            <span>Active Operational Status</span>
          </label>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 22px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('editBranchModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Branch</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddBranchModal() {
  document.getElementById('addBranchModal').classList.remove('hidden');
}

function openEditBranchModal(b) {
  const baseUrl = (window.APP_BASE || '').replace(/\/$/, '');
  const branchKey = b.uuid || b.id;
  document.getElementById('editBranchForm').action = baseUrl + '/branches/' + encodeURIComponent(branchKey);
  document.getElementById('eb_name').value = b.name || '';
  document.getElementById('eb_code').value = b.code || '';
  document.getElementById('eb_phone').value = b.phone || '';
  document.getElementById('eb_address').value = b.address || '';
  document.getElementById('eb_is_main').checked = Boolean(Number(b.is_main));
  document.getElementById('eb_is_active').checked = Boolean(Number(b.is_active));
  document.getElementById('editBranchModal').classList.remove('hidden');
}
</script>
