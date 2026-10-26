<?php $this->layout('layouts.app', ['title' => 'Pharmacy Inventory']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Pharmacy</div>
    <h1>Pharmacy & Inventory Management</h1>
    <p class="muted">Monitor stock levels, batch expiries (FEFO), reorder thresholds, and drug schedules.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <a href="<?= route('pharmacy.pos') ?>" class="btn btn-amber btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span>Open OTC POS</span>
    </a>
    <a href="<?= route('pharmacy.batches.receive') ?>" class="btn btn-primary btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Receive Batch Stock</span>
    </a>
    <a href="<?= route('pharmacy.products.create') ?>" class="btn btn-lg">
      <span>+ Add Medicine</span>
    </a>
    <button type="button" class="btn btn-lg btn-ghost" onclick="document.getElementById('categoryModal').classList.remove('hidden')">
      <span>🏷️ Categories (<?= count($categories) ?>)</span>
    </button>
  </div>
</div>

<!-- Inventory Stats Row with Wholesale Valuation -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    </div>
    <div class="label">Total Stock Volume</div>
    <div class="value"><?= number_format($metrics['total_units'] ?? 0) ?> units</div>
    <div class="delta">Across <?= number_format($metrics['total_products'] ?? 0) ?> active medicines</div>
  </div>

  <div class="stat" style="--accent: #0ea5e9;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
    </div>
    <div class="label">Wholesale Stock Valuation</div>
    <div class="value"><?= money($metrics['wholesale_valuation_minor'] ?? 0) ?></div>
    <div class="delta">Hospital stock asset value at wholesale cost</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Retail Inventory Value</div>
    <div class="value"><?= money($metrics['retail_valuation_minor'] ?? 0) ?></div>
    <div class="delta" style="color: #10b981;">+<?= money($metrics['projected_gross_margin_minor'] ?? 0) ?> projected profit (<?= $metrics['projected_margin_pct'] ?? 0 ?>%)</div>
  </div>

  <div class="stat" style="--accent: #f43f5e;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Expiring Soon Batches</div>
    <div class="value"><?= count($expiringBatches) ?> batches</div>
    <div class="delta">Expiring within 60 days (FEFO alerts)</div>
  </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom: 18px;">
  <a href="<?= route('pharmacy.index', ['tab' => 'inventory']) ?>" class="<?= $tab === 'inventory' ? 'active' : '' ?>">
    Full Inventory (<?= count($products) ?>)
  </a>
  <a href="<?= route('pharmacy.index', ['tab' => 'low_stock']) ?>" class="<?= $tab === 'low_stock' ? 'active' : '' ?>">
    Low Stock Alerts
  </a>
  <a href="<?= route('pharmacy.index', ['tab' => 'expiring']) ?>" class="<?= $tab === 'expiring' ? 'active' : '' ?>">
    Expiring Batches (<?= count($expiringBatches) ?>)
  </a>
</div>

<?php if ($tab === 'expiring'): ?>
  <!-- Expiring Batches Table -->
  <div class="card">
    <div class="card-head">
      <h3>Batches Expiring Soon (&lt; 60 Days)</h3>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
            <th>Qty On Hand</th>
            <th>Unit Cost</th>
            <th>Supplier</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($expiringBatches)): ?>
            <tr><td colspan="6" class="empty">No batches expiring within 60 days.</td></tr>
          <?php else: ?>
            <?php foreach ($expiringBatches as $eb): ?>
              <tr>
                <td>
                  <strong><?= e($eb['product_name']) ?></strong>
                  <div class="dim"><?= e($eb['strength']) ?> • <?= e($eb['form']) ?></div>
                </td>
                <td><strong class="mono"><?= e($eb['batch_no']) ?></strong></td>
                <td>
                  <span class="badge badge-danger mono">
                    <?= date('d M Y', strtotime($eb['expiry_date'])) ?>
                  </span>
                </td>
                <td><strong class="mono"><?= $eb['qty_on_hand'] ?></strong></td>
                <td class="mono"><?= money($eb['cost_minor']) ?></td>
                <td><span class="muted"><?= e($eb['supplier_name'] ?: '—') ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <!-- Standard Inventory Table -->
  <div class="card">
    <div class="toolbar">
      <form method="GET" action="<?= route('pharmacy.index') ?>" style="display: flex; gap: 10px; flex: 1;">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div class="input-icon" style="flex: 1;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" class="input" placeholder="Search by name, generic, barcode, SKU..." value="<?= e($q) ?>">
        </div>
        <select name="category_id" class="input" style="width: 180px;" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="schedule" class="input" style="width: 140px;" onchange="this.form.submit()">
          <option value="">All Schedules</option>
          <option value="otc" <?= $schedule === 'otc' ? 'selected' : '' ?>>OTC Only</option>
          <option value="pom" <?= $schedule === 'pom' ? 'selected' : '' ?>>POM (Rx)</option>
          <option value="controlled" <?= $schedule === 'controlled' ? 'selected' : '' ?>>Controlled</option>
        </select>
        <button type="submit" class="btn">Filter</button>
      </form>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Product Details</th>
            <th>Category</th>
            <th>Schedule</th>
            <th>Stock On Hand</th>
            <th>Retail Price</th>
            <th>Earliest Expiry (FEFO)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="7" class="empty">No medicines found matching criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <strong style="font-size: 14px;"><?= e($p['name']) ?></strong>
                  <div class="dim" style="font-size: 12px;"><?= e($p['generic_name'] ?: '—') ?> • <?= e($p['form']) ?> <?= e($p['strength']) ?></div>
                  <div class="dim mono" style="font-size: 11px;">Barcode: <?= e($p['barcode'] ?: '—') ?> • SKU: <?= e($p['sku']) ?></div>
                </td>
                <td>
                  <span class="muted"><?= e($p['category_name'] ?: 'General') ?></span>
                </td>
                <td>
                  <span class="badge badge-<?= $p['schedule'] === 'otc' ? 'success' : ($p['schedule'] === 'pom' ? 'warning' : 'danger') ?>">
                    <?= strtoupper(e($p['schedule'])) ?>
                  </span>
                </td>
                <td>
                  <?php if ((int) $p['stock_qty'] <= (int) $p['reorder_level']): ?>
                    <strong class="mono" style="color: var(--danger); font-size: 15px;"><?= $p['stock_qty'] ?></strong>
                    <div style="font-size: 10.5px; color: var(--danger);">Reorder (&le; <?= $p['reorder_level'] ?>)</div>
                  <?php else: ?>
                    <strong class="mono" style="font-size: 15px;"><?= $p['stock_qty'] ?></strong>
                    <div class="dim" style="font-size: 10.5px;"><?= $p['unit'] ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong class="mono" style="font-size: 14px;"><?= money($p['price_minor']) ?></strong>
                  <?php if (!empty($p['wholesale_price_minor'])): ?>
                    <div class="dim mono" style="font-size: 10.5px; color: #10b981;">Cost: <?= money($p['wholesale_price_minor']) ?> (<?= round((($p['price_minor'] - $p['wholesale_price_minor']) / max(1, $p['price_minor'])) * 100) ?>% margin)</div>
                  <?php endif; ?>
                  <?php if ((float) $p['tax_rate'] > 0): ?>
                    <div class="dim" style="font-size: 10.5px;">+<?= $p['tax_rate'] ?>% VAT</div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['earliest_expiry']): ?>
                    <span class="mono <?= strtotime($p['earliest_expiry']) < strtotime('+60 days') ? 'badge badge-warning' : 'dim' ?>">
                      <?= date('d M Y', strtotime($p['earliest_expiry'])) ?>
                    </span>
                  <?php else: ?>
                    <span class="dim">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= route('pharmacy.batches.receive', ['product_id' => $p['id']]) ?>" class="btn btn-sm" title="Receive new batch for this product">
                    + Receive
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Category Management Modal -->
<div id="categoryModal" class="modal-overlay hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 580px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); border: 1px solid var(--border-strong);">
    <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
      <div>
        <h3 style="margin: 0; font-size: 16px;">Pharmacy Product Categories</h3>
        <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Create, organize, and manage medication categories.</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('categoryModal').classList.add('hidden')" style="padding: 4px 8px; font-size: 16px;">✕</button>
    </div>
    
    <div class="card-body" style="padding: 18px 20px; overflow-y: auto;">
      <!-- Add New Category Form -->
      <form method="POST" action="<?= route('pharmacy.categories.store') ?>" style="margin-bottom: 20px; background: rgba(255,255,255,0.03); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
        <?= csrf_field() ?>
        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 6px;">Add New Category</label>
        <div style="display: flex; gap: 8px;">
          <input type="text" name="name" class="input" required placeholder="e.g. Antibiotics, Analgesics, Antihypertensives..." style="flex: 1;">
          <button type="submit" class="btn btn-primary" style="white-space: nowrap;">+ Add Category</button>
        </div>
      </form>

      <!-- Existing Categories List -->
      <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
        Existing Categories (<?= count($categories) ?>)
      </div>
      <div style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm);">
        <table class="table" style="margin: 0; font-size: 13px;">
          <thead>
            <tr>
              <th>Category Name</th>
              <th style="text-align: right; width: 80px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($categories)): ?>
              <tr><td colspan="2" class="empty" style="text-align: center; padding: 20px;">No categories created yet.</td></tr>
            <?php else: ?>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td style="font-weight: 600;">
                    <?= e($cat['name']) ?>
                  </td>
                  <td style="text-align: right;">
                    <form method="POST" action="<?= route('pharmacy.categories.delete', ['id' => $cat['uuid'] ?? $cat['id']]) ?>" onsubmit="return confirm('Delete category \'<?= e(addslashes($cat['name'])) ?>\'? Any medicines in this category will be preserved as uncategorized.');" style="margin: 0; display: inline;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--danger); padding: 2px 6px;" title="Delete Category">✕</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <div style="padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; background: rgba(0,0,0,0.1);">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('categoryModal').classList.add('hidden')">Close</button>
    </div>
  </div>
</div>
