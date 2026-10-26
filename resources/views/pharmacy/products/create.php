<?php $this->layout('layouts.app', ['title' => 'Add Medicine']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / New Item</div>
    <h1>Add Medicine / Product</h1>
    <p class="muted">Add a new drug, consumable, or retail healthcare product to the <?= e(tenant('name')) ?> catalog.</p>
  </div>
</div>

<div class="card" style="max-width: 860px;">
  <div class="card-head">
    <h3>Product Information</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route('pharmacy.products.store') ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label for="name">Brand / Trade Name *</label>
          <input type="text" id="name" name="name" class="input" value="<?= e(old('name')) ?>" required autofocus placeholder="e.g. Panadol Extra">
        </div>

        <div class="field">
          <label for="generic_name">Generic / Active Molecule Name</label>
          <input type="text" id="generic_name" name="generic_name" class="input" value="<?= e(old('generic_name')) ?>" placeholder="e.g. Paracetamol + Caffeine">
        </div>

        <div class="field">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <label for="category_id" style="margin-bottom: 0;">Product Category</label>
            <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('quickCatModal').classList.remove('hidden')" style="padding: 2px 8px; font-size: 11px; color: var(--brand);">+ Add Category</button>
          </div>
          <select id="category_id" name="category_id" class="input">
            <option value="">-- General / Uncategorized --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="schedule">Drug Legal Schedule *</label>
          <select id="schedule" name="schedule" class="input" required>
            <option value="otc" selected>OTC (Over The Counter - No Prescription Needed)</option>
            <option value="pom">POM (Prescription Only Medicine)</option>
            <option value="controlled">Controlled Drug (Strict Register)</option>
          </select>
        </div>

        <div class="field">
          <label for="form">Dosage Form *</label>
          <select id="form" name="form" class="input" required>
            <option value="tablet">Tablet</option>
            <option value="capsule">Capsule</option>
            <option value="syrup">Syrup / Suspension</option>
            <option value="injection">Injection</option>
            <option value="gel">Gel / Ointment</option>
            <option value="drops">Drops</option>
            <option value="inhaler">Inhaler</option>
            <option value="suppository">Suppository</option>
            <option value="sachet">Powder / Sachet</option>
            <option value="device">Device / Consumable</option>
          </select>
        </div>

        <div class="field">
          <label for="strength">Strength / Concentration</label>
          <input type="text" id="strength" name="strength" class="input" value="<?= e(old('strength')) ?>" placeholder="e.g. 500mg, 100ml, 5%">
        </div>

        <div class="field">
          <label for="unit">Packaging / Selling Unit *</label>
          <input type="text" id="unit" name="unit" class="input" value="<?= e(old('unit', 'pcs')) ?>" required placeholder="pcs, bottle, strip, box">
        </div>

        <div class="field">
          <label for="barcode" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Barcode (EAN-13 / Code128)</span>
            <button type="button" class="btn btn-sm btn-ghost" style="padding: 2px 8px; font-size: 11px;" onclick="generateMedicineBarcode()">⚡ Auto Generate</button>
          </label>
          <div style="display: flex; gap: 8px;">
            <input type="text" id="barcode" name="barcode" class="input" value="<?= e(old('barcode')) ?>" placeholder="Scan or click Auto Generate">
            <button type="button" class="btn btn-ghost" onclick="generateMedicineBarcode()" title="Generate Barcode">⚡</button>
          </div>
        </div>

        <div class="field">
          <label for="wholesale_price">Wholesale / Cost Price (<?= e(tenant('currency')) ?>) <span class="dim font-normal">(Optional)</span></label>
          <input type="number" step="any" min="0" id="wholesale_price" name="wholesale_price" class="input" value="<?= e(old('wholesale_price')) ?>" placeholder="e.g. 150.00 for margin calculation">
        </div>

        <div class="field">
          <label for="price">Retail Selling Price (<?= e(tenant('currency')) ?>) *</label>
          <input type="number" step="any" min="0" id="price" name="price" class="input" value="<?= e(old('price')) ?>" required placeholder="0.00">
        </div>

        <div class="field">
          <label for="tax_rate">Tax / VAT Rate (%)</label>
          <input type="number" step="any" min="0" id="tax_rate" name="tax_rate" class="input" value="<?= e(old('tax_rate', '0')) ?>" placeholder="e.g. 16 for 16% VAT">
        </div>

        <div class="field">
          <label for="reorder_level">Reorder Threshold Level</label>
          <input type="number" id="reorder_level" name="reorder_level" class="input" value="<?= e(old('reorder_level', '20')) ?>">
        </div>

        <div class="field">
          <label for="sku">Custom SKU Code (Optional)</label>
          <input type="text" id="sku" name="sku" class="input" value="<?= e(old('sku')) ?>" placeholder="Auto-generated if blank">
        </div>
      </div>

      <div class="form-actions">
        <a href="<?= route('pharmacy.index') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Save Product</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function generateMedicineBarcode() {
  const code = 'MED' + Math.floor(10000000 + Math.random() * 90000000);
  document.getElementById('barcode').value = code;
}

async function submitQuickCategory(e) {
  e.preventDefault();
  const input = document.getElementById('qc_name');
  const name = input.value.trim();
  if (!name) return;
  const btn = document.getElementById('btnSubmitQuickCat');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  try {
    const res = await fetch('<?= route('pharmacy.categories.quick_store') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: JSON.stringify({ name: name, _token: window.CSRF_TOKEN || '' })
    });
    const data = await res.json();
    if (data.success && data.category) {
      const select = document.getElementById('category_id');
      const opt = document.createElement('option');
      opt.value = data.category.id;
      opt.textContent = data.category.name;
      opt.selected = true;
      select.appendChild(opt);
      // Trigger change if using custom searchable select
      select.dispatchEvent(new Event('change', { bubbles: true }));
      document.getElementById('quickCatModal').classList.add('hidden');
      input.value = '';
    } else {
      alert(data.message || 'Failed to create category.');
    }
  } catch (err) {
    alert('Server error creating category.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Category';
  }
}
</script>

<!-- Quick Add Category Modal -->
<div id="quickCatModal" class="modal-overlay hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); border: 1px solid var(--border-strong);">
    <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
      <h3 style="margin: 0; font-size: 15px;">Add New Category</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('quickCatModal').classList.add('hidden')" style="padding: 4px 8px;">✕</button>
    </div>
    <form onsubmit="submitQuickCategory(event)">
      <div class="card-body" style="padding: 20px;">
        <div class="field">
          <label>Category Name *</label>
          <input type="text" id="qc_name" class="input" required placeholder="e.g. Analgesics, Antibiotics, IV Fluids..." autofocus>
        </div>
      </div>
      <div style="padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; background: rgba(0,0,0,0.1);">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('quickCatModal').classList.add('hidden')">Cancel</button>
        <button type="submit" id="btnSubmitQuickCat" class="btn btn-primary">Save Category</button>
      </div>
    </form>
  </div>
</div>
