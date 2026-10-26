<?php $this->layout('layouts.app', ['title' => 'Insurance Claims & Payers']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Finance / Insurance & Third-Party Claims</div>
    <h1>Health Insurance Claims & Adjudication</h1>
    <p class="muted">Manage claims for Social Health Authority (SHA/NHIF), Jubilee, AAR, Old Mutual, and private medical schemes.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('billing.index') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      <span>Back to Invoices</span>
    </a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('newClaimModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Submit New Claim</span>
    </button>
  </div>
</div>

<!-- Claims Table -->
<div class="card">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <h3>Submitted Insurance Claims (<?= count($claims) ?>)</h3>
    <div style="width: 100%; max-width: 320px;">
      <input type="text" class="input" placeholder="🔍 Filter claims on keyup (Claim #, Patient, Scheme)..." onkeyup="filterTable(this, 'claimsTable')" style="padding: 6px 12px; font-size: 13px;">
    </div>
  </div>
  <div class="table-wrap">
    <table class="table" id="claimsTable">
      <thead>
        <tr>
          <th>Claim #</th>
          <th>Insurance Payer</th>
          <th>Patient & MRN</th>
          <th>Policy / Preauth Code</th>
          <th>Claimed Amount</th>
          <th>Approved Amount</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($claims)): ?>
          <tr>
            <td colspan="8" class="dim" style="text-align: center; padding: 32px;">No insurance claims submitted yet. Click "Submit New Claim" above.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($claims as $c): ?>
            <tr>
              <td>
                <strong class="mono" style="color: var(--brand);"><?= e($c['claim_no']) ?></strong>
                <div class="dim" style="font-size: 11px;"><?= date('d M Y', strtotime($c['created_at'])) ?></div>
              </td>
              <td>
                <strong><?= e($c['insurer_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;"><?= e($c['insurer_code']) ?></div>
              </td>
              <td>
                <strong style="font-size: 15px;"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong>
                <div class="dim mono" style="font-size: 11px;">MRN: <?= e($c['mrn']) ?></div>
              </td>
              <td>
                <div>Pol: <strong><?= e($c['policy_number'] ?: 'N/A') ?></strong></div>
                <div class="dim mono" style="font-size: 11px;">Auth: <?= e($c['preauth_code'] ?: 'N/A') ?></div>
              </td>
              <td><strong class="mono"><?= format_minor($c['claim_amount_minor'], tenant('currency')) ?></strong></td>
              <td>
                <strong class="mono" style="color: #10b981;">
                  <?= format_minor($c['approved_amount_minor'], tenant('currency')) ?>
                </strong>
              </td>
              <td>
                <span class="badge badge-<?= $c['status'] === 'approved' ? 'success' : ($c['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                  <?= strtoupper(e($c['status'])) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <?php if ($c['status'] === 'submitted'): ?>
                  <button type="button" class="btn btn-sm btn-ghost" onclick="openAdjudicateModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)">
                    <span>Adjudicate</span>
                  </button>
                <?php else: ?>
                  <span class="dim" style="font-size: 12px;">Finalized</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Submit New Claim -->
<div id="newClaimModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 520px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;">Submit Insurance Claim</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newClaimModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('billing.claims.submit') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Select Patient *</label>
          <input type="text" id="claim_patient_input" class="input" placeholder="Search patient name, MRN, phone, ID..." onfocus="searchClaimPatient(this.value)" oninput="searchClaimPatient(this.value)" autocomplete="off">
          <input type="hidden" id="claim_patient_id" name="patient_id" required>
          <div id="claim_search_results" style="margin-top: 6px; max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; background: var(--surface);"></div>
        </div>

        <div class="field full">
          <label>Insurance Scheme / Payer *</label>
          <select name="insurance_provider_id" class="input" required>
            <?php foreach ($insurances as $ins): ?>
              <option value="<?= $ins['id'] ?>"><?= e($ins['name']) ?> (<?= e($ins['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Member Policy / Card # *</label>
          <input type="text" name="policy_number" class="input" required placeholder="e.g. POL-889922">
        </div>

        <div class="field">
          <label>Pre-Authorization Code</label>
          <input type="text" name="preauthorization_code" class="input" placeholder="e.g. AUTH-4491">
        </div>

        <div class="field full">
          <label>Claim Amount (<?= tenant('currency') ?>) *</label>
          <input type="number" step="0.01" name="amount" class="input" required placeholder="3500.00">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newClaimModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Transmit Claim</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Adjudicate Claim -->
<div id="adjudicateModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 500px; padding: 26px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0;" id="adjTitle">Adjudicate Claim</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('adjudicateModal').classList.add('hidden')">&times;</button>
    </div>

    <form id="adjForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full">
          <label>Adjudication Outcome *</label>
          <select name="status" class="input" required>
            <option value="approved">Approve Claim</option>
            <option value="rejected">Reject / Deny Claim</option>
          </select>
        </div>

        <div class="field full">
          <label>Approved Amount (<?= tenant('currency') ?>) *</label>
          <input type="number" step="0.01" id="adj_approved_amount" name="approved_amount" class="input" required>
        </div>

        <div class="field full">
          <label>Payer Notes / Explanation</label>
          <input type="text" name="notes" class="input" placeholder="e.g. Paid in full per negotiated capitation rate">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 20px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('adjudicateModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Adjudication</button>
      </div>
    </form>
  </div>
</div>

<script>
let claimSearchTimeout;
function searchClaimPatient(q) {
  clearTimeout(claimSearchTimeout);
  const container = document.getElementById('claim_search_results');
  claimSearchTimeout = setTimeout(async () => {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || '/patients/search';
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const pts = await res.json();
      if (!pts.length) {
        container.innerHTML = '<div class="dim" style="font-size: 12px; padding: 10px; text-align: center;">No matching patients found.</div>';
        container.style.display = 'block';
        return;
      }
      container.innerHTML = pts.map(p => `
        <div style="padding: 8px 12px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="pickClaimPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.mrn}')">
          <div>
            <strong>${p.first_name} ${p.last_name}</strong>
            <div class="dim mono" style="font-size: 11px;">
              MRN: ${p.mrn} ${p.national_id ? ' • ID: ' + p.national_id : ''} ${p.age ? ' • ' + p.age + ' yrs' : ''} ${p.phone ? ' • ' + p.phone : ''}
            </div>
          </div>
          <span class="badge badge-brand">+ Pick</span>
        </div>
      `).join('');
      container.style.display = 'block';
    } catch (e) {}
  }, 100);
}

function pickClaimPatient(id, name, mrn) {
  document.getElementById('claim_patient_id').value = id;
  document.getElementById('claim_patient_input').value = name + ' (' + mrn + ')';
  document.getElementById('claim_search_results').style.display = 'none';
}

function openAdjudicateModal(c) {
  document.getElementById('adjTitle').textContent = 'Adjudicate Claim ' + c.claim_no;
  document.getElementById('adj_approved_amount').value = (c.claim_amount_minor / 100).toFixed(2);
  document.getElementById('adjForm').action = '/billing/claims/' + c.id + '/adjudicate';
  document.getElementById('adjudicateModal').classList.remove('hidden');
}
</script>