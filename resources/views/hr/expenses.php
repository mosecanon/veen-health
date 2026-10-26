<?php $this->layout('layouts.app', ['title' => 'Hospital Operating Expenses']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('hr.index') ?>">HR</a> / Expenses</div>
    <h1>Hospital Operational Expenses</h1>
    <p class="muted">Record and audit operating expenses across medical supplies, pharmaceuticals, utilities, equipment, and rent.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('newExpenseModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Record New Expense</span>
    </button>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="card" style="margin-bottom: 20px; padding: 14px 20px;">
  <form method="GET" action="<?= route('hr.expenses') ?>" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
    <div>
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Expense Category</label>
      <select name="category" class="input no-search" style="padding: 6px 10px; width: 220px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $catKey => $catLabel): ?>
          <option value="<?= e($catKey) ?>" <?= $currentCategory === $catKey ? 'selected' : '' ?>><?= e($catLabel) ?></option>
        <?php endforeach; ?>
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
      <button type="submit" class="btn btn-primary" style="padding: 6px 14px;">Filter Expenses</button>
      <a href="<?= route('hr.expenses') ?>" class="btn btn-ghost" style="padding: 6px 10px;">Reset</a>
    </div>

    <div class="spacer"></div>
    <div style="text-align: right;">
      <span class="dim" style="font-size: 11px; text-transform: uppercase;">Total Expenses in Period</span>
      <div class="mono" style="font-size: 22px; font-weight: 800; color: var(--danger);">
        <?= money($totals['total_amount'] ?? 0) ?>
      </div>
    </div>
  </form>
</div>

<!-- Expenses Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <div>
      <h3 style="margin: 0;">Expense Audit Register (<?= count($expenses) ?> records)</h3>
      <span class="dim" style="font-size: 12px;">Audited accounting vouchers and payments</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('expensesTable', 'hospital_operating_expenses')" title="Export expenses ledger to Excel / CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 300px;">
        <input type="text" class="input" placeholder="🔍 Search title, vendor, category, receipt..." onkeyup="filterTable(this, 'expensesTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="expensesTable">
      <thead>
        <tr>
          <th>Date</th>
          <th>Expense Title & Purpose</th>
          <th>Category</th>
          <th>Vendor / Recipient</th>
          <th>Receipt / Voucher #</th>
          <th style="text-align: right;">Amount</th>
          <th>Payment Method</th>
          <th>Logged By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($expenses)): ?>
          <tr><td colspan="8" class="empty" style="text-align: center; padding: 40px;">No operational expenses recorded for this period.</td></tr>
        <?php else: ?>
          <?php foreach ($expenses as $ex): ?>
            <tr>
              <td>
                <span class="mono dim" style="font-size: 12px;"><?= date('d M Y', strtotime($ex['expense_date'])) ?></span>
              </td>
              <td>
                <strong style="font-size: 13.5px;"><?= e($ex['title']) ?></strong>
                <?php if ($ex['notes']): ?>
                  <div class="dim" style="font-size: 11px;"><?= e($ex['notes']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-brand" style="font-size: 10px;">
                  <?= e($categories[$ex['category']] ?? $ex['category']) ?>
                </span>
              </td>
              <td>
                <span style="font-size: 12.5px;"><?= e($ex['vendor_name'] ?: 'Internal / Petty Cash') ?></span>
              </td>
              <td>
                <span class="mono dim" style="font-size: 11.5px;"><?= e($ex['receipt_no'] ?: '—') ?></span>
              </td>
              <td style="text-align: right;">
                <strong class="mono" style="font-size: 14px; color: var(--danger);"><?= money($ex['amount_minor']) ?></strong>
              </td>
              <td>
                <span class="badge badge-ghost" style="font-size: 10px; text-transform: uppercase;"><?= e($ex['payment_method']) ?></span>
              </td>
              <td>
                <span class="dim" style="font-size: 11.5px;"><?= e($ex['recorded_by_name'] ?: 'Admin') ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Record New Expense -->
<div id="newExpenseModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Record Hospital Operating Expense</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newExpenseModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('hr.expenses.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Expense Category *</label>
          <select name="category" class="input no-search" required>
            <?php foreach ($categories as $catKey => $catLabel): ?>
              <option value="<?= e($catKey) ?>"><?= e($catLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label>Expense Title / Description *</label>
          <input type="text" name="title" class="input" required placeholder="e.g. Oxygen cylinder replenishment, generator diesel">
        </div>

        <div class="field">
          <label>Expense Amount (<?= tenant('currency', 'KES') ?>) *</label>
          <input type="number" step="any" min="0.01" name="amount" class="input" required placeholder="0.00">
        </div>

        <div class="field">
          <label>Payment Method *</label>
          <select name="payment_method" class="input no-search" required>
            <option value="cash" selected>Cash / Petty Cash</option>
            <option value="mpesa">M-Pesa / Mobile Money</option>
            <option value="bank_transfer">Bank Transfer / EFT</option>
            <option value="cheque">Cheque</option>
            <option value="card">Company Debit / Credit Card</option>
          </select>
        </div>

        <div class="field">
          <label>Vendor / Supplier / Payee</label>
          <input type="text" name="vendor_name" class="input" placeholder="e.g. TotalEnergies, BOC Gases">
        </div>

        <div class="field">
          <label>Receipt / Voucher #</label>
          <input type="text" name="receipt_no" class="input" placeholder="e.g. REC-89212">
        </div>

        <div class="field full">
          <label>Date of Expense *</label>
          <input type="date" name="expense_date" class="input" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="field full">
          <label>Additional Notes / Approval Justification</label>
          <textarea name="notes" class="input" rows="2" placeholder="e.g. Emergency generator refueling approved by Medical Superintendent."></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newExpenseModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Operating Expense</button>
      </div>
    </form>
  </div>
</div>
