<?php $this->layout('layouts.app', ['title' => $title ?? 'Deceased Record']) ?>

<style>
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
    <div class="breadcrumb">
      <a href="<?= route('mortuary.index') ?>">Mortuary & Deceased Registry</a> /
      <?= e($record->body_tag_number) ?>
    </div>
    <h1><?= e($record->deceased_full_name) ?></h1>
    <p class="muted">
      Body Tag: <strong class="mono" style="color: var(--brand);"><?= e($record->body_tag_number) ?></strong> &bull;
      Certificate #: <strong class="mono"><?= e($record->certificate_of_death_number) ?></strong> &bull;
      Preservation: <span style="text-transform: capitalize; font-weight: 600;"><?= e($record->preservation_status) ?></span>
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="<?= route('mortuary.certificate', ['id' => $record->uuid ?? $record->id]) ?>" target="_blank" class="btn btn-primary">
      <span>📜 Death Certificate</span>
    </a>
    <a href="<?= route('mortuary.tag', ['id' => $record->uuid ?? $record->id]) ?>" target="_blank" class="btn btn-ghost">
      <span>🏷️ Print Body Tag</span>
    </a>
    <button type="button" class="btn btn-ghost" onclick="openPreservationModal()">
      <span>Edit Location & Tray</span>
    </button>
    <?php if ($record->status !== 'released'): ?>
      <button type="button" class="btn btn-ghost" style="color: #f59e0b;" onclick="openAutopsyModal()">
        <span>🔬 <?= $record->autopsy_status === 'completed' ? 'Update Autopsy' : 'Record Autopsy' ?></span>
      </button>
      <button type="button" class="btn btn-ghost" style="color: #10b981;" onclick="openReleaseModal()">
        <span>🕊️ Authorize Body Release</span>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Status Alert Banner -->
<?php if ($record->status === 'released'): ?>
  <div style="background: rgba(139, 92, 246, 0.1); border-left: 5px solid #8b5cf6; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span style="font-size: 24px;">🕊️</span>
      <div>
        <div style="font-weight: 800; color: #7c3aed; font-size: 14px;">BODY RELEASED FOR BURIAL / FUNERAL</div>
        <div style="font-size: 12px; color: var(--text);">
          Released on <?= date('d M Y, h:i A', strtotime($record->release_date)) ?> to <strong><?= e($record->collector_name) ?></strong> (ID: <?= e($record->collector_national_id) ?> &bull; Relationship: <?= e($record->collector_relationship) ?>) under Burial Permit #<strong><?= e($record->burial_permit_number) ?></strong>.
        </div>
      </div>
    </div>
    <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: #7c3aed; font-weight: 700;">Released</span>
  </div>
<?php elseif ($record->status === 'autopsy_pending'): ?>
  <div style="background: rgba(245, 158, 11, 0.1); border-left: 5px solid #f59e0b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span style="font-size: 24px;">🔬</span>
      <div>
        <div style="font-weight: 800; color: #d97706; font-size: 14px;">AUTOPSY / POST-MORTEM SCHEDULED</div>
        <div style="font-size: 12px; color: var(--text);">Awaiting post-mortem examination report prior to release authorization.</div>
      </div>
    </div>
    <span class="badge badge-warning">Autopsy Pending</span>
  </div>
<?php endif; ?>

<!-- Overview Header Cards -->
<div class="grid grid-3" style="margin-bottom: 24px;">
  <!-- Card 1: Cold Storage Location -->
  <div class="card card-pad" style="border-top: 3px solid var(--brand);">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Cold Room Placement</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--brand); margin-top: 4px;">
      <?= e($record->cold_room_chamber ?: 'Cold Room A') ?> &bull; <?= e($record->tray_number ?: 'Tray #01') ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Condition: <strong><?= e($record->body_condition ?: 'Intact') ?></strong>
    </div>
  </div>

  <!-- Card 2: Date & Time of Death -->
  <div class="card card-pad" style="border-top: 3px solid #3b82f6;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Time of Death</div>
    <div style="font-size: 20px; font-weight: 800; color: #3b82f6; margin-top: 4px;">
      <?= date('d M Y', strtotime($record->date_of_death)) ?> at <?= date('h:i A', strtotime($record->time_of_death)) ?>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Place: <strong><?= e($record->place_of_death ?: 'Facility') ?></strong>
    </div>
  </div>

  <!-- Card 3: Storage Days & Charges -->
  <div class="card card-pad" style="border-top: 3px solid #10b981;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Storage Duration & Billing</div>
    <div style="font-size: 20px; font-weight: 800; color: #10b981; margin-top: 4px;">
      <?= $storageDays ?> Days (KES <?= number_format($accruedFee, 2) ?>)
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 6px;">
      Rate: KES <?= number_format($record->storage_fee_per_day, 2) ?> / day &bull; Status: <span style="text-transform: uppercase; font-weight: 700;"><?= $record->payment_status ?></span>
    </div>
  </div>
</div>

<div class="grid grid-2" style="margin-bottom: 24px;">
  <!-- Certified Medical Cause of Death -->
  <div class="card card-pad">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Medical Cause of Death (WHO ICD Standard)</h3>
      <span class="badge badge-brand">Certified</span>
    </div>
    
    <dl class="dl">
      <dt>Immediate Cause (a)</dt>
      <dd><strong style="font-size: 14px; color: var(--text);"><?= e($record->cause_of_death_immediate) ?></strong></dd>

      <dt>Underlying Cause (b)</dt>
      <dd><?= e($record->cause_of_death_underlying ?: 'None recorded') ?></dd>

      <dt>Contributory Conditions (c)</dt>
      <dd><?= e($record->contributory_cause ?: 'None') ?></dd>

      <dt>Certifying Doctor</dt>
      <dd>
        <strong><?= e($record->certifying_doctor_name ?: 'Medical Officer') ?></strong>
        <?php if ($record->certifying_doctor_license): ?>
          <span class="muted">(License: <?= e($record->certifying_doctor_license) ?>)</span>
        <?php endif; ?>
      </dd>

      <dt>Certificate Number</dt>
      <dd><span class="mono" style="font-weight: 700;"><?= e($record->certificate_of_death_number) ?></span></dd>
    </dl>
  </div>

  <!-- Next of Kin & Personal Effects -->
  <div class="card card-pad">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Next of Kin & Personal Effects Inventory</h3>
      <span class="badge badge-ghost">Custody Log</span>
    </div>

    <dl class="dl">
      <dt>Next of Kin Name</dt>
      <dd><strong><?= e($record->next_of_kin_name ?: 'Not recorded') ?></strong></dd>

      <dt>Phone Contact</dt>
      <dd><?= e($record->next_of_kin_phone ?: 'N/A') ?></dd>

      <dt>Relationship</dt>
      <dd><?= e($record->next_of_kin_relationship ?: 'Relative') ?></dd>

      <dt>Personal Effects</dt>
      <dd>
        <div style="background: rgba(0,0,0,0.03); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); font-size: 13px;">
          <?= e($record->personal_effects ?: 'No personal belongings deposited with mortuary custody.') ?>
        </div>
      </dd>
    </dl>
  </div>
</div>

<!-- Autopsy & Pathology Section (If applicable) -->
<?php if ($record->autopsy_required): ?>
  <div class="card card-pad" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Post-Mortem / Autopsy Examination (Pathology Report)</h3>
      <span class="badge <?= $record->autopsy_status === 'completed' ? 'badge-success' : 'badge-warning' ?>">
        <?= ucfirst($record->autopsy_status) ?>
      </span>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
      <div><strong>Pathologist:</strong> <?= e($record->pathologist_name ?: 'Pending assignment') ?></div>
      <div><strong>Examination Date:</strong> <?= $record->autopsy_date ? date('d M Y', strtotime($record->autopsy_date)) : 'Pending' ?></div>
    </div>

    <div style="background: rgba(0,0,0,0.03); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border);">
      <div style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--brand); margin-bottom: 6px;">Autopsy Findings & Anatomic Diagnosis:</div>
      <p style="margin: 0; font-size: 13px; line-height: 1.6; white-space: pre-wrap;"><?= e($record->autopsy_findings ?: 'Autopsy examination has not been recorded yet. Click "Record Autopsy" to enter post-mortem report.') ?></p>
    </div>
  </div>
<?php endif; ?>

<!-- ================= MODALS ================= -->

<!-- 1. Edit Cold Room Location Modal -->
<div id="preservationModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closePreservationModal()">
  <div class="maternal-modal-content" style="max-width: 580px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Update Cold Storage & Preservation</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closePreservationModal()">✕</button>
    </div>

    <form method="POST" action="<?= route('mortuary.preservation.update', ['id' => $record->uuid ?? $record->id]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Cold Chamber</label>
            <input type="text" name="cold_room_chamber" value="<?= e($record->cold_room_chamber) ?>" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Tray Number</label>
            <input type="text" name="tray_number" value="<?= e($record->tray_number) ?>" class="input" required />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Preservation Mode</label>
            <select name="preservation_status" class="input">
              <option value="refrigerated" <?= $record->preservation_status === 'refrigerated' ? 'selected' : '' ?>>Refrigerated</option>
              <option value="embalmed" <?= $record->preservation_status === 'embalmed' ? 'selected' : '' ?>>Embalmed</option>
              <option value="frozen" <?= $record->preservation_status === 'frozen' ? 'selected' : '' ?>>Deep Frozen</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Body Condition</label>
            <input type="text" name="body_condition" value="<?= e($record->body_condition) ?>" class="input" />
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px;">Personal Effects & Valuables Inventory</label>
          <textarea name="personal_effects" rows="3" class="input"><?= e($record->personal_effects) ?></textarea>
        </div>

        <div class="form-group">
          <label class="label" style="font-size: 11px;">Daily Storage Rate (KES)</label>
          <input type="number" step="0.01" name="storage_fee_per_day" value="<?= e($record->storage_fee_per_day) ?>" class="input" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closePreservationModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Record Autopsy Modal -->
<div id="autopsyModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeAutopsyModal()">
  <div class="maternal-modal-content" style="max-width: 600px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Record Autopsy / Post-Mortem Report</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeAutopsyModal()">✕</button>
    </div>

    <form method="POST" action="<?= route('mortuary.autopsy.store', ['id' => $record->uuid ?? $record->id]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Examination Date</label>
            <input type="date" name="autopsy_date" value="<?= date('Y-m-d') ?>" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Pathologist Name</label>
            <input type="text" name="pathologist_name" value="<?= e($record->pathologist_name ?: (auth()['name'] ?? 'Pathologist')) ?>" class="input" required />
          </div>
        </div>

        <div class="form-group">
          <label class="label" style="font-size: 11px; font-weight: 700;">Pathological Findings & Anatomic Diagnosis *</label>
          <textarea name="autopsy_findings" rows="5" class="input" required placeholder="Detailed post-mortem observations, internal organ pathology, toxicology, and final anatomical diagnosis..."><?= e($record->autopsy_findings) ?></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closeAutopsyModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Autopsy & Clear Body</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Authorize Body Release Modal -->
<div id="releaseModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeReleaseModal()">
  <div class="maternal-modal-content" style="max-width: 620px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Authorize Body Release & Handover</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeReleaseModal()">✕</button>
    </div>

    <form method="POST" action="<?= route('mortuary.release.store', ['id' => $record->uuid ?? $record->id]) ?>">
      <?= csrf_field() ?>

      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          Authorized Collector Verification
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Collector Full Name *</label>
            <input type="text" name="collector_name" value="<?= e($record->next_of_kin_name) ?>" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Collector National ID / Passport *</label>
            <input type="text" name="collector_national_id" class="input" required placeholder="e.g. 28491023" />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Collector Phone Number</label>
            <input type="text" name="collector_phone" value="<?= e($record->next_of_kin_phone) ?>" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Relationship to Deceased</label>
            <input type="text" name="collector_relationship" value="<?= e($record->next_of_kin_relationship) ?>" class="input" placeholder="e.g. Spouse / Sibling" />
          </div>
        </div>
      </div>

      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; color: var(--brand);">
          Permits & Funeral Transportation
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px; font-weight: 700;">Burial Permit Number *</label>
            <input type="text" name="burial_permit_number" class="input" required placeholder="e.g. BP-2026-88910" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Hearse Vehicle Registration</label>
            <input type="text" name="hearse_registration" class="input" placeholder="e.g. KDF 123Z" />
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="label" style="font-size: 11px;">Funeral Home / Hearse Service Provider</label>
          <input type="text" name="funeral_home_name" class="input" placeholder="e.g. Montezuma Monalisa Funeral Services" />
        </div>

        <div class="form-group">
          <label class="label" style="font-size: 11px;">Handover Notes</label>
          <input type="text" name="release_notes" class="input" placeholder="All personal effects returned, body handed over in dignified condition..." />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="closeReleaseModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">Complete Release & Handover</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPreservationModal() {
  var m = document.getElementById('preservationModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closePreservationModal() {
  var m = document.getElementById('preservationModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function openAutopsyModal() {
  var m = document.getElementById('autopsyModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeAutopsyModal() {
  var m = document.getElementById('autopsyModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function openReleaseModal() {
  var m = document.getElementById('releaseModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeReleaseModal() {
  var m = document.getElementById('releaseModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closePreservationModal();
    closeAutopsyModal();
    closeReleaseModal();
  }
});
</script>
