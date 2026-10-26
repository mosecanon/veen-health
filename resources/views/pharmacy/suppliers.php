<?php $this->layout('layouts.app', ['title' => $title]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Suppliers</div>
    <h1>Pharmaceutical Suppliers & Supply Chain Analytics</h1>
    <p class="muted">Track medicine distributors, analyze annual procurement volume, and manage vendor contact profiles.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('pharmacy.batches.index') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      <span>Restock Batches</span>
    </a>
    <button type="button" class="btn btn-primary" onclick="openAddSupplierModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Register New Supplier</span>
    </button>
  </div>
</div>

<!-- Supplier KPIs -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    </div>
    <div class="label">Total Suppliers</div>
    <div class="value"><?= (int) ($summary['total_suppliers'] ?? count($suppliers)) ?></div>
    <div class="delta">Active pharmaceutical distributors</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Procurement Spend (<?= date('Y') ?>)</div>
    <div class="value" style="font-size: 20px;"><?= format_minor((int) ($summary['total_spend_this_year'] ?? 0), tenant('currency')) ?></div>
    <div class="delta">Total purchases this calendar year</div>
  </div>

  <div class="stat" style="--accent: #3b82f6;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <div class="label">Units Supplied (<?= date('Y') ?>)</div>
    <div class="value"><?= number_format((int) ($summary['total_units_this_year'] ?? 0)) ?></div>
    <div class="delta">Medicine units delivered this year</div>
  </div>

  <div class="stat" style="--accent: #8b5cf6;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    </div>
    <div class="label">Total Batches</div>
    <div class="value"><?= (int) ($summary['total_batches_received'] ?? 0) ?></div>
    <div class="delta">All-time received deliveries</div>
  </div>
</div>

<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h3 style="margin: 0;">Suppliers & Annual Supply Volumes</h3>
      <span class="muted" style="font-size: 12px;">Complete breakdown of what each supplier has supplied this year and all-time.</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <input type="text" id="supplierSearchInput" class="input" placeholder="🔍 Search supplier, contact, phone..." onkeyup="tableSearch('suppliersTable', this.value)" style="width: 250px; padding: 6px 12px; font-size: 13px;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('suppliersTable', 'pharmacy_suppliers')">
        <span>📥 Export CSV</span>
      </button>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="suppliersTable">
      <thead>
        <tr>
          <th>Supplier / Company</th>
          <th>Contact & Phone</th>
          <th>Email & Location</th>
          <th>Supplied in <?= date('Y') ?></th>
          <th>All-Time Supplies</th>
          <th>Last Supply Date</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($suppliers)): ?>
          <tr>
            <td colspan="7" class="dim" style="text-align: center; padding: 36px;">
              No suppliers registered yet. Click <strong>+ Register New Supplier</strong> above to add one.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($suppliers as $s): ?>
            <tr>
              <td>
                <strong style="font-size: 15px;"><?= e($s['name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">ID #<?= $s['id'] ?></div>
              </td>
              <td>
                <div><?= e($s['contact_person'] ?: '—') ?></div>
                <div class="dim mono" style="font-size: 11px;"><?= e($s['phone'] ?: 'No phone') ?></div>
              </td>
              <td>
                <div><?= e($s['email'] ?: '—') ?></div>
                <div class="dim" style="font-size: 11px;"><?= e($s['address'] ?: '—') ?></div>
              </td>
              <td>
                <strong style="font-size: 13px; color: var(--brand);"><?= format_minor((int) $s['total_value_this_year'], tenant('currency')) ?></strong>
                <div class="dim" style="font-size: 11px;">
                  <?= number_format((int) $s['total_units_this_year']) ?> units • <?= (int) $s['total_batches_this_year'] ?> batches
                </div>
              </td>
              <td>
                <strong style="font-size: 13px;"><?= format_minor((int) $s['total_value_all_time'], tenant('currency')) ?></strong>
                <div class="dim" style="font-size: 11px;">
                  <?= number_format((int) $s['total_units_all_time']) ?> units • <?= (int) $s['total_batches_all_time'] ?> batches
                </div>
              </td>
              <td class="dim" style="font-size: 12px;">
                <?php if ($s['last_supply_date']): ?>
                  <span class="mono"><?= date('d M Y', strtotime($s['last_supply_date'])) ?></span>
                <?php else: ?>
                  <span>Never</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <button type="button" class="btn btn-sm btn-ghost" onclick="openEditSupplierModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)">
                    ✏️ Edit
                  </button>
                  <form method="POST" action="<?= route('pharmacy.suppliers.delete', ['id' => $s['id']]) ?>" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete supplier <?= e($s['name']) ?>?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--danger);" title="Delete supplier">
                      🗑️
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New Supplier -->
<div id="addSupplierModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Register Pharmaceutical Supplier</h3>
      <button type="button" class="icon-btn" onclick="closeAddSupplierModal()">&times;</button>
    </div>

    <form method="POST" action="<?= route('pharmacy.suppliers.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label for="as_name">Supplier / Distributor Company Name *</label>
          <input type="text" id="as_name" name="name" class="input" required placeholder="e.g. Harleys Ltd">
        </div>

        <div class="field">
          <label for="as_contact">Contact Person</label>
          <input type="text" id="as_contact" name="contact_person" class="input" placeholder="e.g. Sales Rep / Account Mgr">
        </div>

        <div class="field">
          <label for="as_phone">Phone Number</label>
          <input type="text" id="as_phone" name="phone" class="input" placeholder="e.g. +254 712 345 678">
        </div>

        <div class="field full">
          <label for="as_email">Email Address</label>
          <input type="email" id="as_email" name="email" class="input" placeholder="e.g. orders@harleys.co.ke">
        </div>

        <div class="field full">
          <label for="as_address">Physical Address / Warehouse Location</label>
          <textarea id="as_address" name="address" class="input" rows="2" placeholder="e.g. Industrial Area, Road A, Warehouse 4"></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="closeAddSupplierModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Supplier</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Supplier -->
<div id="editSupplierModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Edit Supplier Details</h3>
      <button type="button" class="icon-btn" onclick="closeEditSupplierModal()">&times;</button>
    </div>

    <form id="editSupplierForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label for="es_name">Supplier / Distributor Company Name *</label>
          <input type="text" id="es_name" name="name" class="input" required>
        </div>

        <div class="field">
          <label for="es_contact">Contact Person</label>
          <input type="text" id="es_contact" name="contact_person" class="input">
        </div>

        <div class="field">
          <label for="es_phone">Phone Number</label>
          <input type="text" id="es_phone" name="phone" class="input">
        </div>

        <div class="field full">
          <label for="es_email">Email Address</label>
          <input type="email" id="es_email" name="email" class="input">
        </div>

        <div class="field full">
          <label for="es_address">Physical Address / Warehouse Location</label>
          <textarea id="es_address" name="address" class="input" rows="2"></textarea>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="closeEditSupplierModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Supplier</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddSupplierModal() {
  document.getElementById('addSupplierModal').classList.remove('hidden');
  document.getElementById('as_name').focus();
}

function closeAddSupplierModal() {
  document.getElementById('addSupplierModal').classList.add('hidden');
}

function openEditSupplierModal(s) {
  document.getElementById('editSupplierForm').action = `/pharmacy/suppliers/${s.id}`;
  document.getElementById('es_name').value = s.name || '';
  document.getElementById('es_contact').value = s.contact_person || '';
  document.getElementById('es_phone').value = s.phone || '';
  document.getElementById('es_email').value = s.email || '';
  document.getElementById('es_address').value = s.address || '';
  document.getElementById('editSupplierModal').classList.remove('hidden');
}

function closeEditSupplierModal() {
  document.getElementById('editSupplierModal').classList.add('hidden');
}
</script>
