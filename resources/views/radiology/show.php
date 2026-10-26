<?php $this->layout('layouts.app', ['title' => $title]) ?>

<style>
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
  box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.85);
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

.timeline-step {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-left: 2px solid var(--border);
  padding-left: 18px;
  position: relative;
}
.timeline-step::before {
  content: '';
  position: absolute;
  left: -7px;
  top: 16px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--border);
}
.timeline-step.active {
  border-left-color: var(--brand);
}
.timeline-step.active::before {
  background: var(--brand);
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.2);
}
.timeline-step.done {
  border-left-color: #10b981;
}
.timeline-step.done::before {
  background: #10b981;
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb">
      <a href="<?= route('dashboard') ?>">Dashboard</a> / 
      <a href="<?= route('radiology.index') ?>">Radiology & MRI</a> / 
      <?= e($order->order_number) ?>
    </div>
    <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
      <h1 style="margin: 0;">Order <?= e($order->order_number) ?> &mdash; <?= strtoupper(str_replace('_', ' ', e($order->modality))) ?>: <?= e($order->body_part) ?></h1>
      <?php if ($order->priority === 'stat'): ?>
        <span class="badge badge-danger">⚡ STAT / EMERGENCY</span>
      <?php elseif ($order->priority === 'urgent'): ?>
        <span class="badge badge-warning">⚠️ URGENT</span>
      <?php endif; ?>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-secondary" onclick="openTransportModal()">
      <span>🛞 Update Porter Transport</span>
    </button>
    <button type="button" class="btn btn-primary" onclick="openReportModal()">
      <span>✍️ Enter Radiologist Report</span>
    </button>
    <?php if ($order->status === 'completed'): ?>
      <a href="<?= route('radiology.report', ['id' => $order->uuid ?? $order->id]) ?>" class="btn btn-ghost">
        <span>🖨️ Print Final Report</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Patient Header Card -->
<div class="card card-pad" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Patient Demographics</div>
      <div style="font-size: 20px; font-weight: 800; color: var(--brand); margin-top: 2px;">
        <a href="<?= route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" style="color: inherit; text-decoration: none;">
          <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?>
        </a>
      </div>
      <div class="muted mono" style="font-size: 13px; margin-top: 4px;">
        MRN: <?= e($patient['mrn']) ?> &bull; <?= ucfirst(e($patient['sex'])) ?> &bull; Age: <?= $patient['dob'] ? age_from_dob($patient['dob']) . ' yrs' : 'N/A' ?> &bull; Phone: <?= e($patient['phone'] ?: 'N/A') ?>
      </div>
    </div>

    <?php if ($admission): ?>
      <div style="background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.25); border-radius: 12px; padding: 12px 18px;">
        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #8b5cf6;">Admitted Inpatient</div>
        <div style="font-size: 15px; font-weight: 800; color: var(--text); margin-top: 2px;">
          Ward: <?= e($admission['ward_name']) ?> (Bed: <?= e($admission['bed_number']) ?>)
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 2px;">
          Admission #<?= e($admission['admission_number']) ?> &bull; Floor: <?= e($admission['floor'] ?? '1') ?>
        </div>
      </div>
    <?php else: ?>
      <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 12px 18px;">
        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Encounter Type</div>
        <div style="font-size: 15px; font-weight: 700; color: var(--text); margin-top: 2px;">Outpatient Imaging</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-3" style="gap: 24px; margin-bottom: 24px;">
  <!-- Column 1: Order Details & Clinical History -->
  <div class="card card-pad" style="grid-column: span 2;">
    <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      Requisition & Clinical Details
    </h3>

    <div class="grid grid-2" style="gap: 16px; margin-bottom: 16px;">
      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Study Modality</div>
        <div style="font-size: 15px; font-weight: 700; margin-top: 2px; color: var(--brand);">
          <?= strtoupper(str_replace('_', ' ', e($order->modality))) ?> &mdash; <?= e($order->body_part) ?>
        </div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Contrast Protocol</div>
        <div style="font-size: 15px; font-weight: 600; margin-top: 2px;">
          <?= ucwords(str_replace('_', ' ', e($order->contrast_type))) ?>
        </div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Ordering Practitioner</div>
        <div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
          Dr. <?= e($orderingDoctor['name'] ?? 'Attending Clinician') ?>
        </div>
      </div>

      <div>
        <div class="dim" style="font-size: 11px; text-transform: uppercase;">Scheduled At</div>
        <div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
          <?= $order->scheduled_at ? date('D, d M Y H:i', strtotime($order->scheduled_at)) : 'Immediate / On Demand' ?>
        </div>
      </div>
    </div>

    <div style="margin-bottom: 16px;">
      <div class="dim" style="font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">Clinical Indication / Reason for Exam</div>
      <div style="padding: 12px; background: var(--surface-2); border-radius: 8px; font-size: 13.5px; line-height: 1.5;">
        <?= nl2br(e($order->clinical_indication)) ?>
      </div>
    </div>

    <?php if (!empty($order->provisional_diagnosis)): ?>
      <div style="margin-bottom: 16px;">
        <div class="dim" style="font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">Provisional Clinical Diagnosis</div>
        <div style="font-size: 13.5px; font-weight: 600; color: var(--text);">
          <?= e($order->provisional_diagnosis) ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Diagnostic Report Findings (if recorded) -->
    <div style="border-top: 1px solid var(--border); padding-top: 16px; margin-top: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h3 style="font-size: 15px; font-weight: 700; margin: 0; color: #10b981;">
          Diagnostic Imaging Report & Findings
        </h3>
        <?php if ($order->status === 'completed'): ?>
          <span class="badge badge-success">✓ Final Sign-off</span>
        <?php else: ?>
          <span class="badge badge-warning">Awaiting Radiologist</span>
        <?php endif; ?>
      </div>

      <?php if (!empty($order->findings)): ?>
        <div style="margin-bottom: 14px;">
          <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Radiological Findings</div>
          <div style="padding: 14px; background: var(--surface-2); border-radius: 8px; font-size: 13px; line-height: 1.6; white-space: pre-wrap;"><?= e($order->findings) ?></div>
        </div>

        <div>
          <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--brand);">Diagnostic Impression / Conclusion</div>
          <div style="padding: 14px; background: rgba(20, 184, 166, 0.08); border-left: 4px solid var(--brand); border-radius: 8px; font-size: 14px; font-weight: 600; line-height: 1.5;"><?= e($order->impression) ?></div>
        </div>

        <div style="margin-top: 14px; font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;">
          <div>Reported by: <strong>Dr. <?= e($order->radiologist_name ?: 'Radiologist') ?></strong></div>
          <div>Completed at: <?= date('d M Y H:i', strtotime($order->completed_at)) ?></div>
        </div>
      <?php else: ?>
        <div style="padding: 24px; text-align: center; background: var(--surface-2); border-radius: 8px; color: var(--text-muted);">
          <div style="font-size: 24px; margin-bottom: 6px;">📋</div>
          <div>No diagnostic report has been filed yet.</div>
          <button type="button" class="btn btn-sm btn-primary" onclick="openReportModal()" style="margin-top: 10px;">
            Input Radiologist Findings
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Column 2: Safety Screen & Inpatient Porter Transport Tracker -->
  <div>
    <!-- MRI Safety Screening -->
    <div class="card card-pad" style="margin-bottom: 24px; border-left: 4px solid #8b5cf6;">
      <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #8b5cf6;">
        🛡️ Safety Screening Screen
      </h3>

      <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12.5px;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dotted var(--border);">
          <span>Cardiac Pacemaker / ICD</span>
          <?php if ($order->has_pacemaker): ?>
            <span class="badge badge-danger">CONTRAINDICATED</span>
          <?php else: ?>
            <span style="color: #10b981; font-weight: bold;">None</span>
          <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dotted var(--border);">
          <span>Aneurysm Clips</span>
          <?php if ($order->has_aneurysm_clips): ?>
            <span class="badge badge-danger">CONTRAINDICATED</span>
          <?php else: ?>
            <span style="color: #10b981; font-weight: bold;">None</span>
          <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dotted var(--border);">
          <span>Metallic Implants</span>
          <span><?= $order->has_metallic_implants ? '⚠️ Present (Screened)' : 'No Implants' ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dotted var(--border);">
          <span>Claustrophobia</span>
          <span><?= $order->has_claustrophobia ? '⚠️ Mild/Severe Anxiety' : 'None' ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dotted var(--border);">
          <span>Renal eGFR (Contrast Safety)</span>
          <strong><?= $order->egfr_renal_value ? e($order->egfr_renal_value) . ' mL/min' : 'Not Done' ?></strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0;">
          <span>Safety Form Cleared</span>
          <?php if ($order->safety_checklist_cleared): ?>
            <span class="badge badge-success">✓ Cleared</span>
          <?php else: ?>
            <span class="badge badge-warning">Pending</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Inpatient Porter Transport Tracker -->
    <div class="card card-pad">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 14px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
          🛞 Ward Porter Tracking
        </h3>
        <button type="button" class="btn btn-xs btn-secondary" onclick="openTransportModal()">Update</button>
      </div>

      <?php
        $stages = [
          'ordered'          => 'Scan Ordered in Ward',
          'scheduled'        => 'Slot Scheduled with Rad',
          'transporting'     => 'Porter Wheeled to Radiology',
          'in_scan'          => 'Patient in Scanner Room',
          'completed'        => 'Scan Concluded / Holding',
          'returned_to_ward' => 'Safely Returned to Ward Bed',
        ];
        $currentIdx = array_search($order->transport_status, array_keys($stages));
        if ($currentIdx === false) $currentIdx = 0;
        $i = 0;
      ?>

      <div>
        <?php foreach ($stages as $key => $label): ?>
          <?php
            $class = '';
            if ($i < $currentIdx) $class = 'done';
            elseif ($i === $currentIdx) $class = 'active';
            $i++;
          ?>
          <div class="timeline-step <?= $class ?>">
            <div>
              <div style="font-size: 12.5px; font-weight: <?= $class === 'active' ? '800' : '600' ?>; color: <?= $class === 'active' ? 'var(--brand)' : 'var(--text)' ?>;">
                <?= $label ?>
              </div>
              <?php if ($key === $order->transport_status && !empty($order->porter_notes)): ?>
                <div class="muted" style="font-size: 11px; margin-top: 2px;">
                  Note: <?= e($order->porter_notes) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 100% OPAQUE PORTER TRANSPORT MODAL         -->
<!-- ========================================== -->
<div id="transportModal" class="radiology-modal-backdrop">
  <div class="radiology-modal-content" style="max-width: 520px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 800;">Update Ward Porter Transport</h3>
      <button type="button" class="btn btn-ghost" onclick="closeTransportModal()">&times;</button>
    </div>

    <form method="POST" action="<?= route('radiology.transport.update', ['id' => $order->uuid ?? $order->id]) ?>">
      <?= csrf_field() ?>

      <div style="margin-bottom: 14px;">
        <label class="form-label">Transport Status *</label>
        <select name="transport_status" class="form-control">
          <option value="ordered" <?= $order->transport_status === 'ordered' ? 'selected' : '' ?>>Ordered (Patient in Inpatient Ward)</option>
          <option value="scheduled" <?= $order->transport_status === 'scheduled' ? 'selected' : '' ?>>Scheduled for Scanner</option>
          <option value="transporting" <?= $order->transport_status === 'transporting' ? 'selected' : '' ?>>Transporting / Wheeled via Stretcher or Chair</option>
          <option value="in_scan" <?= $order->transport_status === 'in_scan' ? 'selected' : '' ?>>Patient in Scan Room</option>
          <option value="completed" <?= $order->transport_status === 'completed' ? 'selected' : '' ?>>Scan Completed (In Recovery/Holding)</option>
          <option value="returned_to_ward" <?= $order->transport_status === 'returned_to_ward' ? 'selected' : '' ?>>Returned to Inpatient Ward Bed</option>
        </select>
      </div>

      <div style="margin-bottom: 16px;">
        <label class="form-label">Porter Notes / Escort Details</label>
        <input type="text" name="porter_notes" value="<?= e($order->porter_notes ?? '') ?>" class="form-control" placeholder="e.g. Wheeled via Stretcher 4 with nurse escort">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-secondary" onclick="closeTransportModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Transport Stage</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 100% OPAQUE RADIOLOGIST REPORT MODAL        -->
<!-- ========================================== -->
<div id="reportModal" class="radiology-modal-backdrop">
  <div class="radiology-modal-content" style="max-width: 680px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 800;">Radiologist Diagnostic Report Sign-Off</h3>
      <button type="button" class="btn btn-ghost" onclick="closeReportModal()">&times;</button>
    </div>

    <form method="POST" action="<?= route('radiology.complete', ['id' => $order->uuid ?? $order->id]) ?>">
      <?= csrf_field() ?>

      <div style="margin-bottom: 12px;">
        <label class="form-label">Consultant Radiologist Name *</label>
        <input type="text" name="radiologist_name" value="<?= e($order->radiologist_name ?: Auth::user()['name']) ?>" class="form-control" required>
      </div>

      <div style="margin-bottom: 12px;">
        <label class="form-label">Diagnostic Findings *</label>
        <textarea name="findings" class="form-control" rows="5" placeholder="Detailed radiological observations across sequences..." required><?= e($order->findings ?? '') ?></textarea>
      </div>

      <div style="margin-bottom: 16px;">
        <label class="form-label">Clinical Impression / Conclusion *</label>
        <textarea name="impression" class="form-control" rows="3" placeholder="Summary conclusion and clinical recommendations..." required><?= e($order->impression ?? '') ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Sign & Finalize Report</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTransportModal() {
  document.getElementById('transportModal').style.display = 'flex';
}
function closeTransportModal() {
  document.getElementById('transportModal').style.display = 'none';
}

function openReportModal() {
  document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
  document.getElementById('reportModal').style.display = 'none';
}
</script>
