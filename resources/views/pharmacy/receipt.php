<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt <?= e($sale['receipt_no']) ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace;
      font-size: 12px;
      color: #000;
      background: #fff;
      margin: 0;
      padding: 16px;
      line-height: 1.35;
    }
    .receipt {
      max-width: 320px;
      margin: 0 auto;
    }
    .header {
      text-align: center;
      margin-bottom: 12px;
    }
    .title {
      font-size: 16px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .meta {
      font-size: 11px;
      color: #333;
      margin-top: 2px;
    }
    .divider {
      border-top: 1px dashed #666;
      margin: 8px 0;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0;
    }
    .table th {
      text-align: left;
      font-size: 10.5px;
      text-transform: uppercase;
      border-bottom: 1px dashed #666;
      padding: 4px 0;
    }
    .table td {
      padding: 4px 0;
      vertical-align: top;
    }
    .text-right { text-align: right; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .totals {
      width: 100%;
      margin-top: 6px;
    }
    .totals td {
      padding: 2px 0;
    }
    .grand-total {
      font-size: 15px;
      font-weight: 800;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 6px 0 !important;
    }
    .footer {
      text-align: center;
      margin-top: 14px;
      font-size: 10.5px;
      color: #444;
    }
    .barcode {
      text-align: center;
      margin-top: 10px;
      font-weight: 700;
      letter-spacing: 0.15em;
      font-size: 13px;
    }
    @media print {
      body { padding: 0; margin: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="receipt">
    <!-- Header -->
    <div class="header">
      <div class="title"><?= e($tenant['name']) ?></div>
      <div class="meta"><?= e($tenant['address'] ?: 'Hospital Pharmacy Department') ?></div>
      <?php if ($tenant['phone']): ?>
        <div class="meta">Tel: <?= e($tenant['phone']) ?></div>
      <?php endif; ?>
      <?php if ($tenant['tax_pin']): ?>
        <div class="meta">TAX PIN: <?= e($tenant['tax_pin']) ?></div>
      <?php endif; ?>
      <div class="meta" style="font-weight: 700; margin-top: 4px;">OFFICIAL PHARMACY RECEIPT</div>
    </div>

    <div class="divider"></div>

    <!-- Sale Meta -->
    <div style="font-size: 11px;">
      <div>Receipt: <strong class="mono"><?= e($sale['receipt_no']) ?></strong></div>
      <div>Date: <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></div>
      <div>Cashier: <?= e($sale['cashier_name'] ?: 'Dispenser') ?></div>
      <div>Customer: <strong><?= e($sale['first_name'] ? $sale['first_name'] . ' ' . $sale['last_name'] : ($sale['customer_name'] ?: 'Walk-in')) ?></strong></div>
      <?php if ($sale['mrn']): ?>
        <div>MRN: <span class="mono"><?= e($sale['mrn']) ?></span></div>
      <?php endif; ?>
      <?php if ($sale['prescription_ref']): ?>
        <div>Rx Ref: <span class="mono"><?= e($sale['prescription_ref']) ?></span> (<?= e($sale['prescriber_name'] ?: 'Doctor') ?>)</div>
      <?php endif; ?>
    </div>

    <div class="divider"></div>

    <!-- Line Items -->
    <table class="table">
      <thead>
        <tr>
          <th>Item / Batch</th>
          <th class="text-right">Qty</th>
          <th class="text-right">Price</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <strong style="display: block;"><?= e($it['product_name']) ?></strong>
              <span style="font-size: 10px; color: #555;">
                <?= e($it['strength'] ?: $it['form']) ?>
                <?php if ($it['batch_no']): ?>• B:<?= e($it['batch_no']) ?><?php endif; ?>
              </span>
            </td>
            <td class="text-right mono"><?= $it['qty'] ?></td>
            <td class="text-right mono"><?= money($it['unit_price_minor']) ?></td>
            <td class="text-right mono"><strong><?= money($it['total_minor']) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals Table -->
    <table class="totals">
      <tr>
        <td>Subtotal</td>
        <td class="text-right mono"><?= money($sale['subtotal_minor']) ?></td>
      </tr>
      <?php if ((int) $sale['discount_minor'] > 0): ?>
      <tr>
        <td>Discount</td>
        <td class="text-right mono">-<?= money($sale['discount_minor']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ((int) $sale['tax_minor'] > 0): ?>
      <tr>
        <td>VAT / Tax</td>
        <td class="text-right mono"><?= money($sale['tax_minor']) ?></td>
      </tr>
      <?php endif; ?>
      <tr class="grand-total">
        <td>TOTAL DUE</td>
        <td class="text-right mono"><?= money($sale['total_minor']) ?></td>
      </tr>
    </table>

    <div class="divider"></div>

    <!-- Payments -->
    <div style="font-size: 11px;">
      <?php foreach ($payments as $pay): ?>
        <div style="display: flex; justify-content: space-between;">
          <span style="text-transform: uppercase; font-weight: 600;"><?= e($pay['method']) ?> <?= $pay['reference'] ? '(' . e($pay['reference']) . ')' : '' ?></span>
          <span class="mono"><?= money($pay['amount_minor']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <div class="barcode mono">*<?= e($sale['receipt_no']) ?>*</div>

    <!-- Footer -->
    <div class="footer">
      <div>Medicines once sold cannot be returned.</div>
      <div>Keep this receipt for all inquiries.</div>
      <div style="margin-top: 8px; font-weight: 700;">Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Veen Africa</a></div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
      <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer;">Print Receipt</button>
    </div>
  </div>
</body>
</html>
