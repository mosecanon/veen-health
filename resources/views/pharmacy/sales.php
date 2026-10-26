<?php $this->layout('layouts.app', ['title' => "Today's Sales & Revenue Report"]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Sales</div>
    <h1>Today's Pharmacy Sales & Revenue</h1>
    <p class="muted">Detailed audit breakdown of over-the-counter and prescription revenue processed by payment instruments.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('pharmacy.pos') ?>" class="btn btn-primary btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span>Launch OTC POS</span>
    </a>
  </div>
</div>

<!-- Date Filter & Quick Switch -->
<div class="card" style="margin-bottom: 20px; padding: 14px 20px;">
  <form method="GET" action="<?= route('pharmacy.sales') ?>" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 8px;">
      <label style="font-size: 13px; font-weight: 700;">Sales Date:</label>
      <input type="date" name="date" class="input" value="<?= e($date) ?>" onchange="this.form.submit()" style="padding: 6px 10px; width: 160px;">
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <label style="font-size: 13px; font-weight: 700;">Payment Method:</label>
      <select name="payment_method" class="input no-search" onchange="this.form.submit()" style="padding: 6px 10px; width: 170px;">
        <option value="">All Payment Types</option>
        <option value="cash" <?= $paymentFilter === 'cash' ? 'selected' : '' ?>>Cash Only</option>
        <option value="mpesa" <?= $paymentFilter === 'mpesa' ? 'selected' : '' ?>>M-Pesa / Mobile Money</option>
        <option value="card" <?= $paymentFilter === 'card' ? 'selected' : '' ?>>Debit / Credit Card</option>
        <option value="bank_transfer" <?= $paymentFilter === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
      </select>
    </div>

    <div style="display: flex; gap: 6px;">
      <a href="<?= route('pharmacy.sales') ?>?date=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $date === date('Y-m-d') ? 'btn-primary' : 'btn-ghost' ?>">Today</a>
      <a href="<?= route('pharmacy.sales') ?>?date=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-sm <?= $date === date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-ghost' ?>">Yesterday</a>
    </div>

    <div class="spacer"></div>
    <span class="dim" style="font-size: 12px;">Showing audited sales for: <strong><?= date('D, d M Y', strtotime($date)) ?></strong></span>
  </form>
</div>

<!-- KPI Cards -->
<div class="grid grid-4" style="margin-bottom: 24px; gap: 14px;">
  <div class="card" style="padding: 18px; border-left: 4px solid var(--brand);">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Daily Revenue</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: var(--brand); margin-top: 4px;">
      <?= money($totalSalesMinor) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <strong><?= (int) ($daySummary['total_transactions'] ?? 0) ?></strong> transactions • <strong><?= (int) ($daySummary['total_units_sold'] ?? 0) ?></strong> units sold
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #10b981;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">💵 Cash Collected</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #10b981; margin-top: 4px;">
      <?= money($methodBreakdown['cash']) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <?= $totalSalesMinor > 0 ? round(($methodBreakdown['cash'] / $totalSalesMinor) * 100) : 0 ?>% of total daily volume
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #06b6d4;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">📱 M-Pesa / Mobile Money</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #06b6d4; margin-top: 4px;">
      <?= money($methodBreakdown['mpesa']) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <?= $totalSalesMinor > 0 ? round(($methodBreakdown['mpesa'] / $totalSalesMinor) * 100) : 0 ?>% digital settlements
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #8b5cf6;">
    <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">💳 Card & Bank Transfer</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #8b5cf6; margin-top: 4px;">
      <?= money($methodBreakdown['card'] + $methodBreakdown['bank_transfer']) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      Card: <?= money($methodBreakdown['card']) ?> • Bank: <?= money($methodBreakdown['bank_transfer']) ?>
    </div>
  </div>
</div>

<!-- Sales Transactions Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0;">Sales Audit Log (<?= count($sales) ?>)</h3>
      <span class="dim" style="font-size: 12px;">Real-time verified checkout events with inventory and cash-flow tracking</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('salesTable', 'pharmacy_daily_sales_<?= $date ?>')" title="Export sales report to Excel/CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 300px;">
        <input type="text" class="input" placeholder="🔍 Search receipt, patient, cashier..." onkeyup="filterTable(this, 'salesTable')" style="padding: 6px 12px; font-size: 12.5px;">
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="salesTable">
      <thead>
        <tr>
          <th>Receipt #</th>
          <th>Time</th>
          <th>Customer / Patient</th>
          <th>Medicines & Items Dispensed</th>
          <th>Payment Breakdown</th>
          <th style="text-align: right;">Total Amount</th>
          <th>Dispensed By</th>
          <th style="text-align: right;">Receipt</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sales)): ?>
          <tr>
            <td colspan="8" class="empty" style="padding: 40px; text-align: center;">
              No sales transactions recorded on <?= date('d M Y', strtotime($date)) ?>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($sales as $s): ?>
            <tr>
              <td>
                <span class="mono" style="font-weight: 800; color: var(--brand); font-size: 13.5px;">
                  <?= e($s['receipt_no']) ?>
                </span>
                <?php if ($s['prescription_ref']): ?>
                  <div style="font-size: 10px; color: #fbbf24;">Rx: <?= e($s['prescription_ref']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="mono dim" style="font-size: 12px;"><?= date('H:i', strtotime($s['created_at'])) ?></span>
              </td>
              <td>
                <strong><?= e($s['customer_name'] ?: 'Walk-in Customer') ?></strong>
                <?php if ($s['mrn']): ?>
                  <div class="mono dim" style="font-size: 11px;">MRN: <?= e($s['mrn']) ?></div>
                <?php endif; ?>
              </td>
              <td style="max-width: 280px;">
                <div style="font-size: 12px; line-height: 1.35; max-height: 40px; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($s['items_summary'] ?: 'General Medicines') ?>
                </div>
              </td>
              <td>
                <?php
                  $methods = explode(',', (string)$s['payment_methods_list']);
                  foreach (array_unique(array_filter($methods)) as $mth):
                    $mBadge = match (strtolower($mth)) {
                      'cash' => 'success',
                      'mpesa' => 'info',
                      'card' => 'purple',
                      default => 'ghost',
                    };
                ?>
                  <span class="badge badge-<?= $mBadge ?>" style="font-size: 10px; text-transform: uppercase;">
                    <?= e($mth) ?>
                  </span>
                <?php endforeach; ?>
              </td>
              <td style="text-align: right;">
                <strong class="mono" style="font-size: 14px; color: var(--text);"><?= money($s['total_minor']) ?></strong>
                <?php if ($s['discount_minor'] > 0): ?>
                  <div class="dim" style="font-size: 10px; color: var(--success);">-<?= money($s['discount_minor']) ?> disc</div>
                <?php endif; ?>
              </td>
              <td>
                <span style="font-size: 12.5px;"><?= e($s['cashier_name'] ?: 'Cashier') ?></span>
              </td>
              <td style="text-align: right;">
                <a href="<?= route('pharmacy.receipt', ['id' => $s['uuid'] ?? $s['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost" style="padding: 3px 8px; font-size: 11px;">
                  <span>Print Slip</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
