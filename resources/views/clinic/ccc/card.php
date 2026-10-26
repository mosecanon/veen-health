<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCC PATIENT APPOINTMENT PASSPORT - <?= e($profile->ccc_number) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: #f8fafc;
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

    .passport-card {
      width: 720px;
      background: #ffffff;
      border: 2px solid #0f172a;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .header {
      text-align: center;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .hospital-title {
      font-size: 17px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .dept-title {
      font-size: 13px;
      color: #0284c7;
      font-weight: 700;
      text-transform: uppercase;
      margin-top: 2px;
    }
    .passport-heading {
      font-size: 15px;
      font-weight: 800;
      color: #b91c1c;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-top: 6px;
    }
    .banner {
      background: #0f172a;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 18px;
      border-radius: 6px;
      margin-bottom: 16px;
    }
    .banner .ccc-num {
      font-size: 20px;
      font-weight: 800;
      font-family: monospace;
      letter-spacing: 0.05em;
    }
    .client-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 16px;
      font-size: 12.5px;
    }
    .field {
      border-bottom: 1px dotted #cbd5e1;
      padding-bottom: 4px;
    }
    .field .lbl {
      color: #64748b;
      font-size: 10.5px;
      text-transform: uppercase;
      font-weight: 700;
    }
    .field .val {
      color: #0f172a;
      font-weight: 700;
      font-size: 13.5px;
      margin-top: 1px;
    }
    .sec-head {
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #0369a1;
      background: #f1f5f9;
      padding: 4px 8px;
      margin: 16px 0 10px;
      border-left: 3px solid #0284c7;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11.5px;
      margin-bottom: 16px;
    }
    .history-table th, .history-table td {
      border: 1px solid #cbd5e1;
      padding: 6px 8px;
      text-align: left;
    }
    .history-table th {
      background: #f8fafc;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 10px;
    }
    .notice {
      text-align: center;
      font-size: 10.5px;
      color: #64748b;
      border-top: 1px dashed #cbd5e1;
      padding-top: 8px;
      margin-top: 16px;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .no-print-bar { display: none !important; }
      .passport-card {
        box-shadow: none;
        width: 100%;
        max-width: 100%;
        border: 2px solid #000;
      }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print Appointment Passport</button>
    <a class="btn btn-secondary" href="<?= route('clinic.ccc.show', ['id' => $profile->uuid ?? $profile->id]) ?>">← Back to Profile</a>
  </div>

  <div class="passport-card">
    <div class="header">
      <div class="hospital-title">DEPARTMENT OF HEALTH SERVICES</div>
      <div class="dept-title">Comprehensive Care Centre (CCC) & Chronic Care Clinic</div>
      <div class="passport-heading">Client Appointment & ARV Refill Passport</div>
    </div>

    <div class="banner">
      <div>
        <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Unique CCC Identifier</div>
        <div class="ccc-num"><?= e($profile->ccc_number) ?></div>
      </div>
      <div style="text-align: right;">
        <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Current Regimen</div>
        <div style="font-size: 16px; font-weight: 800; color: #38bdf8; font-family: monospace;"><?= e($profile->current_regimen) ?></div>
      </div>
    </div>

    <div class="client-grid">
      <div class="field">
        <div class="lbl">Client Legal Name</div>
        <div class="val"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></div>
      </div>
      <div class="field">
        <div class="lbl">Medical Record Number (MRN)</div>
        <div class="val mono"><?= e($patient['mrn']) ?></div>
      </div>
      <div class="field">
        <div class="lbl">Sex / Age</div>
        <div class="val"><?= ucfirst(e($patient['sex'])) ?> / <?= $patient['dob'] ? age_from_dob($patient['dob']) . ' yrs' : 'N/A' ?></div>
      </div>
      <div class="field">
        <div class="lbl">Contact Phone</div>
        <div class="val"><?= e($patient['phone'] ?: 'No Phone Recorded') ?></div>
      </div>
      <div class="field">
        <div class="lbl">ART Start Date</div>
        <div class="val"><?= $profile->art_start_date ? date('d M Y', strtotime($profile->art_start_date)) : 'N/A' ?></div>
      </div>
      <div class="field">
        <div class="lbl">Treatment Supporter (Buddy)</div>
        <div class="val"><?= e($profile->treatment_buddy_name ?: 'Self / None') ?> <?= $profile->treatment_buddy_phone ? '(' . e($profile->treatment_buddy_phone) . ')' : '' ?></div>
      </div>
    </div>

    <div class="sec-head">Recent Clinical Encounters & ARV Refill Log</div>
    <table class="history-table">
      <thead>
        <tr>
          <th>Visit Date</th>
          <th>Weight</th>
          <th>Regimen Dispensed</th>
          <th>Months</th>
          <th>Viral Load (c/mL)</th>
          <th>CD4</th>
          <th>Next Refill Date</th>
          <th>Clinician Stamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($visits)): ?>
          <tr><td colspan="8" style="text-align: center;">No visits logged yet.</td></tr>
        <?php else: ?>
          <?php foreach ($visits as $v): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($v['visit_date'])) ?></td>
              <td><?= $v['weight_kg'] ? e($v['weight_kg']) . ' kg' : '-' ?></td>
              <td class="mono font-bold"><?= e($v['regimen_dispensed']) ?></td>
              <td><?= e($v['months_dispensed']) ?> MMD</td>
              <td class="mono"><?= $v['viral_load_copies_ml'] !== null ? number_format($v['viral_load_copies_ml']) : '-' ?></td>
              <td><?= $v['cd4_count'] ?: '-' ?></td>
              <td class="font-bold mono"><?= $v['next_appointment_date'] ? date('d/m/Y', strtotime($v['next_appointment_date'])) : '-' ?></td>
              <td style="width: 80px; text-align: center; color: #94a3b8; font-size: 9px;">[SIGNED]</td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="notice">
      Please carry this passport on every clinic visit. For any questions, medicine inquiries, or urgent consultations, contact the CCC Nurse Helpline immediately.
    </div>
  </div>

</body>
</html>
