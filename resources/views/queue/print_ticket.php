<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ticket <?= e($ticket['code']) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace;
      background: #fff;
      color: #000;
      width: 80mm;
      margin: 0 auto;
      padding: 16px 12px;
      font-size: 13px;
      text-align: center;
    }
    .h-name {
      font-size: 16px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .dept {
      font-size: 12px;
      color: #333;
      margin-top: 2px;
      font-weight: 600;
    }
    .dash {
      border-top: 1px dashed #444;
      margin: 12px 0;
    }
    .label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #555;
    }
    .ticket-code {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 56px;
      font-weight: 900;
      letter-spacing: -0.02em;
      line-height: 1.05;
      margin: 6px 0;
    }
    .patient-name {
      font-size: 14px;
      font-weight: 700;
      margin-top: 4px;
    }
    .mrn {
      font-family: monospace;
      font-size: 12px;
      color: #444;
    }
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border: 1px solid #000;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 800;
      margin-top: 6px;
      text-transform: uppercase;
    }
    .barcode-box {
      margin: 10px 0;
    }
    .barcode-box svg {
      max-width: 100%;
      height: 44px;
    }
    .footer {
      font-size: 11px;
      color: #555;
      line-height: 1.35;
    }
    .timestamp {
      font-size: 10px;
      color: #777;
      margin-top: 8px;
    }
    @media print {
      body { width: 100%; padding: 4px; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="h-name"><?= e($tenant['name']) ?></div>
  <div class="dept"><?= e($ticket['queue_name']) ?></div>
  
  <div class="dash"></div>

  <div class="label">Your Ticket Number</div>
  <div class="ticket-code"><?= e($ticket['code']) ?></div>

  <?php if ($ticket['first_name']): ?>
    <div class="patient-name"><?= e($ticket['first_name'] . ' ' . $ticket['last_name']) ?></div>
    <div class="mrn">MRN: <?= e($ticket['mrn']) ?></div>
  <?php elseif ($ticket['customer_name']): ?>
    <div class="patient-name"><?= e($ticket['customer_name']) ?></div>
  <?php endif; ?>

  <?php if ((int) $ticket['priority'] === 2): ?>
    <div class="badge">EMERGENCY</div>
  <?php elseif ((int) $ticket['priority'] === 1): ?>
    <div class="badge">PRIORITY</div>
  <?php endif; ?>

  <!-- Barcode for Automated Scanner Check-in -->
  <div class="barcode-box">
    <?= barcode($ticket['code'], 38, 2, false) ?>
  </div>

  <div class="dash"></div>

  <div class="footer">
    Please watch the TV display screen<br>and listen for your audio announcement.
  </div>

  <div class="timestamp">
    <?= date('d M Y • H:i:s', strtotime($ticket['created_at'])) ?>
    <br>Powered by <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline; font-weight: 600;">Veen Africa</a>
  </div>

  <div class="no-print" style="margin-top: 18px;">
    <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer;">Print Ticket</button>
    <button onclick="window.close()" style="padding: 8px 16px; font-size: 13px; margin-left: 6px; cursor: pointer;">Close</button>
  </div>
</body>
</html>
