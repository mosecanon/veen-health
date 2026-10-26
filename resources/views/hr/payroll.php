<?php $this->layout('layouts.app', ['title' => 'Salaries & Payroll Management']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('hr.index') ?>">HR</a> / Salaries</div>
    <h1>Hospital Salaries & Monthly Payroll</h1>
    <p class="muted">Generate monthly payroll schedules, configure staff allowances and statutory deductions, and track disbursements.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <form method="GET" action="<?= route('hr.payroll') ?>" style="display: flex; gap: 8px; align-items: center;">
      <label style="font-size: 13px; font-weight: 700;">Payroll Month:</label>
      <input type="month" name="month" class="input" value="<?= e($month) ?>" onchange="this.form.submit()" style="padding: 6px 10px; width: 170px;">
    </form>
  </div>
</div>

<!-- Payroll KPI Totals -->
<div class="grid grid-4" style="gap: 14px; margin-bottom: 24px;">
  <div class="card" style="padding: 18px; border-left: 4px solid var(--brand);">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Net Salary Payable</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: var(--brand); margin-top: 4px;">
      <?= money($totals['total_net'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <?= (int) ($totals['count_processed'] ?? 0) ?> of <?= count($staff) ?> staff processed for <?= date('F Y', strtotime($month . '-01')) ?>
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #3b82f6;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Basic Salaries</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #3b82f6; margin-top: 4px;">
      <?= money($totals['total_basic'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      Contractual base payroll
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #10b981;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Allowances & Bonuses</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #10b981; margin-top: 4px;">
      +<?= money($totals['total_allowances'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      Overtime, on-call, and hardship allowances
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #ef4444;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Statutory Deductions</div>
    <div class="mono" style="font-size: 26px; font-weight: 800; color: #ef4444; margin-top: 4px;">
      -<?= money($totals['total_deductions'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      Tax (PAYE), health insurance, social security
    </div>
  </div>
</div>

<!-- Payroll Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <div>
      <h3 style="margin: 0;">Payroll Register for <?= date('F Y', strtotime($month . '-01')) ?></h3>
      <span class="dim" style="font-size: 12px;">Active employees list and compensation breakdown</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('payrollTable', 'salaries_payroll_<?= $month ?>')" title="Export payroll schedule to Excel / CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 300px;">
        <input type="text" class="input" placeholder="🔍 Search employee name, title, role..." onkeyup="filterTable(this, 'payrollTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="payrollTable">
      <thead>
        <tr>
          <th>Employee Details</th>
          <th>Designation / Role</th>
          <th style="text-align: right;">Basic Salary</th>
          <th style="text-align: right;">Allowances</th>
          <th style="text-align: right;">Deductions</th>
          <th style="text-align: right;">Net Salary</th>
          <th>Payment Method</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff as $st): ?>
          <?php
            $sal = $salaryMap[$st['id']] ?? null;
            $basic = $sal ? $sal['basic_salary_minor'] : 0;
            $allow = $sal ? $sal['allowances_minor'] : 0;
            $ded = $sal ? $sal['deductions_minor'] : 0;
            $net = $sal ? $sal['net_salary_minor'] : 0;
            $status = $sal ? $sal['status'] : 'pending';
            $method = $sal ? $sal['payment_method'] : 'bank_transfer';
          ?>
          <tr>
            <td>
              <strong><?= e($st['name']) ?></strong>
              <div class="mono dim" style="font-size: 11px;"><?= e($st['email']) ?></div>
            </td>
            <td>
              <strong><?= e($st['title'] ?: 'Staff') ?></strong>
              <div class="badge badge-ghost" style="font-size: 9.5px; text-transform: uppercase;"><?= e($st['role']) ?></div>
            </td>
            <td style="text-align: right;">
              <span class="mono"><?= money($basic) ?></span>
            </td>
            <td style="text-align: right;">
              <span class="mono" style="color: var(--success);">+<?= money($allow) ?></span>
            </td>
            <td style="text-align: right;">
              <span class="mono" style="color: var(--danger);">-<?= money($ded) ?></span>
            </td>
            <td style="text-align: right;">
              <strong class="mono" style="font-size: 14px; color: var(--brand);"><?= money($net) ?></strong>
            </td>
            <td>
              <span class="badge badge-ghost" style="font-size: 10px; text-transform: uppercase;">
                <?= e(str_replace('_', ' ', $method)) ?>
              </span>
            </td>
            <td>
              <span class="badge badge-<?= $status === 'paid' ? 'success' : ($status === 'processed' ? 'info' : 'warning') ?>" style="font-size: 10px; text-transform: uppercase;">
                <?= e($status) ?>
              </span>
            </td>
            <td style="text-align: right;">
              <button type="button" class="btn btn-sm btn-ghost" onclick='openSalaryModal(<?= $st['id'] ?>, "<?= addslashes($st['name']) ?>", <?= $basic ?>, <?= $allow ?>, <?= $ded ?>, "<?= e($method) ?>", "<?= e($status) ?>", "<?= addslashes($sal['notes'] ?? '') ?>")'>
                <span>⚙ Edit Salary</span>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Configure Employee Salary -->
<div id="salaryModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 500px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h3 style="margin: 0;" id="salModalEmpName">Configure Salary</h3>
        <span class="dim" style="font-size: 12px;">Month: <strong><?= date('F Y', strtotime($month . '-01')) ?></strong></span>
      </div>
      <button type="button" class="icon-btn" onclick="document.getElementById('salaryModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('hr.payroll.store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" id="sal_user_id" name="user_id">
      <input type="hidden" name="month_year" value="<?= e($month) ?>">

      <div class="form-grid">
        <div class="field full">
          <label>Basic Contractual Salary (<?= tenant('currency', 'KES') ?>) *</label>
          <input type="number" step="any" min="0" id="sal_basic" name="basic_salary" class="input" required oninput="calcNetSalary()" placeholder="0.00">
        </div>

        <div class="field">
          <label>Allowances & Overtime</label>
          <input type="number" step="any" min="0" id="sal_allowances" name="allowances" class="input" oninput="calcNetSalary()" placeholder="0.00" value="0">
        </div>

        <div class="field">
          <label>Statutory Deductions (PAYE, NHIF, NSSF)</label>
          <input type="number" step="any" min="0" id="sal_deductions" name="deductions" class="input" oninput="calcNetSalary()" placeholder="0.00" value="0">
        </div>

        <div class="field full" style="background: rgba(20, 184, 166, 0.08); border: 1px solid var(--brand); border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-weight: 700; color: var(--brand);">Net Salary Payable:</span>
          <strong id="sal_net_preview" class="mono" style="font-size: 20px; color: var(--brand); font-weight: 800;"><?= money(0) ?></strong>
        </div>

        <div class="field">
          <label>Payment Method *</label>
          <select id="sal_method" name="payment_method" class="input no-search" required>
            <option value="bank_transfer">Bank Transfer / EFT</option>
            <option value="mpesa">M-Pesa / Mobile Money</option>
            <option value="cash">Cash</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>

        <div class="field">
          <label>Payroll Status *</label>
          <select id="sal_status" name="status" class="input no-search" required>
            <option value="pending">Pending Review</option>
            <option value="processed">Processed & Approved</option>
            <option value="paid">Paid & Disbursed</option>
          </select>
        </div>

        <div class="field full">
          <label>Payment Reference / Bank Transaction Code</label>
          <input type="text" id="sal_notes" name="notes" class="input" placeholder="e.g. Bank Batch #B77192">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('salaryModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Salary Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
const currDec = <?= (int) currency_meta()['decimals'] ?>;
const currSym = '<?= e(currency_meta()['symbol']) ?>';

function calcNetSalary() {
  const b = parseFloat(document.getElementById('sal_basic').value || 0);
  const a = parseFloat(document.getElementById('sal_allowances').value || 0);
  const d = parseFloat(document.getElementById('sal_deductions').value || 0);
  const net = Math.max(0, (b + a) - d);
  document.getElementById('sal_net_preview').innerText = `${currSym} ${net.toFixed(currDec)}`;
}

function openSalaryModal(userId, empName, basicMinor, allowMinor, dedMinor, method, status, notes) {
  document.getElementById('salModalEmpName').innerText = `Salary: ${empName}`;
  document.getElementById('sal_user_id').value = userId;
  document.getElementById('sal_basic').value = (basicMinor / Math.pow(10, currDec)).toFixed(currDec);
  document.getElementById('sal_allowances').value = (allowMinor / Math.pow(10, currDec)).toFixed(currDec);
  document.getElementById('sal_deductions').value = (dedMinor / Math.pow(10, currDec)).toFixed(currDec);
  document.getElementById('sal_method').value = method || 'bank_transfer';
  document.getElementById('sal_status').value = status || 'pending';
  document.getElementById('sal_notes').value = notes || '';
  calcNetSalary();
  document.getElementById('salaryModal').classList.remove('hidden');
}
</script>
