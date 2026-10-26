<?php $this->layout('layouts.app', ['title' => 'Product Categories']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Categories</div>
    <h1>Product & Medicine Categories</h1>
    <p class="muted">Manage pharmaceutical categories, groupings, and inventory volume distribution.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('pharmacy.index') ?>" class="btn btn-ghost">← Back to Pharmacy</a>
  </div>
</div>

<div class="grid grid-3" style="gap: 24px; align-items: flex-start;">
  <!-- Create Category Form -->
  <div class="card card-pad">
    <h3 style="margin-top: 0;">Add New Category</h3>
    <p class="muted" style="font-size: 13px;">Create new classification groups for inventory products.</p>
    
    <form method="POST" action="<?= route('pharmacy.categories.store') ?>" style="margin-top: 16px;">
      <?= csrf_field() ?>
      <div class="field">
        <label>Category Name *</label>
        <input type="text" name="name" class="input" required placeholder="e.g. Analgesics, Antibiotics, IV Fluids...">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top: 14px; width: 100%;">Create Category</button>
    </form>
  </div>

  <!-- Categories Table -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-head">
      <h3>Active Medication Categories (<?= count($categories) ?>)</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Category Name</th>
            <th style="text-align: center;">Active Medicines</th>
            <th style="text-align: center;">Stock Units</th>
            <th style="text-align: right;">Wholesale Valuation</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="empty">No categories recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><strong style="font-size: 14px;"><?= e($cat['name']) ?></strong></td>
                <td style="text-align: center;"><span class="badge"><?= $cat['products_count'] ?></span></td>
                <td style="text-align: center;"><span class="mono"><?= number_format($cat['total_stock_units']) ?></span></td>
                <td style="text-align: right;" class="mono"><?= money($cat['category_wholesale_minor']) ?></td>
                <td style="text-align: right;">
                  <form method="POST" action="<?= route('pharmacy.categories.delete', ['id' => $cat['uuid'] ?? $cat['id']]) ?>" onsubmit="return confirm('Delete category \'<?= e(addslashes($cat['name'])) ?>\'? Any associated medicines will be preserved.');" style="margin: 0; display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--danger);">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
