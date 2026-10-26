<?php $this->layout('layouts.app', ['title' => 'Patient Registry']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Patients</div>
    <h1>Master Patient Index (MPI)</h1>
    <p class="muted">Search and manage registered patients with per-tenant unique medical record numbers (MRN).</p>
  </div>
  <a href="<?= route('patients.create') ?>" class="btn btn-primary btn-lg">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    <span>Register New Patient</span>
  </a>
</div>

<div class="card">
  <div class="toolbar">
    <form method="GET" action="<?= route('patients.index') ?>" class="input-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" class="input" placeholder="Search by MRN, name, phone, national ID..." value="<?= e($q) ?>" onkeyup="filterTable(this, 'patientsTable')" autofocus>
    </form>
    <div class="spacer"></div>
    <button type="button" class="btn btn-sm btn-ghost" onclick="exportTableToCSV('patientsTable', 'patients_registry')" title="Export table data as CSV / Excel spreadsheet">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      <span>Export CSV</span>
    </button>
    <span class="muted" style="font-size: 13px;"><?= number_format($total) ?> total patients</span>
  </div>

  <div class="table-wrap">
    <table class="table" id="patientsTable">
      <thead>
        <tr>
          <th>MRN</th>
          <th>Patient Name</th>
          <th>Gender / Age</th>
          <th>Phone</th>
          <th>National ID</th>
          <th>Registered</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td colspan="7" class="empty">No patients found.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <tr>
              <td>
                <a href="<?= route('patients.show', ['id' => $p['uuid'] ?? $p['id']]) ?>">
                  <strong class="mono" style="color: var(--brand); font-size: 14px;"><?= e($p['mrn']) ?></strong>
                </a>
              </td>
              <td>
                <div class="cell-user">
                  <div class="avatar" style="background: linear-gradient(135deg, var(--brand), var(--brand-2));"><?= initials($p['first_name'] . ' ' . $p['last_name']) ?></div>
                  <div>
                    <a href="<?= route('patients.show', ['id' => $p['uuid'] ?? $p['id']]) ?>">
                      <strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                    </a>
                    <?php if (!empty($p['is_child'])): ?>
                      <span class="badge badge-warning" style="font-size: 10.5px; padding: 1px 6px;">👶 Child</span>
                    <?php endif; ?>
                    <?php if ($p['allergies']): ?>
                      <span class="badge badge-danger" style="font-size: 10.5px; padding: 1px 6px;">Allergy: <?= e($p['allergies']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <span style="text-transform: capitalize;"><?= e($p['sex']) ?></span>
                <?php if ($age = age_from_dob($p['dob'])): ?>
                  <span class="dim">(<?= $age ?> yrs)</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="mono"><?= e($p['phone'] ?: '—') ?></span>
              </td>
              <td>
                <span class="muted"><?= e($p['national_id'] ?: '—') ?></span>
              </td>
              <td>
                <span class="dim"><?= human_date($p['created_at']) ?></span>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= route('patients.show', ['id' => $p['uuid'] ?? $p['id']]) ?>" class="btn btn-sm btn-ghost">View</a>
                  <a href="<?= route('consultations.start.get') . '?patient_id=' . urlencode($p['uuid'] ?? $p['id']) ?>" class="btn btn-sm btn-primary" title="Start Consultation">
                    <span>🩺 Consult</span>
                  </a>
                  <a href="<?= route('patients.edit', ['id' => $p['uuid'] ?? $p['id']]) ?>" class="btn btn-sm btn-ghost" title="Edit demographics">Edit</a>
                  <a href="<?= route('queue.kiosk', ['patient_id' => $p['uuid'] ?? $p['id']]) ?>" class="btn btn-sm" title="Issue ticket for patient">
                    <span>Ticket</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($lastPage > 1): ?>
  <div class="pagination">
    <div class="pagination-info">Showing page <?= $page ?> of <?= $lastPage ?></div>
    <div class="pagination-links">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($q) ?>">&larr; Prev</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="active"><?= $i ?></span>
        <?php elseif ($i == 1 || $i == $lastPage || abs($i - $page) <= 2): ?>
          <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $lastPage): ?>
        <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($q) ?>">Next &rarr;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
