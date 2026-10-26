<?php $this->layout('layouts.app', ['title' => 'Register Sessions']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('pharmacy.index') ?>">Pharmacy</a> / Cash Sessions</div>
    <h1>Pharmacy Cash Register & Shift Sessions</h1>
    <p class="muted">Track opening floats, daily cash intake, mobile money totals, and register balancing.</p>
  </div>
</div>

<div class="grid grid-3" style="align-items: start; margin-bottom: 24px;">
  <!-- Active / Open Session Card -->
  <div class="card card-pad span-2">
    <?php if ($activeSession): ?>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
          <span class="badge badge-success" style="font-size: 13px;">ACTIVE SHIFT OPEN</span>
          <h2 style="margin-top: 8px;">Register Session #<?= $activeSession['id'] ?></h2>
          <div class="muted">Opened <?= date('d M Y, H:i', strtotime($activeSession['opened_at'])) ?> by <?= e(auth()->name) ?></div>
        </div>
      </div>

      <div class="grid grid-3" style="margin-bottom: 20px;">
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px;">
          <div class="dim" style="font-size: 12px;">Opening Float</div>
          <strong class="mono" style="font-size: 20px;"><?= money($activeSession['opening_float_minor']) ?></strong>
        </div>
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px;">
          <div class="dim" style="font-size: 12px;">Total Shift Sales</div>
          <strong class="mono" style="font-size: 20px; color: var(--brand);"><?= money($activeSession['total_sales_minor']) ?></strong>
        </div>
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px;">
          <div class="dim" style="font-size: 12px;">Cash in Drawer</div>
          <strong class="mono" style="font-size: 20px; color: var(--success);"><?= money($activeSession['opening_float_minor'] + $activeSession['cash_sales_minor']) ?></strong>
        </div>
      </div>

      <form method="POST" action="<?= route('pharmacy.sessions.close', ['id' => $activeSession['id']]) ?>" style="background: rgba(244, 63, 94, 0.05); border: 1px solid rgba(244, 63, 94, 0.2); border-radius: var(--radius); padding: 18px;">
        <?= csrf_field() ?>
        <h4 style="margin-bottom: 12px; color: var(--danger);">Close & Reconcile Shift</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="field">
            <label for="closing_cash">Physical Cash Count in Drawer (<?= e(tenant('currency')) ?>) *</label>
            <input type="number" step="any" min="0" id="closing_cash" name="closing_cash" class="input" required placeholder="0.00">
          </div>
          <div class="field">
            <label for="note">Handover / Shift Closing Note</label>
            <input type="text" id="note" name="note" class="input" placeholder="e.g. Handed over to night shift">
          </div>
        </div>
        <button type="submit" class="btn btn-danger btn-lg">
          <span>Close Shift & Calculate Variance</span>
        </button>
      </form>
    <?php else: ?>
      <!-- Open New Session Form -->
      <h3 style="margin-bottom: 10px;">Open New Cashier Shift</h3>
      <p class="muted" style="margin-bottom: 20px;">Start your cashier register shift by recording the opening drawer cash float.</p>

      <form method="POST" action="<?= route('pharmacy.sessions.open') ?>">
        <?= csrf_field() ?>
        <div class="form-grid" style="margin-bottom: 16px;">
          <div class="field">
            <label for="opening_float">Opening Cash Float (<?= e(tenant('currency')) ?>) *</label>
            <input type="number" step="any" min="0" id="opening_float" name="opening_float" class="input" required value="0.00">
          </div>
          <div class="field">
            <label for="note">Station / Register Note</label>
            <input type="text" id="note" name="note" class="input" placeholder="e.g. Counter 1 Morning Shift">
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Open Register Shift</span>
        </button>
      </form>
    <?php endif; ?>
  </div>

  <div class="card card-pad">
    <h3 style="margin-bottom: 12px;">Fast Action</h3>
    <a href="<?= route('pharmacy.pos') ?>" class="btn btn-amber btn-block btn-lg" style="margin-bottom: 12px;">
      <span>Open OTC Sales POS &rarr;</span>
    </a>
    <div class="dim" style="font-size: 12px;">
      Sales can also automatically link to the active cashier session for real-time reconciliation.
    </div>
  </div>
</div>

<!-- Past Sessions Table -->
<div class="card">
  <div class="card-head">
    <h3>Recent Cash Register Sessions</h3>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Session</th>
          <th>Cashier</th>
          <th>Opened</th>
          <th>Closed</th>
          <th>Opening Float</th>
          <th>Total Sales</th>
          <th>Variance</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sessions)): ?>
          <tr><td colspan="8" class="empty">No sessions recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td><strong class="mono">#<?= $s['id'] ?></strong></td>
              <td><?= e($s['user_name']) ?></td>
              <td><span class="dim"><?= date('d M, H:i', strtotime($s['opened_at'])) ?></span></td>
              <td><span class="dim"><?= $s['closed_at'] ? date('d M, H:i', strtotime($s['closed_at'])) : '—' ?></span></td>
              <td class="mono"><?= money($s['opening_float_minor']) ?></td>
              <td class="mono"><strong style="color: var(--brand);"><?= money($s['total_sales_minor']) ?></strong></td>
              <td class="mono">
                <?php if ($s['diff_minor'] !== null): ?>
                  <span style="color: <?= (int) $s['diff_minor'] < 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                    <?= money($s['diff_minor']) ?>
                  </span>
                <?php else: ?>
                  <span class="dim">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= $s['status'] === 'open' ? 'success' : 'muted' ?>">
                  <?= ucfirst($s['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
