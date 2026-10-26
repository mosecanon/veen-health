<?php $this->layout('layouts.app', ['title' => 'OTC Pharmacy POS']) ?>

<style>
.main .content:has(.pos-full-view) {
  padding: 10px 18px !important;
  max-width: 100% !important;
}
@media (max-height: 800px) {
  .pos-item-card { padding: 8px 10px !important; }
}
</style>

<div class="pos-full-view" style="height: calc(100vh - 80px); display: flex; flex-direction: column; overflow: hidden;">

  <!-- Compact Top Bar -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-shrink: 0;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Over-The-Counter (OTC) Pharmacy POS</h2>
      <span class="dim" style="font-size: 12px;">FEFO Batch Allocation • Real-time Barcode Scanner</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
      <?php if ($session): ?>
        <span class="badge badge-success" style="font-size: 11px; padding: 4px 10px;">
          Register Open (Shift #<?= $session['id'] ?>)
        </span>
        <a href="<?= route('pharmacy.sessions.index') ?>" class="btn btn-sm btn-ghost" style="padding: 3px 8px; font-size: 11px;">Close Shift</a>
      <?php else: ?>
        <span class="badge badge-warning" style="font-size: 11px; padding: 4px 10px;">
          ⚠️ No Open Shift
        </span>
        <a href="<?= route('pharmacy.sessions.index') ?>" class="btn btn-sm btn-amber" style="padding: 3px 8px; font-size: 11px;">Open Register Shift</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- POS Fixed 2-Column Grid -->
  <div style="flex: 1; display: grid; grid-template-columns: 1.45fr 1fr; gap: 14px; min-height: 0; overflow: hidden;">
    
    <!-- Left Side: Product Search, Category Filters & Grid -->
    <div class="card" style="height: 100%; display: flex; flex-direction: column; overflow: hidden; padding: 14px; gap: 10px;">
      <!-- Barcode & Search Bar -->
      <div style="display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 10px; flex-shrink: 0;">
        <div class="input-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
          <input type="text" id="barcodeInput" class="input" style="padding: 7px 10px 7px 32px; font-size: 13px;" placeholder="Scan Barcode (Auto-Add)..." autofocus onkeydown="handleBarcodeKey(event)">
        </div>
        <div class="input-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" id="searchInput" class="input" style="padding: 7px 10px 7px 32px; font-size: 13px;" placeholder="Search medicine, generic name..." oninput="debounceSearch()">
        </div>
      </div>

      <!-- Category Pills Filter -->
      <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px; flex-shrink: 0;" id="catPills">
        <button type="button" class="btn btn-sm btn-primary cat-btn" style="padding: 3px 10px; font-size: 11px;" onclick="filterCategory(0, this)">All Categories</button>
        <?php foreach ($categories as $cat): ?>
          <button type="button" class="btn btn-sm btn-ghost cat-btn" style="padding: 3px 10px; font-size: 11px;" onclick="filterCategory(<?= $cat['id'] ?>, this)"><?= e($cat['name']) ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Products Grid (Scrolls internally) -->
      <div id="productGrid" style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding-right: 4px; min-height: 0;">
        <?php foreach ($topProducts as $p): ?>
          <div class="pos-item-card" onclick="addToCart(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)" style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; cursor: pointer; transition: all .15s; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                <span class="badge badge-<?= $p['schedule'] === 'otc' ? 'success' : 'warning' ?>" style="font-size: 9px; text-transform: uppercase; padding: 2px 6px;">
                  <?= e($p['schedule']) ?>
                </span>
                <span class="mono dim" style="font-size: 10.5px;">Stock: <strong><?= $p['stock_qty'] ?></strong></span>
              </div>
              <strong style="font-size: 13px; display: block; line-height: 1.2; margin-bottom: 2px;"><?= e($p['name']) ?></strong>
              <span class="dim" style="font-size: 11px;"><?= e($p['strength']) ?> • <?= e($p['form']) ?></span>
              <?php if ($p['fefo_expiry']): ?>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">Exp: <?= date('M Y', strtotime($p['fefo_expiry'])) ?></div>
              <?php endif; ?>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid var(--border); padding-top: 6px;">
              <strong class="mono" style="color: var(--brand); font-size: 13px;"><?= money($p['price_minor']) ?></strong>
              <span class="btn btn-sm btn-ghost" style="padding: 2px 6px; font-size: 10.5px;">+ Add</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Side: Order Cart & Checkout (100% visible, no outer scroll!) -->
    <div class="card" style="height: 100%; display: flex; flex-direction: column; overflow: hidden; padding: 14px;">
      
      <!-- Customer & Patient Selection (Live Fuzzy Autocomplete) -->
      <div style="margin-bottom: 10px; flex-shrink: 0; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
          <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em;">PATIENT / CUSTOMER</label>
          <div style="display: flex; gap: 4px;">
            <button type="button" class="btn btn-sm btn-ghost" onclick="resetToWalkin()" style="font-size: 10.5px; padding: 1px 6px;">Walk-in</button>
            <button type="button" class="btn btn-sm btn-ghost" onclick="focusPosPatientSearch()" style="font-size: 10.5px; padding: 1px 6px; color: var(--brand);">🔍 Search Patient</button>
          </div>
        </div>
        <div style="position: relative;">
          <?php 
            $initialPatientText = !empty($selectedPatient) ? e($selectedPatient['first_name'] . ' ' . $selectedPatient['last_name'] . ' (' . $selectedPatient['mrn'] . ')') : 'Walk-in Customer';
            $initialCustomerName = !empty($selectedPatient) ? e($selectedPatient['first_name'] . ' ' . $selectedPatient['last_name']) : 'Walk-in Customer';
            $initialPatientId = !empty($selectedPatient) ? ($selectedPatient['uuid'] ?? $selectedPatient['id']) : '';
          ?>
          <input type="text" id="posPatientSearch" class="input" style="padding: 6px 10px; font-size: 12.5px;" placeholder="Search registered patient (MRN, Name, Phone)..." value="<?= $initialPatientText ?>" onfocus="searchPosPatients(this.value)" onclick="searchPosPatients(this.value)" oninput="searchPosPatients(this.value)" autocomplete="off">
          <input type="hidden" id="customerName" value="<?= $initialCustomerName ?>">
          <input type="hidden" id="patientId" value="<?= $initialPatientId ?>">
          <div id="posPatientDropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 120; max-height: 220px; overflow-y: auto; background: var(--surface-solid, #111827); border: 1px solid var(--border-strong, #374151); border-radius: 6px; box-shadow: 0 16px 36px rgba(0,0,0,0.85); display: none; margin-top: 3px;"></div>
        </div>
        <div id="patientSelectedNotice" style="<?= !empty($selectedPatient) ? 'display: inline-block;' : 'display: none;' ?> margin-top: 4px; font-size: 11px;" class="badge badge-brand">
          <?= !empty($selectedPatient) ? '✓ Prescribed Patient Linked: ' . e($selectedPatient['first_name'] . ' ' . $selectedPatient['last_name']) . ' [' . e($selectedPatient['mrn']) . ']' : '' ?>
        </div>
      </div>

      <!-- Cart Items Table (Scrolls internally) -->
      <div style="flex: 1; overflow-y: auto; margin-bottom: 10px; min-height: 120px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: rgba(0,0,0,0.2);">
        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
          <thead style="position: sticky; top: 0; background: var(--surface); z-index: 2;">
            <tr style="border-bottom: 1px solid var(--border); color: var(--text-dim); text-transform: uppercase; font-size: 10px; text-align: left;">
              <th style="padding: 8px 10px;">Item</th>
              <th style="padding: 8px 6px; text-align: center; width: 75px;">Qty</th>
              <th style="padding: 8px 10px; text-align: right; width: 85px;">Total</th>
              <th style="padding: 8px 6px; width: 28px;"></th>
            </tr>
          </thead>
          <tbody id="cartTableBody">
            <tr><td colspan="4" class="empty" style="padding: 40px 10px; text-align: center; color: var(--muted); font-size: 12px;">Cart is empty.<br>Scan barcode or click items on the left.</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Bottom Pinned Section: POM Warning, Totals, Actions -->
      <div style="flex-shrink: 0;">
        <!-- POM Prescription Reference -->
        <div id="pomWarningBox" style="display: none; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-sm); padding: 8px 10px; margin-bottom: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: #fbbf24; margin-bottom: 4px;">⚠️ PRESCRIPTION REQUIRED FOR POM ITEMS</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <input type="text" id="prescriptionRef" class="input" placeholder="Rx Ref # *" style="font-size: 11px; padding: 4px 8px;">
            <input type="text" id="prescriberName" class="input" placeholder="Doctor Name" style="font-size: 11px; padding: 4px 8px;">
          </div>
        </div>

        <!-- Totals Summary (Compact) -->
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 10px;">
          <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">
            <span class="muted">Subtotal:</span>
            <span id="summarySubtotal" class="mono">--</span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
            <span class="muted">Tax / VAT:</span>
            <span id="summaryTax" class="mono">--</span>
          </div>
          <div class="divider" style="margin: 4px 0 6px 0;"></div>
          <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <strong style="font-size: 14px;">Total Due:</strong>
            <strong id="summaryGrandTotal" class="mono" style="font-size: 22px; color: var(--brand); font-weight: 800;">
              <?= money(0) ?>
            </strong>
          </div>
        </div>

        <!-- Actions -->
        <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 8px;">
          <button type="button" class="btn btn-ghost" style="padding: 8px;" onclick="clearCart()">Clear</button>
          <button type="button" id="btnCheckout" class="btn btn-primary" onclick="openCheckoutModal()" disabled style="font-size: 15px; font-weight: 700; padding: 10px 14px;">
            <span>Checkout & Pay &rarr;</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Dosage & Course Pack Calculator Modal -->
<div id="dosageModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 105; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 500px; padding: 24px; box-shadow: var(--shadow-glow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">💊</span>
        <div>
          <h3 style="margin: 0; font-size: 16px;">Dosage & Course Calculator</h3>
          <span id="doseProdName" class="dim" style="font-size: 12px;">Product Name</span>
        </div>
      </div>
      <button type="button" class="icon-btn" onclick="closeDosageModal()">&times;</button>
    </div>

    <!-- Quick Course Presets -->
    <div style="margin-bottom: 14px;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">
        ⚡ Instant Standard Courses
      </label>
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(3, 5, 'TDS x 5d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>TDS &times; 5 Days</strong><br><span style="color: var(--brand); font-weight: 800;">15 Units</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(3, 7, 'TDS x 7d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>TDS &times; 7 Days</strong><br><span style="color: var(--brand); font-weight: 800;">21 Units</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(2, 5, 'BD x 5d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>BD &times; 5 Days</strong><br><span style="color: var(--brand); font-weight: 800;">10 Units</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(2, 7, 'BD x 7d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>BD &times; 7 Days</strong><br><span style="color: var(--brand); font-weight: 800;">14 Units</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(1, 7, 'OD x 7d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>OD &times; 7 Days</strong><br><span style="color: var(--brand); font-weight: 800;">7 Units</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost" onclick="applyPresetCourse(4, 5, 'QDS x 5d')" style="padding: 8px 6px; font-size: 11.5px; text-align: center; border: 1px solid var(--border);">
          <strong>QDS &times; 5 Days</strong><br><span style="color: var(--brand); font-weight: 800;">20 Units</span>
        </button>
      </div>
    </div>

    <!-- Half Course Action -->
    <div style="background: rgba(20, 184, 166, 0.08); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <strong style="font-size: 13px; color: var(--brand);">🌗 Half Dosage / Course</strong>
        <div class="dim" style="font-size: 11px;">Automatically halves the current course or prescribed quantity.</div>
      </div>
      <button type="button" class="btn btn-sm btn-primary" onclick="applyHalfCourse()" style="padding: 5px 12px; font-size: 12px;">
        Apply Half Course
      </button>
    </div>

    <!-- Custom Dosage Calculator Form -->
    <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">
        Custom Dosage Formulation
      </label>
      <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 8px;">
        <div>
          <label style="font-size: 11px;">Frequency</label>
          <select id="doseFreq" class="input" style="padding: 6px; font-size: 12px;" onchange="computeCustomDosage()">
            <option value="1">Once Daily (OD)</option>
            <option value="2">Twice Daily (BD)</option>
            <option value="3" selected>Three Times (TDS)</option>
            <option value="4">Four Times (QDS)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 11px;">Units / Dose</label>
          <input type="number" id="doseUnits" class="input" value="1" min="1" max="10" style="padding: 6px; font-size: 12px; text-align: center;" oninput="computeCustomDosage()">
        </div>
        <div>
          <label style="font-size: 11px;">Days Duration</label>
          <input type="number" id="doseDays" class="input" value="5" min="1" max="90" style="padding: 6px; font-size: 12px; text-align: center;" oninput="computeCustomDosage()">
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border);">
        <span class="dim" style="font-size: 12px;">Computed Total Units:</span>
        <strong id="doseTotalComputed" class="mono" style="font-size: 20px; color: var(--brand);">15 Units</strong>
      </div>
    </div>

    <div style="display: flex; gap: 8px; justify-content: flex-end;">
      <button type="button" class="btn btn-ghost" onclick="closeDosageModal()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="applyCustomDosage()">
        <span>Apply Computed Dosage</span>
      </button>
    </div>
  </div>
</div>

<!-- Split Payment Checkout Modal -->
<div id="checkoutModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 28px; box-shadow: var(--shadow-glow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
      <h3 style="margin: 0;">Complete Payment</h3>
      <button type="button" class="icon-btn" onclick="closeCheckoutModal()">&times;</button>
    </div>

    <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px; text-align: center; margin-bottom: 20px;">
      <div class="muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">Total Amount Payable</div>
      <div id="modalTotalDue" class="mono" style="font-size: 36px; font-weight: 800; color: var(--brand); margin-top: 4px;">--</div>
    </div>

    <form id="paymentForm" onsubmit="submitSale(event)">
      <!-- Payment Method Selection -->
      <div class="field" style="margin-bottom: 14px;">
        <label>Payment Method</label>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
          <?php foreach ($paymentMethods as $pm): ?>
            <label style="cursor: pointer;">
              <input type="radio" name="pay_method" value="<?= e($pm) ?>" <?= $pm === 'cash' ? 'checked' : '' ?> style="display: none;" onchange="updatePayMethod(this.value)">
              <div class="pay-method-pill" id="pm-<?= e($pm) ?>" style="border: 1px solid var(--border); border-radius: 8px; padding: 10px; text-align: center; font-weight: 600; text-transform: uppercase; font-size: 12px;">
                <?= e(str_replace('_', ' ', $pm)) ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Cash Details (Tendered & Change Calculator) -->
      <div id="cashDetailsBox">
        <div class="field" style="margin-bottom: 12px;">
          <label for="cashTendered">Cash Tendered (Amount Given by Customer)</label>
          <input type="number" step="any" id="cashTendered" class="input" placeholder="0.00" oninput="calculateChange()">
        </div>
        <div style="display: flex; justify-content: space-between; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
          <span style="font-weight: 600; color: var(--success);">Change to Return:</span>
          <strong id="changeDue" class="mono" style="font-size: 18px; color: var(--success); font-weight: 800;"><?= money(0) ?></strong>
        </div>
      </div>

      <!-- Mobile Money / Card Reference -->
      <div id="referenceBox" style="display: none; margin-bottom: 14px;">
        <div class="field">
          <label id="referenceLabel">Transaction / MPESA Reference / Sender Phone (Optional)</label>
          <input type="text" id="payReference" class="input" placeholder="e.g. QJK89H720 or 0712345678 (optional)">
          <span class="dim" style="font-size: 11px; margin-top: 4px; display: block;">You can enter the M-Pesa transaction code, sender phone number, or leave blank for direct Send Money.</span>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="closeCheckoutModal()">Cancel</button>
        <button type="submit" id="btnFinalPay" class="btn btn-primary btn-lg" style="flex: 1;">
          <span>Authorize & Print Receipt</span>
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Receipt Modal View for instant printing -->
<div id="receiptModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 150; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 440px; padding: 24px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="margin: 0;">Sale Receipt</h3>
      <button type="button" class="icon-btn" onclick="closeReceiptModal()">&times;</button>
    </div>
    <div id="receiptIframeContainer" style="background: #fff; border-radius: 8px; overflow: hidden; height: 500px;">
      <iframe id="receiptIframe" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
    <div style="display: flex; gap: 10px; margin-top: 16px;">
      <button type="button" class="btn btn-ghost" onclick="closeReceiptModal()" style="flex: 1;">Close</button>
      <button type="button" class="btn btn-primary" onclick="printReceiptIframe()" style="flex: 1;">
        <span>Print Receipt</span>
      </button>
    </div>
  </div>
</div>

<!-- JavaScript POS Engine -->
<script>
  let cart = [];
  const currencySymbol = '<?= e(currency_meta()['symbol']) ?>';
  const currencyDecimals = <?= (int) currency_meta()['decimals'] ?>;
  const searchUrl = '<?= route('pharmacy.pos.search') ?>';
  const checkoutUrl = '<?= route('pharmacy.pos.checkout') ?>';
  let activeCategoryId = 0;
  let searchTimer = null;
  let currentPayMethod = 'cash';

  function formatMoney(minor) {
    const val = (minor / Math.pow(10, currencyDecimals)).toFixed(currencyDecimals);
    return `${currencySymbol} ${val}`;
  }

  function handleBarcodeKey(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = e.target.value.trim();
      if (!code) return;
      lookupBarcode(code);
      e.target.value = '';
    }
  }

  async function lookupBarcode(code) {
    try {
      const res = await fetch(`${searchUrl}?q=${encodeURIComponent(code)}`);
      const items = await res.json();
      if (items.length > 0) {
        addToCart(items[0]);
      } else {
        alert('Product with barcode ' + code + ' not found in inventory.');
      }
    } catch (err) {
      console.error(err);
    }
  }

  function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadProducts, 250);
  }

  function filterCategory(catId, btn) {
    activeCategoryId = catId;
    document.querySelectorAll('.cat-btn').forEach(b => {
      b.classList.remove('btn-primary');
      b.classList.add('btn-ghost');
    });
    btn.classList.add('btn-primary');
    btn.classList.remove('btn-ghost');
    loadProducts();
  }

  async function loadProducts() {
    const q = document.getElementById('searchInput').value.trim();
    try {
      const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}&category_id=${activeCategoryId}`);
      const items = await res.json();
      renderProductGrid(items);
    } catch (err) {
      console.error(err);
    }
  }

  function renderProductGrid(items) {
    const grid = document.getElementById('productGrid');
    if (!items || items.length === 0) {
      grid.innerHTML = '<div class="empty" style="grid-column: 1 / -1;"><p>No medicines found.</p></div>';
      return;
    }
    grid.innerHTML = items.map(p => `
      <div class="pos-item-card" onclick='addToCart(${JSON.stringify(p)})' style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; cursor: pointer; transition: all .2s; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
            <span class="badge badge-${p.schedule === 'otc' ? 'success' : 'warning'}" style="font-size: 10px; text-transform: uppercase;">
              ${p.schedule}
            </span>
            <span class="mono dim" style="font-size: 11px;">Stock: <strong>${p.stock_qty}</strong></span>
          </div>
          <strong style="font-size: 14px; display: block; line-height: 1.25; margin-bottom: 2px;">${p.name}</strong>
          <span class="dim" style="font-size: 11.5px;">${p.strength || ''} • ${p.form}</span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid var(--border); padding-top: 8px;">
          <strong class="mono" style="color: var(--brand); font-size: 14px;">${formatMoney(p.price_minor)}</strong>
          <span class="btn btn-sm btn-ghost" style="padding: 2px 8px; font-size: 11px;">+ Add</span>
        </div>
      </div>
    `).join('');
  }

  function addToCart(product) {
    const existing = cart.find(c => c.product.id === product.id);
    if (existing) {
      if (existing.qty + 1 > product.stock_qty) {
        alert(`Cannot add more than available inventory (${product.stock_qty}).`);
        return;
      }
      existing.qty++;
    } else {
      if (product.stock_qty < 1) {
        alert('Item is currently out of stock.');
        return;
      }
      cart.push({
        product: product,
        qty: 1,
        discount_minor: 0
      });
    }
    renderCart();
  }

  function setCartQty(idx, val) {
    const item = cart[idx];
    if (!item) return;
    let qty = parseInt(val, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    if (qty > item.product.stock_qty) {
      alert(`Cannot exceed available inventory of ${item.product.stock_qty} units.`);
      qty = item.product.stock_qty;
    }
    item.qty = qty;
    renderCart(false); // don't steal input focus
  }

  function updateQty(idx, delta) {
    const item = cart[idx];
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty <= 0) {
      cart.splice(idx, 1);
    } else if (newQty > item.product.stock_qty) {
      alert(`Cannot exceed stock level of ${item.product.stock_qty}.`);
    } else {
      item.qty = newQty;
    }
    renderCart();
  }

  function removeFromCart(idx) {
    cart.splice(idx, 1);
    renderCart();
  }

  function clearCart() {
    cart = [];
    renderCart();
  }

  // --- Dosage & Course Calculator Logic ---
  let activeDosageIdx = null;

  function openDosagePicker(idx) {
    activeDosageIdx = idx;
    const item = cart[idx];
    if (!item) return;
    document.getElementById('doseProdName').innerText = `${item.product.name} (Available: ${item.product.stock_qty})`;
    computeCustomDosage();
    document.getElementById('dosageModal').classList.remove('hidden');
  }

  function closeDosageModal() {
    document.getElementById('dosageModal').classList.add('hidden');
    activeDosageIdx = null;
  }

  function applyPresetCourse(freq, days, label) {
    if (activeDosageIdx === null) return;
    const item = cart[activeDosageIdx];
    const targetQty = freq * days;
    if (targetQty > item.product.stock_qty) {
      alert(`Preset course requires ${targetQty} units, but only ${item.product.stock_qty} are currently in stock.`);
      return;
    }
    item.qty = targetQty;
    item.dose_label = `${label} (${targetQty} units)`;
    renderCart();
    closeDosageModal();
  }

  function applyHalfCourse() {
    if (activeDosageIdx === null) return;
    const item = cart[activeDosageIdx];
    const halfQty = Math.max(1, Math.ceil(item.qty / 2));
    item.qty = halfQty;
    item.dose_label = `Half Course (${halfQty} units)`;
    renderCart();
    closeDosageModal();
  }

  function computeCustomDosage() {
    const freq = parseInt(document.getElementById('doseFreq').value, 10) || 1;
    const units = parseInt(document.getElementById('doseUnits').value, 10) || 1;
    const days = parseInt(document.getElementById('doseDays').value, 10) || 1;
    const total = freq * units * days;
    document.getElementById('doseTotalComputed').innerText = `${total} Units`;
    return total;
  }

  function applyCustomDosage() {
    if (activeDosageIdx === null) return;
    const item = cart[activeDosageIdx];
    const total = computeCustomDosage();
    if (total > item.product.stock_qty) {
      alert(`Calculated course requires ${total} units, but only ${item.product.stock_qty} are available in stock.`);
      return;
    }
    const freqText = document.getElementById('doseFreq').options[document.getElementById('doseFreq').selectedIndex].text.split(' ')[0];
    const days = document.getElementById('doseDays').value;
    item.qty = total;
    item.dose_label = `${freqText} x ${days}d (${total}u)`;
    renderCart();
    closeDosageModal();
  }

  function renderCart(refocus = true) {
    const tbody = document.getElementById('cartTableBody');
    const pomBox = document.getElementById('pomWarningBox');
    const checkoutBtn = document.getElementById('btnCheckout');

    if (cart.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty" style="padding: 60px 10px;">Cart is empty.<br>Scan a barcode or click items on the left.</td></tr>';
      document.getElementById('summarySubtotal').innerText = formatMoney(0);
      document.getElementById('summaryTax').innerText = formatMoney(0);
      document.getElementById('summaryGrandTotal').innerText = formatMoney(0);
      pomBox.style.display = 'none';
      checkoutBtn.disabled = true;
      return;
    }

    let subtotal = 0;
    let tax = 0;
    let hasPom = false;

    tbody.innerHTML = cart.map((it, idx) => {
      const lineSub = it.product.price_minor * it.qty;
      const taxRate = parseFloat(it.product.tax_rate || 0);
      const lineTax = taxRate > 0 ? Math.round(lineSub * (taxRate / 100)) : 0;
      subtotal += lineSub;
      tax += lineTax;

      if (it.product.schedule === 'pom' || it.product.schedule === 'controlled') {
        hasPom = true;
      }

      return `
        <tr style="border-bottom: 1px solid var(--border);">
          <td style="padding: 8px 10px;">
            <strong style="display: block; font-size: 13px; line-height: 1.2;">${it.product.name}</strong>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px;">
              <span class="dim" style="font-size: 11px;">${formatMoney(it.product.price_minor)}/unit</span>
              <button type="button" class="btn btn-sm btn-ghost" onclick="openDosagePicker(${idx})" style="padding: 1px 6px; font-size: 10px; color: var(--brand); border: 1px solid rgba(20, 184, 166, 0.3);" title="Calculate dose course pack">
                💊 Course
              </button>
              ${it.dose_label ? `<span class="badge badge-brand" style="font-size: 9.5px; padding: 1px 5px;">${it.dose_label}</span>` : ''}
            </div>
          </td>
          <td style="padding: 8px 4px; text-align: center;">
            <div style="display: inline-flex; align-items: center; border: 1px solid var(--border); border-radius: 6px; background: var(--surface);">
              <button type="button" onclick="updateQty(${idx}, -1)" style="border: none; background: none; color: var(--text); padding: 4px 6px; cursor: pointer; font-size: 13px; font-weight: 700;" title="Minus 1">-</button>
              <input type="number" min="1" max="${it.product.stock_qty}" value="${it.qty}" onchange="setCartQty(${idx}, this.value)" style="width: 44px; border: none; background: transparent; text-align: center; font-weight: 700; font-family: monospace; font-size: 13px; color: var(--text); outline: none; padding: 2px 0;">
              <button type="button" onclick="updateQty(${idx}, 1)" style="border: none; background: none; color: var(--text); padding: 4px 6px; cursor: pointer; font-size: 13px; font-weight: 700;" title="Plus 1">+</button>
            </div>
          </td>
          <td style="padding: 8px 10px; text-align: right;" class="mono">
            <strong style="font-size: 13px;">${formatMoney(lineSub + lineTax)}</strong>
          </td>
          <td style="padding: 8px 6px; text-align: center;">
            <button type="button" onclick="removeFromCart(${idx})" style="border: none; background: none; color: var(--danger); cursor: pointer; font-size: 16px; line-height: 1;" title="Remove">&times;</button>
          </td>
        </tr>
      `;
    }).join('');

    pomBox.style.display = hasPom ? 'block' : 'none';
    document.getElementById('summarySubtotal').innerText = formatMoney(subtotal);
    document.getElementById('summaryTax').innerText = formatMoney(tax);
    document.getElementById('summaryGrandTotal').innerText = formatMoney(subtotal + tax);
    checkoutBtn.disabled = false;
  }

  function getGrandTotalMinor() {
    let sub = 0;
    let tax = 0;
    cart.forEach(it => {
      const lineSub = it.product.price_minor * it.qty;
      const rate = parseFloat(it.product.tax_rate || 0);
      sub += lineSub;
      tax += rate > 0 ? Math.round(lineSub * (rate / 100)) : 0;
    });
    return sub + tax;
  }

  function openCheckoutModal() {
    if (cart.length === 0) return;
    const total = getGrandTotalMinor();
    document.getElementById('modalTotalDue').innerText = formatMoney(total);
    document.getElementById('cashTendered').value = (total / Math.pow(10, currencyDecimals)).toFixed(currencyDecimals);
    calculateChange();
    updatePayMethod('cash');
    document.getElementById('checkoutModal').classList.remove('hidden');
  }

  function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
  }

  function updatePayMethod(method) {
    currentPayMethod = method;
    document.querySelectorAll('.pay-method-pill').forEach(el => {
      el.style.borderColor = 'var(--border)';
      el.style.background = 'transparent';
      el.style.color = 'var(--text-muted)';
    });
    const pill = document.getElementById('pm-' + method);
    if (pill) {
      pill.style.borderColor = 'var(--brand)';
      pill.style.background = 'rgba(20, 184, 166, 0.15)';
      pill.style.color = 'var(--brand)';
    }

    const cashBox = document.getElementById('cashDetailsBox');
    const refBox = document.getElementById('referenceBox');
    const refLabel = document.getElementById('referenceLabel');

    if (method === 'cash') {
      cashBox.style.display = 'block';
      refBox.style.display = 'none';
    } else {
      cashBox.style.display = 'none';
      refBox.style.display = 'block';
      refLabel.innerText = `${method.toUpperCase()} Confirmation Code OR Sender Phone # (Optional)`;
    }
  }

  function calculateChange() {
    const total = getGrandTotalMinor();
    const tenderedVal = parseFloat(document.getElementById('cashTendered').value || 0);
    const tenderedMinor = Math.round(tenderedVal * Math.pow(10, currencyDecimals));
    const change = Math.max(0, tenderedMinor - total);
    document.getElementById('changeDue').innerText = formatMoney(change);
  }

  async function submitSale(e) {
    e.preventDefault();
    const grandTotal = getGrandTotalMinor();
    const customer = document.getElementById('customerName').value.trim() || 'Walk-in Customer';
    const patientId = document.getElementById('patientId').value || null;
    const prescriptionRef = document.getElementById('prescriptionRef')?.value.trim() || null;
    const prescriberName = document.getElementById('prescriberName')?.value.trim() || null;

    let payAmount = grandTotal;
    let payRef = null;

    if (currentPayMethod === 'cash') {
      const tenderedVal = parseFloat(document.getElementById('cashTendered').value || 0);
      const tenderedMinor = Math.round(tenderedVal * Math.pow(10, currencyDecimals));
      if (tenderedMinor < grandTotal) {
        alert('Tendered cash is less than the total due.');
        return;
      }
    } else {
      payRef = document.getElementById('payReference').value.trim() || null;
    }

    const payload = {
      _token: '<?= csrf_token() ?>',
      items: cart.map(it => ({
        product_id: it.product.id,
        qty: it.qty,
        unit_price_minor: it.product.price_minor,
        discount_minor: it.discount_minor || 0
      })),
      payments: [{
        method: currentPayMethod,
        amount_minor: grandTotal,
        reference: payRef
      }],
      patient_id: patientId,
      customer_name: customer,
      prescription_ref: prescriptionRef,
      prescriber_name: prescriberName
    };

    const btn = document.getElementById('btnFinalPay');
    btn.disabled = true;
    btn.innerText = 'Processing Transaction...';

    try {
      const res = await fetch(checkoutUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (!res.ok) {
        alert(data.message || 'Error completing checkout');
        btn.disabled = false;
        btn.innerText = 'Authorize & Print Receipt';
        return;
      }

      // Success
      closeCheckoutModal();
      clearCart();
      showReceiptModal(data.receipt_url);
    } catch (err) {
      console.error(err);
      alert('Network or server error during checkout');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Authorize & Print Receipt';
    }
  }

  function showReceiptModal(url) {
    const iframe = document.getElementById('receiptIframe');
    iframe.src = url;
    document.getElementById('receiptModal').classList.remove('hidden');
  }

  function closeReceiptModal() {
    document.getElementById('receiptModal').classList.add('hidden');
    loadProducts(); // Refresh inventory counts
  }

  function printReceiptIframe() {
    const iframe = document.getElementById('receiptIframe');
    if (iframe.contentWindow) {
      iframe.contentWindow.print();
    }
  }

  // --- Live Fuzzy Patient Autocomplete in POS ---
  let posPatientTimer;

  function focusPosPatientSearch() {
    const input = document.getElementById('posPatientSearch');
    input.focus();
    searchPosPatients('');
  }

  function resetToWalkin() {
    document.getElementById('posPatientSearch').value = 'Walk-in Customer';
    document.getElementById('customerName').value = 'Walk-in Customer';
    document.getElementById('patientId').value = '';
    const notice = document.getElementById('patientSelectedNotice');
    notice.style.display = 'none';
    notice.innerText = '';
    document.getElementById('posPatientDropdown').style.display = 'none';
  }

  function searchPosPatients(q) {
    clearTimeout(posPatientTimer);
    const dropdown = document.getElementById('posPatientDropdown');
    posPatientTimer = setTimeout(async () => {
      try {
        const baseUrl = window.PATIENTS_SEARCH_URL || '<?= route('patients.search') ?>';
        const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const pts = await res.json();
        if (!pts || !pts.length) {
          dropdown.innerHTML = '<div style="padding: 10px; font-size: 12px; color: var(--muted); text-align: center;">No matching patients found.</div>';
          dropdown.style.display = 'block';
          return;
        }
        dropdown.innerHTML = pts.map(p => `
          <div style="padding: 8px 12px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickPosPatient('${p.uuid || p.id}', '${p.first_name} ${p.last_name}', '${p.mrn}', '${p.phone || ''}', '${p.national_id || ''}', ${p.age || 'null'})">
            <div>
              <strong style="font-size: 13px;">${p.first_name} ${p.last_name}</strong>
              <div class="dim mono" style="font-size: 10.5px; margin-top: 1px;">
                MRN: <span style="color: var(--brand); font-weight: 700;">${p.mrn}</span>
                ${p.national_id ? ' • ID: ' + p.national_id : ''}
                ${p.age ? ' • ' + p.age + ' yrs' : ''}
                ${p.phone ? ' • Tel: ' + p.phone : ''}
              </div>
            </div>
            <span class="badge badge-brand">+ Select</span>
          </div>
        `).join('');
        dropdown.style.display = 'block';
      } catch (err) {
        console.error(err);
      }
    }, 50);
  }

  function pickPosPatient(id, name, mrn, phone, nationalId, age) {
    document.getElementById('posPatientSearch').value = `${name} (${mrn})`;
    document.getElementById('customerName').value = name;
    document.getElementById('patientId').value = id;
    document.getElementById('posPatientDropdown').style.display = 'none';

    const notice = document.getElementById('patientSelectedNotice');
    notice.innerText = `Selected: ${name} [${mrn}] ${age ? '• ' + age + ' yrs' : ''} ${phone ? '• ' + phone : ''}`;
    notice.style.display = 'inline-block';
  }

  // Close POS patient dropdown on outside click
  document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('posPatientDropdown');
    const searchIn = document.getElementById('posPatientSearch');
    if (dropdown && !dropdown.contains(e.target) && e.target !== searchIn) {
      dropdown.style.display = 'none';
    }
  });
</script>
