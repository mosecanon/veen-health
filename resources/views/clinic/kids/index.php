<?php $this->layout('layouts.app', ['title' => 'Kids Clinic & Child Welfare (CWC)']) ?>

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
#topScrollWrap {
  overflow-x: auto;
  overflow-y: hidden;
  height: 14px;
  background: rgba(0, 0, 0, 0.03);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
#topScrollContent {
  height: 14px;
}
</style>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Clinical Services / Pediatric Care</div>
    <h1>👶 Kids Clinic & Child Welfare (CWC)</h1>
    <p class="muted">Longitudinal pediatric health monitoring: birth records, vitals progression, WHO growth indicators, and complete immunization registry.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-primary" onclick="document.getElementById('enrollChildModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>+ Enroll Child in Clinic</span>
    </button>
    <a href="<?= route('patients.create') ?>?is_child=1" class="btn btn-ghost">
      <span>Register New Child Patient</span>
    </a>
  </div>
</div>

<!-- Metrics & Growth Census Cards -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="card card-pad" style="border-left: 4px solid var(--brand);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Enrolled Children</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: var(--brand); margin-top: 4px;"><?= number_format($totalEnrolled) ?></div>
      </div>
      <div style="background: rgba(20, 184, 166, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">👶</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Active in Child Welfare Clinic</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #10b981;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Adequate Growth</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: #10b981; margin-top: 4px;">
          <?= max(0, $totalEnrolled - ($mamCount + $samCount)) ?>
        </div>
      </div>
      <div style="background: rgba(16, 185, 129, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📈</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">Normal MUAC (≥ 12.5 cm)</div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid <?= $samCount > 0 ? '#ef4444' : '#f59e0b' ?>;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Nutritional Risk (MAM/SAM)</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: <?= $samCount > 0 ? '#ef4444' : '#f59e0b' ?>; margin-top: 4px;">
          <?= $mamCount + $samCount ?>
          <?php if ($samCount > 0): ?>
            <span class="badge badge-danger" style="font-size: 11px; vertical-align: middle; margin-left: 4px;"><?= $samCount ?> SAM</span>
          <?php endif; ?>
        </div>
      </div>
      <div style="background: rgba(239, 68, 68, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">⚠️</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">MAM: <?= $mamCount ?> | Severe (SAM): <?= $samCount ?></div>
  </div>

  <div class="card card-pad" style="border-left: 4px solid #6366f1;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <div class="dim" style="font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Vaccines Due / Pending</div>
        <div class="mono" style="font-size: 28px; font-weight: 800; color: #6366f1; margin-top: 4px;"><?= number_format($pendingVaccines) ?></div>
      </div>
      <div style="background: rgba(99, 102, 241, 0.1); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">💉</div>
    </div>
    <div class="muted" style="font-size: 12px; margin-top: 8px;">WHO Immunization Schedule</div>
  </div>
</div>

<!-- Filters & Search Bar -->
<div class="card card-pad" style="margin-bottom: 20px;">
  <form method="GET" action="<?= route('clinic.kids.index') ?>" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 260px;">
      <input type="text" name="q" value="<?= e($q) ?>" class="input" placeholder="Search child by name, MRN, or parent contact...">
    </div>
    <div style="width: 220px;">
      <select name="nutrition" class="input">
        <option value="">-- All Nutritional States --</option>
        <option value="normal" <?= $nutritionFilter === 'normal' ? 'selected' : '' ?>>Normal (Healthy Growth)</option>
        <option value="mam" <?= $nutritionFilter === 'mam' ? 'selected' : '' ?>>MAM (Moderate Malnutrition)</option>
        <option value="sam" <?= $nutritionFilter === 'sam' ? 'selected' : '' ?>>SAM (Severe Malnutrition)</option>
        <option value="overweight" <?= $nutritionFilter === 'overweight' ? 'selected' : '' ?>>Overweight</option>
      </select>
    </div>
    <input type="hidden" name="per_page" value="<?= (int)($perPage ?? 20) ?>">
    <button type="submit" class="btn btn-primary">Filter</button>
    <?php if ($q || $nutritionFilter): ?>
      <a href="<?= route('clinic.kids.index') ?>?per_page=<?= (int)($perPage ?? 20) ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Children Roster Table -->
<div class="card" style="position: relative;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 16px 20px;">
    <div>
      <h3 style="margin: 0;">Child Welfare Registry (<?= number_format($total ?? count($children)) ?> Registered Children)</h3>
      <p class="muted" style="margin: 2px 0 0; font-size: 13px;">
        Showing <strong><?= min($total ?? count($children), max(1, (($page ?? 1) - 1) * ($perPage ?? 20) + 1)) ?></strong>–<strong><?= min($total ?? count($children), ($page ?? 1) * ($perPage ?? 20)) ?></strong> of <strong><?= number_format($total ?? count($children)) ?></strong> children (<?= $perPage ?? 20 ?> per page)
      </p>
    </div>

    <!-- Table Navigation Helper Buttons -->
    <div style="display: flex; gap: 8px; align-items: center; background: rgba(0,0,0,0.03); padding: 4px 8px; border-radius: 8px; border: 1px solid var(--border);">
      <span class="dim" style="font-size: 11.5px; font-weight: 600;">Scroll Table:</span>
      <button type="button" class="btn btn-sm btn-ghost" onclick="scrollKidsTable('start')" title="Scroll back to Child Name and MRN">
        ⬅️ Start (Name)
      </button>
      <button type="button" class="btn btn-sm btn-ghost" onclick="scrollKidsTable('end')" style="color: var(--brand); font-weight: 700;" title="Scroll immediately to Actions and Passport">
        ➡️ Jump to Actions & Passport
      </button>
    </div>
  </div>

  <!-- Synced Top Horizontal Scrollbar (Makes it effortless to scroll without reaching bottom) -->
  <div id="topScrollWrap">
    <div id="topScrollContent"></div>
  </div>

  <div class="table-wrap" id="kidsTableWrap">
    <table class="table" id="kidsTable" style="margin-bottom: 0;">
      <thead>
        <tr>
          <th style="min-width: 220px;">MRN & Child Name</th>
          <th style="min-width: 130px;">Sex & Age</th>
          <th style="min-width: 140px;">Birth History</th>
          <th style="min-width: 200px;">Latest Vitals (Weight / Ht / MUAC)</th>
          <th style="min-width: 150px;">Nutritional Status</th>
          <th style="min-width: 180px;">Immunization Progress</th>
          <th style="min-width: 110px;">Visits Count</th>
          <th class="sticky-actions-col" style="min-width: 190px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($children)): ?>
          <tr>
            <td colspan="8" class="empty" style="text-align: center; padding: 36px;">
              <div style="font-size: 32px; margin-bottom: 8px;">👶</div>
              <strong>No children records found.</strong>
              <p class="muted" style="margin: 4px 0 16px;">Enroll an existing child patient or register a new one to begin tracking their growth and vitals.</p>
              <button type="button" class="btn btn-primary" onclick="document.getElementById('enrollChildModal').classList.remove('hidden')">Enroll Child Now</button>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($children as $c): ?>
            <?php
              $ageFormatted = '—';
              if (!empty($c['dob'])) {
                $dob = new DateTime($c['dob']);
                $now = new DateTime();
                $diff = $now->diff($dob);
                if ($diff->y > 0) {
                  $ageFormatted = $diff->y . 'y ' . $diff->m . 'm';
                } else {
                  $ageFormatted = $diff->m . ' months (' . $diff->d . 'd)';
                }
              }
              $totalVac = ($c['vaccines_given'] + $c['vaccines_pending']) ?: 20;
              $pctVac = round(($c['vaccines_given'] / $totalVac) * 100);
            ?>
            <tr>
              <td>
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                  <div class="avatar" style="background: linear-gradient(135deg, #0284c7, #38bdf8); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; margin-top: 2px;">
                    <?= initials($c['first_name'] . ' ' . $c['last_name']) ?>
                  </div>
                  <div>
                    <a href="<?= route('clinic.kids.show', ['id' => $c['patient_uuid'] ?? $c['patient_id']]) ?>" style="color: var(--text); font-weight: 700; font-size: 14px; text-decoration: none;">
                      <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                    </a>
                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px; flex-wrap: wrap;">
                      <span class="mono dim" style="font-size: 11px;"><?= e($c['mrn']) ?></span>
                      <!-- Direct Quick Passport Link right under child name -->
                      <a href="<?= route('clinic.kids.card', ['id' => $c['patient_uuid'] ?? $c['patient_id']]) ?>" target="_blank" class="badge" style="background: rgba(2, 132, 199, 0.12); color: #0284c7; text-decoration: none; font-size: 10px; font-weight: 700; padding: 2px 6px;" title="Open Health Passport immediately without scrolling">
                        🖨️ Passport
                      </a>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span style="text-transform: capitalize; font-weight: 600;"><?= e($c['sex'] ?? '—') ?></span>
                <div class="dim" style="font-size: 11.5px;"><?= $ageFormatted ?></div>
              </td>
              <td>
                <?php if ($c['birth_weight_kg']): ?>
                  <span class="badge" style="background: rgba(2, 132, 199, 0.1); color: #0284c7; font-size: 11px; font-weight: 700;">BW: <?= $c['birth_weight_kg'] ?> kg</span>
                  <div class="dim" style="font-size: 11px; text-transform: uppercase; margin-top: 2px;"><?= e(str_replace('_', ' ', $c['delivery_mode'] ?? 'SVD')) ?></div>
                <?php else: ?>
                  <span class="dim" style="font-size: 11.5px;">Pending profile</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['last_weight_kg']): ?>
                  <strong class="mono" style="color: var(--brand); font-size: 13.5px;"><?= $c['last_weight_kg'] ?> kg</strong>
                  <?php if ($c['last_height_cm']): ?>
                    <span class="dim">/ <?= $c['last_height_cm'] ?> cm</span>
                  <?php endif; ?>
                  <?php if ($c['last_muac_cm']): ?>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">MUAC: <strong><?= $c['last_muac_cm'] ?> cm</strong></div>
                  <?php endif; ?>
                  <div class="dim" style="font-size: 10.5px;"><?= human_date($c['last_visit_date']) ?></div>
                <?php else: ?>
                  <span class="dim">No visits recorded</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['last_nutrition'] === 'sam'): ?>
                  <span class="badge badge-danger" style="font-weight: 700;">SAM (Severe)</span>
                <?php elseif ($c['last_nutrition'] === 'mam'): ?>
                  <span class="badge badge-warning" style="font-weight: 700;">MAM (Moderate)</span>
                <?php elseif ($c['last_nutrition'] === 'overweight'): ?>
                  <span class="badge" style="background: rgba(168, 85, 247, 0.15); color: #a855f7; font-weight: 700;">Overweight</span>
                <?php elseif ($c['last_nutrition'] === 'normal'): ?>
                  <span class="badge badge-success" style="font-weight: 700;">Normal Growth</span>
                <?php else: ?>
                  <span class="dim">—</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div style="flex: 1; min-width: 60px; height: 6px; background: rgba(0,0,0,0.1); border-radius: 3px; overflow: hidden;">
                    <div style="width: <?= $pctVac ?>%; height: 100%; background: #10b981; border-radius: 3px;"></div>
                  </div>
                  <span class="mono" style="font-size: 11.5px; color: #10b981; font-weight: 800;"><?= $c['vaccines_given'] ?></span>
                  <span class="dim" style="font-size: 10px;">/ <?= $totalVac ?></span>
                </div>
              </td>
              <td>
                <span class="badge badge-ghost mono" style="font-weight: 700;"><?= $c['total_visits'] ?> visits</span>
              </td>
              <!-- Pinned Sticky Actions Column on the right -->
              <td class="sticky-actions-col">
                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                  <a href="<?= route('clinic.kids.show', ['id' => $c['patient_uuid'] ?? $c['patient_id']]) ?>" class="btn btn-sm btn-primary" title="Open Complete Longitudinal Child Chart">
                    <span>Child Chart</span>
                  </a>
                  <a href="<?= route('clinic.kids.card', ['id' => $c['patient_uuid'] ?? $c['patient_id']]) ?>" class="btn btn-sm btn-ghost" target="_blank" title="Print Digital Child Health Passport">
                    <span>Passport</span>
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
        Showing <strong><?= min($total, (($page ?? 1) - 1) * ($perPage ?? 20) + 1) ?></strong> to <strong><?= min($total, ($page ?? 1) * ($perPage ?? 20)) ?></strong> of <strong><?= number_format($total) ?></strong> children (Page <?= $page ?? 1 ?> of <?= $lastPage ?? 1 ?>)
      </div>

      <div style="display: flex; gap: 6px; align-items: center;">
        <?php
          $queryString = '';
          if (!empty($q)) $queryString .= '&q=' . urlencode($q);
          if (!empty($nutritionFilter)) $queryString .= '&nutrition=' . urlencode($nutritionFilter);
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
          <a href="?page=1<?= !empty($q) ? '&q=' . urlencode($q) : '' ?><?= !empty($nutritionFilter) ? '&nutrition=' . urlencode($nutritionFilter) : '' ?>&per_page=<?= $opt ?>" 
             class="badge <?= ($perPage ?? 20) === $opt ? 'badge-primary' : 'badge-ghost' ?>" 
             style="font-size: 11px; padding: 3px 8px; text-decoration: none;">
            <?= $opt ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Enroll Child into Kids Clinic -->
<div id="enrollChildModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 10000;">
  <div class="card" style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 22px;">👶</span>
        <h3 style="margin: 0;">Enroll Child in Child Welfare Clinic</h3>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('enrollChildModal').classList.add('hidden')">✕</button>
    </div>

    <form method="POST" action="<?= route('clinic.kids.profile.store') ?>">
      <?= csrf_field() ?>
      
      <div class="form-grid">
        <div class="field full">
          <label style="font-weight: 700;">Select Child from Patients Registry *</label>
          <select name="patient_id" class="input" required>
            <option value="">-- Choose Child Patient --</option>
            <?php foreach ($unenrolledKids as $uk): ?>
              <option value="<?= $uk['uuid'] ?? $uk['id'] ?>">
                <?= e($uk['first_name'] . ' ' . $uk['last_name']) ?> (MRN: <?= e($uk['mrn']) ?> • Born: <?= $uk['dob'] ?: 'Unknown' ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <p class="muted" style="margin: 4px 0 0; font-size: 11.5px;">If the child is not registered yet, <a href="<?= route('patients.create') ?>?is_child=1" target="_blank" style="color: var(--brand);">register them here first</a>.</p>
        </div>

        <div class="field">
          <label>Birth Weight (kg)</label>
          <input type="number" step="0.01" name="birth_weight_kg" class="input" placeholder="e.g. 3.25">
        </div>

        <div class="field">
          <label>Birth Length (cm)</label>
          <input type="number" step="0.1" name="birth_length_cm" class="input" placeholder="e.g. 50.0">
        </div>

        <div class="field">
          <label>Head Circumference at Birth (cm)</label>
          <input type="number" step="0.1" name="birth_head_circ_cm" class="input" placeholder="e.g. 35.0">
        </div>

        <div class="field">
          <label>Gestational Age at Birth (weeks)</label>
          <input type="number" name="gestational_age_weeks" class="input" placeholder="e.g. 40" value="40">
        </div>

        <div class="field">
          <label>Mode of Delivery</label>
          <select name="delivery_mode" class="input">
            <option value="svd">Spontaneous Vertex Delivery (SVD)</option>
            <option value="c_section">Caesarean Section (C-Section)</option>
            <option value="assisted">Assisted (Vacuum / Forceps)</option>
            <option value="breech">Breech Delivery</option>
          </select>
        </div>

        <div class="field">
          <label>Feeding Method</label>
          <select name="feeding_method" class="input">
            <option value="exclusive_breastfeeding">Exclusive Breastfeeding (EBF)</option>
            <option value="mixed">Mixed Feeding</option>
            <option value="formula">Infant Formula</option>
            <option value="weaned">Complementary / Weaned</option>
          </select>
        </div>

        <div class="field">
          <label>APGAR Score (1 min / 5 min)</label>
          <div style="display: flex; gap: 8px;">
            <input type="number" name="apgar_score_1min" class="input" placeholder="1 min (e.g. 8)" min="0" max="10">
            <input type="number" name="apgar_score_5min" class="input" placeholder="5 min (e.g. 10)" min="0" max="10">
          </div>
        </div>

        <div class="field">
          <label>Child Blood Group</label>
          <select name="blood_group" class="input">
            <option value="">Unknown</option>
            <option value="O+">O Positive (O+)</option>
            <option value="O-">O Negative (O-)</option>
            <option value="A+">A Positive (A+)</option>
            <option value="A-">A Negative (A-)</option>
            <option value="B+">B Positive (B+)</option>
            <option value="B-">B Negative (B-)</option>
            <option value="AB+">AB Positive (AB+)</option>
            <option value="AB-">AB Negative (AB-)</option>
          </select>
        </div>

        <div class="field full">
          <label>Place of Birth / Facility</label>
          <input type="text" name="place_of_birth" class="input" placeholder="e.g. Nairobi Hospital Maternity Wing">
        </div>

        <div class="field full">
          <label>Birth Complications (if any)</label>
          <textarea name="birth_complications" class="input" rows="2" placeholder="e.g. Neonatal jaundice, meconium aspiration, delayed crying"></textarea>
        </div>

        <div class="field full">
          <label>Allergies & Special Healthcare Needs</label>
          <input type="text" name="special_needs_or_allergies" class="input" placeholder="e.g. Penicillin allergy, asthma, G6PD deficiency">
        </div>
      </div>

      <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('enrollChildModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Enroll & Initialize Vaccine Schedule</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var topWrap = document.getElementById('topScrollWrap');
  var topContent = document.getElementById('topScrollContent');
  var tableWrap = document.getElementById('kidsTableWrap');
  var table = document.getElementById('kidsTable');

  if (topWrap && topContent && tableWrap && table) {
    function syncScrollWidth() {
      topContent.style.width = table.scrollWidth + 'px';
      if (table.scrollWidth <= tableWrap.clientWidth + 5) {
        topWrap.style.display = 'none';
      } else {
        topWrap.style.display = 'block';
      }
    }

    syncScrollWidth();
    window.addEventListener('resize', syncScrollWidth);

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
});

function scrollKidsTable(direction) {
  var tableWrap = document.getElementById('kidsTableWrap');
  if (!tableWrap) return;
  if (direction === 'start') {
    tableWrap.scrollTo({ left: 0, behavior: 'smooth' });
  } else if (direction === 'end') {
    tableWrap.scrollTo({ left: tableWrap.scrollWidth, behavior: 'smooth' });
  }
}
</script>
