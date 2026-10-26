<?php $this->layout('layouts.app', ['title' => 'Patient Billing & Invoices']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Finance & Cashier / Patient Billing</div>
    <h1>Invoicing & Financial Management</h1>
    <p class="muted">Itemized patient bills, cash/M-Pesa/MoMo payments, and insurance claims reconciliation.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('billing.claims') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      <span>Insurance Claims (<?= $metrics['pending_claims'] ?>)</span>
    </a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('newInvoiceModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Create Patient Invoice</span>
    </button>
  </div>
</div>

<!-- Revenue KPIs -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
    </div>
    <div class="label">Total Billed</div>
    <div class="value"><?= format_minor($metrics['total_billed'], tenant('currency')) ?></div>
    <div class="delta">Cumulative hospital gross billing</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <div class="label">Collected Payments</div>
    <div class="value"><?= format_minor($metrics['total_collected'], tenant('currency')) ?></div>
    <div class="delta">Realized revenue in register</div>
  </div>

  <div class="stat" style="--accent: #ef4444;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Outstanding Balances</div>
    <div class="value"><?= format_minor($metrics['total_outstanding'], tenant('currency')) ?></div>
    <div class="delta">Pending collections & insurance</div>
  </div>

  <div class="stat" style="--accent: #f59e0b;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <div class="label">Pending Claims</div>
    <div class="value"><?= $metrics['pending_claims'] ?></div>
    <div class="delta">Awaiting payer adjudication</div>
  </div>
</div>

<!-- Invoices Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <h3>Hospital Patient Invoices</h3>
    <div style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('invoicesTable', 'billing_invoices')" title="Export invoices to Excel / CSV">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </button>
      <div style="width: 100%; max-width: 300px;">
        <input type="text" class="input" placeholder="🔍 Filter invoices on keyup..." onkeyup="filterTable(this, 'invoicesTable')" style="padding: 6px 12px; font-size: 13px;">
      </div>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="invoicesTable">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Patient Name & MRN</th>
          <th>Billing Type</th>
          <th>Total Bill</th>
          <th>Paid</th>
          <th>Balance Due</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr>
            <td colspan="8" class="dim" style="text-align: center; padding: 32px;">No patient invoices recorded yet. Click "Create Patient Invoice" above.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td>
                <strong class="mono" style="color: var(--brand);"><?= e($inv['invoice_no']) ?></strong>
                <div class="dim" style="font-size: 11px;"><?= date('d M Y H:i', strtotime($inv['created_at'])) ?></div>
              </td>
              <td>
                <strong style="font-size: 15px;"><?= e($inv['first_name'] . ' ' . $inv['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($inv['mrn']) ?></div>
              </td>
              <td>
                <span class="badge badge-brand"><?= !empty($inv['patient_insurance']) ? 'INSURANCE' : 'CASH' ?></span>
                <?php if (!empty($inv['patient_insurance'])): ?>
                  <div class="dim" style="font-size: 11px; margin-top: 2px;"><?= e($inv['patient_insurance']) ?></div>
                <?php endif; ?>
              </td>
              <td><strong class="mono"><?= format_minor($inv['total_minor'], tenant('currency')) ?></strong></td>
              <td><span class="mono" style="color: #10b981;"><?= format_minor($inv['paid_minor'], tenant('currency')) ?></span></td>
              <td>
                <strong class="mono" style="color: <?= $inv['balance_minor'] > 0 ? '#ef4444' : 'var(--text)' ?>;">
                  <?= format_minor($inv['balance_minor'], tenant('currency')) ?>
                </strong>
              </td>
              <td>
                <span class="badge badge-<?= $inv['status'] === 'paid' ? 'success' : ($inv['status'] === 'partial' ? 'warning' : 'danger') ?>">
                  <?= ucfirst(str_replace('_', ' ', $inv['status'])) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="<?= route('billing.show', ['id' => $inv['uuid'] ?? $inv['id']]) ?>" class="btn btn-sm btn-primary">
                  <span>View / Settle</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Create New Invoice -->
<div id="newInvoiceModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Create Patient Invoice</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newInvoiceModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('billing.create') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Select Patient *</label>
          <input type="text" id="bill_patient_input" class="input" placeholder="Search patient name, MRN, phone, ID..." onfocus="searchBillPatient(this.value)" oninput="searchBillPatient(this.value)" autocomplete="off">
          <input type="hidden" id="bill_patient_id" name="patient_id" required>
          <div id="bill_search_results" style="margin-top: 6px; max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; background: var(--surface);"></div>
        </div>

        <div class="field">
          <label>Billing Method *</label>
          <select name="billing_type" class="input" required onchange="toggleInsurerField(this.value)">
            <option value="cash" selected>Cash / Self Pay</option>
            <option value="insurance">Insurance Payer</option>
            <option value="corporate">Corporate Account</option>
          </select>
        </div>

        <div class="field" id="insurerField" style="display: none;">
          <label>Insurance Provider</label>
          <select name="insurance_provider_id" class="input">
            <option value="">-- Choose Payer --</option>
            <?php foreach ($insurances as $ins): ?>
              <option value="<?= $ins['id'] ?>"><?= e($ins['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label>Initial Clinical Service</label>
          <select name="service_id" class="input">
            <option value="">-- Select Service Charge --</option>
            <?php foreach ($services as $srv): ?>
              <option value="<?= $srv['id'] ?>"><?= e($srv['name']) ?> (<?= format_minor($srv['price_minor'], tenant('currency')) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newInvoiceModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate Invoice</button>
      </div>
    </form>
  </div>
</div>

<script>
let billSearchTimeout;
function searchBillPatient(q) {
  clearTimeout(billSearchTimeout);
  const container = document.getElementById('bill_search_results');
  billSearchTimeout = setTimeout(async () => {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || '/patients/search';
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const pts = await res.json();
      if (!pts.length) {
        container.innerHTML = '<div class="dim" style="font-size: 12px; padding: 10px; text-align: center;">No matching patients found.</div>';
        container.style.display = 'block';
        return;
      }
      container.innerHTML = pts.map(p => `
        <div style="padding: 8px 12px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickBillPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.mrn}')">
          <div>
            <strong>${p.first_name} ${p.last_name}</strong>
            <div class="dim mono" style="font-size: 11px;">
              MRN: ${p.mrn} ${p.national_id ? ' • ID: ' + p.national_id : ''} ${p.age ? ' • ' + p.age + ' yrs' : ''} ${p.phone ? ' • ' + p.phone : ''}
            </div>
          </div>
          <span class="badge badge-brand">+ Pick</span>
        </div>
      `).join('');
      container.style.display = 'block';
    } catch (e) {}
  }, 100);
}

function pickBillPatient(id, name, mrn) {
  document.getElementById('bill_patient_id').value = id;
  document.getElementById('bill_patient_input').value = name + ' (' + mrn + ')';
  document.getElementById('bill_search_results').style.display = 'none';
}

function toggleInsurerField(val) {
  document.getElementById('insurerField').style.display = val === 'insurance' ? 'block' : 'none';
}
</script>