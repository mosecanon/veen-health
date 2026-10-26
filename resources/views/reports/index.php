<?php $this->layout('layouts.app', ['title' => 'Hospital Financial & Operations Reports']) ?>

<style>
@media print {
  body * { visibility: hidden; }
  #printableReport, #printableReport * { visibility: visible; }
  #printableReport {
    position: absolute; left: 0; top: 0; width: 100%;
    background: #ffffff !important; color: #000000 !important;
    padding: 20px;
  }
  .no-print { display: none !important; }
}
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Analytics & Financial Reports</div>
    <h1>Hospital Financial & Operations Reports</h1>
    <p class="muted">Fiscal year auditing, revenue realization, department volume, and wholesale profit margins.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <!-- Financial Year Selector -->
    <form method="GET" action="<?= route('reports.index') ?>" style="display: flex; gap: 6px; margin: 0;">
      <select name="fy" class="input" onchange="this.form.submit()" style="padding: 7px 12px; font-weight: 600;">
        <option value="2025_2026" <?= $fy === '2025_2026' ? 'selected' : '' ?>>FY 2025/2026 (Current Fiscal Year)</option>
        <option value="2026_2027" <?= $fy === '2026_2027' ? 'selected' : '' ?>>FY 2026/2027 (Upcoming)</option>
        <option value="2024_2025" <?= $fy === '2024_2025' ? 'selected' : '' ?>>FY 2024/2025 (Previous)</option>
        <option value="cy_2026" <?= $fy === 'cy_2026' ? 'selected' : '' ?>>Calendar Year 2026</option>
        <option value="all_time" <?= $fy === 'all_time' ? 'selected' : '' ?>>All-Time Historical</option>
      </select>
    </form>

    <button type="button" class="btn btn-primary" onclick="window.print()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Executive Audit</span>
    </button>
  </div>
</div>

<div id="printableReport">
  <!-- Official Header for Print -->
  <div style="border-bottom: 2px solid var(--brand); padding-bottom: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
    <div>
      <h2 style="margin: 0; font-size: 22px; color: var(--brand);"><?= e(tenant('name', 'Veen Hospital')) ?></h2>
      <div class="dim" style="font-size: 13px; margin-top: 2px;">Comprehensive Operations & Financial Year Summary</div>
    </div>
    <div style="text-align: right;">
      <span class="badge badge-brand" style="font-size: 12px; padding: 4px 10px;"><?= e($fyLabel) ?></span>
      <div class="dim mono" style="font-size: 11px; margin-top: 4px;">Audited On: <?= date('d M Y H:i') ?></div>
    </div>
  </div>

  <!-- Primary Executive KPIs -->
  <div class="grid grid-4" style="margin-bottom: 24px;">
    <!-- Total Patients -->
    <div class="stat" style="--accent: var(--brand);">
      <div class="stat-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <div class="label">Total Patients Registered</div>
      <div class="value"><?= number_format($metrics['total_patients_all_time']) ?></div>
      <div class="delta"><?= number_format($metrics['total_patients_fy']) ?> registered during this FY period</div>
    </div>

    <!-- Gross Revenue -->
    <div class="stat" style="--accent: #10b981;">
      <div class="stat-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="label">Total Gross Billed</div>
      <div class="value"><?= format_minor($metrics['gross_revenue'], tenant('currency')) ?></div>
      <div class="delta"><?= format_minor($metrics['total_collected'], tenant('currency')) ?> successfully collected</div>
    </div>

    <!-- Outstanding -->
    <div class="stat" style="--accent: #ef4444;">
      <div class="stat-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="label">Uncollected Receivables</div>
      <div class="value"><?= format_minor($metrics['invoice_outstanding'], tenant('currency')) ?></div>
      <div class="delta">Pending patient & insurance claims</div>
    </div>

    <!-- Pharmacy Gross Profit Margin -->
    <div class="stat" style="--accent: #f59e0b;">
      <div class="stat-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      </div>
      <div class="label">Pharmacy Profit Margin</div>
      <div class="value"><?= format_minor($metrics['pharmacy_gross_profit'], tenant('currency')) ?></div>
      <div class="delta"><?= $metrics['pharmacy_margin_pct'] ?>% net margin over wholesale COGS</div>
    </div>
  </div>

  <!-- Operational Volume Breakdown Grid -->
  <div class="grid grid-2" style="margin-bottom: 24px; gap: 20px;">
    <!-- Clinical & Hospital Volume -->
    <div class="card">
      <div class="card-head">
        <h3>Clinical Department Volume</h3>
      </div>
      <div class="card-body">
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <strong>🩺 Doctor Clinical Consultations</strong>
              <div class="dim" style="font-size: 12px;">Outpatient and specialist encounters</div>
            </div>
            <strong class="mono" style="font-size: 18px; color: var(--brand);"><?= number_format($metrics['total_consultations']) ?></strong>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <strong>🔬 Laboratory Diagnostic Tests</strong>
              <div class="dim" style="font-size: 12px;">Pathology, hematology, and biochemistry</div>
            </div>
            <div style="text-align: right;">
              <strong class="mono" style="font-size: 18px;"><?= number_format($metrics['total_lab_orders']) ?></strong>
              <div class="dim" style="font-size: 11px;"><?= $metrics['verified_lab_orders'] ?> released</div>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <strong>🏨 Inpatient Bed Admissions (IPD)</strong>
              <div class="dim" style="font-size: 12px;">Ward stays, surgeries, and monitoring</div>
            </div>
            <div style="text-align: right;">
              <strong class="mono" style="font-size: 18px;"><?= number_format($metrics['total_admissions']) ?></strong>
              <div class="dim" style="font-size: 11px;"><?= $metrics['discharged_admissions'] ?> discharged</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pharmacy Wholesale Profitability Card -->
    <div class="card">
      <div class="card-head">
        <h3>Pharmacy Financial Profitability & COGS</h3>
      </div>
      <div class="card-body">
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <strong>Pharmacy Retail Sales Revenue</strong>
              <div class="dim" style="font-size: 12px;">Total OTC & prescription sales realized</div>
            </div>
            <strong class="mono" style="font-size: 16px;"><?= format_minor($metrics['pharmacy_revenue'], tenant('currency')) ?></strong>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <strong>Wholesale Cost of Goods Sold (COGS)</strong>
              <div class="dim" style="font-size: 12px;">Acquisition cost from pharmaceutical suppliers</div>
            </div>
            <strong class="mono" style="font-size: 16px; color: #ef4444;">-<?= format_minor($metrics['pharmacy_cogs'], tenant('currency')) ?></strong>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(16, 185, 129, 0.06); padding: 10px; border-radius: 8px;">
            <div>
              <strong style="color: #10b981; font-size: 15px;">Gross Pharmacy Operating Profit</strong>
              <div class="dim" style="font-size: 11px;">Surplus contribution after inventory deduction</div>
            </div>
            <div style="text-align: right;">
              <strong class="mono" style="font-size: 20px; color: #10b981;"><?= format_minor($metrics['pharmacy_gross_profit'], tenant('currency')) ?></strong>
              <div style="font-size: 11px; color: #10b981; font-weight: 700;"><?= $metrics['pharmacy_margin_pct'] ?>% Margin</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Trend Breakdown -->
  <div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
      <h3>Monthly Billing & Collections Breakdown (<?= e($fyLabel) ?>)</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Month Period</th>
            <th style="text-align: center;">Invoices Generated</th>
            <th style="text-align: right;">Billed Amount</th>
            <th style="text-align: right;">Collected Payments</th>
            <th style="text-align: right;">Period Balance Due</th>
            <th style="text-align: right;">Realization Rate</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($monthlyTrend)): ?>
            <tr><td colspan="6" class="dim" style="text-align: center; padding: 28px;">No transaction records found for this period.</td></tr>
          <?php else: ?>
            <?php foreach ($monthlyTrend as $m): ?>
              <?php
                $bal = max(0, $m['billed'] - $m['collected']);
                $pct = $m['billed'] > 0 ? round(($m['collected'] / $m['billed']) * 100) : 100;
              ?>
              <tr>
                <td><strong class="mono" style="font-size: 14px;"><?= date('F Y', strtotime($m['ym'] . '-01')) ?></strong></td>
                <td style="text-align: center;" class="mono"><?= number_format($m['inv_count']) ?></td>
                <td style="text-align: right;" class="mono font-bold"><?= format_minor($m['billed'], tenant('currency')) ?></td>
                <td style="text-align: right;" class="mono font-bold" style="color: #10b981;"><?= format_minor($m['collected'], tenant('currency')) ?></td>
                <td style="text-align: right;" class="mono font-bold" style="color: <?= $bal > 0 ? '#ef4444' : 'inherit' ?>;"><?= format_minor($bal, tenant('currency')) ?></td>
                <td style="text-align: right;">
                  <span class="badge badge-<?= $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                    <?= $pct ?>%
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Multi-Branch Performance Matrix -->
  <?php if (!empty($branchBreakdown)): ?>
  <div class="card">
    <div class="card-head">
      <h3>Multi-Branch Facility Operations & Revenue Matrix</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Branch / Facility</th>
            <th>Type</th>
            <th style="text-align: center;">Stationed Staff</th>
            <th style="text-align: center;">Registered Patients</th>
            <th style="text-align: center;">Sales Transactions</th>
            <th style="text-align: right;">Pharmacy Sales Volume</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branchBreakdown as $bb): ?>
            <tr>
              <td>
                <strong style="font-size: 14px;"><?= e($bb['name']) ?></strong>
                <span class="mono dim" style="margin-left: 6px;">(<?= e($bb['code']) ?>)</span>
              </td>
              <td>
                <span class="badge badge-<?= !empty($bb['is_main']) ? 'brand' : 'info' ?>">
                  <?= !empty($bb['is_main']) ? 'Main HQ' : 'Satellite Branch' ?>
                </span>
              </td>
              <td style="text-align: center;" class="mono"><?= (int) $bb['staff_count'] ?></td>
              <td style="text-align: center;" class="mono font-bold"><?= number_format($bb['patient_count']) ?></td>
              <td style="text-align: center;" class="mono"><?= number_format($bb['sales_count']) ?></td>
              <td style="text-align: right;" class="mono font-bold" style="color: #10b981;">
                <?= format_minor((int) $bb['sales_volume'], tenant('currency')) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
