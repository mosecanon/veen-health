<?php $this->layout('layouts.app', ['title' => $title]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Restock Batches</div>
    <h1>Stock Restocks & Batch Approvals</h1>
    <p class="muted">Monitor incoming medicine batches, supervise restock approvals, and verify delivery notes before inventory activation.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('pharmacy.suppliers.index') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      <span>Suppliers Directory</span>
    </a>
    <a href="<?= route('pharmacy.batches.receive') ?>" class="btn btn-primary">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Receive New Stock</span>
    </a>
  </div>
</div>

<!-- Filter Tabs & Stats -->
<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 14px;">
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="<?= route('pharmacy.batches.index') ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-ghost' ?>">
      All Restocks (<?= $counts['all'] ?>)
    </a>
    <a href="<?= route('pharmacy.batches.index', ['status' => 'pending_approval']) ?>" class="btn btn-sm <?= $status === 'pending_approval' ? 'btn-warning' : 'btn-ghost' ?>" style="display: inline-flex; align-items: center; gap: 6px;">
      <span>⏳ Pending Approval</span>
      <span class="badge badge-warning" style="font-size: 11px; padding: 2px 6px;"><?= $counts['pending'] ?></span>
    </a>
    <a href="<?= route('pharmacy.batches.index', ['status' => 'approved']) ?>" class="btn btn-sm <?= $status === 'approved' ? 'btn-primary' : 'btn-ghost' ?>">
      ✅ Approved (<?= $counts['approved'] ?>)
    </a>
    <a href="<?= route('pharmacy.batches.index', ['status' => 'rejected']) ?>" class="btn btn-sm <?= $status === 'rejected' ? 'btn-danger' : 'btn-ghost' ?>">
      ❌ Rejected (<?= $counts['rejected'] ?>)
    </a>
  </div>

  <div style="display: flex; gap: 8px; align-items: center;">
    <input type="text" id="batchSearchInput" class="input" placeholder="🔍 Search batch #, medicine, supplier..." onkeyup="tableSearch('batchesTable', this.value)" style="width: 260px; padding: 6px 12px; font-size: 13px;">
    <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('batchesTable', 'pharmacy_restock_batches')">
      <span>📥 Export CSV</span>
    </button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table" id="batchesTable">
      <thead>
        <tr>
          <th>Batch # / Delivery Note</th>
          <th>Product / Medicine</th>
          <th>Supplier</th>
          <th>Qty (Recv / Hand)</th>
          <th>Unit Cost / Total</th>
          <th>Expiry Date</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($batches)): ?>
          <tr>
            <td colspan="8" class="dim" style="text-align: center; padding: 36px;">
              No restock batches found matching this criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($batches as $b): ?>
            <?php
              $isPending = ($b['status'] ?? 'approved') === 'pending_approval';
              $isApproved = ($b['status'] ?? 'approved') === 'approved';
              $isRejected = ($b['status'] ?? 'approved') === 'rejected';
              $totalValue = (int) $b['cost_minor'] * (int) $b['qty_received'];
              $daysToExpiry = (int) round((strtotime($b['expiry_date']) - time()) / 86400);
            ?>
            <tr>
              <td>
                <strong class="mono" style="font-size: 14px;"><?= e($b['batch_no']) ?></strong>
                <?php if (!empty($b['delivery_note_no'])): ?>
                  <div class="dim mono" style="font-size: 11px;">DN: <?= e($b['delivery_note_no']) ?></div>
                <?php endif; ?>
                <div class="dim" style="font-size: 10px; margin-top: 2px;">
                  <?= date('d M Y H:i', strtotime($b['received_at'])) ?>
                </div>
              </td>
              <td>
                <strong style="font-size: 14px;"><?= e($b['product_name']) ?></strong>
                <div class="dim" style="font-size: 11px;">
                  <?= e($b['strength'] ?? '') ?> <?= e($b['form'] ?? '') ?>
                  <?php if (!empty($b['sku'])): ?> • <span class="mono">SKU: <?= e($b['sku']) ?></span><?php endif; ?>
                </div>
              </td>
              <td>
                <?php if (!empty($b['supplier_name'])): ?>
                  <span class="badge badge-brand"><?= e($b['supplier_name']) ?></span>
                <?php else: ?>
                  <span class="dim">Direct / Unknown</span>
                <?php endif; ?>
              </td>
              <td>
                <strong style="font-size: 14px;"><?= number_format($b['qty_received']) ?> units</strong>
                <div class="dim" style="font-size: 11px;">
                  On Hand: <strong class="mono"><?= number_format($b['qty_on_hand']) ?></strong>
                </div>
              </td>
              <td>
                <div><?= format_minor($b['cost_minor'], tenant('currency')) ?> / unit</div>
                <strong class="mono" style="font-size: 12px; color: var(--brand);">Total: <?= format_minor($totalValue, tenant('currency')) ?></strong>
              </td>
              <td>
                <div class="mono font-bold" style="font-size: 13px;"><?= date('d M Y', strtotime($b['expiry_date'])) ?></div>
                <?php if ($daysToExpiry < 0): ?>
                  <span class="badge badge-danger" style="font-size: 10px;">EXPIRED (<?= abs($daysToExpiry) ?>d ago)</span>
                <?php elseif ($daysToExpiry <= 90): ?>
                  <span class="badge badge-warning" style="font-size: 10px;">EXPIRING (<?= $daysToExpiry ?>d)</span>
                <?php else: ?>
                  <span class="dim" style="font-size: 11px;"><?= $daysToExpiry ?> days remaining</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($isPending): ?>
                  <span class="badge badge-warning" style="font-size: 11px; font-weight: 700;">⏳ Pending Approval</span>
                  <div class="dim" style="font-size: 10px; margin-top: 3px;">By: <?= e($b['received_by_name'] ?? 'Staff') ?></div>
                <?php elseif ($isApproved): ?>
                  <span class="badge badge-success" style="font-size: 11px; font-weight: 700;">✅ Approved</span>
                  <?php if (!empty($b['approved_by_name'])): ?>
                    <div class="dim" style="font-size: 10px; margin-top: 3px;">By: <?= e($b['approved_by_name']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-danger" style="font-size: 11px; font-weight: 700;">❌ Rejected</span>
                  <?php if (!empty($b['rejection_reason'])): ?>
                    <div class="dim" style="font-size: 10px; margin-top: 3px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($b['rejection_reason']) ?>">
                      <?= e($b['rejection_reason']) ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <?php if ($isPending): ?>
                  <div style="display: inline-flex; gap: 6px; align-items: center;">
                    <a href="<?= route('pharmacy.batches.edit', ['id' => $b['id']]) ?>" class="btn btn-sm btn-ghost" title="Edit batch details before approval">
                      ✏️ Edit
                    </a>

                    <!-- Approve Restock Form -->
                    <form method="POST" action="<?= route('pharmacy.batches.approve', ['id' => $b['id']]) ?>" style="margin: 0;" onsubmit="return confirm('Approve batch <?= e($b['batch_no']) ?>? <?= $b['qty_received'] ?> units will be immediately added to active stock.');">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-primary" title="Approve and add to inventory">
                        ✅ Approve
                      </button>
                    </form>

                    <!-- Reject Restock Trigger -->
                    <button type="button" class="btn btn-sm btn-ghost" style="color: var(--danger);" onclick="openRejectModal(<?= $b['id'] ?>, '<?= e($b['batch_no']) ?>', '<?= e($b['product_name']) ?>')" title="Reject restock">
                      ❌ Reject
                    </button>
                  </div>
                <?php elseif ($isApproved): ?>
                  <span class="badge badge-ghost" style="font-size: 11px;" title="Approved restocks are permanently locked to preserve inventory audit integrity">
                    🔒 Locked
                  </span>
                <?php else: ?>
                  <span class="dim" style="font-size: 11px;">Rejected</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Reject Restock Batch -->
<div id="rejectModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 480px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; color: var(--danger);">Reject Stock Restock</h3>
      <button type="button" class="icon-btn" onclick="closeRejectModal()">&times;</button>
    </div>

    <form id="rejectForm" method="POST" action="">
      <?= csrf_field() ?>
      <p id="rejectModalDesc" class="muted" style="margin-top: 0; font-size: 13px;"></p>

      <div class="field" style="margin-bottom: 18px;">
        <label for="rejection_reason">Reason for Rejection *</label>
        <textarea id="rejection_reason" name="rejection_reason" class="input" rows="3" required placeholder="e.g. Broken packaging, discrepancy in count, near expiry on delivery..."></textarea>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRejectModal(batchId, batchNo, productName) {
  document.getElementById('rejectModalDesc').innerText = `You are rejecting Batch ${batchNo} for ${productName}. This batch will NOT be added to active stock.`;
  document.getElementById('rejectForm').action = `/pharmacy/batches/${batchId}/reject`;
  document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
  document.getElementById('rejectModal').classList.add('hidden');
}
</script>
