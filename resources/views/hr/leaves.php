<?php $this->layout('layouts.app', ['title' => 'Staff Leave Management']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('hr.index') ?>">HR</a> / Leaves</div>
    <h1>Hospital Staff Leave Management</h1>
    <p class="muted">Review and approve annual, sick, maternity, and study leave requests for doctors and staff.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newLeaveModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Submit Leave Application</span>
    </button>
  </div>
</div>

<!-- Status Filters -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
  <a href="<?= route('hr.leaves') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-ghost' ?>">
    All Requests (<?= count($leaves) ?>)
  </a>
  <a href="<?= route('hr.leaves') ?>?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-ghost' ?>">
    ⏳ Pending Approval
  </a>
  <a href="<?= route('hr.leaves') ?>?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-ghost' ?>">
    ✓ Approved Leaves
  </a>
  <a href="<?= route('hr.leaves') ?>?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-ghost' ?>">
    ✕ Rejected
  </a>
</div>

<!-- Leaves Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <h3 style="margin: 0;">Leave Applications (<?= count($leaves) ?>)</h3>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('leavesTable', 'staff_leave_requests')" title="Export leaves to Excel / CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 300px;">
        <input type="text" class="input" placeholder="🔍 Search employee, reason, leave type..." onkeyup="filterTable(this, 'leavesTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="leavesTable">
      <thead>
        <tr>
          <th>Staff Employee</th>
          <th>Leave Type</th>
          <th>Duration & Dates</th>
          <th>Days</th>
          <th>Reason / Justification</th>
          <th>Status</th>
          <th>Adjudication</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaves)): ?>
          <tr><td colspan="8" class="empty" style="text-align: center; padding: 40px;">No leave applications found for this filter.</td></tr>
        <?php else: ?>
          <?php foreach ($leaves as $l): ?>
            <tr>
              <td>
                <strong><?= e($l['employee_name']) ?></strong>
                <div class="dim" style="font-size: 11px;"><?= e($l['employee_title'] ?: $l['employee_role']) ?></div>
              </td>
              <td>
                <?php
                  $lBadge = match ($l['leave_type']) {
                    'sick' => 'danger',
                    'annual' => 'brand',
                    'maternity' => 'purple',
                    'study' => 'info',
                    default => 'ghost',
                  };
                ?>
                <span class="badge badge-<?= $lBadge ?>" style="text-transform: uppercase; font-size: 10px;">
                  <?= e($l['leave_type']) ?>
                </span>
              </td>
              <td>
                <span class="mono" style="font-size: 12.5px;"><?= date('d M Y', strtotime($l['start_date'])) ?> &rarr; <?= date('d M Y', strtotime($l['end_date'])) ?></span>
              </td>
              <td>
                <strong class="mono" style="font-size: 14px; color: var(--brand);"><?= $l['days_count'] ?></strong> days
              </td>
              <td style="max-width: 240px;">
                <span class="dim" style="font-size: 12px;"><?= e($l['reason'] ?: 'None specified') ?></span>
              </td>
              <td>
                <span class="badge badge-<?= $l['status'] === 'approved' ? 'success' : ($l['status'] === 'rejected' ? 'danger' : 'warning') ?>" style="text-transform: uppercase; font-size: 10px;">
                  <?= e($l['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($l['approved_by_name']): ?>
                  <span style="font-size: 11px;">By <?= e($l['approved_by_name']) ?></span>
                <?php elseif ($l['rejection_reason']): ?>
                  <span style="font-size: 11px; color: var(--danger);"><?= e($l['rejection_reason']) ?></span>
                <?php else: ?>
                  <span class="dim" style="font-size: 11px;">Awaiting Review</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <?php if ($l['status'] === 'pending'): ?>
                  <div style="display: inline-flex; gap: 6px;">
                    <form method="POST" action="<?= route('hr.leaves.status', ['id' => $l['id']]) ?>" style="margin: 0;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="btn btn-sm btn-primary" title="Approve Leave Application">✓ Approve</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-ghost" style="color: var(--danger);" onclick="openRejectModal(<?= $l['id'] ?>, '<?= addslashes($l['employee_name']) ?>')">✕ Reject</button>
                  </div>
                <?php else: ?>
                  <span class="dim" style="font-size: 11px;">Completed</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: New Leave Application -->
<div id="newLeaveModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Submit Staff Leave Application</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newLeaveModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('hr.leaves.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Employee / Staff Member *</label>
          <select name="user_id" class="input" required>
            <?php foreach ($staff as $st): ?>
              <option value="<?= $st['id'] ?>"><?= e($st['name']) ?> (<?= e($st['title'] ?: $st['role']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label>Leave Category *</label>
          <select name="leave_type" class="input" required>
            <option value="annual" selected>Annual Paid Leave</option>
            <option value="sick">Sick / Medical Leave</option>
            <option value="maternity">Maternity Leave</option>
            <option value="paternity">Paternity Leave</option>
            <option value="compassionate">Compassionate / Bereavement</option>
            <option value="study">Study / Professional Exam Leave</option>
            <option value="unpaid">Unpaid Leave</option>
          </select>
        </div>

        <div class="field">
          <label>Start Date *</label>
          <input type="date" id="leave_start" name="start_date" class="input" required value="<?= date('Y-m-d') ?>" onchange="computeLeaveDays()">
        </div>

        <div class="field">
          <label>End Date *</label>
          <input type="date" id="leave_end" name="end_date" class="input" required value="<?= date('Y-m-d', strtotime('+5 days')) ?>" onchange="computeLeaveDays()">
        </div>

        <div class="field full" style="background: rgba(20, 184, 166, 0.08); padding: 8px 12px; border-radius: 6px; font-size: 12px;">
          <span>Calculated Leave Duration: <strong id="leaveDaysCount" style="color: var(--brand);">6 Days</strong></span>
        </div>

        <div class="field full">
          <label>Reason / Handover Notes</label>
          <textarea name="reason" class="input" rows="2" placeholder="e.g. Reliever assigned to Doctor Room 2; travelling for family event."></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newLeaveModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Application</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Reject Leave -->
<div id="rejectLeaveModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 105; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 440px; padding: 24px;">
    <h3 style="margin-top: 0;">Reject Leave Application</h3>
    <p id="rejectModalDesc" class="dim" style="font-size: 13px; margin-bottom: 14px;">State the reason for rejecting this leave application.</p>

    <form id="rejectLeaveForm" method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reject">
      <div class="field full" style="margin-bottom: 16px;">
        <label>Reason for Rejection *</label>
        <textarea name="rejection_reason" class="input" rows="2" required placeholder="e.g. Critical staff shortage in surgery department during this period."></textarea>
      </div>

      <div style="display: flex; gap: 8px; justify-content: flex-end;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectLeaveModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--danger); border-color: var(--danger);">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<script>
function computeLeaveDays() {
  const s = new Date(document.getElementById('leave_start').value);
  const e = new Date(document.getElementById('leave_end').value);
  if (!isNaN(s) && !isNaN(e) && e >= s) {
    const diff = Math.floor((e - s) / (1000 * 60 * 60 * 24)) + 1;
    document.getElementById('leaveDaysCount').innerText = `${diff} Days`;
  }
}

function openRejectModal(id, name) {
  document.getElementById('rejectModalDesc').innerText = `Reject leave application for ${name}:`;
  document.getElementById('rejectLeaveForm').action = `<?= url('hr/leaves') ?>/${id}/status`;
  document.getElementById('rejectLeaveModal').classList.remove('hidden');
}
</script>
