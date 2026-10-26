<?php $this->layout('layouts.app', ['title' => 'Mortuary & Deceased Registry']) ?>

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

.maternal-modal-backdrop {
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

.maternal-modal-content {
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

[data-theme="light"] .maternal-modal-content {
  background: #ffffff !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  border-color: rgba(15, 23, 42, 0.15);
  box-shadow: 0 25px 65px -15px rgba(15, 23, 42, 0.35);
}

.maternal-modal-section {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.03);
}

[data-theme="light"] .maternal-modal-section {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Clinical Services / Pathology & Mortuary</div>
    <h1>🕊️ Mortuary & Deceased Registry</h1>
    <p class="muted">Complete hospital death certification, cold room preservation, autopsy pathology, and body release tracking.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openAdmitDeceasedModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Record Death / Admit to Mortuary</span>
    </button>
  </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid var(--brand);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">In Cold Storage</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: var(--brand); margin-top: 4px;"><?= number_format($stats['totalInColdRoom']) ?></div>
      </div>
      <div style="background: rgba(20, 184, 166, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">❄️</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Active in Cold Chambers</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #f59e0b;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Autopsies Pending</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: #f59e0b; margin-top: 4px;"><?= number_format($stats['autopsiesPending']) ?></div>
      </div>
      <div style="background: rgba(245, 158, 11, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔬</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Post-Mortem Examination</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #10b981;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Cleared for Release</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: #10b981; margin-top: 4px;"><?= number_format($stats['clearedForRelease']) ?></div>
      </div>
      <div style="background: rgba(16, 185, 129, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📋</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Permits Ready for Handover</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #8b5cf6;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Released Bodies</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: #8b5cf6; margin-top: 4px;"><?= number_format($stats['releasedCount']) ?></div>
      </div>
      <div style="background: rgba(139, 92, 246, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🕊️</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Brought In Dead (BID): <?= number_format($stats['broughtInDeadCount']) ?></div>
  </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="card card-pad" style="margin-bottom: 20px;">
  <form method="GET" action="<?= route('mortuary.index') ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
      <a href="<?= route('mortuary.index', ['status' => 'in_cold_room', 'q' => $q]) ?>" class="btn btn-sm <?= $status === 'in_cold_room' ? 'btn-primary' : 'btn-ghost' ?>">In Cold Storage (<?= $stats['totalInColdRoom'] ?>)</a>
      <a href="<?= route('mortuary.index', ['status' => 'autopsy_pending', 'q' => $q]) ?>" class="btn btn-sm <?= $status === 'autopsy_pending' ? 'btn-primary' : 'btn-ghost' ?>">Autopsy Pending (<?= $stats['autopsiesPending'] ?>)</a>
      <a href="<?= route('mortuary.index', ['status' => 'cleared_for_release', 'q' => $q]) ?>" class="btn btn-sm <?= $status === 'cleared_for_release' ? 'btn-primary' : 'btn-ghost' ?>">Cleared for Release (<?= $stats['clearedForRelease'] ?>)</a>
      <a href="<?= route('mortuary.index', ['status' => 'released', 'q' => $q]) ?>" class="btn btn-sm <?= $status === 'released' ? 'btn-primary' : 'btn-ghost' ?>">Released (<?= $stats['releasedCount'] ?>)</a>
      <a href="<?= route('mortuary.index', ['status' => 'all', 'q' => $q]) ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-ghost' ?>">All Records</a>
    </div>

    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
      <input type="hidden" name="status" value="<?= e($status) ?>" />
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search body tag, name, death certificate #, kin..." class="input" style="min-width: 280px;" />
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($q !== '' || $status !== 'all'): ?>
        <a href="<?= route('mortuary.index') ?>" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Deceased Records Table -->
<div class="card" style="position: relative;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border);">
    <div>
      <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Deceased Registry (<?= number_format($total) ?> Total Records)</h3>
      <p class="muted" style="margin: 4px 0 0; font-size: 13px;">Showing <strong><?= min($total, max(1, (($page - 1) * $perPage) + 1)) ?></strong>–<strong><?= min($total, $page * $perPage) ?></strong> of <strong><?= number_format($total) ?></strong> deceased records</p>
    </div>
  </div>

  <!-- Top Horizontal Scroll Helper -->
  <div id="mortuaryTopScroll" style="overflow-x: auto; overflow-y: hidden; height: 16px; border-bottom: 1px solid var(--border); background: var(--surface-2, rgba(255,255,255,0.02));">
    <div id="mortuaryTopScrollInner" style="height: 1px;"></div>
  </div>

  <div id="mortuaryTableWrapper" class="table-wrap" style="overflow-x: auto;">
    <table class="table" style="width: 100%; margin-bottom: 0;">
      <thead>
        <tr>
          <th style="min-width: 220px;">Deceased Name & Tag</th>
          <th style="min-width: 150px;">Death Type & Place</th>
          <th style="min-width: 150px;">Date & Time</th>
          <th style="min-width: 220px;">Certified Cause of Death</th>
          <th style="min-width: 140px;">Cold Room / Tray</th>
          <th style="min-width: 130px;">Preservation</th>
          <th style="min-width: 130px;">Storage Days & Fee</th>
          <th style="min-width: 130px;">Status</th>
          <th class="sticky-actions-col" style="min-width: 210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 40px;" class="muted">
              <div style="font-size: 32px; margin-bottom: 8px;">🕊️</div>
              <div style="font-weight: 600;">No deceased records found.</div>
              <p class="muted" style="margin: 4px 0 16px;">Record an inpatient death, casualty emergency death, or external intake.</p>
              <button type="button" class="btn btn-primary" onclick="openAdmitDeceasedModal()">+ Record Death Notification</button>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $r): ?>
            <tr>
              <td>
                <div style="font-weight: 700; font-size: 14px;">
                  <a href="<?= route('mortuary.show', ['id' => $r['uuid'] ?? $r['id']]) ?>" style="color: var(--text);">
                    <?= e($r['deceased_full_name']) ?>
                  </a>
                </div>
                <div style="display: flex; gap: 6px; align-items: center; margin-top: 4px;">
                  <span class="badge" style="background: rgba(20, 184, 166, 0.12); color: var(--brand); font-weight: 700; font-family: monospace;">
                    <?= e($r['body_tag_number']) ?>
                  </span>
                  <span class="dim" style="font-size: 11px;">
                    <?= ucfirst($r['gender']) ?><?= $r['estimated_or_actual_age'] ? " &bull; {$r['estimated_or_actual_age']} yrs" : '' ?>
                  </span>
                </div>
              </td>
              <td>
                <div>
                  <?php if ($r['death_type'] === 'inpatient'): ?>
                    <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">🏥 Inpatient Death</span>
                  <?php elseif ($r['death_type'] === 'brought_in_dead'): ?>
                    <span class="badge" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">🚨 Brought In Dead (BID)</span>
                  <?php else: ?>
                    <span class="badge" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">External Intake</span>
                  <?php endif; ?>
                </div>
                <div class="muted" style="font-size: 11px; margin-top: 3px;"><?= e($r['place_of_death'] ?: 'Facility') ?></div>
              </td>
              <td>
                <div style="font-weight: 600; font-size: 12.5px;"><?= date('d M Y', strtotime($r['date_of_death'])) ?></div>
                <div class="muted" style="font-size: 11px;"><?= date('h:i A', strtotime($r['time_of_death'])) ?></div>
              </td>
              <td>
                <div style="font-weight: 600; font-size: 12px; color: var(--text);">
                  <?= e($r['cause_of_death_immediate']) ?>
                </div>
                <?php if ($r['cause_of_death_underlying']): ?>
                  <div class="muted" style="font-size: 11px;">Due to: <?= e($r['cause_of_death_underlying']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 700; font-size: 12.5px;"><?= e($r['cold_room_chamber'] ?: 'Chamber A') ?></div>
                <div class="muted" style="font-size: 11px;"><?= e($r['tray_number'] ?: 'Tray #1') ?></div>
              </td>
              <td>
                <span class="badge badge-ghost" style="text-transform: capitalize; font-size: 11px;">
                  <?= e($r['preservation_status']) ?>
                </span>
                <?php if ($r['autopsy_required']): ?>
                  <div style="margin-top: 4px;">
                    <span class="badge <?= $r['autopsy_status'] === 'completed' ? 'badge-success' : 'badge-warning' ?>" style="font-size: 10px;">
                      Autopsy: <?= ucfirst($r['autopsy_status']) ?>
                    </span>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 700; font-size: 12.5px;"><?= $r['storage_days'] ?> day(s)</div>
                <div class="dim" style="font-size: 11px;">KES <?= number_format($r['calculated_charges'], 2) ?></div>
              </td>
              <td>
                <?php
                  $stBadge = match($r['status']) {
                    'in_cold_room'        => '<span class="badge" style="background: rgba(20, 184, 166, 0.15); color: var(--brand); font-weight: 700;">In Cold Room</span>',
                    'autopsy_pending'     => '<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-weight: 700;">Autopsy Pending</span>',
                    'cleared_for_release' => '<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #059669; font-weight: 700;">Cleared for Release</span>',
                    'released'            => '<span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; font-weight: 700;">Released to Kin</span>',
                    default               => '<span class="badge badge-ghost">Unknown</span>',
                  };
                  echo $stBadge;
                ?>
              </td>
              <td class="sticky-actions-col">
                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                  <a href="<?= route('mortuary.show', ['id' => $r['uuid'] ?? $r['id']]) ?>" class="btn btn-sm btn-primary">
                    Details
                  </a>
                  <a href="<?= route('mortuary.certificate', ['id' => $r['uuid'] ?? $r['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost" title="Medical Certificate of Cause of Death">
                    📜 Cert
                  </a>
                  <a href="<?= route('mortuary.tag', ['id' => $r['uuid'] ?? $r['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost" title="Print Body Tag & Barcode">
                    🏷️ Tag
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php if (($lastPage ?? 1) > 1 || ($total ?? 0) > 20): ?>
    <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;">
      <div class="pagination-info" style="font-size: 12.5px; color: var(--text-muted); padding: 0;">
        Showing <strong><?= min($total, (($page - 1) * $perPage) + 1) ?></strong> to <strong><?= min($total, $page * $perPage) ?></strong> of <strong><?= number_format($total) ?></strong> deceased records
      </div>

      <div style="display: flex; gap: 6px; align-items: center;">
        <?php
          $qs = '';
          if (!empty($q)) $qs .= '&q=' . urlencode($q);
          if (!empty($status)) $qs .= '&status=' . urlencode($status);
          if ($perPage !== 20) $qs .= '&per_page=' . (int)$perPage;
        ?>

        <?php if ($page > 1): ?>
          <a href="?page=<?= ($page - 1) . $qs ?>" class="btn btn-sm btn-ghost">&larr; Prev</a>
        <?php else: ?>
          <span class="btn btn-sm btn-ghost disabled" style="opacity: 0.4; cursor: not-allowed;">&larr; Prev</span>
        <?php endif; ?>

        <div class="pagination-links" style="display: flex; gap: 4px;">
          <?php for ($i = 1; $i <= $lastPage; $i++): ?>
            <?php if ($i === (int)$page): ?>
              <span class="btn btn-sm btn-primary" style="min-width: 32px; padding: 4px 8px; font-weight: 700;"><?= $i ?></span>
            <?php elseif ($i === 1 || $i === (int)$lastPage || abs($i - (int)$page) <= 2): ?>
              <a href="?page=<?= $i . $qs ?>" class="btn btn-sm btn-ghost" style="min-width: 32px; padding: 4px 8px;"><?= $i ?></a>
            <?php elseif ($i === 2 || $i === (int)$lastPage - 1): ?>
              <span class="dim" style="padding: 4px 4px;">…</span>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <?php if ($page < $lastPage): ?>
          <a href="?page=<?= ($page + 1) . $qs ?>" class="btn btn-sm btn-ghost">Next &rarr;</a>
        <?php else: ?>
          <span class="btn btn-sm btn-ghost disabled" style="opacity: 0.4; cursor: not-allowed;">Next &rarr;</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- 100% Opaque Modal: Admit Deceased Person / Record Death -->
<div id="admitDeceasedModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeAdmitDeceasedModal()">
  <div class="maternal-modal-content" style="max-width: 720px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">🕊️</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Record Death Notification & Cold Room Intake</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Official death certification, clinical causes, preservation, and cold room assignment</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeAdmitDeceasedModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('mortuary.store') ?>">
      <?= csrf_field() ?>

      <!-- 1. Pathway: Inpatient or Casualty or External -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          1. Death Pathway & Patient Linkage
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Death Intake Type *</label>
            <select name="death_type" id="death_type_select" class="input" required onchange="handleDeathTypeChange(this.value)">
              <option value="inpatient">🏥 Inpatient Ward Death</option>
              <option value="brought_in_dead">🚨 Brought In Dead (Casualty / A&E)</option>
              <option value="external_intake">External Intake / Transfer</option>
            </select>
          </div>
          <div class="form-group" id="inpatient_admission_wrap">
            <label class="label" style="font-size: 11px; font-weight: 700;">Select Inpatient Admission</label>
            <select name="admission_id" id="admission_select" class="input" onchange="autoFillInpatientDetails(this)">
              <option value="">-- Choose Admitted Patient --</option>
              <?php foreach ($activeAdmissions as $adm): ?>
                <option value="<?= $adm['id'] ?>"
                        data-name="<?= e($adm['first_name'] . ' ' . $adm['last_name']) ?>"
                        data-patient-id="<?= $adm['patient_id'] ?>"
                        data-sex="<?= e($adm['sex']) ?>"
                        data-place="Ward: <?= e($adm['ward_name']) ?>, Bed #<?= e($adm['bed_number']) ?>"
                        data-kin="<?= e($adm['next_of_kin_name'] ?? '') ?>"
                        data-phone="<?= e($adm['next_of_kin_phone'] ?? '') ?>">
                  <?= e($adm['first_name'] . ' ' . $adm['last_name']) ?> (<?= e($adm['admission_number']) ?> &bull; <?= e($adm['ward_name']) ?> Bed <?= e($adm['bed_number']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <input type="hidden" name="patient_id" id="hidden_patient_id" value="" />

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Deceased Full Name *</label>
            <input type="text" name="deceased_full_name" id="deceased_name_input" class="input" required placeholder="Full legal name..." />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Gender</label>
            <select name="gender" id="gender_select" class="input">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="unknown">Unknown</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Age (Years)</label>
            <input type="number" name="estimated_or_actual_age" id="age_input" class="input" placeholder="e.g. 54" />
          </div>
        </div>
      </div>

      <!-- 2. Date, Time & Certification -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          2. Death Occurrence & Certification
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Date of Death *</label>
            <input type="date" name="date_of_death" value="<?= date('Y-m-d') ?>" class="input" required max="<?= date('Y-m-d') ?>" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Time of Death *</label>
            <input type="time" name="time_of_death" value="<?= date('H:i') ?>" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Place of Death</label>
            <input type="text" name="place_of_death" id="place_of_death_input" class="input" placeholder="e.g. ICU Bed 2, Casualty Bay 1..." />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Certifying Doctor Name</label>
            <input type="text" name="certifying_doctor_name" value="<?= e(auth()['name'] ?? '') ?>" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Medical License #</label>
            <input type="text" name="certifying_doctor_license" placeholder="e.g. KMPDC A-14920" class="input" />
          </div>
        </div>
      </div>

      <!-- 3. WHO Medical Certificate of Cause of Death -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          3. WHO Certified Cause of Death
        </div>
        <div class="form-group" style="margin-bottom: 10px;">
          <label class="label" style="font-size: 11px; font-weight: 700;">(a) Immediate Cause of Death *</label>
          <input type="text" name="cause_of_death_immediate" class="input" required placeholder="Disease or condition directly leading to death (e.g. Cardiopulmonary arrest)" />
        </div>
        <div class="form-group" style="margin-bottom: 10px;">
          <label class="label" style="font-size: 11px;">(b) Underlying / Antecedent Cause</label>
          <input type="text" name="cause_of_death_underlying" class="input" placeholder="Antecedent disease which initiated chain of events (e.g. Severe Pneumonia, Polytrauma)" />
        </div>
        <div class="form-group">
          <label class="label" style="font-size: 11px;">(c) Other Contributory Significant Conditions</label>
          <input type="text" name="contributory_cause" class="input" placeholder="Conditions contributing to death but not related to immediate disease (e.g. Type 2 Diabetes)" />
        </div>
      </div>

      <!-- 4. Cold Storage, Preservation & Valuables -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          4. Mortuary Cold Chamber & Valuables
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Cold Chamber</label>
            <input type="text" name="cold_room_chamber" value="Cold Room A" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Tray Number</label>
            <input type="text" name="tray_number" value="Tray #04" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Preservation Mode</label>
            <select name="preservation_status" class="input">
              <option value="refrigerated">Refrigeration (Standard)</option>
              <option value="embalmed">Embalmed</option>
              <option value="frozen">Deep Frozen</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Personal Effects / Belongings Inventory</label>
            <input type="text" name="personal_effects" placeholder="e.g. Gold ring, watch, phone deposited in safe..." class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Daily Storage Rate (KES)</label>
            <input type="number" step="0.01" name="storage_fee_per_day" value="1500.00" class="input" />
          </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: center; margin-top: 6px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; font-weight: 700; color: #f59e0b;">
            <input type="checkbox" name="autopsy_required" value="1" />
            <span>Post-Mortem / Autopsy Examination Required (Pathology)</span>
          </label>
        </div>
      </div>

      <!-- 5. Next of Kin Contact -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          5. Next of Kin / Contact Details
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Next of Kin Full Name</label>
            <input type="text" name="next_of_kin_name" id="kin_name_input" class="input" placeholder="e.g. John Muthoni" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Kin Phone Number</label>
            <input type="text" name="next_of_kin_phone" id="kin_phone_input" class="input" placeholder="e.g. 0712345678" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Relationship</label>
            <input type="text" name="next_of_kin_relationship" class="input" placeholder="e.g. Spouse / Brother / Parent" />
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
        <button type="button" class="btn btn-ghost" onclick="closeAdmitDeceasedModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Admit Body & Generate Tag</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAdmitDeceasedModal() {
  var m = document.getElementById('admitDeceasedModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeAdmitDeceasedModal() {
  var m = document.getElementById('admitDeceasedModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function handleDeathTypeChange(val) {
  var wrap = document.getElementById('inpatient_admission_wrap');
  if (wrap) wrap.style.display = (val === 'inpatient') ? 'block' : 'none';
}

function autoFillInpatientDetails(selectEl) {
  var opt = selectEl.options[selectEl.selectedIndex];
  if (!opt || !opt.value) return;

  var name = opt.getAttribute('data-name');
  var patId = opt.getAttribute('data-patient-id');
  var sex = opt.getAttribute('data-sex');
  var place = opt.getAttribute('data-place');
  var kin = opt.getAttribute('data-kin');
  var phone = opt.getAttribute('data-phone');

  if (name) document.getElementById('deceased_name_input').value = name;
  if (patId) document.getElementById('hidden_patient_id').value = patId;
  if (sex) document.getElementById('gender_select').value = sex;
  if (place) document.getElementById('place_of_death_input').value = place;
  if (kin) document.getElementById('kin_name_input').value = kin;
  if (phone) document.getElementById('kin_phone_input').value = phone;
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAdmitDeceasedModal();
});

// Dual Scrollbar Sync
document.addEventListener('DOMContentLoaded', function() {
  const topScroll = document.getElementById('mortuaryTopScroll');
  const topScrollInner = document.getElementById('mortuaryTopScrollInner');
  const tableWrapper = document.getElementById('mortuaryTableWrapper');

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
