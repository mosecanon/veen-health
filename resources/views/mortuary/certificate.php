<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MEDICAL CERTIFICATE OF CAUSE OF DEATH - <?= e($record->certificate_of_death_number) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Times New Roman", Times, serif;
      background: #f8fafc;
      color: #111827;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 30px 20px;
    }
    .no-print-bar {
      margin-bottom: 24px;
      display: flex;
      gap: 12px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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

    .certificate-page {
      width: 790px;
      background: #ffffff;
      border: 4px double #1e293b;
      padding: 40px 50px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      position: relative;
    }
    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      font-size: 80px;
      color: rgba(226, 232, 240, 0.35);
      font-weight: 900;
      pointer-events: none;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      z-index: 1;
    }
    .cert-content {
      position: relative;
      z-index: 2;
    }
    .header {
      text-align: center;
      border-bottom: 2px solid #1e293b;
      padding-bottom: 16px;
      margin-bottom: 20px;
    }
    .hospital-title {
      font-size: 20px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #0f172a;
    }
    .sub-title {
      font-size: 13px;
      font-style: italic;
      color: #475569;
      margin-top: 2px;
    }
    .cert-heading {
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-top: 10px;
      text-decoration: underline;
    }
    .ref-bar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 18px;
      font-size: 13px;
      font-weight: bold;
      border-bottom: 1px solid #94a3b8;
      padding-bottom: 6px;
    }
    .section-title {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      background: #f1f5f9;
      padding: 4px 8px;
      margin: 14px 0 10px;
      border-left: 4px solid #0f172a;
    }
    .details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 16px;
    }
    .details-table td {
      padding: 6px 4px;
      font-size: 13px;
      vertical-align: top;
    }
    .details-table .lbl {
      width: 25%;
      color: #475569;
      font-weight: bold;
    }
    .details-table .val {
      width: 75%;
      color: #0f172a;
      border-bottom: 1px dotted #94a3b8;
      font-weight: 500;
    }
    .cause-box {
      border: 1px solid #94a3b8;
      padding: 12px;
      margin-bottom: 16px;
    }
    .cause-row {
      margin-bottom: 8px;
      font-size: 13px;
      display: flex;
    }
    .cause-row .code {
      width: 32px;
      font-weight: bold;
    }
    .cause-row .text {
      flex: 1;
      border-bottom: 1px dotted #94a3b8;
      padding-bottom: 2px;
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
      margin-top: 50px;
      padding-top: 6px;
      font-size: 12px;
      font-weight: bold;
    }
    .notice {
      margin-top: 30px;
      font-size: 10.5px;
      color: #64748b;
      text-align: center;
      border-top: 1px solid #cbd5e1;
      padding-top: 8px;
      font-style: italic;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .no-print-bar { display: none !important; }
      .certificate-page {
        box-shadow: none;
        width: 100%;
        max-width: 100%;
        border: 2px solid #000;
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print Death Certificate</button>
    <a class="btn btn-secondary" href="<?= route('mortuary.show', ['id' => $record->uuid ?? $record->id]) ?>">← Back to Mortuary Record</a>
  </div>

  <div class="certificate-page">
    <div class="watermark">OFFICIAL RECORD</div>

    <div class="cert-content">
      <div class="header">
        <div class="hospital-title">DEPARTMENT OF HEALTH & MEDICAL SERVICES</div>
        <div class="sub-title">Pathology & Medico-Legal Services Department</div>
        <div class="cert-heading">Medical Certificate of Cause of Death</div>
        <div style="font-size: 11px; margin-top: 4px; color: #475569;">(In accordance with World Health Organization ICD-11 Standards)</div>
      </div>

      <div class="ref-bar">
        <div>Certificate No: <span style="font-family: monospace;"><?= e($record->certificate_of_death_number) ?></span></div>
        <div>Body Tag Ref: <span style="font-family: monospace;"><?= e($record->body_tag_number) ?></span></div>
      </div>

      <div class="section-title">1. Particulars of the Deceased</div>
      <table class="details-table">
        <tr>
          <td class="lbl">Full Legal Name:</td>
          <td class="val" style="font-size: 15px; font-weight: bold;"><?= e($record->deceased_full_name) ?></td>
        </tr>
        <tr>
          <td class="lbl">Sex / Gender:</td>
          <td class="val"><?= ucfirst(e($record->gender)) ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Age:</strong> <?= $record->estimated_or_actual_age ? e($record->estimated_or_actual_age) . ' Years' : 'Unknown / Unrecorded' ?></td>
        </tr>
        <tr>
          <td class="lbl">Date & Time of Death:</td>
          <td class="val"><?= e($record->date_of_death) ?> at <?= e($record->time_of_death) ?></td>
        </tr>
        <tr>
          <td class="lbl">Place of Occurrence:</td>
          <td class="val"><?= e($record->place_of_death ?: 'Hospital Facility') ?> (Classification: <?= strtoupper(str_replace('_', ' ', e($record->death_type))) ?>)</td>
        </tr>
        <?php if (!empty($record->next_of_kin_name)): ?>
        <tr>
          <td class="lbl">Next of Kin:</td>
          <td class="val"><?= e($record->next_of_kin_name) ?> (<?= e($record->next_of_kin_relationship ?: 'Kin') ?>, <?= e($record->next_of_kin_phone ?: 'Phone Not Provided') ?>)</td>
        </tr>
        <?php endif; ?>
      </table>

      <div class="section-title">2. Cause of Death (WHO Standard Format)</div>
      <div class="cause-box">
        <div style="font-size: 11.5px; font-weight: bold; margin-bottom: 8px; color: #334155;">
          Part I: Disease or condition directly leading to death (not the mode of dying)
        </div>
        <div class="cause-row">
          <div class="code">(a)</div>
          <div class="text">
            <strong>Immediate Cause:</strong> <?= e($record->cause_of_death_immediate ?: 'Cardiopulmonary Arrest') ?>
          </div>
        </div>
        <div class="cause-row">
          <div class="code">(b)</div>
          <div class="text">
            <strong>Antecedent / Underlying Cause:</strong> <?= e($record->cause_of_death_underlying ?: 'Not documented') ?>
          </div>
        </div>

        <div style="font-size: 11.5px; font-weight: bold; margin: 12px 0 6px; color: #334155;">
          Part II: Other significant conditions contributing to the death
        </div>
        <div class="cause-row">
          <div class="code">--</div>
          <div class="text">
            <?= e($record->contributory_cause ?: 'None documented') ?>
          </div>
        </div>
      </div>

      <div class="section-title">3. Medico-Legal / Post-Mortem Status</div>
      <table class="details-table">
        <tr>
          <td class="lbl">Autopsy Performed:</td>
          <td class="val">
            <?= $record->autopsy_required ? 'YES (Post-Mortem Status: ' . strtoupper(str_replace('_', ' ', e($record->autopsy_status))) . ')' : 'NO (Clinical Certification)' ?>
            <?php if (!empty($record->pathologist_name)): ?>
              — Conducted by: Dr. <?= e($record->pathologist_name) ?> on <?= e($record->autopsy_date) ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php if (!empty($record->autopsy_findings)): ?>
        <tr>
          <td class="lbl">Autopsy Findings:</td>
          <td class="val"><?= nl2br(e($record->autopsy_findings)) ?></td>
        </tr>
        <?php endif; ?>
      </table>

      <div class="signatures">
        <div class="sig-col">
          <div class="sig-line">
            Dr. <?= e($record->certifying_doctor_name ?: 'Attending Medical Officer') ?><br>
            <span style="font-size: 11px; font-weight: normal; color: #475569;">
              Certifying Medical Practitioner<br>
              Licence / Reg No: <?= e($record->certifying_doctor_license ?: 'MED-' . rand(10000, 99999)) ?>
            </span>
          </div>
        </div>

        <div class="sig-col">
          <div class="sig-line">
            Hospital Registrar / Medical Superintendent<br>
            <span style="font-size: 11px; font-weight: normal; color: #475569;">
              Official Hospital Seal & Stamp<br>
              Date: <?= date('d M Y') ?>
            </span>
          </div>
        </div>
      </div>

      <div class="notice">
        This document is an authentic clinical certification of death. Any unauthorized alteration, erasure, or duplication renders it invalid under the Public Health and Births & Deaths Registration Acts.
      </div>
    </div>
  </div>

</body>
</html>
