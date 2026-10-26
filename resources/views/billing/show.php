<?php $this->layout('layouts.app', ['title' => 'Invoice ' . $invoice['invoice_no']]) ?>

<style>
@media print {
  body * { visibility: hidden; }
  #printInvoiceArea, #printInvoiceArea * { visibility: visible; }
  #printInvoiceArea {
    position: absolute; left: 0; top: 0; width: 100%;
    background: #ffffff !important; color: #000000 !important;
    padding: 20px;
  }
  .no-print { display: none !important; }
}
</style>

<div class="page-head no-print">
  <div>
    <div class="breadcrumb">Patient Billing / Invoices / <?= e($invoice['invoice_no']) ?></div>
    <h1>Invoice <?= e($invoice['invoice_no']) ?></h1>
    <p class="muted">Issued to <?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?> (MRN: <?= e($invoice['mrn']) ?>)</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-ghost" onclick="window.print()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Official Bill</span>
    </button>
    <a href="<?= route('billing.index') ?>" class="btn btn-primary">
      <span>Back to Invoices</span>
    </a>
  </div>
</div>

<div class="grid grid-3" style="gap: 20px;">
  <!-- Left 2 Cols: Itemized Printable Invoice -->
  <div style="grid-column: span 2;">
    <div class="card" id="printInvoiceArea" style="padding: 32px;">
      <!-- Hospital Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--brand); padding-bottom: 20px; margin-bottom: 24px;">
        <div>
          <h2 style="margin: 0; font-size: 24px; color: var(--brand);"><?= e(tenant('name', 'Veen Hospital')) ?></h2>
          <div class="dim" style="font-size: 13px; margin-top: 4px;">
            <?= e(tenant('address', 'Medical Center Plaza')) ?> • Tel: <?= e(tenant('phone', '+254 700 000 000')) ?>
          </div>
          <div class="dim" style="font-size: 12px;">Email: <?= e(tenant('email', 'billing@hospital.com')) ?></div>
        </div>

        <div style="text-align: right;">
          <h3 style="margin: 0; font-size: 20px;">TAX INVOICE</h3>
          <div class="mono" style="font-size: 16px; font-weight: 800; color: var(--brand); margin: 4px 0;">
            <?= e($invoice['invoice_no']) ?>
          </div>
          <div style="margin: 6px 0;">
            <?= barcode_data_uri($invoice['invoice_no'], 140, 36) ? '<img src="' . barcode_data_uri($invoice['invoice_no'], 140, 36) . '" alt="Barcode" style="max-height: 36px;">' : '' ?>
          </div>
          <div class="dim" style="font-size: 11px;">Date: <?= date('d M Y H:i', strtotime($invoice['created_at'])) ?></div>
        </div>
      </div>

      <!-- Patient & Payer Details -->
      <div style="display: flex; justify-content: space-between; margin-bottom: 24px; background: rgba(255,255,255,0.02); padding: 14px; border-radius: 8px;">
        <div>
          <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Billed To (Patient):</div>
          <strong style="font-size: 16px;"><?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></strong>
          <div class="mono dim" style="font-size: 12px;">MRN: <?= e($invoice['mrn']) ?></div>
          <div class="dim" style="font-size: 12px;"><?= e($invoice['phone'] ?: 'Phone N/A') ?></div>
        </div>

        <div style="text-align: right;">
          <div class="dim" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Payment Model:</div>
          <strong style="font-size: 15px; text-transform: uppercase;"><?= !empty($invoice['insurer_name']) ? 'INSURANCE' : 'CASH / SELF PAY' ?></strong>
          <?php if (!empty($invoice['insurer_name'])): ?>
            <div style="font-size: 12px; color: var(--brand); font-weight: 600;"><?= e($invoice['insurer_name']) ?></div>
          <?php endif; ?>
          <div style="margin-top: 4px;">
            <span class="badge badge-<?= $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'partial' ? 'warning' : 'danger') ?>">
              <?= strtoupper(str_replace('_', ' ', $invoice['status'])) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Itemized Table -->
      <table class="table" style="margin-bottom: 24px;">
        <thead>
          <tr>
            <th>Item / Clinical Service</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: right;">Unit Price</th>
            <th style="text-align: right;">Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="4" class="dim" style="text-align: center; padding: 24px;">No items added to invoice yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td>
                  <strong><?= e($it['item_name']) ?></strong>
                </td>
                <td style="text-align: center;"><?= $it['qty'] ?></td>
                <td style="text-align: right;" class="mono"><?= format_minor($it['unit_price_minor'], tenant('currency')) ?></td>
                <td style="text-align: right;" class="mono font-bold"><?= format_minor($it['total_minor'], tenant('currency')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr style="border-top: 2px solid var(--border);">
            <td colspan="3" style="text-align: right; font-weight: 700;">Subtotal:</td>
            <td style="text-align: right;" class="mono font-bold"><?= format_minor($invoice['subtotal_minor'], tenant('currency')) ?></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align: right; font-weight: 700; font-size: 16px;">Total Amount Due:</td>
            <td style="text-align: right; font-size: 18px; color: var(--brand);" class="mono font-bold">
              <?= format_minor($invoice['total_minor'], tenant('currency')) ?>
            </td>
          </tr>
          <tr>
            <td colspan="3" style="text-align: right; color: #10b981; font-weight: 700;">Total Paid:</td>
            <td style="text-align: right; color: #10b981;" class="mono font-bold"><?= format_minor($invoice['paid_minor'], tenant('currency')) ?></td>
          </tr>
          <tr style="background: rgba(239, 68, 68, 0.05);">
            <td colspan="3" style="text-align: right; font-weight: 800; font-size: 16px; color: #ef4444;">Balance Outstanding:</td>
            <td style="text-align: right; font-size: 18px; color: #ef4444;" class="mono font-bold">
              <?= format_minor($invoice['balance_minor'], tenant('currency')) ?>
            </td>
          </tr>
        </tfoot>
      </table>

      <div style="border-top: 1px dashed var(--border); padding-top: 16px; font-size: 11px; color: var(--muted); text-align: center;">
        Thank you for choosing <?= e(tenant('name')) ?>. All payments are non-refundable unless authorized by hospital administration.
      </div>
    </div>
  </div>

  <!-- Right Col: Cashier Actions (Add item & Record payment) -->
  <div class="no-print">
    <!-- Record Payment Card -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-head">
        <h3>Record Payment</h3>
      </div>
      <form method="POST" action="<?= route('billing.pay', ['id' => $invoice['id']]) ?>">
        <?= csrf_field() ?>
        <div class="field" style="margin-bottom: 12px;">
          <label>Payment Amount (<?= tenant('currency') ?>) *</label>
          <input type="number" step="0.01" name="amount" class="input" required value="<?= to_major($invoice['balance_minor'], tenant('currency')) ?>" max="<?= to_major($invoice['balance_minor'], tenant('currency')) ?>">
        </div>

        <div class="field" style="margin-bottom: 16px;">
          <label>Payment Method *</label>
          <select name="method" class="input">
            <option value="cash">Cash Counter</option>
            <option value="mpesa">M-Pesa / Mobile Money</option>
            <option value="card">Debit / Credit Card</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block" <?= $invoice['balance_minor'] <= 0 ? 'disabled' : '' ?>>
          <span><?= $invoice['balance_minor'] <= 0 ? 'Invoice Fully Paid' : 'Accept & Record Payment' ?></span>
        </button>
      </form>
    </div>

    <!-- Add Line Item Card -->
    <div class="card">
      <div class="card-head">
        <h3>Add Service / Procedure</h3>
      </div>
      <form method="POST" action="<?= route('billing.items.add', ['id' => $invoice['id']]) ?>">
        <?= csrf_field() ?>
        <div class="field" style="margin-bottom: 12px;">
          <label>Service Description *</label>
          <input type="text" name="description" class="input" required placeholder="e.g. Minor Dressing or Doctor Review">
        </div>
        <div class="field" style="margin-bottom: 12px;">
          <label>Quantity</label>
          <input type="number" name="quantity" class="input" value="1" min="1">
        </div>
        <div class="field" style="margin-bottom: 16px;">
          <label>Unit Price (<?= tenant('currency') ?>) *</label>
          <input type="number" step="0.01" name="unit_price" class="input" required placeholder="500.00">
        </div>
        <button type="submit" class="btn btn-ghost btn-block">Add Charge to Bill</button>
      </form>
    </div>
  </div>
</div>