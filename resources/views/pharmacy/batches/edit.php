<?php $this->layout('layouts.app', ['title' => $title]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / <a href="<?= route('pharmacy.batches.index') ?>">Restock Batches</a> / Edit</div>
    <h1>Edit Pending Restock Batch</h1>
    <p class="muted">Modify batch parameters prior to supervisory approval and inventory commit.</p>
  </div>
</div>

<div class="card" style="max-width: 800px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0;"><?= e($batch['product_name']) ?></h3>
      <span class="dim"><?= e($batch['strength'] ?? '') ?> <?= e($batch['form'] ?? '') ?></span>
    </div>
    <span class="badge badge-warning">⏳ Pending Approval</span>
  </div>

  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom: 20px; font-size: 13px;">
      💡 This restock batch is currently pending supervisor approval. You may adjust the quantities, unit cost, batch number, or supplier. Once approved, the batch will become locked to maintain strict financial and inventory audit trails.
    </div>

    <form method="POST" action="<?= route('pharmacy.batches.update', ['id' => $batch['id']]) ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label for="batch_no">Manufacturer Batch / Lot Number *</label>
          <input type="text" id="batch_no" name="batch_no" class="input" required value="<?= e($batch['batch_no']) ?>">
        </div>

        <div class="field">
          <label for="delivery_note_no">Delivery Note / Invoice #</label>
          <input type="text" id="delivery_note_no" name="delivery_note_no" class="input" value="<?= e($batch['delivery_note_no'] ?? '') ?>" placeholder="e.g. DN-88129">
        </div>

        <div class="field">
          <label for="expiry_date">Expiry Date *</label>
          <input type="date" id="expiry_date" name="expiry_date" class="input" required value="<?= e($batch['expiry_date']) ?>">
        </div>

        <div class="field">
          <label for="qty_received">Quantity Received (Units) *</label>
          <input type="number" min="1" id="qty_received" name="qty_received" class="input" required value="<?= (int) $batch['qty_received'] ?>">
        </div>

        <div class="field">
          <label for="cost">Unit Cost Price (<?= e(tenant('currency')) ?>) *</label>
          <?php $unitCostDecimal = $batch['cost_minor'] / pow(10, (int) currency_meta()['decimals']); ?>
          <input type="number" step="any" min="0" id="cost" name="cost" class="input" required value="<?= number_format($unitCostDecimal, 2, '.', '') ?>">
        </div>

        <div class="field">
          <label for="supplier_id">Supplier / Distributor</label>
          <select id="supplier_id" name="supplier_id" class="input">
            <option value="">-- Direct / Unknown Supplier --</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($batch['supplier_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                <?= e($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <a href="<?= route('pharmacy.batches.index') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
