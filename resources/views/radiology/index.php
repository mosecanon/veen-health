<?php $this->layout('layouts.app', ['title' => 'Radiology & MRI Diagnostic Center']) ?>

<style>
/* Sticky Action Column pinned to the right edge - 100% Opaque (Never Transparent) */
.sticky-actions-col {
  position: sticky;
  right: 0;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  box-shadow: -8px 0 16px rgba(0, 0, 0, 0.25);
  z-index: 10;
  text-align: right;
  white-space: nowrap;
}
th.sticky-actions-col {
  z-index: 11;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
}
.table tbody tr:hover .sticky-actions-col {
  background: #1f2937 !important;
  background-color: #1f2937 !important;
  box-shadow: -8px 0 18px rgba(0, 0, 0, 0.35);
}
[data-theme="light"] .sticky-actions-col,
[data-theme="light"] th.sticky-actions-col {
  background: #ffffff !important;
  background-color: #ffffff !important;
  box-shadow: -8px 0 16px rgba(15, 23, 42, 0.08);
}
[data-theme="light"] .table tbody tr:hover .sticky-actions-col {
  background: #f1f5f9 !important;
  background-color: #f1f5f9 !important;
  box-shadow: -8px 0 18px rgba(15, 23, 42, 0.12);
}

.radiology-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow-y: auto;
}

.radiology-modal-content {
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  color: var(--text, #f9fafb);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
  border-radius: 16px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px;
  box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.08);
  position: relative;
  isolation: isolate;
}

[data-theme="light"] .radiology-modal-content {
  background: #ffffff !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  border-color: rgba(15, 23, 42, 0.15);
  box-shadow: 0 25px 65px -15px rgba(15, 23, 42, 0.35);
}

.radiology-modal-section {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.03);
}

[data-theme="light"] .radiology-modal-section {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Clinical Services / Diagnostic Imaging</div>
    <h1>🩻 Radiology & MRI Diagnostic Center</h1>
    <p class="muted">Requisition, MRI safety screening, inpatient ward porter tracking, and radiologist diagnostic imaging reporting.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openRequisitionModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Order MRI / Scan</span>
    </button>
  </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-5" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid var(--brand);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Total Orders</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: var(--brand); margin-top: 4px;"><?= number_format($stats['total']) ?></div>
      </div>
      <div style="background: rgba(20, 184, 166, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🩻</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">All Imaging Orders</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #8b5cf6;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">MRI Scans</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #8b5cf6; margin-top: 4px;"><?= number_format($stats['mriTotal']) ?></div>
      </div>
      <div style="background: rgba(139, 92, 246, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🧠</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">High-Field MRI Studies</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #f59e0b;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Inpatient Transit</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #f59e0b; margin-top: 4px;"><?= number_format($stats['inpatientsInTransit']) ?></div>
      </div>
      <div style="background: rgba(245, 158, 11, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🛞</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">En Route / In Scanner</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #3b82f6;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">In Reading</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #3b82f6; margin-top: 4px;"><?= number_format($stats['pendingReport']) ?></div>
      </div>
      <div style="background: rgba(59, 130, 246, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📋</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Radiologist Reading</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #10b981;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Completed</div>
        <div class="mono" style="font-size: 26px; font-weight: 800; color: #10b981; margin-top: 4px;"><?= number_format($stats['completed']) ?></div>
      </div>
      <div style="background: rgba(16, 185, 129, 0.1); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">✅</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">Reports Signed & Archival</div>
  </div>
</div>

<!-- Filters & Search Form -->
<div class="card card-pad" style="margin-bottom: 20px;">
  <form method="GET" action="<?= route('radiology.index') ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
    <div style="flex: 2; min-width: 220px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Search Scans</label>
      <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Order #, patient name, MRN, body part...">
    </div>

    <div style="flex: 1; min-width: 140px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Modality</label>
      <select name="modality" class="form-control">
        <option value="all" <?= $modality === 'all' ? 'selected' : '' ?>>All Modalities</option>
        <option value="mri" <?= $modality === 'mri' ? 'selected' : '' ?>>MRI (Magnetic Resonance)</option>
        <option value="ct_scan" <?= $modality === 'ct_scan' ? 'selected' : '' ?>>CT Scan</option>
        <option value="x_ray" <?= $modality === 'x_ray' ? 'selected' : '' ?>>Digital X-Ray</option>
        <option value="ultrasound" <?= $modality === 'ultrasound' ? 'selected' : '' ?>>Ultrasound (US)</option>
        <option value="mammography" <?= $modality === 'mammography' ? 'selected' : '' ?>>Mammography</option>
      </select>
    </div>

    <div style="flex: 1; min-width: 140px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Status</label>
      <select name="status" class="form-control">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
        <option value="ordered" <?= $status === 'ordered' ? 'selected' : '' ?>>Ordered / Queued</option>
        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <div style="flex: 1; min-width: 140px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Inpatient Transport</label>
      <select name="transport" class="form-control">
        <option value="all" <?= $transport === 'all' ? 'selected' : '' ?>>All Transport Stages</option>
        <option value="ordered" <?= $transport === 'ordered' ? 'selected' : '' ?>>Ordered (In Ward)</option>
        <option value="scheduled" <?= $transport === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
        <option value="transporting" <?= $transport === 'transporting' ? 'selected' : '' ?>>Transporting to Rad</option>
        <option value="in_scan" <?= $transport === 'in_scan' ? 'selected' : '' ?>>In Scan Room</option>
        <option value="completed" <?= $transport === 'completed' ? 'selected' : '' ?>>Scan Done / Holding</option>
        <option value="returned_to_ward" <?= $transport === 'returned_to_ward' ? 'selected' : '' ?>>Returned to Ward</option>
      </select>
    </div>

    <div style="display: flex; gap: 8px;">
      <button type="submit" class="btn btn-secondary">Filter</button>
      <?php if ($q !== '' || $modality !== 'all' || $status !== 'all' || $transport !== 'all'): ?>
        <a href="<?= route('radiology.index') ?>" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Requisitions Table Card -->
<div class="card">
  <!-- Top Horizontal Scroll Helper -->
  <div id="radiologyTopScroll" style="overflow-x: auto; overflow-y: hidden; height: 16px; border-bottom: 1px solid var(--border); background: var(--surface-2, rgba(255,255,255,0.02));">
    <div id="radiologyTopScrollInner" style="height: 1px;"></div>
  </div>

  <div id="radiologyTableWrapper" style="overflow-x: auto; width: 100%;">
    <table class="table" style="margin-bottom: 0; min-width: 1150px;">
      <thead>
        <tr>
          <th>Order # & Priority</th>
          <th>Patient / Inpatient Bed</th>
          <th>Modality & Body Part</th>
          <th>Contrast & Protocol</th>
          <th>MRI Safety Screen</th>
          <th>Transport Status</th>
          <th>Clinical Indication</th>
          <th>Order Status</th>
          <th class="sticky-actions-col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 48px; color: var(--text-muted);">
              <div style="font-size: 32px; margin-bottom: 8px;">🩻</div>
              <div style="font-size: 15px; font-weight: 600;">No radiology requisitions match your criteria</div>
              <p class="muted" style="margin-top: 4px; font-size: 13px;">Create an MRI, CT, or X-Ray order using the button above.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <div class="mono" style="font-weight: 700; color: var(--text); font-size: 13px;"><?= e($o['order_number']) ?></div>
                <div style="margin-top: 4px;">
                  <?php if ($o['priority'] === 'stat'): ?>
                    <span class="badge badge-danger">⚡ STAT / EMERGENCY</span>
                  <?php elseif ($o['priority'] === 'urgent'): ?>
                    <span class="badge badge-warning">⚠️ URGENT</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">Routine</span>
                  <?php endif; ?>
                </div>
              </td>

              <td>
                <div style="font-weight: 700; color: var(--brand); font-size: 13.5px;">
                  <?= e($o['first_name'] . ' ' . $o['last_name']) ?>
                </div>
                <div class="muted mono" style="font-size: 11px; margin-top: 2px;">
                  MRN: <?= e($o['mrn']) ?> &bull; <?= ucfirst(e($o['sex'])) ?>
                </div>
                <?php if (!empty($o['admission_id'])): ?>
                  <div style="margin-top: 4px; font-size: 11.5px; color: #8b5cf6; font-weight: 600;">
                    🏥 Inpatient: <?= e($o['ward_name'] ?? 'Ward') ?> (Bed: <?= e($o['bed_number'] ?? 'N/A') ?>)
                  </div>
                <?php else: ?>
                  <div style="margin-top: 4px; font-size: 11px; color: var(--text-muted);">
                    🚶 Outpatient
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <span class="badge" style="background: <?= $o['modality'] === 'mri' ? 'rgba(139,92,246,0.15)' : 'rgba(2,132,199,0.15)' ?>; color: <?= $o['modality'] === 'mri' ? '#8b5cf6' : '#0284c7' ?>; font-weight: 700;">
                  <?= strtoupper(str_replace('_', ' ', e($o['modality']))) ?>
                </span>
                <div style="font-weight: 600; margin-top: 4px; font-size: 13px; color: var(--text);">
                  <?= e($o['body_part']) ?>
                </div>
              </td>

              <td>
                <div style="font-size: 12px; font-weight: 600; color: var(--text);">
                  <?= ucwords(str_replace('_', ' ', e($o['contrast_type']))) ?>
                </div>
                <?php if ($o['contrast_type'] !== 'without_contrast'): ?>
                  <div class="muted" style="font-size: 11px;">
                    eGFR: <?= $o['egfr_renal_value'] ? e($o['egfr_renal_value']) . ' mL/min' : 'Not recorded' ?>
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($o['has_pacemaker'] || $o['has_aneurysm_clips']): ?>
                  <span class="badge badge-danger" title="Pacemaker / Clip Alert">🚫 HIGH RISK CONTRAINDICATION</span>
                <?php elseif ($o['safety_checklist_cleared']): ?>
                  <span class="badge badge-success">🛡️ Safety Cleared</span>
                <?php else: ?>
                  <span class="badge badge-warning">⏳ Pending Checklist</span>
                <?php endif; ?>
                <?php if ($o['has_claustrophobia']): ?>
                  <div style="font-size: 10.5px; color: #f59e0b; margin-top: 3px;">⚠️ Claustrophobic</div>
                <?php endif; ?>
              </td>

              <td>
                <?php
                  $tColors = [
                    'ordered'          => 'badge-secondary',
                    'scheduled'        => 'badge-info',
                    'transporting'     => 'badge-warning',
                    'in_scan'          => 'badge-primary',
                    'completed'        => 'badge-success',
                    'returned_to_ward' => 'badge-success',
                  ];
                ?>
                <span class="badge <?= $tColors[$o['transport_status']] ?? 'badge-secondary' ?>">
                  <?= strtoupper(str_replace('_', ' ', e($o['transport_status']))) ?>
                </span>
                <?php if (!empty($o['porter_notes'])): ?>
                  <div class="muted" style="font-size: 10.5px; margin-top: 3px; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($o['porter_notes']) ?>">
                    <?= e($o['porter_notes']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td style="max-width: 220px;">
                <div style="font-size: 12px; color: var(--text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= e($o['clinical_indication']) ?>">
                  <?= e($o['clinical_indication']) ?>
                </div>
              </td>

              <td>
                <?php if ($o['status'] === 'completed'): ?>
                  <span class="badge badge-success">✓ Signed Report</span>
                <?php elseif ($o['status'] === 'in_progress'): ?>
                  <span class="badge badge-primary">Scanning / Reading</span>
                <?php elseif ($o['status'] === 'cancelled'): ?>
                  <span class="badge badge-danger">Cancelled</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Requisitioned</span>
                <?php endif; ?>
              </td>

              <td class="sticky-actions-col">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= route('radiology.show', ['id' => $o['uuid'] ?? $o['id']]) ?>" class="btn btn-sm btn-secondary" title="View details and manage transport">
                    Open Order
                  </a>
                  <?php if ($o['status'] === 'completed'): ?>
                    <a href="<?= route('radiology.report', ['id' => $o['uuid'] ?? $o['id']]) ?>" class="btn btn-sm btn-ghost" title="Print formal diagnostic imaging report">
                      🖨️ Report
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Bar (at least 20 records per page) -->
  <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-top: 1px solid var(--border); font-size: 13px;">
    <div class="muted">
      Showing <?= min($total, ($page - 1) * $perPage + 1) ?> to <?= min($total, $page * $perPage) ?> of <?= number_format($total) ?> orders
      (<?= $perPage ?> per page)
    </div>
    <div style="display: flex; gap: 6px;">
      <?php if ($page > 1): ?>
        <a href="<?= route('radiology.index', array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-sm btn-secondary">← Previous</a>
      <?php endif; ?>
      <?php if ($page < $lastPage): ?>
        <a href="<?= route('radiology.index', array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-sm btn-secondary">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 100% OPAQUE REQUISITION MODAL               -->
<!-- ========================================== -->
<div id="radiologyRequisitionModal" class="radiology-modal-backdrop">
  <div class="radiology-modal-content" style="max-width: 820px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
      <div>
        <h3 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
          <span>🩻</span> New Radiology & MRI Scan Requisition
        </h3>
        <p class="muted" style="margin: 4px 0 0 0; font-size: 12.5px;">Schedule inpatient or outpatient imaging with built-in MRI contraindication screening.</p>
      </div>
      <button type="button" class="btn btn-ghost" onclick="closeRequisitionModal()" style="font-size: 18px; line-height: 1;">&times;</button>
    </div>

    <form method="POST" action="<?= route('radiology.store') ?>">
      <?= csrf_field() ?>

      <!-- Patient & Inpatient Source Selection -->
      <div class="radiology-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; color: var(--brand); font-weight: 700;">1. Patient / Inpatient Referral</h4>
        
        <div style="margin-bottom: 12px;">
          <label class="form-label">Order Type / Patient Source *</label>
          <div style="display: flex; gap: 16px;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
              <input type="radio" name="patient_source_type" value="inpatient" checked onchange="toggleSourceType('inpatient')">
              <strong>Inpatient Ward (Direct Inpatient Transfer)</strong>
            </label>
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
              <input type="radio" name="patient_source_type" value="outpatient" onchange="toggleSourceType('outpatient')">
              <span>Outpatient / General Patient</span>
            </label>
          </div>
        </div>

        <!-- Inpatient Selection Dropdown -->
        <div id="inpatientSourceBox" style="margin-bottom: 10px;">
          <label class="form-label">Select Admitted Inpatient *</label>
          <select name="admission_id" id="radAdmissionSelect" class="form-control" onchange="handleInpatientSelect(this)">
            <option value="">-- Choose currently admitted patient --</option>
            <?php foreach ($activeInpatients as $adm): ?>
              <option value="<?= $adm['admission_id'] ?>" data-patient-id="<?= $adm['patient_id'] ?>">
                <?= e($adm['first_name'] . ' ' . $adm['last_name']) ?> (MRN: <?= e($adm['mrn']) ?>) &mdash; <?= e($adm['ward_name']) ?> [Bed: <?= e($adm['bed_number']) ?>]
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Outpatient Selection Dropdown -->
        <div id="outpatientSourceBox" style="display: none; margin-bottom: 10px;">
          <label class="form-label">Search / Select Patient *</label>
          <select name="patient_id" id="radPatientSelect" class="form-control">
            <option value="">-- Choose registered patient --</option>
            <?php foreach ($recentPatients as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= e($p['first_name'] . ' ' . $p['last_name']) ?> (MRN: <?= e($p['mrn']) ?>) &mdash; <?= e($p['phone'] ?: 'No Phone') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Modality & Anatomical Region -->
      <div class="radiology-modal-section">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; color: var(--brand); font-weight: 700;">2. Study Modality & Protocol</h4>
        
        <div class="grid grid-3" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Modality *</label>
            <select name="modality" id="radModalitySelect" class="form-control" onchange="updateModalityOptions(this.value)">
              <option value="mri" selected>MRI (Magnetic Resonance)</option>
              <option value="ct_scan">CT Scan (Computed Tomography)</option>
              <option value="x_ray">Digital X-Ray</option>
              <option value="ultrasound">Ultrasound (US)</option>
              <option value="mammography">Mammography</option>
            </select>
          </div>

          <div>
            <label class="form-label">Anatomical Body Part *</label>
            <input type="text" name="body_part" class="form-control" placeholder="e.g. Brain, Lumbar Spine, Knee, Abdomen" value="Brain" required>
          </div>

          <div>
            <label class="form-label">Contrast Administration</label>
            <select name="contrast_type" class="form-control">
              <option value="without_contrast" selected>Without Contrast (Plain)</option>
              <option value="with_contrast">With IV Contrast</option>
              <option value="with_and_without_contrast">With & Without Contrast (Pre & Post)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-2" style="gap: 12px; margin-bottom: 12px;">
          <div>
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <option value="routine" selected>Routine</option>
              <option value="urgent">Urgent</option>
              <option value="stat">⚡ STAT (Immediate)</option>
            </select>
          </div>

          <div>
            <label class="form-label">Scheduled Date / Time</label>
            <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
          </div>
        </div>

        <div>
          <label class="form-label">Clinical Indication & History *</label>
          <textarea name="clinical_indication" class="form-control" rows="2" placeholder="e.g. Chronic headache, suspected intracranial mass, acute stroke rule out..." required></textarea>
        </div>
      </div>

      <!-- MRI Pre-Scan Safety Screening Checklist -->
      <div id="mriSafetyBox" class="radiology-modal-section" style="border-left: 4px solid #8b5cf6;">
        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 10px; color: #8b5cf6; font-weight: 700;">
          3. MRI Safety & Implant Clearance Checklist
        </h4>
        <p class="muted" style="font-size: 12px; margin-bottom: 12px;">Essential ferromagnetic screening before exposure to 1.5T / 3.0T magnetic field.</p>

        <div class="grid grid-2" style="gap: 10px; margin-bottom: 14px;">
          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="has_pacemaker" value="1">
            <span style="color: #ef4444; font-weight: 700;">Cardiac Pacemaker / ICD (High Risk)</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="has_aneurysm_clips" value="1">
            <span style="color: #ef4444; font-weight: 700;">Intracranial Aneurysm Clip (High Risk)</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="has_metallic_implants" value="1">
            <span>Metallic Implants / Shrapnel / Prosthesis</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="has_claustrophobia" value="1">
            <span>Claustrophobia / Anxiety in Tunneled Scanner</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="sedation_required" value="1">
            <span>Sedation / Anesthesia Required</span>
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="safety_checklist_cleared" value="1" checked>
            <strong style="color: #10b981;">Safety Checklist Form Completed & Cleared</strong>
          </label>
        </div>

        <div class="grid grid-2" style="gap: 12px;">
          <div>
            <label class="form-label">Renal Function eGFR (mL/min) <span class="muted">(Required for Gadolinium IV Contrast)</span></label>
            <input type="number" step="0.1" name="egfr_renal_value" class="form-control" placeholder="e.g. 90.0">
          </div>

          <div>
            <label class="form-label">Porter / Ward Transport Notes</label>
            <input type="text" name="porter_notes" class="form-control" placeholder="e.g. Stretcher transport with continuous O2 flow">
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="closeRequisitionModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="padding: 9px 24px;">Submit Requisition</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRequisitionModal() {
  document.getElementById('radiologyRequisitionModal').style.display = 'flex';
}
function closeRequisitionModal() {
  document.getElementById('radiologyRequisitionModal').style.display = 'none';
}

function toggleSourceType(type) {
  if (type === 'inpatient') {
    document.getElementById('inpatientSourceBox').style.display = 'block';
    document.getElementById('outpatientSourceBox').style.display = 'none';
  } else {
    document.getElementById('inpatientSourceBox').style.display = 'none';
    document.getElementById('outpatientSourceBox').style.display = 'block';
  }
}

function handleInpatientSelect(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  if (opt && opt.dataset.patientId) {
    document.getElementById('radPatientSelect').value = opt.dataset.patientId;
  }
}

function updateModalityOptions(val) {
  const safetyBox = document.getElementById('mriSafetyBox');
  if (val === 'mri') {
    safetyBox.style.display = 'block';
  } else {
    // Hide or dim MRI-specific screen for ultrasound/xray
    safetyBox.style.display = 'block';
  }
}

// Dual Scrollbar Sync
document.addEventListener('DOMContentLoaded', function() {
  const topScroll = document.getElementById('radiologyTopScroll');
  const topScrollInner = document.getElementById('radiologyTopScrollInner');
  const tableWrapper = document.getElementById('radiologyTableWrapper');

  if (topScroll && topScrollInner && tableWrapper) {
    function syncWidth() {
      topScrollInner.style.width = tableWrapper.scrollWidth + 'px';
    }
    syncWidth();
    window.addEventListener('resize', syncWidth);

    topScroll.addEventListener('scroll', function() {
      tableWrapper.scrollLeft = topScroll.scrollLeft;
    });

    tableWrapper.addEventListener('scroll', function() {
      topScroll.scrollLeft = tableWrapper.scrollLeft;
    });
  }
});
</script>
