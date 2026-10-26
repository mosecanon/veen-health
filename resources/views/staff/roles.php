<?php $this->layout('layouts.app', ['title' => 'Custom Hospital Roles & Permissions']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('staff.index') ?>">Staff</a> / Roles</div>
    <h1>Hospital Roles & Access Control</h1>
    <p class="muted">Define custom hospital designations, configure doctor responsibilities, and control access permissions.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('staff.index') ?>" class="btn btn-ghost btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      <span>Back to Staff Directory</span>
    </a>
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newRoleModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Add New Role</span>
    </button>
  </div>
</div>

<!-- Roles Grid -->
<div class="grid grid-2" style="gap: 16px; margin-bottom: 24px;">
  <?php foreach ($roles as $r): ?>
    <?php
      $perms = json_decode((string)$r['permissions'], true) ?: [];
    ?>
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
          <div>
            <h3 style="margin: 0; font-size: 17px;"><?= e($r['name']) ?></h3>
            <span class="mono dim" style="font-size: 11px;">Slug: <strong><?= e($r['slug']) ?></strong></span>
          </div>
          <div style="display: flex; gap: 6px; align-items: center;">
            <span class="badge badge-<?= $r['is_system'] ? 'ghost' : 'brand' ?>" style="font-size: 10px; text-transform: uppercase;">
              <?= $r['is_system'] ? 'System Core' : 'Custom Defined' ?>
            </span>
            <span class="badge badge-info" style="font-size: 10px;">
              <?= (int) $r['users_count'] ?> Active Staff
            </span>
          </div>
        </div>

        <p class="dim" style="font-size: 12.5px; margin: 6px 0 14px 0; line-height: 1.4;">
          <?= e($r['description'] ?: 'No operational description provided.') ?>
        </p>

        <!-- Permissions Badges -->
        <div style="margin-bottom: 16px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
            Granted Capabilities (<?= count($perms) ?>):
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 4px;">
            <?php foreach ($perms as $pKey): ?>
              <span class="badge badge-brand" style="font-size: 10.5px; font-weight: 600;">
                <?= e($availablePermissions[$pKey] ?? ucfirst(str_replace('_', ' ', $pKey))) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 10px;">
        <span class="dim" style="font-size: 11px;">Updated: <?= date('d M Y', strtotime($r['updated_at'])) ?></span>
        <div style="display: flex; gap: 6px;">
          <button type="button" class="btn btn-sm btn-ghost" onclick='openEditRoleModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)'>
            <span>Edit Rights & Permissions</span>
          </button>
          <?php if (!$r['is_system']): ?>
            <form method="POST" action="<?= route('staff.roles.delete', ['id' => $r['id']]) ?>" style="margin: 0;" onsubmit="return confirm('Remove custom role <?= addslashes($r['name']) ?>?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--danger);">Delete</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modal: Add New Role -->
<div id="newRoleModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 640px; padding: 26px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Add New Hospital Designation & Role</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newRoleModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('staff.roles.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Role / Title Name *</label>
          <input type="text" name="name" class="input" required placeholder="e.g. Consultant Surgeon, Emergency Triage Officer, Pharmacy Lead">
        </div>

        <div class="field full">
          <label>Operational Scope / Description</label>
          <input type="text" name="description" class="input" placeholder="e.g. Conducts general surgeries, post-op ward rounds, and writes surgical discharge notes.">
        </div>
      </div>

      <div style="margin-top: 16px;">
        <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">
          Assign Permissions & Rights *
        </label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 280px; overflow-y: auto; padding: 8px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface-2);">
          <?php foreach ($availablePermissions as $pKey => $pLabel): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer; padding: 4px;">
              <input type="checkbox" name="permissions[]" value="<?= e($pKey) ?>">
              <span><?= e($pLabel) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newRoleModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save & Activate Role</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Existing Role Permissions -->
<div id="editRoleModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 640px; padding: 26px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;" id="editModalHeading">Configure Role Permissions</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('editRoleModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="editRoleForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Role Name *</label>
          <input type="text" id="edit_role_name" name="name" class="input" required>
        </div>

        <div class="field full">
          <label>Description</label>
          <input type="text" id="edit_role_desc" name="description" class="input">
        </div>
      </div>

      <div style="margin-top: 16px;">
        <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">
          Configured Rights & Privileges *
        </label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 280px; overflow-y: auto; padding: 8px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface-2);">
          <?php foreach ($availablePermissions as $pKey => $pLabel): ?>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer; padding: 4px;">
              <input type="checkbox" name="permissions[]" value="<?= e($pKey) ?>" class="edit-perm-check" id="perm_<?= e($pKey) ?>">
              <span><?= e($pLabel) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('editRoleModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Rights & Permissions</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditRoleModal(role) {
  document.getElementById('editModalHeading').innerText = `Configure Permissions: ${role.name}`;
  document.getElementById('edit_role_name').value = role.name;
  document.getElementById('edit_role_desc').value = role.description || '';
  document.getElementById('editRoleForm').action = `<?= url('staff/roles') ?>/${role.id}`;

  let perms = [];
  try {
    perms = JSON.parse(role.permissions || '[]');
  } catch(e) {}

  document.querySelectorAll('.edit-perm-check').forEach(cb => {
    cb.checked = perms.includes(cb.value);
  });

  document.getElementById('editRoleModal').classList.remove('hidden');
}
</script>
