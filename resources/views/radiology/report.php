<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DIAGNOSTIC IMAGING REPORT - <?= e($order->order_number) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: #f8fafc;
      color: #0f172a;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 30px 20px;
    }
    .no-print-bar {
      margin-bottom: 24px;
      display: flex;
      gap: 12px;
    }
    .btn {
      padding: 9px 20px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-primary { background: #0284c7; color: #fff; border: 1px solid #0284c7; }
    .btn-secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }

    .report-card {
      width: 790px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      padding: 40px 50px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    }
    .header {
      border-bottom: 2px solid #0f172a;
      padding-bottom: 14px;
      margin-bottom: 18px;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }
    .hospital-title {
      font-size: 19px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #0f172a;
    }
    .dept-title {
      font-size: 13px;
      color: #0284c7;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .report-heading {
      text-align: center;
      background: #f1f5f9;
      padding: 6px;
      font-size: 14px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 18px;
      border: 1px solid #e2e8f0;
    }
    .patient-bar {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 14px;
      background: #fafafa;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 12.5px;
    }
    .grid-row {
      display: flex;
      justify-content: space-between;
      padding: 3px 0;
    }
    .lbl { color: #64748b; font-weight: 600; }
    .val { color: #0f172a; font-weight: 700; }
    
    .section-title {
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #0369a1;
      border-bottom: 1px solid #cbd5e1;
      padding-bottom: 4px;
      margin: 16px 0 8px;
    }
    .content-box {
      font-size: 13px;
      line-height: 1.6;
      color: #1e293b;
      white-space: pre-wrap;
      margin-bottom: 16px;
    }
    .impression-box {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-left: 4px solid #16a34a;
      padding: 12px 16px;
      border-radius: 6px;
      font-size: 13.5px;
      font-weight: 600;
      color: #14532d;
      line-height: 1.5;
      margin-top: 14px;
    }
    .signatures {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
      padding-top: 10px;
    }
    .sig-col {
      width: 45%;
      text-align: center;
    }
    .sig-line {
      border-top: 1px solid #0f172a;
      margin-top: 40px;
      padding-top: 6px;
      font-size: 12px;
      font-weight: bold;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .no-print-bar { display: none !important; }
      .report-card {
        box-shadow: none;
        border: none;
        width: 100%;
        max-width: 100%;
        padding: 20px;
      }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print Diagnostic Report</button>
    <a class="btn btn-secondary" href="<?= route('radiology.show', ['id' => $order->uuid ?? $order->id]) ?>">← Back to Order</a>
  </div>

  <div class="report-card">
    <div class="header">
      <div>
        <div class="hospital-title">Department of Diagnostic Imaging & Radiology</div>
        <div class="dept-title">High-Field Magnetic Resonance Imaging (MRI) & CT Division</div>
      </div>
      <div style="text-align: right; font-size: 12px;">
        <div>Order #: <strong style="font-family: monospace; font-size: 14px;"><?= e($order->order_number) ?></strong></div>
        <div class="lbl" style="margin-top: 2px;">Date: <?= date('d M Y') ?></div>
      </div>
    </div>

    <div class="report-heading">
      Official Diagnostic Imaging Report
    </div>

    <div class="patient-bar">
      <div>
        <div class="grid-row"><span class="lbl">Patient Full Name:</span> <span class="val" style="font-size: 14px;"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></span></div>
        <div class="grid-row"><span class="lbl">Medical Record # (MRN):</span> <span class="val mono"><?= e($patient['mrn']) ?></span></div>
        <div class="grid-row"><span class="lbl">Sex / Age:</span> <span class="val"><?= ucfirst(e($patient['sex'])) ?> / <?= $patient['dob'] ? age_from_dob($patient['dob']) . ' Years' : 'N/A' ?></span></div>
        <?php if ($admission): ?>
          <div class="grid-row"><span class="lbl">Inpatient Ward & Bed:</span> <span class="val" style="color: #8b5cf6;"><?= e($admission['ward_name']) ?> &mdash; Bed <?= e($admission['bed_number']) ?></span></div>
        <?php else: ?>
          <div class="grid-row"><span class="lbl">Patient Status:</span> <span class="val">Outpatient</span></div>
        <?php endif; ?>
      </div>

      <div style="border-left: 1px solid #e2e8f0; padding-left: 14px;">
        <div class="grid-row"><span class="lbl">Modality:</span> <span class="val"><?= strtoupper(str_replace('_', ' ', e($order->modality))) ?></span></div>
        <div class="grid-row"><span class="lbl">Region:</span> <span class="val"><?= e($order->body_part) ?></span></div>
        <div class="grid-row"><span class="lbl">Protocol:</span> <span class="val"><?= ucwords(str_replace('_', ' ', e($order->contrast_type))) ?></span></div>
        <div class="grid-row"><span class="lbl">Referring Doctor:</span> <span class="val">Dr. <?= e($doctor['name'] ?? 'Attending Clinician') ?></span></div>
      </div>
    </div>

    <div class="section-title">Clinical Indication & History</div>
    <div class="content-box"><?= nl2br(e($order->clinical_indication)) ?></div>

    <?php if ($order->egfr_renal_value): ?>
      <div style="font-size: 11.5px; color: #64748b; margin-bottom: 12px;">
        Renal Function: eGFR = <?= e($order->egfr_renal_value) ?> mL/min (Cleared for IV Gadolinium/Iodinated Contrast).
      </div>
    <?php endif; ?>

    <div class="section-title">Technique & Protocol</div>
    <div class="content-box">
      Multi-planar, multi-sequence <?= strtoupper(str_replace('_', ' ', e($order->modality))) ?> examination of the <?= e($order->body_part) ?> performed with <?= ucwords(str_replace('_', ' ', e($order->contrast_type))) ?>. Standard diagnostic imaging protocols acquired in sagittal, coronal, and axial planes.
    </div>

    <div class="section-title">Radiological Findings</div>
    <div class="content-box"><?= e($order->findings ?: 'No diagnostic findings filed yet.') ?></div>

    <div class="section-title" style="color: #15803d; border-bottom-color: #86efac;">Impression & Diagnostic Conclusion</div>
    <div class="impression-box">
      <?= nl2br(e($order->impression ?: 'Awaiting radiologist conclusion.')) ?>
    </div>

    <div class="signatures">
      <div class="sig-col">
        <div class="sig-line">
          Dr. <?= e($order->radiologist_name ?: 'Radiologist on Duty') ?><br>
          <span style="font-size: 11px; font-weight: normal; color: #475569;">
            Consultant Radiologist & Diagnostic Specialist<br>
            Signed: <?= $order->completed_at ? date('d M Y H:i', strtotime($order->completed_at)) : date('d M Y H:i') ?>
          </span>
        </div>
      </div>

      <div class="sig-col">
        <div class="sig-line">
          Department of Radiology & Imaging Sciences<br>
          <span style="font-size: 11px; font-weight: normal; color: #475569;">
            Hospital Electronic Diagnostic Archive<br>
            Verification Key: <?= strtoupper(substr(md5($order->order_number), 0, 10)) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
