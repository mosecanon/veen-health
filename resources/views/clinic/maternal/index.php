<?php $this->layout('layouts.app', ['title' => $title ?? 'Clinic for Pregnant Women (ANC)']) ?>

<style>
/* Sticky Action Column pinned to the right edge - 100% Opaque (Never Transparent) */
.sticky-actions-col {
  position: sticky;
  right: 0;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  box-shadow: -8px 0 16px rgba(0, 0, 0, 0.25);
  z-index: 10;
  text-align: right;
  white-space: nowrap;
}
th.sticky-actions-col {
  z-index: 11;
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
}
.table tbody tr:hover .sticky-actions-col {
  background: #1f2937 !important;
  background-color: #1f2937 !important;
  box-shadow: -8px 0 18px rgba(0, 0, 0, 0.35);
}
[data-theme="light"] .sticky-actions-col,
[data-theme="light"] th.sticky-actions-col {
  background: #ffffff !important;
  background-color: #ffffff !important;
  box-shadow: -8px 0 16px rgba(15, 23, 42, 0.08);
}
[data-theme="light"] .table tbody tr:hover .sticky-actions-col {
  background: #f1f5f9 !important;
  background-color: #f1f5f9 !important;
  box-shadow: -8px 0 18px rgba(15, 23, 42, 0.12);
}

/* Synced Top Horizontal Scrollbar */
#topScrollWrapMaternal {
  overflow-x: auto;
  overflow-y: hidden;
  height: 14px;
  background: rgba(0, 0, 0, 0.03);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
#topScrollContentMaternal {
  height: 14px;
}

/* Maternal Clinic 100% Opaque Modal System (Zero bleed-through) */
.maternal-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow-y: auto;
}

.maternal-modal-content {
  background: var(--surface-solid, #111827) !important;
  background-color: var(--surface-solid, #111827) !important;
  color: var(--text, #f9fafb);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
  border-radius: 16px;
  max-width: 660px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px;
  box-shadow: 0 25px 65px -15px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.08);
  position: relative;
  isolation: isolate;
}

[data-theme="light"] .maternal-modal-content {
  background: #ffffff !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  border-color: rgba(15, 23, 42, 0.15);
  box-shadow: 0 25px 65px -15px rgba(15, 23, 42, 0.35);
}

.maternal-modal-section {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.03);
}

[data-theme="light"] .maternal-modal-section {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Maternal Health (ANC)</div>
    <h1>🤰 Clinic for Pregnant Women (Antenatal Care - ANC)</h1>
    <p class="muted">Longitudinal maternal health monitoring from 1st trimester to delivery, pre-eclampsia screening, and birth preparedness.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="openEnrollMaternalModal()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Enroll Pregnant Mother</span>
    </button>
    <a href="<?= route('patients.create') ?>" class="btn btn-ghost">
      <span>Register New Female Patient</span>
    </a>
  </div>
</div>

<!-- Stats Cards Grid -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="card" style="padding: 16px; border-left: 4px solid var(--brand);">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Active Pregnancies</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px;"><?= number_format($activeCount) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;">Enrolled in ANC</div>
  </div>

  <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">1st Trimester</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px; color: #3b82f6;"><?= number_format($trimesterCounts['first']) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;">1 to 13 weeks</div>
  </div>

  <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">2nd Trimester</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px; color: #f59e0b;"><?= number_format($trimesterCounts['second']) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;">14 to 27 weeks</div>
  </div>

  <div class="card" style="padding: 16px; border-left: 4px solid #10b981;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">3rd Trimester</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px; color: #10b981;"><?= number_format($trimesterCounts['third']) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;">28 to 40+ weeks</div>
  </div>

  <div class="card" style="padding: 16px; border-left: 4px solid #ef4444;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #ef4444;">High-Risk Pregnancies</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px; color: #ef4444;"><?= number_format($highRiskCount) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;">Requiring Close Care</div>
  </div>

  <div class="card" style="padding: 16px; border-left: 4px solid #8b5cf6;">
    <div class="muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Delivered Mothers</div>
    <div style="font-size: 26px; font-weight: 800; margin-top: 4px; color: #8b5cf6;"><?= number_format($deliveredCount) ?></div>
    <div class="muted" style="font-size: 12px; margin-top: 2px;"><?= number_format($totalVisitsCount) ?> Total Visits</div>
  </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="card" style="padding: 16px; margin-bottom: 20px;">
  <form method="GET" action="<?= route('clinic.maternal.index') ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
      <a href="<?= route('clinic.maternal.index', ['filter' => 'active', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === 'active' ? 'btn-primary' : 'btn-ghost' ?>">All Active</a>
      <a href="<?= route('clinic.maternal.index', ['filter' => '1st', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === '1st' ? 'btn-primary' : 'btn-ghost' ?>">1st Trimester (<?= $trimesterCounts['first'] ?>)</a>
      <a href="<?= route('clinic.maternal.index', ['filter' => '2nd', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === '2nd' ? 'btn-primary' : 'btn-ghost' ?>">2nd Trimester (<?= $trimesterCounts['second'] ?>)</a>
      <a href="<?= route('clinic.maternal.index', ['filter' => '3rd', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === '3rd' ? 'btn-primary' : 'btn-ghost' ?>">3rd Trimester (<?= $trimesterCounts['third'] ?>)</a>
      <a href="<?= route('clinic.maternal.index', ['filter' => 'high_risk', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === 'high_risk' ? 'btn-danger' : 'btn-ghost' ?>">⚠️ High Risk (<?= $highRiskCount ?>)</a>
      <a href="<?= route('clinic.maternal.index', ['filter' => 'delivered', 'q' => $q, 'per_page' => $perPage ?? 20]) ?>" class="btn btn-sm <?= $filter === 'delivered' ? 'btn-primary' : 'btn-ghost' ?>">Delivered</a>
    </div>

    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
      <input type="hidden" name="filter" value="<?= e($filter) ?>" />
      <input type="hidden" name="per_page" value="<?= (int)($perPage ?? 20) ?>" />
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search mother name, MRN, ANC #..." class="input" style="min-width: 260px;" />
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($q !== '' || $filter !== 'active'): ?>
        <a href="<?= route('clinic.maternal.index', ['per_page' => $perPage ?? 20]) ?>" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Pregnant Women Roster Table Card -->
<div class="card" style="position: relative;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border);">
    <div>
      <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Antenatal Care Registry & Longitudinal Records (<?= number_format($total ?? count($profiles)) ?> Registered Mothers)</h3>
      <p class="muted" style="margin: 4px 0 0; font-size: 13px;">
        Showing <strong><?= min($total ?? count($profiles), max(1, (($page ?? 1) - 1) * ($perPage ?? 20) + 1)) ?></strong>–<strong><?= min($total ?? count($profiles), ($page ?? 1) * ($perPage ?? 20)) ?></strong> of <strong><?= number_format($total ?? count($profiles)) ?></strong> mothers (<?= $perPage ?? 20 ?> per page)
      </p>
    </div>

    <!-- Table Navigation Helper Buttons (Jump to Actions immediately) -->
    <div style="display: flex; gap: 8px; align-items: center; background: rgba(0,0,0,0.03); padding: 4px 8px; border-radius: 8px; border: 1px solid var(--border);">
      <span class="dim" style="font-size: 11.5px; font-weight: 600;">Scroll Table:</span>
      <button type="button" class="btn btn-sm btn-ghost" onclick="scrollMaternalTable('start')" title="Scroll back to Mother Details">
        ⬅️ Start (Mother)
      </button>
      <button type="button" class="btn btn-sm btn-ghost" onclick="scrollMaternalTable('end')" style="color: var(--brand); font-weight: 700;" title="Scroll immediately to Actions and Booklet">
        ➡️ Jump to Actions & Booklet
      </button>
    </div>
  </div>

  <!-- Synced Top Horizontal Scrollbar (Effortless scrolling without reaching bottom) -->
  <div id="topScrollWrapMaternal">
    <div id="topScrollContentMaternal"></div>
  </div>

  <div class="table-wrap" id="maternalTableWrap" style="overflow-x: auto;">
    <table class="table" id="maternalTable" style="width: 100%; margin-bottom: 0;">
      <thead>
        <tr>
          <th style="min-width: 250px;">Mother Details</th>
          <th style="min-width: 140px;">ANC Number</th>
          <th style="min-width: 170px;">LNMP & EDD</th>
          <th style="min-width: 150px;">Gestational Age</th>
          <th style="min-width: 140px;">Trimester</th>
          <th style="min-width: 150px;">Obstetric History</th>
          <th style="min-width: 190px;">Latest Vitals (BP / FHR)</th>
          <th style="min-width: 130px;">Risk Status</th>
          <th style="min-width: 100px;">Visits</th>
          <th class="sticky-actions-col" style="min-width: 190px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($profiles)): ?>
          <tr>
            <td colspan="10" style="text-align: center; padding: 40px;" class="muted">
              <div style="font-size: 32px; margin-bottom: 8px;">🤰</div>
              <div style="font-weight: 600;">No maternal records found</div>
              <div style="font-size: 12px; margin: 4px 0 16px;">Enroll an eligible pregnant mother to start tracking antenatal visits from 1st trimester.</div>
              <button type="button" class="btn btn-primary" onclick="openEnrollMaternalModal()">+ Enroll Pregnant Mother</button>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($profiles as $p): ?>
            <tr>
              <td>
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;">
                  <div>
                    <div style="font-weight: 700; font-size: 14px;">
                      <a href="<?= route('clinic.maternal.show', ['id' => $p['uuid'] ?? $p['id']]) ?>" style="color: var(--text);">
                        <?= e($p['first_name'] . ' ' . $p['last_name']) ?>
                      </a>
                    </div>
                    <div class="muted" style="font-size: 11px;">
                      MRN: <?= e($p['mrn']) ?> &bull; <?= e($p['phone'] ?: 'No Phone') ?>
                    </div>
                  </div>
                  <!-- Quick 1-click links directly in Column 1 -->
                  <div style="display: flex; gap: 4px;">
                    <a href="<?= route('clinic.maternal.show', ['id' => $p['uuid'] ?? $p['id']]) ?>" class="badge badge-brand" style="font-size: 10.5px; text-decoration: none;" title="Open ANC Chart">
                      Chart
                    </a>
                    <a href="<?= route('clinic.maternal.card', ['id' => $p['uuid'] ?? $p['id']]) ?>" target="_blank" class="badge badge-ghost" style="font-size: 10.5px; text-decoration: none;" title="Print ANC Booklet">
                      🖨️
                    </a>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge" style="background: rgba(2, 132, 199, 0.1); color: #0284c7; font-weight: 700; font-family: monospace;">
                  <?= e($p['anc_number']) ?>
                </span>
              </td>
              <td>
                <div style="font-size: 12px;"><strong>EDD:</strong> <?= date('d M Y', strtotime($p['edd'])) ?></div>
                <div class="muted" style="font-size: 11px;">
                  LNMP: <?= date('d M Y', strtotime($p['lnmp'])) ?>
                  <?php if ($p['status'] === 'active'): ?>
                    &bull; <span style="font-weight: 600; color: <?= $p['days_to_edd'] < 14 ? '#ef4444' : 'inherit' ?>;"><?= $p['days_to_edd'] > 0 ? "in {$p['days_to_edd']}d" : 'Due now' ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 700; font-size: 13px;"><?= e($p['gestational_age']) ?></div>
                <div class="muted" style="font-size: 11px;"><?= $p['gestational_weeks'] ?> completed weeks</div>
              </td>
              <td>
                <?php
                  $trimBadge = match($p['current_trimester']) {
                    'first'  => '<span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb; font-weight: 700;">1st Trimester</span>',
                    'second' => '<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-weight: 700;">2nd Trimester</span>',
                    default  => '<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #059669; font-weight: 700;">3rd Trimester</span>',
                  };
                  echo $trimBadge;
                ?>
              </td>
              <td>
                <span style="font-size: 12px; font-weight: 600;">G<?= $p['gravida'] ?> P<?= $p['parity'] ?></span>
                <div class="muted" style="font-size: 11px;">
                  Living: <?= $p['living_children'] ?>
                  <?php if ($p['c_sections'] > 0): ?> &bull; C/S: <?= $p['c_sections'] ?><?php endif; ?>
                  <?php if ($p['miscarriages'] > 0): ?> &bull; Mis: <?= $p['miscarriages'] ?><?php endif; ?>
                </div>
              </td>
              <td>
                <?php if ($p['last_visit_date']): ?>
                  <?php
                    $isPreEclampsia = ($p['last_systolic'] >= 140 || $p['last_diastolic'] >= 90);
                  ?>
                  <div style="font-size: 12px; font-weight: 600;">
                    <span style="color: <?= $isPreEclampsia ? '#ef4444; font-weight: 800;' : 'inherit' ?>">
                      BP: <?= $p['last_systolic'] ?>/<?= $p['last_diastolic'] ?>
                    </span>
                    <?php if ($isPreEclampsia): ?>
                      <span class="badge badge-danger" style="font-size: 9px; padding: 1px 4px;">HTN</span>
                    <?php endif; ?>
                  </div>
                  <div class="muted" style="font-size: 11px;">
                    <?= $p['last_fundal_height'] ? $p['last_fundal_height'] . 'cm' : '-' ?> &bull;
                    <?= $p['last_fhr'] ? $p['last_fhr'] . ' bpm' : '-' ?> &bull;
                    <?= date('d/m', strtotime($p['last_visit_date'])) ?>
                  </div>
                <?php else: ?>
                  <span class="muted" style="font-size: 11px;">No visits recorded</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['high_risk_pregnancy']): ?>
                  <span class="badge badge-danger" title="<?= e($p['risk_factors']) ?>">⚠️ High Risk</span>
                <?php else: ?>
                  <span class="badge badge-success">Standard</span>
                <?php endif; ?>
                <?php if ($p['status'] === 'delivered'): ?>
                  <div style="margin-top: 4px;"><span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;">Delivered</span></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge" style="background: rgba(0,0,0,0.06); font-weight: 700;">
                  <?= $p['total_anc_visits'] ?>
                </span>
              </td>
              <td class="sticky-actions-col">
                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                  <a href="<?= route('clinic.maternal.show', ['id' => $p['uuid'] ?? $p['id']]) ?>" class="btn btn-sm btn-primary" title="Open Complete Longitudinal ANC Chart">
                    <span>View Chart</span>
                  </a>
                  <a href="<?= route('clinic.maternal.card', ['id' => $p['uuid'] ?? $p['id']]) ?>" class="btn btn-sm btn-ghost" target="_blank" title="Print Maternal Antenatal Health Booklet">
                    <span>🖨️ Booklet</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls (At least 20 records per page) -->
  <?php if (($lastPage ?? 1) > 1 || ($total ?? 0) > 20): ?>
    <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;">
      <div class="pagination-info" style="font-size: 12.5px; color: var(--text-muted); padding: 0;">
        Showing <strong><?= min($total, (($page ?? 1) - 1) * ($perPage ?? 20) + 1) ?></strong> to <strong><?= min($total, ($page ?? 1) * ($perPage ?? 20)) ?></strong> of <strong><?= number_format($total) ?></strong> mothers (Page <?= $page ?? 1 ?> of <?= $lastPage ?? 1 ?>)
      </div>

      <div style="display: flex; gap: 6px; align-items: center;">
        <?php
          $queryString = '';
          if (!empty($q)) $queryString .= '&q=' . urlencode($q);
          if (!empty($filter) && $filter !== 'active') $queryString .= '&filter=' . urlencode($filter);
          if (($perPage ?? 20) !== 20) $queryString .= '&per_page=' . (int)$perPage;
        ?>

        <?php if (($page ?? 1) > 1): ?>
          <a href="?page=<?= ($page - 1) . $queryString ?>" class="btn btn-sm btn-ghost">&larr; Prev</a>
        <?php else: ?>
          <span class="btn btn-sm btn-ghost disabled" style="opacity: 0.4; cursor: not-allowed;">&larr; Prev</span>
        <?php endif; ?>

        <div class="pagination-links" style="display: flex; gap: 4px;">
          <?php for ($i = 1; $i <= ($lastPage ?? 1); $i++): ?>
            <?php if ($i === (int)($page ?? 1)): ?>
              <span class="btn btn-sm btn-primary" style="min-width: 32px; padding: 4px 8px; font-weight: 700;"><?= $i ?></span>
            <?php elseif ($i === 1 || $i === (int)$lastPage || abs($i - (int)($page ?? 1)) <= 2): ?>
              <a href="?page=<?= $i . $queryString ?>" class="btn btn-sm btn-ghost" style="min-width: 32px; padding: 4px 8px;"><?= $i ?></a>
            <?php elseif ($i === 2 || $i === (int)$lastPage - 1): ?>
              <span class="dim" style="padding: 4px 4px;">…</span>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <?php if (($page ?? 1) < ($lastPage ?? 1)): ?>
          <a href="?page=<?= ($page + 1) . $queryString ?>" class="btn btn-sm btn-ghost">Next &rarr;</a>
        <?php else: ?>
          <span class="btn btn-sm btn-ghost disabled" style="opacity: 0.4; cursor: not-allowed;">Next &rarr;</span>
        <?php endif; ?>
      </div>

      <!-- Quick Per-Page Selector -->
      <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted);">
        <span>Per page:</span>
        <?php foreach ([20, 50, 100] as $opt): ?>
          <a href="?page=1<?= !empty($q) ? '&q=' . urlencode($q) : '' ?><?= !empty($filter) && $filter !== 'active' ? '&filter=' . urlencode($filter) : '' ?>&per_page=<?= $opt ?>" 
             class="badge <?= ($perPage ?? 20) === $opt ? 'badge-primary' : 'badge-ghost' ?>" 
             style="font-size: 11px; padding: 3px 8px; text-decoration: none;">
            <?= $opt ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- 100% Opaque Enrollment Modal -->
<div id="enrollMaternalModal" class="maternal-modal-backdrop" onclick="if(event.target===this) closeEnrollMaternalModal()">
  <div class="maternal-modal-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 26px;">🤰</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text);">Enroll Pregnant Mother</h3>
          <p class="muted" style="margin: 2px 0 0; font-size: 12px;">New Antenatal Care (ANC) Episode & Baseline Trimester Initialization</p>
        </div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeEnrollMaternalModal()" style="font-size: 18px; padding: 4px 10px; line-height: 1;">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.maternal.profile.store') ?>">
      <?= csrf_field() ?>

      <!-- Patient Selection -->
      <div class="form-group maternal-modal-section">
        <label class="label" style="font-weight: 700; font-size: 12.5px; color: var(--text);">Select Female Patient from Registry *</label>
        
        <!-- Instant search helper to filter female patient dropdown -->
        <input type="text" id="candidateSearchInput" class="input" placeholder="🔍 Type name, MRN, or phone to quickly filter candidate list..." style="margin: 6px 0 8px; font-size: 12px; background: rgba(0,0,0,0.04);" oninput="filterCandidateDropdown(this.value)">

        <select name="patient_id" id="enroll_patient_select" class="input" required style="width: 100%;">
          <option value="">-- Choose female patient (<?= count($candidates) ?> available) --</option>
          <?php foreach ($candidates as $cand): ?>
            <?php
              $isSelected = (!empty($targetPatientId) && ($targetPatientId == ($cand['uuid'] ?? '') || $targetPatientId == ($cand['id'] ?? '')));
            ?>
            <option value="<?= $cand['uuid'] ?? $cand['id'] ?>" <?= $isSelected ? 'selected' : '' ?> data-search="<?= strtolower(e($cand['first_name'] . ' ' . $cand['last_name'] . ' ' . $cand['mrn'] . ' ' . ($cand['phone'] ?? ''))) ?>">
              <?= e($cand['first_name'] . ' ' . $cand['last_name']) ?> (MRN: <?= e($cand['mrn']) ?> &bull; Phone: <?= e($cand['phone'] ?: 'N/A') ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <div class="muted" style="font-size: 11.5px; margin-top: 6px;">
          If the mother is not listed, <a href="<?= route('patients.create') ?>" target="_blank" style="color: var(--brand); font-weight: 600;">register her under Patients first</a>.
        </div>
      </div>

      <!-- Dates: LNMP and EDD -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          1. Gestational Milestones & Due Date
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="label" style="font-weight: 700; font-size: 11.5px;">Last Normal Menstrual Period (LNMP) *</label>
            <input type="date" name="lnmp" id="modal_lnmp" class="input" required max="<?= date('Y-m-d') ?>" onchange="calculateEddFromLnmp(this.value)" />
            <div class="muted" style="font-size: 11px; margin-top: 3px;">Day 1 of 1st trimester</div>
          </div>
          <div class="form-group">
            <label class="label" style="font-weight: 700; font-size: 11.5px;">Expected Date of Delivery (EDD)</label>
            <input type="date" name="edd" id="modal_edd" class="input" />
            <div class="muted" style="font-size: 11px; margin-top: 3px;">Naegele's rule (+280 days)</div>
          </div>
        </div>

        <!-- Dynamic Gestational Age Preview -->
        <div id="gestationalPreview" style="display: none; margin-top: 12px; padding: 10px 14px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border-left: 3px solid #3b82f6; font-size: 12px;">
          <strong>Calculated Status:</strong> <span id="previewWeeksText" style="font-weight: 700; color: var(--brand);"></span> &bull; <span id="previewTrimesterText" style="font-weight: 700;"></span>
        </div>
      </div>

      <!-- Obstetric History Grid -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          2. Obstetric History (Gravida / Parity)
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Gravida (Total Pregnancies)</label>
            <input type="number" name="gravida" min="1" value="1" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Parity (Past Deliveries)</label>
            <input type="number" name="parity" min="0" value="0" class="input" required />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Living Children</label>
            <input type="number" name="living_children" min="0" value="0" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Miscarriages</label>
            <input type="number" name="miscarriages" min="0" value="0" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Stillbirths</label>
            <input type="number" name="stillbirths" min="0" value="0" class="input" />
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Previous C-Sections</label>
            <input type="number" name="c_sections" min="0" value="0" class="input" />
          </div>
        </div>
      </div>

      <!-- Baseline Lab & Physicals -->
      <div class="maternal-modal-section">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; color: var(--brand);">
          3. Baseline Clinical & Lab Parameters
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Blood Group & Rhesus Factor</label>
            <div style="display: flex; gap: 8px;">
              <select name="blood_group" class="input" style="flex: 1;">
                <option value="">Blood Group</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
              </select>
              <select name="rhesus_factor" class="input" style="flex: 1;">
                <option value="">Rhesus</option>
                <option value="positive">Rh Positive (+)</option>
                <option value="negative">Rh Negative (-)</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Baseline Haemoglobin (Hb g/dL)</label>
            <input type="number" step="0.1" name="hb_baseline" placeholder="e.g. 12.4" class="input" />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
          <div class="form-group">
            <label class="label" style="font-size: 11px;">HIV Status</label>
            <select name="hiv_status" class="input">
              <option value="unknown">Unknown</option>
              <option value="negative">Non-Reactive (Negative)</option>
              <option value="positive">Reactive (Positive)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Syphilis VDRL</label>
            <select name="syphilis_vdrl" class="input">
              <option value="pending">Pending</option>
              <option value="non_reactive">Non-Reactive</option>
              <option value="reactive">Reactive</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label" style="font-size: 11px;">Hep B (HBsAg)</label>
            <select name="hep_b_surface_ag" class="input">
              <option value="pending">Pending</option>
              <option value="negative">Negative</option>
              <option value="positive">Positive</option>
            </select>
          </div>
        </div>
      </div>

      <!-- High Risk Pregnancy Screening -->
      <div style="margin-bottom: 20px; padding: 14px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 10px;">
        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; cursor: pointer; color: #dc2626; font-size: 13px;">
          <input type="checkbox" name="high_risk_pregnancy" value="1" id="modal_high_risk_cb" onchange="toggleRiskFactorNotes(this.checked)" />
          <span>Flag as High-Risk Pregnancy (e.g. Previous C/S, Chronic HTN, Rh-, Grand Multipara)</span>
        </label>
        <div id="riskFactorNotesWrapper" style="margin-top: 10px; display: none;">
          <label class="label" style="font-size: 11px; color: var(--text);">Identified Risk Factors / Obstetric Precautions:</label>
          <input type="text" name="risk_factors" class="input" placeholder="e.g. 2 previous scars, severe anemia, gestational diabetes..." style="width: 100%; margin-top: 4px;" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
        <button type="button" class="btn btn-ghost" onclick="closeEnrollMaternalModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Enroll Mother & Open Chart</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var topWrap = document.getElementById('topScrollWrapMaternal');
  var topContent = document.getElementById('topScrollContentMaternal');
  var tableWrap = document.getElementById('maternalTableWrap');
  var table = document.getElementById('maternalTable');

  if (topWrap && topContent && tableWrap && table) {
    function syncMaternalScrollWidth() {
      topContent.style.width = table.scrollWidth + 'px';
      if (table.scrollWidth <= tableWrap.clientWidth + 5) {
        topWrap.style.display = 'none';
      } else {
        topWrap.style.display = 'block';
      }
    }

    syncMaternalScrollWidth();
    window.addEventListener('resize', syncMaternalScrollWidth);

    var isSyncingTop = false;
    var isSyncingTable = false;

    topWrap.addEventListener('scroll', function () {
      if (!isSyncingTop) {
        isSyncingTable = true;
        tableWrap.scrollLeft = topWrap.scrollLeft;
      }
      isSyncingTop = false;
    });

    tableWrap.addEventListener('scroll', function () {
      if (!isSyncingTable) {
        isSyncingTop = true;
        topWrap.scrollLeft = tableWrap.scrollLeft;
      }
      isSyncingTable = false;
    });
  }

  // Keyboard shortcut to close modal
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeEnrollMaternalModal();
    }
  });
});

function scrollMaternalTable(direction) {
  var tableWrap = document.getElementById('maternalTableWrap');
  if (!tableWrap) return;
  if (direction === 'start') {
    tableWrap.scrollTo({ left: 0, behavior: 'smooth' });
  } else if (direction === 'end') {
    tableWrap.scrollTo({ left: tableWrap.scrollWidth, behavior: 'smooth' });
  }
}

function openEnrollMaternalModal() {
  var m = document.getElementById('enrollMaternalModal');
  if (m) {
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeEnrollMaternalModal() {
  var m = document.getElementById('enrollMaternalModal');
  if (m) {
    m.style.display = 'none';
    document.body.style.overflow = '';
  }
}

function toggleRiskFactorNotes(checked) {
  var el = document.getElementById('riskFactorNotesWrapper');
  if (el) el.style.display = checked ? 'block' : 'none';
}

function calculateEddFromLnmp(lnmpVal) {
  if (!lnmpVal) return;
  var d = new Date(lnmpVal);
  if (isNaN(d.getTime())) return;

  // Naegele's rule: +280 days
  var eddDate = new Date(d.getTime() + (280 * 86400000));
  var yyyy = eddDate.getFullYear();
  var mm = String(eddDate.getMonth() + 1).padStart(2, '0');
  var dd = String(eddDate.getDate()).padStart(2, '0');
  var eddEl = document.getElementById('modal_edd');
  if (eddEl) eddEl.value = yyyy + '-' + mm + '-' + dd;

  // Calculate current gestational weeks
  var now = new Date();
  var diffDays = Math.floor((now.getTime() - d.getTime()) / 86400000);
  if (diffDays >= 0) {
    var weeks = Math.floor(diffDays / 7);
    var days = diffDays % 7;
    var trim = '1st Trimester (Early)';
    if (weeks >= 14 && weeks <= 27) trim = '2nd Trimester';
    else if (weeks >= 28) trim = '3rd Trimester';

    var wEl = document.getElementById('previewWeeksText');
    var tEl = document.getElementById('previewTrimesterText');
    var pEl = document.getElementById('gestationalPreview');
    if (wEl) wEl.textContent = weeks + ' weeks ' + days + ' days';
    if (tEl) tEl.textContent = trim;
    if (pEl) pEl.style.display = 'block';
  }
}
function filterCandidateDropdown(query) {
  var q = (query || '').toLowerCase().trim();
  var sel = document.getElementById('enroll_patient_select');
  if (!sel) return;
  var options = sel.querySelectorAll('option');
  options.forEach(function (opt) {
    if (!opt.value) return; // Keep default prompt option
    var haystack = (opt.getAttribute('data-search') || opt.textContent).toLowerCase();
    if (!q || haystack.indexOf(q) !== -1) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
    }
  });
}

// Auto-open modal if target patient was provided
<?php if (!empty($targetPatientId)): ?>
document.addEventListener('DOMContentLoaded', function () {
  openEnrollMaternalModal();
});
<?php endif; ?>
</script>