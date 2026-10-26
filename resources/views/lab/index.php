<?php $this->layout('layouts.app', ['title' => 'Laboratory & Diagnostics (LIMS)']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Diagnostics / Laboratory Information Management System</div>
    <h1>LIMS Laboratory & Diagnostics Workspace</h1>
    <p class="muted">Specimen accessioning, diagnostic test processing, abnormal value verification, and clinical reporting.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-ghost" onclick="document.getElementById('catalogModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      <span>Diagnostic Test Catalog</span>
    </button>
  </div>
</div>

<!-- Pending Test Orders Queue -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0;">Pending & In-Progress Test Orders (<?= count($pendingResults) ?> Pending)</h3>
      <span class="muted" style="font-size: 12px;">Specimens ready for collection, bench testing, and technologist analysis.</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <input type="text" id="pendingLabSearch" class="input" placeholder="🔍 Search pending tests..." oninput="tableSearch('pendingLabTable', this.value)" onkeyup="tableSearch('pendingLabTable', this.value)" style="width: 240px; padding: 6px 12px; font-size: 13px;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('pendingLabTable', 'pending_lab_orders')">
        <span>📥 Export CSV</span>
      </button>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="pendingLabTable">
      <thead>
        <tr>
          <th>Priority</th>
          <th>Order #</th>
          <th>Patient Name & MRN</th>
          <th>Test Name</th>
          <th>Specimen</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingResults)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 32px;">
              No pending laboratory tests in queue. All clinical requests have been processed!
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pendingResults as $res): ?>
            <tr>
              <td>
                <span class="badge badge-<?= $res['priority'] === 'stat' ? 'danger' : ($res['priority'] === 'urgent' ? 'warning' : 'brand') ?>">
                  <?= strtoupper($res['priority']) ?>
                </span>
              </td>
              <td>
                <strong class="mono"><?= e($res['order_no']) ?></strong>
                <div class="dim mono" style="font-size: 11px;"><?= date('H:i', strtotime($res['order_date'])) ?></div>
              </td>
              <td>
                <strong style="font-size: 15px;"><?= e($res['first_name'] . ' ' . $res['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($res['mrn']) ?> • <?= ucfirst($res['sex']) ?></div>
              </td>
              <td>
                <strong><?= e($res['test_name']) ?></strong>
                <div class="dim" style="font-size: 11px;">Ref: <?= e($res['reference_range'] ?: 'Standard') ?></div>
              </td>
              <td>
                <span class="badge badge-info"><?= e($res['specimen_type']) ?></span>
              </td>
              <td>
                <span class="badge badge-<?= $res['order_status'] === 'sample_collected' ? 'brand' : ($res['order_status'] === 'processing' ? 'success' : 'ghost') ?>">
                  <?= ucfirst(str_replace('_', ' ', $res['order_status'])) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <?php if ($res['order_status'] === 'ordered'): ?>
                    <form method="POST" action="<?= route('lab.specimen.collect', ['id' => $res['id']]) ?>" style="margin: 0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Collect Specimen</span>
                      </button>
                    </form>
                  <?php elseif ($res['order_status'] === 'sample_collected'): ?>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openResultModal(<?= htmlspecialchars(json_encode($res), ENT_QUOTES, 'UTF-8') ?>)">
                      <span>Enter Findings</span>
                    </button>
                  <?php elseif ($res['order_status'] === 'processing'): ?>
                    <form method="POST" action="<?= route('lab.verify', ['id' => $res['id']]) ?>" style="margin: 0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-primary" style="background: #10b981; border-color: #10b981;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Verify & Release</span>
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

<!-- Verified Results Log -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0;">Recently Verified Lab Results (Released to Patient EMR)</h3>
      <span class="muted" style="font-size: 12px;">Completed investigations verified by lab technologist and linked to doctor consultation notes.</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <input type="text" id="verifiedLabSearch" class="input" placeholder="🔍 Search verified results..." oninput="tableSearch('verifiedLabTable', this.value)" onkeyup="tableSearch('verifiedLabTable', this.value)" style="width: 240px; padding: 6px 12px; font-size: 13px;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('verifiedLabTable', 'verified_lab_results')">
        <span>📥 Export CSV</span>
      </button>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="verifiedLabTable">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Patient</th>
          <th>Test Name</th>
          <th>Result Value</th>
          <th>Conclusion</th>
          <th>Verified By</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($verifiedResults)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 24px;">No verified results released yet today.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($verifiedResults as $vr): ?>
            <tr>
              <td class="mono font-bold"><?= e($vr['order_no']) ?></td>
              <td><?= e($vr['first_name'] . ' ' . $vr['last_name']) ?> <span class="mono dim">(<?= e($vr['mrn']) ?>)</span></td>
              <td><strong><?= e($vr['test_name']) ?></strong></td>
              <td>
                <strong style="color: <?= $vr['is_abnormal'] ? '#ef4444' : 'var(--brand)' ?>;">
                  <?= e($vr['observed_value']) ?> <?= e($vr['unit']) ?>
                </strong>
                <?php if ($vr['is_abnormal']): ?>
                  <span class="badge badge-danger" style="margin-left: 4px;">ABNORMAL</span>
                <?php endif; ?>
              </td>
              <td class="dim" style="font-size: 12px;"><?= e($vr['technician_notes'] ?: 'Within normal limits') ?></td>
              <td><?= e($vr['verified_by_name'] ?: 'Lab Technologist') ?></td>
              <td class="dim" style="font-size: 12px;"><?= date('d M Y H:i', strtotime($vr['verified_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Enter Result Values -->
<div id="resultModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;" id="modalTestTitle">Enter Test Findings</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('resultModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="resultForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Test Result Value *</label>
          <input type="text" name="result_value" class="input" required placeholder="e.g. 13.5 or Negative">
        </div>

        <div class="field full" style="display: flex; align-items: center; gap: 10px;">
          <input type="checkbox" id="is_abnormal" name="is_abnormal" value="1" style="width: 18px; height: 18px;">
          <label for="is_abnormal" style="margin: 0; font-weight: 700; color: #ef4444; cursor: pointer;">
            Flag as Abnormal / Critical Value
          </label>
        </div>

        <div class="field full">
          <label>Microscopic / Technical Findings</label>
          <textarea name="findings" class="input" rows="2" placeholder="Detailed microscopic observations or analyzer notes..."></textarea>
        </div>

        <div class="field full">
          <label>Pathologist / Technologist Conclusion</label>
          <input type="text" name="conclusion" class="input" placeholder="e.g. Normocytic normochromic, No malaria parasites seen">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('resultModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Findings</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Catalog Preview -->
<div id="catalogModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 700px; padding: 26px; max-height: 85vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Hospital Diagnostic Test Catalog</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('catalogModal').classList.add('hidden')">&times;</button>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Test Name</th>
          <th>Category</th>
          <th>Specimen</th>
          <th>Reference Range</th>
          <th>Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($testsCatalog as $tc): ?>
          <tr>
            <td class="mono font-bold"><?= e($tc['code']) ?></td>
            <td><strong><?= e($tc['name']) ?></strong></td>
            <td><span class="badge badge-brand"><?= strtoupper(e($tc['category'])) ?></span></td>
            <td><?= e($tc['specimen_type']) ?></td>
            <td class="dim" style="font-size: 12px;"><?= e($tc['reference_range']) ?></td>
            <td class="mono font-bold"><?= format_minor($tc['price_minor'], tenant('currency')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openResultModal(data) {
  document.getElementById('modalTestTitle').textContent = data.test_name + ' (' + data.order_no + ')';
  document.getElementById('resultForm').action = '/lab/results/' + data.id + '/record';
  document.getElementById('resultModal').classList.remove('hidden');
}
</script>