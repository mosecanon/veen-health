<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= e($title ?? 'Veen Health') ?> — Smarter Healthcare Operations</title>
  <link rel="icon" type="image/x-icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/apple-touch-icon.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
  <div class="app">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <?php if ($tLogo = tenant('logo')): ?>
          <img src="<?= asset($tLogo) ?>" class="brand-logo-img" alt="Logo">
        <?php else: ?>
          <img src="<?= asset('img/logo-mark.png') ?>" alt="Veen Health" style="width: 36px; height: 36px; object-fit: contain; flex-shrink: 0;">
        <?php endif; ?>
        <div class="brand-text">
          <strong style="letter-spacing: -0.02em;"><?= e(tenant('name', 'Veen Health')) ?></strong>
          <span><?= e(tenant('country_code', 'KE')) ?> • Smart Health System</span>
        </div>
      </div>

      <?php if (auth() && auth()->role === 'super_admin'): ?>
      <div class="nav-label" style="color: var(--brand); font-weight: 800;">SaaS Fleet Console</div>
      <a href="<?= route('platform.dashboard') ?>" class="nav-link <?= active_route('/platform') && !active_route('/platform/plans') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Hospital Fleet</span>
      </a>
      <a href="<?= route('platform.plans') ?>" class="nav-link <?= active_route('/platform/plans') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Subscription Plans</span>
      </a>
      <?php endif; ?>

      <div class="nav-label">Core Clinical</div>
      <a href="<?= route('dashboard') ?>" class="nav-link <?= active_route('/') && !active_route('/patients') && !active_route('/queue') && !active_route('/pharmacy') && !active_route('/consultations') && !active_route('/clinic') && !active_route('/lab') && !active_route('/inpatient') && !active_route('/billing') && !active_route('/appointments') && !active_route('/reports') && !active_route('/platform') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Hospital Dashboard</span>
      </a>

      <?php if (tenant_can('consultations') && user_can('consultations')): ?>
      <a href="<?= route('consultations.index') ?>" class="nav-link <?= active_route('/consultations') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span>OPD & Consultations</span>
      </a>
      <?php endif; ?>

      <?php if ((tenant_can('clinic_kids') && user_can('clinic_kids')) || (tenant_can('clinic_maternal') && user_can('clinic_maternal')) || (tenant_can('clinic_ccc') && user_can('clinic_ccc'))): ?>
      <div class="nav-label">Maternal & Specialized Clinics</div>
      <?php if (tenant_can('clinic_kids') && user_can('clinic_kids')): ?>
      <a href="<?= route('clinic.kids.index') ?>" class="nav-link <?= active_route('/clinic/kids') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>👶 Clinic for Kids (CWC)</span>
      </a>
      <?php endif; ?>
      <?php if (tenant_can('clinic_maternal') && user_can('clinic_maternal')): ?>
      <a href="<?= route('clinic.maternal.index') ?>" class="nav-link <?= active_route('/clinic/maternal') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        <span>🤰 Pregnant Women (ANC)</span>
      </a>
      <?php endif; ?>
      <?php if (tenant_can('clinic_ccc') && user_can('clinic_ccc')): ?>
      <a href="<?= route('clinic.ccc.index') ?>" class="nav-link <?= active_route('/clinic/ccc') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>🎗️ HIV Clinic (CCC)</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if (tenant_can('appointments') && user_can('appointments')): ?>
      <a href="<?= route('appointments.index') ?>" class="nav-link <?= active_route('/appointments') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Appointments & Calendar</span>
      </a>
      <?php endif; ?>

      <?php if (user_can('patients')): ?>
      <a href="<?= route('patients.index') ?>" class="nav-link <?= active_route('/patients') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Patients (MPI)</span>
      </a>
      <?php endif; ?>

      <?php if ((tenant_can('lab') && user_can('lab')) || (tenant_can('radiology') && user_can('radiology')) || (tenant_can('inpatient') && user_can('inpatient')) || (tenant_can('mortuary') && user_can('mortuary'))): ?>
      <div class="nav-label">Diagnostics, IPD & Pathology</div>
      <?php if (tenant_can('lab') && user_can('lab')): ?>
      <a href="<?= route('lab.index') ?>" class="nav-link <?= active_route('/lab') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
        <span>Laboratory (LIMS)</span>
      </a>
      <?php endif; ?>

      <?php if (tenant_can('radiology') && user_can('radiology')): ?>
      <a href="<?= route('radiology.index') ?>" class="nav-link <?= active_route('/radiology') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
        <span>🩻 Radiology & MRI</span>
      </a>
      <?php endif; ?>

      <?php if (tenant_can('inpatient') && user_can('inpatient')): ?>
      <a href="<?= route('inpatient.index') ?>" class="nav-link <?= active_route('/inpatient') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m0 0h16m-16 0V4a2 2 0 012-2h12a2 2 0 012 2v13m0 0v3m-16 0h16M7 10h4m-4 4h8"/></svg>
        <span>Wards & Beds (IPD)</span>
      </a>
      <?php endif; ?>

      <?php if (tenant_can('mortuary') && user_can('mortuary')): ?>
      <a href="<?= route('mortuary.index') ?>" class="nav-link <?= active_route('/mortuary') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        <span>🕊️ Mortuary Registry</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if (tenant_can('queue') && user_can('queue')): ?>
      <div class="nav-label">Queue & Calling</div>
      <a href="<?= route('queue.index') ?>" class="nav-link <?= active_route('/queue') && !active_route('/queue/kiosk') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
        <span>Calling Console</span>
      </a>
      <a href="<?= route('queue.kiosk') ?>" class="nav-link <?= active_route('/queue/kiosk') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <span>Ticket Dispenser</span>
      </a>
      <?php endif; ?>

      <?php if (tenant_can('pharmacy') && (user_can('pharmacy') || user_can('pharmacy_pos'))): ?>
      <div class="nav-label">Pharmacy & OTC</div>
      <?php if (user_can('pharmacy_pos')): ?>
      <a href="<?= route('pharmacy.pos') ?>" class="nav-link <?= active_route('/pharmacy/pos') ?>" style="color: var(--brand-2);">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <strong style="font-weight: 700;">OTC Sales POS</strong>
      </a>
      <?php endif; ?>
      <?php if (user_can('pharmacy')): ?>
      <?php
        $pendingBatchCount = 0;
        if (tenant('id')) {
          $pendingBatchCount = (int) (\Veen\Core\Database\DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ? AND status = 'pending_approval'", [tenant('id')])['c'] ?? 0);
        }
      ?>
      <a href="<?= route('pharmacy.index') ?>" class="nav-link <?= active_route('/pharmacy') && !active_route('/pharmacy/pos') && !active_route('/pharmacy/sessions') && !active_route('/pharmacy/sales') && !active_route('/pharmacy/stock-movements') && !active_route('/pharmacy/batches') && !active_route('/pharmacy/suppliers') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Pharmacy Inventory</span>
      </a>
      <a href="<?= route('pharmacy.batches.index') ?>" class="nav-link <?= active_route('/pharmacy/batches') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>Restock Batches</span>
        <?php if ($pendingBatchCount > 0): ?>
          <span class="badge badge-warning" style="font-size: 10px; padding: 2px 6px; margin-left: auto;"><?= $pendingBatchCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= route('pharmacy.suppliers.index') ?>" class="nav-link <?= active_route('/pharmacy/suppliers') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Suppliers</span>
      </a>
      <a href="<?= route('pharmacy.sales') ?>" class="nav-link <?= active_route('/pharmacy/sales') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Today's Sales Report</span>
      </a>
      <a href="<?= route('pharmacy.stock_movements') ?>" class="nav-link <?= active_route('/pharmacy/stock-movements') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <span>Stock Ledger Movements</span>
      </a>
      <a href="<?= route('pharmacy.sessions.index') ?>" class="nav-link <?= active_route('/pharmacy/sessions') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Cash Register</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if (tenant_can('billing') && user_can('billing')): ?>
      <div class="nav-label">Finance & Billing</div>
      <a href="<?= route('billing.index') ?>" class="nav-link <?= active_route('/billing') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
        <span>Invoices & Claims</span>
      </a>
      <?php endif; ?>

      <?php if (tenant_can('reports') && user_can('reports_view')): ?>
      <div class="nav-label">Reports & Analytics</div>
      <a href="<?= route('reports.index') ?>" class="nav-link <?= active_route('/reports') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Financial Reports & Audits</span>
      </a>
      <?php endif; ?>

      <?php if (user_can('hospital_admin') || user_can('hr')): ?>
      <div class="nav-label">HR & Staff</div>
      <a href="<?= route('hr.index') ?>" class="nav-link <?= active_route('/hr') && !active_route('/hr/leaves') && !active_route('/hr/payroll') && !active_route('/hr/expenses') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>HR Overview</span>
      </a>
      <a href="<?= route('hr.leaves') ?>" class="nav-link <?= active_route('/hr/leaves') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Leave Management</span>
      </a>
      <a href="<?= route('hr.payroll') ?>" class="nav-link <?= active_route('/hr/payroll') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Salaries & Payroll</span>
      </a>
      <a href="<?= route('hr.expenses') ?>" class="nav-link <?= active_route('/hr/expenses') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Hospital Expenses</span>
      </a>
      <a href="<?= route('staff.index') ?>" class="nav-link <?= active_route('/staff') && !active_route('/staff/roles') ? 'active' : '' ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Staff Directory</span>
      </a>
      <a href="<?= route('staff.roles') ?>" class="nav-link <?= active_route('/staff/roles') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>Roles & Permissions</span>
      </a>
      <?php endif; ?>

      <?php if (user_can_all_branches()): ?>
      <a href="<?= route('branches.index') ?>" class="nav-link <?= active_route('/branches') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Branches & Locations</span>
      </a>
      <?php endif; ?>

      <div class="nav-label">Configuration</div>
      <a href="<?= route('settings.index') ?>" class="nav-link <?= active_route('/settings') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Settings & Profile</span>
      </a>

      <div class="sidebar-footer" style="padding: 12px 14px; border-top: 1px solid var(--border); font-size: 11px; display: flex; align-items: center; gap: 10px;">
        <img src="<?= asset('img/logo-mark.png') ?>" alt="Veen Health" style="width: 26px; height: 26px; object-fit: contain; flex-shrink: 0;">
        <div style="line-height: 1.35;">
          <div style="font-weight: 700; color: var(--text);"><?= e(tenant('name', 'Veen Health System')) ?></div>
          <div class="dim" style="font-size: 10.5px;">
            Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: var(--brand); font-weight: 700; text-decoration: none;">Veen Africa</a>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main View Area -->
    <div class="main">
      <header class="topbar">
        <button class="icon-btn menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="tenant-chip">
          <span class="tenant-dot"></span>
          <span><?= e(tenant('name', 'Hospital Workspace')) ?></span>
          <span class="badge badge-brand"><?= e(tenant('country_code', 'KE')) ?></span>
        </div>

        <!-- Multi-Branch Selector / Active Branch Chip -->
        <?php
          $tenantBranches = tenant('id') ? \Veen\Core\Database\DB::select("SELECT id, uuid, name, code, is_main FROM branches WHERE tenant_id = ? AND is_active = 1 ORDER BY is_main DESC, name ASC", [tenant('id')]) : [];
          $activeBranchId = \Veen\Core\Session::get('active_branch_id');
        ?>
        <?php if (!empty($tenantBranches) && count($tenantBranches) > 1): ?>
          <?php if (user_can_all_branches()): ?>
            <div class="branch-selector" style="position: relative; margin-left: 8px;">
              <form id="branchSwitchForm" method="POST" action="" style="display: none;">
                <?= csrf_field() ?>
              </form>
              <select class="input no-search" style="padding: 4px 10px; font-size: 12px; height: 32px; background: rgba(20, 184, 166, 0.12); border: 1px solid var(--brand); color: var(--brand); font-weight: 700; border-radius: 20px; cursor: pointer;" onchange="switchBranchView(this.value)">
                <option value="0" <?= empty($activeBranchId) ? 'selected' : '' ?>>🌐 All Branches (Consolidated)</option>
                <?php foreach ($tenantBranches as $tb): ?>
                  <option value="<?= $tb['uuid'] ?? $tb['id'] ?>" <?= ($activeBranchId && (int)$activeBranchId === (int)$tb['id']) ? 'selected' : '' ?>>
                    🏥 <?= e($tb['name']) ?> (<?= e($tb['code']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <script>
                function switchBranchView(bid) {
                  const f = document.getElementById('branchSwitchForm');
                  const baseUrl = (window.APP_BASE || '').replace(/\/$/, '');
                  f.action = baseUrl + '/branches/switch/' + encodeURIComponent(bid);
                  f.submit();
                }
              </script>
            </div>
          <?php else: ?>
            <?php $myBranch = current_branch(); ?>
            <?php if ($myBranch): ?>
              <div class="badge badge-brand" style="margin-left: 8px; font-size: 11px; padding: 4px 8px;" title="Stationed Facility">
                🏥 <?= e($myBranch['name']) ?> (<?= e($myBranch['code']) ?>)
              </div>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (auth() && auth()->role === 'super_admin' && \Veen\Core\Session::get('super_tenant_id')): ?>
          <a href="<?= route('platform.exit_tenant') ?>" class="badge badge-warning" style="text-decoration: none; padding: 6px 12px; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px;" title="Leave this hospital workspace and return to Platform Fleet Console">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Exit to Platform Fleet</span>
          </a>
        <?php endif; ?>

        <div class="spacer"></div>

        <!-- Appointment Reminders Notification -->
        <?php
          $tenantIdForReminders = tenant('id');
          if ($tenantIdForReminders && tenant_can('appointments') && user_can('appointments')):
            $todayAppCount = (int) (\Veen\Core\Database\DB::selectOne("SELECT COUNT(*) AS c FROM appointments WHERE tenant_id = ? AND appointment_date = CURDATE() AND status IN ('scheduled', 'confirmed')", [$tenantIdForReminders])['c'] ?? 0);
        ?>
        <a href="<?= route('appointments.index') ?>" class="btn btn-sm btn-ghost" style="position: relative; display: inline-flex; align-items: center; gap: 6px;" title="<?= $todayAppCount ?> appointments scheduled for today">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span>Appointments</span>
          <?php if ($todayAppCount > 0): ?>
            <span class="badge badge-brand" style="font-size: 10px; padding: 2px 6px;"><?= $todayAppCount ?> today</span>
          <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- TV Display quick preview link -->
        <?php
          $disp = \Veen\Core\Database\DB::selectOne('SELECT token FROM displays WHERE tenant_id = ? LIMIT 1', [tenant('id')]);
          if ($disp):
        ?>
        <a href="<?= route('display.show', ['token' => $disp['token']]) ?>" target="_blank" class="btn btn-sm btn-ghost" title="Open TV Calling Board in new tab">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>Open TV Display</span>
        </a>
        <?php endif; ?>

        <!-- Theme toggle -->
        <button class="icon-btn" onclick="toggleTheme()" title="Toggle light/dark theme">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <?php if (auth() && (auth()->role === 'hospital_admin' || auth()->role === 'super_admin')): ?>
        <!-- Quick Cache Flush Shortcut -->
        <form method="POST" action="<?= route('settings.cache.flush') ?>" style="margin: 0;" onsubmit="return confirm('Flush application runtime cache now?');">
          <?= csrf_field() ?>
          <button type="submit" class="icon-btn" title="Flush Application Runtime Cache (Redis/File)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: var(--warning);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          </button>
        </form>
        <?php endif; ?>

        <!-- User profile -->
        <div class="user-menu">
          <a href="<?= route('settings.index') ?>" class="avatar" title="View Profile & Settings" style="text-decoration: none;"><?= initials(auth()?->name) ?></a>
          <div class="user-meta">
            <a href="<?= route('settings.index') ?>" style="color: inherit; text-decoration: none;">
              <strong><?= e(auth()?->name) ?></strong>
            </a>
            <span><?= e(auth()?->role_label) ?></span>
          </div>
          <a href="<?= route('settings.index') ?>" class="icon-btn" title="Settings & Profile">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </a>
          <form method="POST" action="<?= route('logout') ?>" style="margin: 0;">
            <?= csrf_field() ?>
            <button type="submit" class="icon-btn" title="Sign out">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
          </form>
        </div>
      </header>

      <main class="content">
        <!-- Flash messages -->
        <?php if ($msg = flash('success')): ?>
          <div class="alert alert-success">
            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?= e($msg) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
          <div class="alert alert-error">
            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span><?= e($msg) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($allErrors = errors()): ?>
          <div class="alert alert-error" style="display: flex; gap: 10px; align-items: flex-start;">
            <svg style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
              <strong>Action could not be completed:</strong>
              <ul style="margin: 4px 0 0 16px; font-size: 13px;">
                <?php foreach ($allErrors as $field => $fieldErrors): ?>
                  <?php foreach ((array)$fieldErrors as $errText): ?>
                    <li><?= e($errText) ?></li>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($msg = flash('info')): ?>
          <div class="alert alert-info">
            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?= e($msg) ?></span>
          </div>
        <?php endif; ?>

        <?= $this->slot('content') ?>
      </main>

      <!-- App Main Footer -->
      <footer class="app-footer" style="padding: 16px 32px; border-top: 1px solid var(--border); font-size: 12.5px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: auto; background: rgba(0, 0, 0, 0.08);">
        <div style="display: flex; align-items: center; gap: 8px;">
          <img src="<?= asset('img/logo-mark.png') ?>" alt="Veen Health" style="width: 20px; height: 20px; object-fit: contain;">
          <span>© <?= date('Y') ?> <strong><?= e(tenant('name', 'Veen Health')) ?></strong>. All rights reserved.</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
          <span>Powered by</span>
          <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: var(--brand); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
            <span>Veen Africa</span>
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          </a>
        </div>
      </footer>
    </div>
  </div>

  <script>
    function toggleTheme() {
      const cur = document.documentElement.getAttribute('data-theme');
      const next = cur === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('veen_theme', next);
    }
    const saved = localStorage.getItem('veen_theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);

    window.APP_BASE = <?= json_encode(url('')) ?>;
    window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    window.PATIENTS_SEARCH_URL = <?= json_encode(route('patients.search')) ?>;
    window.PATIENTS_QUICK_STORE_URL = <?= json_encode(route('patients.quick_store')) ?>;

    // Sidebar scroll position persistence & active section centering
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.getElementById('sidebar');
      if (!sidebar) return;

      // Restore scroll or scroll active link into view
      const activeLink = sidebar.querySelector('.nav-link.active');
      const savedPos = sessionStorage.getItem('sidebar_scroll_pos');

      if (savedPos !== null) {
        sidebar.scrollTop = parseInt(savedPos, 10);
      } else if (activeLink) {
        activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
      }

      // Also ensure active link is visible if not in view
      if (activeLink) {
        const sRect = sidebar.getBoundingClientRect();
        const aRect = activeLink.getBoundingClientRect();
        if (aRect.top < sRect.top || aRect.bottom > sRect.bottom) {
          activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
        }
      }

      sidebar.addEventListener('scroll', () => {
        sessionStorage.setItem('sidebar_scroll_pos', sidebar.scrollTop);
      }, { passive: true });
    });
  </script>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
