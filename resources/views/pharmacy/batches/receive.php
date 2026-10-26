<?php $this->layout('layouts.app', ['title' => 'Receive Batch Stock']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Stock In</div>
    <h1>Receive Supplier Batch Stock</h1>
    <p class="muted">Record incoming inventory batches with expiry dates to ensure First-Expired, First-Out (FEFO) dispensing.</p>
  </div>
</div>

<div class="card" style="max-width: 860px;">
  <div class="card-head">
    <h3>Batch Receipt Details</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route('pharmacy.batches.store') ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field full">
          <label for="product_id">Select Product *</label>
          <select id="product_id" name="product_id" class="input" required>
            <option value="">-- Choose medicine from inventory --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?> (<?= e($p['strength']) ?> <?= e($p['form']) ?>) — Selling: <?= money($p['price_minor']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="batch_no">Manufacturer Batch / Lot Number *</label>
          <input type="text" id="batch_no" name="batch_no" class="input" required placeholder="e.g. B260012">
        </div>

        <div class="field">
          <label for="delivery_note_no">Delivery Note / Invoice Number</label>
          <input type="text" id="delivery_note_no" name="delivery_note_no" class="input" placeholder="e.g. DN-88219">
        </div>

        <div class="field">
          <label for="expiry_date">Expiry Date *</label>
          <input type="date" id="expiry_date" name="expiry_date" class="input" required>
        </div>

        <div class="field">
          <label for="qty_received">Quantity Received (Units) *</label>
          <input type="number" min="1" id="qty_received" name="qty_received" class="input" required placeholder="e.g. 100">
        </div>

        <div class="field">
          <label for="cost">Unit Cost Price (<?= e(tenant('currency')) ?>) *</label>
          <input type="number" step="any" min="0" id="cost" name="cost" class="input" required placeholder="0.00">
        </div>

        <div class="field">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="supplier_id" style="margin-bottom: 0;">Supplier / Distributor</label>
            <button type="button" class="btn btn-sm btn-ghost" onclick="openNewSupplierModal()" style="padding: 2px 8px; font-size: 11px; color: var(--brand);">
              ➕ New Supplier
            </button>
          </div>
          <select id="supplier_id" name="supplier_id" class="input">
            <option value="">-- Direct / Unknown Supplier --</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label for="note">Restock Note / Internal Remarks</label>
          <input type="text" id="note" name="note" class="input" placeholder="e.g. Received via DHL Express, cooling box verified intact">
        </div>

        <div class="field full" style="background: rgba(14, 116, 144, 0.05); border: 1px solid rgba(14, 116, 144, 0.2); border-radius: 8px; padding: 14px 16px;">
          <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 4px;">
            <input type="checkbox" name="require_approval" value="1" id="require_approval" checked style="width: 18px; height: 18px; accent-color: var(--brand);">
            <span>Submit for Supervisor Approval (Recommended)</span>
          </label>
          <p class="muted" style="margin: 0; font-size: 12px; margin-left: 26px;">
            When checked, stock is placed in <strong>Pending Approval</strong> state. Details can be edited, inspected, and verified before activating in POS inventory. Uncheck only if you wish to bypass approval and instantly increment stock.
          </p>
        </div>
      </div>

      <div class="form-actions">
        <a href="<?= route('pharmacy.index') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Submit Restock Batch</span>
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Quick Add Supplier -->
<div id="newSupplierModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 480px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">➕ Register New Supplier</h3>
      <button type="button" class="icon-btn" onclick="closeNewSupplierModal()">&times;</button>
    </div>

    <form id="quickSupplierForm" onsubmit="submitQuickSupplier(event)">
      <div class="field" style="margin-bottom: 12px;">
        <label for="qs_name">Supplier / Company Name *</label>
        <input type="text" id="qs_name" class="input" required placeholder="e.g. Medisell Pharma Ltd">
      </div>

      <div class="field" style="margin-bottom: 12px;">
        <label for="qs_contact">Contact Person</label>
        <input type="text" id="qs_contact" class="input" placeholder="e.g. Dr. Samuel Kimani">
      </div>

      <div class="field" style="margin-bottom: 12px;">
        <label for="qs_phone">Phone Number</label>
        <input type="text" id="qs_phone" class="input" placeholder="e.g. +254 712 345 678">
      </div>

      <div class="field" style="margin-bottom: 18px;">
        <label for="qs_email">Email Address</label>
        <input type="email" id="qs_email" class="input" placeholder="e.g. orders@medisell.co.ke">
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeNewSupplierModal()">Cancel</button>
        <button type="submit" id="btnSaveSupplier" class="btn btn-primary">Save Supplier</button>
      </div>
    </form>
  </div>
</div>

<script>
function openNewSupplierModal() {
  document.getElementById('newSupplierModal').classList.remove('hidden');
  document.getElementById('qs_name').focus();
}

function closeNewSupplierModal() {
  document.getElementById('newSupplierModal').classList.add('hidden');
}

async function submitQuickSupplier(e) {
  e.preventDefault();
  const name = document.getElementById('qs_name').value.trim();
  const contact = document.getElementById('qs_contact').value.trim();
  const phone = document.getElementById('qs_phone').value.trim();
  const email = document.getElementById('qs_email').value.trim();

  if (!name) return;

  const btn = document.getElementById('btnSaveSupplier');
  btn.disabled = true;
  btn.innerText = 'Saving...';

  try {
    const res = await fetch('<?= route('pharmacy.suppliers.quick_store') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({
        _token: '<?= csrf_token() ?>',
        name: name,
        contact_person: contact,
        phone: phone,
        email: email
      })
    });
    const data = await res.json();
    if (res.ok && data.success) {
      // Add option to supplier_id select and select it
      const select = document.getElementById('supplier_id');
      const opt = document.createElement('option');
      opt.value = data.supplier.id;
      opt.text = data.supplier.name;
      opt.selected = true;
      select.appendChild(opt);
      closeNewSupplierModal();
      document.getElementById('quickSupplierForm').reset();
    } else {
      alert(data.message || 'Failed to add supplier');
    }
  } catch (err) {
    console.error(err);
    alert('An error occurred while creating supplier.');
  } finally {
    btn.disabled = false;
    btn.innerText = 'Save Supplier';
  }
}
</script>
