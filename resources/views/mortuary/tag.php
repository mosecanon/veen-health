<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MORTUARY BODY IDENTIFICATION TAG - <?= e($record->body_tag_number) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 30px 15px;
    }
    .no-print-bar {
      margin-bottom: 20px;
      display: flex;
      gap: 12px;
    }
    .btn {
      padding: 8px 18px;
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
    
    .body-tag-card {
      width: 480px;
      background: #ffffff;
      border: 3px solid #0f172a;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
      position: relative;
    }
    .hole-punch {
      position: absolute;
      top: 18px;
      left: 18px;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 2px dashed #94a3b8;
      background: #f8fafc;
    }
    .header {
      text-align: center;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .header h2 {
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #b91c1c;
      font-weight: 800;
    }
    .header h1 {
      font-size: 13px;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 2px;
    }
    .tag-number-banner {
      background: #0f172a;
      color: #ffffff;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 16px;
    }
    .tag-number-banner .label {
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #94a3b8;
    }
    .tag-number-banner .val {
      font-size: 26px;
      font-weight: 900;
      letter-spacing: 0.05em;
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .barcode {
      display: flex;
      justify-content: center;
      margin: 10px 0 16px;
    }
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 14px;
    }
    .field {
      border-bottom: 1px dotted #cbd5e1;
      padding-bottom: 4px;
    }
    .field.full {
      grid-column: span 2;
    }
    .field .lbl {
      font-size: 10px;
      text-transform: uppercase;
      color: #64748b;
      font-weight: 700;
      letter-spacing: 0.03em;
    }
    .field .txt {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
      margin-top: 1px;
    }
    .location-box {
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 10px;
      display: flex;
      justify-content: space-around;
      text-align: center;
      margin-bottom: 14px;
    }
    .location-box .sub-val {
      font-size: 15px;
      font-weight: 800;
      color: #0369a1;
    }
    .footer-note {
      text-align: center;
      font-size: 9.5px;
      color: #dc2626;
      font-weight: 700;
      text-transform: uppercase;
      border-top: 1px dashed #cbd5e1;
      padding-top: 8px;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      .no-print-bar { display: none !important; }
      .body-tag-card {
        box-shadow: none;
        border: 2px solid #000;
        width: 100%;
        max-width: 500px;
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print Body Tag</button>
    <a class="btn btn-secondary" href="javascript:history.back()">← Return to Mortuary</a>
  </div>

  <div class="body-tag-card">
    <div class="hole-punch"></div>

    <div class="header">
      <h2>Mortuary Identification Tag</h2>
      <h1>Pathology & Mortuary Services Department</h1>
    </div>

    <div class="tag-number-banner">
      <div class="label">Official Body Tag Number</div>
      <div class="val"><?= e($record->body_tag_number) ?></div>
    </div>

    <!-- Barcode Representation -->
    <div class="barcode">
      <svg width="280" height="42" viewBox="0 0 280 42">
        <rect x="0" y="0" width="280" height="42" fill="#fff" />
        <?php
          $chars = str_split(crc32($record->body_tag_number) . '981247');
          $x = 10;
          foreach ($chars as $i => $c) {
              $w = ((int)$c % 3) + 1;
              echo "<rect x='{$x}' y='2' width='{$w}' height='36' fill='#000' />";
              $x += $w + 2;
          }
        ?>
        <rect x="250" y="2" width="4" height="36" fill="#000" />
        <rect x="258" y="2" width="2" height="36" fill="#000" />
        <rect x="264" y="2" width="5" height="36" fill="#000" />
      </svg>
    </div>

    <div class="grid">
      <div class="field full">
        <div class="lbl">Deceased Full Name</div>
        <div class="txt" style="font-size: 16px;"><?= e($record->deceased_full_name) ?></div>
      </div>
      <div class="field">
        <div class="lbl">Gender / Sex</div>
        <div class="txt"><?= ucfirst(e($record->gender)) ?></div>
      </div>
      <div class="field">
        <div class="lbl">Age</div>
        <div class="txt"><?= $record->estimated_or_actual_age ? e($record->estimated_or_actual_age) . ' yrs' : 'Unknown' ?></div>
      </div>
      <div class="field">
        <div class="lbl">Date of Death</div>
        <div class="txt"><?= e($record->date_of_death) ?></div>
      </div>
      <div class="field">
        <div class="lbl">Time of Death</div>
        <div class="txt"><?= e($record->time_of_death) ?></div>
      </div>
      <div class="field full">
        <div class="lbl">Intake Category / Origin</div>
        <div class="txt"><?= strtoupper(str_replace('_', ' ', e($record->death_type))) ?> <?= $record->place_of_death ? '— ' . e($record->place_of_death) : '' ?></div>
      </div>
    </div>

    <div class="location-box">
      <div>
        <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Chamber</div>
        <div class="sub-val"><?= e($record->cold_room_chamber) ?></div>
      </div>
      <div style="border-left: 1px solid #cbd5e1; height: 30px;"></div>
      <div>
        <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Storage Tray</div>
        <div class="sub-val"><?= e($record->tray_number) ?></div>
      </div>
      <div style="border-left: 1px solid #cbd5e1; height: 30px;"></div>
      <div>
        <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Preservation</div>
        <div class="sub-val"><?= ucfirst(e($record->preservation_status)) ?></div>
      </div>
    </div>

    <div class="grid" style="margin-bottom: 8px;">
      <div class="field full">
        <div class="lbl">Next of Kin / Contact</div>
        <div class="txt"><?= e($record->next_of_kin_name ?: 'None Registered') ?> (<?= e($record->next_of_kin_phone ?: 'N/A') ?>) - <?= e($record->next_of_kin_relationship ?: 'Kin') ?></div>
      </div>
      <div class="field full">
        <div class="lbl">Certifying Medical Officer</div>
        <div class="txt"><?= e($record->certifying_doctor_name ?: 'Medical Officer On Duty') ?></div>
      </div>
    </div>

    <div class="footer-note">
      DO NOT REMOVE THIS TAG UNTIL OFFICIAL RELEASE & HANDOVER OF THE BODY
    </div>
  </div>

</body>
</html>
