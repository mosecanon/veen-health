<?php $this->layout('layouts.app', ['title' => 'Human Resources & Operating Expenses']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / HR Overview</div>
    <h1>Hospital Human Resources & Operations</h1>
    <p class="muted">Manage staff leave requests, monthly salary payrolls, and institutional operating expenses.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('hr.leaves') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span>Leaves</span>
    </a>
    <a href="<?= route('hr.payroll') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Salaries</span>
    </a>
    <a href="<?= route('hr.expenses') ?>" class="btn btn-primary">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span>Expenses</span>
    </a>
  </div>
</div>

<!-- Operational KPI Overview -->
<div class="grid grid-4" style="gap: 14px; margin-bottom: 24px;">
  <div class="card" style="padding: 18px; border-left: 4px solid var(--brand);">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Active Employees</div>
    <div class="mono" style="font-size: 28px; font-weight: 800; color: var(--brand); margin-top: 4px;">
      <?= $totalStaff ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <a href="<?= route('staff.index') ?>" style="color: var(--brand);">View Staff Directory &rarr;</a>
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #f59e0b;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Staff on Leave Today</div>
    <div class="mono" style="font-size: 28px; font-weight: 800; color: #f59e0b; margin-top: 4px;">
      <?= $onLeaveToday ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <strong><?= $pendingLeaves ?></strong> applications pending approval
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #10b981;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Payroll Net (<?= date('M Y') ?>)</div>
    <div class="mono" style="font-size: 24px; font-weight: 800; color: #10b981; margin-top: 4px;">
      <?= money($payrollSummary['total_net'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <strong><?= (int) ($payrollSummary['payroll_count'] ?? 0) ?></strong> salaries configured this month
    </div>
  </div>

  <div class="card" style="padding: 18px; border-left: 4px solid #ef4444;">
    <div class="dim" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Hospital Expenses (<?= date('M Y') ?>)</div>
    <div class="mono" style="font-size: 24px; font-weight: 800; color: #ef4444; margin-top: 4px;">
      <?= money($expensesSummary['total_expenses'] ?? 0) ?>
    </div>
    <div class="dim" style="font-size: 11px; margin-top: 4px;">
      <strong><?= (int) ($expensesSummary['expense_count'] ?? 0) ?></strong> operating vouchers logged
    </div>
  </div>
</div>

<!-- 2-Column Split: Recent Leaves & Recent Expenses -->
<div class="grid grid-2" style="gap: 16px;">
  <!-- Recent Leaves -->
  <div class="card">
    <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0;">Recent Leave Requests</h3>
      <a href="<?= route('hr.leaves') ?>" class="btn btn-sm btn-ghost">View All &rarr;</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Type</th>
            <th>Dates</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLeaves)): ?>
            <tr><td colspan="4" class="dim" style="text-align: center; padding: 24px;">No recent leave requests.</td></tr>
          <?php else: ?>
            <?php foreach ($recentLeaves as $l): ?>
              <tr>
                <td>
                  <strong><?= e($l['employee_name']) ?></strong>
                  <div class="dim" style="font-size: 11px;"><?= e($l['employee_title']) ?></div>
                </td>
                <td>
                  <span class="badge badge-brand" style="font-size: 10px; text-transform: uppercase;"><?= e($l['leave_type']) ?></span>
                </td>
                <td>
                  <span class="mono dim" style="font-size: 11.5px;"><?= date('d M', strtotime($l['start_date'])) ?> – <?= date('d M', strtotime($l['end_date'])) ?> (<?= $l['days_count'] ?>d)</span>
                </td>
                <td>
                  <span class="badge badge-<?= $l['status'] === 'approved' ? 'success' : ($l['status'] === 'rejected' ? 'danger' : 'warning') ?>" style="font-size: 10px; text-transform: uppercase;">
                    <?= e($l['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Expenses -->
  <div class="card">
    <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0;">Recent Operating Expenses</h3>
      <a href="<?= route('hr.expenses') ?>" class="btn btn-sm btn-ghost">View All &rarr;</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Title & Category</th>
            <th>Date</th>
            <th style="text-align: right;">Amount</th>
            <th>Method</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentExpenses)): ?>
            <tr><td colspan="4" class="dim" style="text-align: center; padding: 24px;">No operating expenses recorded recently.</td></tr>
          <?php else: ?>
            <?php foreach ($recentExpenses as $ex): ?>
              <tr>
                <td>
                  <strong><?= e($ex['title']) ?></strong>
                  <div class="dim" style="font-size: 11px;"><?= e(str_replace('_', ' ', $ex['category'])) ?></div>
                </td>
                <td>
                  <span class="mono dim" style="font-size: 11.5px;"><?= date('d M Y', strtotime($ex['expense_date'])) ?></span>
                </td>
                <td style="text-align: right;">
                  <strong class="mono" style="font-size: 13.5px; color: var(--danger);"><?= money($ex['amount_minor']) ?></strong>
                </td>
                <td>
                  <span class="badge badge-ghost" style="font-size: 10px; text-transform: uppercase;"><?= e($ex['payment_method']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
