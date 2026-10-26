<?php $this->layout('layouts.app', ['title' => 'Detailed Inventory Stock Ledger']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Stock Movements</div>
    <h1>Inventory Stock Ledger & Movements</h1>
    <p class="muted">Complete audit trail of all inventory inflows, sales dispensations, batch returns, and adjustments.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('pharmacy.batches.receive') ?>" class="btn btn-primary">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Receive New Stock Batch</span>
    </a>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="card" style="margin-bottom: 20px; padding: 16px 20px; position: relative; z-index: 100; overflow: visible !important;">
  <form method="GET" action="<?= route('pharmacy.stock_movements') ?>" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; position: relative; z-index: 100; overflow: visible !important;">
    <div style="position: relative; z-index: 110; min-width: 240px; overflow: visible !important;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Medicine / Product</label>
      <select name="product_id" class="input" style="padding: 6px 10px; width: 240px;">
        <option value="0">All Medicines</option>
        <?php foreach ($products as $pr): ?>
          <option value="<?= $pr['id'] ?>" <?= $productId == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?> (<?= e($pr['sku']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="position: relative; z-index: 105; overflow: visible !important;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Movement Type</label>
      <select name="type" class="input no-search" style="padding: 6px 10px; width: 160px;">
        <option value="">All Movement Types</option>
        <option value="receive" <?= $type === 'receive' ? 'selected' : '' ?>>Stock Received (+)</option>
        <option value="sale" <?= $type === 'sale' ? 'selected' : '' ?>>Sale Dispensed (-)</option>
        <option value="adjust" <?= $type === 'adjust' ? 'selected' : '' ?>>Inventory Adjustment</option>
        <option value="return" <?= $type === 'return' ? 'selected' : '' ?>>Supplier / Patient Return</option>
        <option value="expire" <?= $type === 'expire' ? 'selected' : '' ?>>Damaged / Expired (-)</option>
      </select>
    </div>

    <div>
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">From Date</label>
      <input type="date" name="date_from" class="input" value="<?= e($dateFrom) ?>" style="padding: 6px 10px; width: 140px;">
    </div>

    <div>
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">To Date</label>
      <input type="date" name="date_to" class="input" value="<?= e($dateTo) ?>" style="padding: 6px 10px; width: 140px;">
    </div>

    <div>
      <button type="submit" class="btn btn-primary" style="padding: 6px 14px;">Filter Ledger</button>
      <a href="<?= route('pharmacy.stock_movements') ?>" class="btn btn-ghost" style="padding: 6px 10px;">Reset</a>
    </div>
  </form>
</div>

<!-- Ledger Table -->
<div class="card" style="position: relative; z-index: 1;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0;">Stock Movement Log (<?= count($movements) ?> entries)</h3>
      <span class="dim" style="font-size: 12px;">Traceable perpetual inventory history</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('movementsTable', 'stock_ledger_movements')" title="Export inventory ledger to CSV / Excel">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 300px;">
        <input type="text" class="input" placeholder="🔍 Search medicine, batch, note, staff..." onkeyup="filterTable(this, 'movementsTable')" style="padding: 6px 12px; font-size: 12.5px;">
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="movementsTable">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Medicine / Product</th>
          <th>Batch #</th>
          <th>Type</th>
          <th style="text-align: center;">Qty Changed</th>
          <th>Reference</th>
          <th>Staff Attributed</th>
          <th>Audit Note</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movements)): ?>
          <tr>
            <td colspan="8" class="empty" style="padding: 40px; text-align: center;">
              No stock movements recorded for the selected criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($movements as $m): ?>
            <tr>
              <td>
                <span class="mono dim" style="font-size: 12px;"><?= date('d M Y H:i', strtotime($m['created_at'])) ?></span>
              </td>
              <td>
                <strong style="font-size: 13.5px;"><?= e($m['product_name']) ?></strong>
                <div class="dim" style="font-size: 11px;"><?= e($m['generic_name']) ?> • <?= e($m['sku']) ?></div>
              </td>
              <td>
                <span class="mono" style="font-weight: 700; color: var(--brand);"><?= e($m['batch_no'] ?: '—') ?></span>
                <?php if ($m['expiry_date']): ?>
                  <div class="dim" style="font-size: 10px;">Exp: <?= date('M Y', strtotime($m['expiry_date'])) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $badge = match ($m['type']) {
                    'receive' => 'success',
                    'sale' => 'brand',
                    'adjust' => 'warning',
                    'return' => 'info',
                    'expire' => 'danger',
                    default => 'ghost',
                  };
                ?>
                <span class="badge badge-<?= $badge ?>" style="font-size: 10.5px; text-transform: uppercase;">
                  <?= e($m['type']) ?>
                </span>
              </td>
              <td style="text-align: center;">
                <span class="mono font-bold" style="font-size: 14px; color: <?= $m['qty'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                  <?= $m['qty'] > 0 ? '+' . $m['qty'] : $m['qty'] ?>
                </span>
              </td>
              <td>
                <span class="mono dim" style="font-size: 11.5px;"><?= e($m['reference_type']) ?> #<?= e($m['reference_id'] ?: '—') ?></span>
              </td>
              <td>
                <span style="font-size: 12px;"><?= e($m['user_name'] ?: 'System Process') ?></span>
              </td>
              <td style="max-width: 250px;">
                <span class="dim" style="font-size: 11.5px;"><?= e($m['note'] ?: '—') ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
