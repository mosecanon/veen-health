<?php $this->layout('layouts.app', ['title' => 'Hospital Overview']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Hospital</a> / Dashboard</div>
    <h1>Welcome, <?= e(auth()->name) ?></h1>
    <?php
      $currBranch = function_exists('current_branch') ? current_branch() : null;
      $branchDisplay = $currBranch ? $currBranch['name'] : 'Consolidated Overview';
    ?>
    <p class="muted"><?= e(tenant('name')) ?> • Branch: <?= e($branchDisplay) ?> • <?= date('l, d F Y') ?></p>
  </div>
  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="<?= route('pharmacy.pos') ?>" class="btn btn-primary btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span>Open OTC POS</span>
    </a>
    <a href="<?= route('queue.kiosk') ?>" class="btn btn-amber btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
      <span>Issue Ticket</span>
    </a>
    <?php if ($display): ?>
    <a href="<?= route('display.show', ['token' => $display['token']]) ?>" target="_blank" class="btn btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      <span>TV Audio Display</span>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- 4 Key Stat Cards -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat" style="--accent: var(--brand);">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <div class="label">Patients Registered</div>
    <div class="value"><?= number_format($totalPatients) ?></div>
    <div class="delta">+<?= $todayPatients ?> registered today</div>
  </div>

  <div class="stat" style="--accent: #f59e0b;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Queue in Waiting</div>
    <div class="value"><?= $waitingTickets ?></div>
    <div class="delta"><?= $servingTickets ?> currently in service • <?= $completedTickets ?> completed</div>
  </div>

  <div class="stat" style="--accent: #10b981;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="label">Today's OTC Pharmacy Sales</div>
    <div class="value"><?= money($salesRevenueToday) ?></div>
    <div class="delta"><?= $salesCountToday ?> OTC transactions today</div>
  </div>

  <div class="stat" style="--accent: #f43f5e;">
    <div class="stat-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
    <div class="label">Stock Alerts</div>
    <div class="value"><?= $lowStockCount ?></div>
    <div class="delta"><?= $expiringCount ?> batches expiring soon (< 60 days)</div>
  </div>
</div>

<!-- Active Queues & Departments Grid -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head">
    <div>
      <h3>Live Hospital Queues</h3>
      <span class="muted" style="font-size: 12.5px;">Click a room/counter below to enter the staff calling console</span>
    </div>
    <a href="<?= route('queue.index') ?>" class="btn btn-sm btn-ghost">Manage Queues &rarr;</a>
  </div>
  <div class="card-body">
    <div class="grid grid-3">
      <?php foreach ($queues as $q): ?>
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; border-left: 4px solid <?= e($q['color']) ?>;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="font-size: 15px; font-family: var(--font-display);"><?= e($q['name']) ?></strong>
            <span class="badge" style="background: <?= e($q['color']) ?>20; color: <?= e($q['color']) ?>; border-color: <?= e($q['color']) ?>40;">Prefix <?= e($q['prefix']) ?></span>
          </div>
          <div style="display: flex; gap: 16px; margin-bottom: 12px; font-size: 13px;">
            <div><span class="muted">Waiting:</span> <strong style="color: var(--brand-2);"><?= $q['waiting_count'] ?></strong></div>
            <div><span class="muted">In Room:</span> <strong style="color: var(--brand);"><?= $q['serving_count'] ?></strong></div>
          </div>
          <?php
            $sps = \Veen\Core\Database\DB::select('SELECT id, name FROM service_points WHERE queue_id = ? AND tenant_id = ? AND is_active = 1', [$q['id'], tenant('id')]);
          ?>
          <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
            <?php foreach ($sps as $sp): ?>
              <a href="<?= route('queue.console', ['id' => $sp['id']]) ?>" class="btn btn-sm" style="font-size: 11.5px; padding: 4px 8px;">
                <span><?= e($sp['name']) ?></span> &rarr;
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Two Column Split: Recent Tickets & Recent OTC Sales -->
<div class="grid grid-2">
  <!-- Recent Tickets -->
  <div class="card">
    <div class="card-head">
      <h3>Recent Patient Flow Tickets</h3>
      <a href="<?= route('queue.kiosk') ?>" class="btn btn-sm btn-primary">+ Issue</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>Patient / Name</th>
            <th>Queue</th>
            <th>Room</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentTickets)): ?>
            <tr><td colspan="5" class="empty">No tickets issued today yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentTickets as $t): ?>
              <tr>
                <td>
                  <strong class="mono" style="font-size: 15px; color: var(--brand);"><?= e($t['code']) ?></strong>
                  <?php if ((int) $t['priority'] === 2): ?>
                    <span class="badge badge-danger" style="margin-left: 4px;">EMERGENCY</span>
                  <?php elseif ((int) $t['priority'] === 1): ?>
                    <span class="badge badge-warning" style="margin-left: 4px;">PRIORITY</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($t['first_name']): ?>
                    <strong><?= e($t['first_name'] . ' ' . $t['last_name']) ?></strong>
                    <div class="dim mono" style="font-size: 11px;"><?= e($t['mrn']) ?></div>
                  <?php else: ?>
                    <span class="muted"><?= e($t['customer_name'] ?: 'Walk-in') ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge" style="background: <?= e($t['queue_color']) ?>20; color: <?= e($t['queue_color']) ?>;">
                    <?= e($t['queue_name']) ?>
                  </span>
                </td>
                <td>
                  <span class="dim"><?= e($t['service_point_name'] ?: '—') ?></span>
                </td>
                <td>
                  <?php if ($t['status'] === 'waiting'): ?>
                    <span class="badge badge-warning">Waiting</span>
                  <?php elseif ($t['status'] === 'called'): ?>
                    <span class="badge badge-brand">Called</span>
                  <?php elseif ($t['status'] === 'serving'): ?>
                    <span class="badge badge-info">Serving</span>
                  <?php elseif ($t['status'] === 'completed'): ?>
                    <span class="badge badge-success">Completed</span>
                  <?php else: ?>
                    <span class="badge badge-muted"><?= e(ucfirst($t['status'])) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent OTC Sales -->
  <div class="card">
    <div class="card-head">
      <h3>Recent OTC Pharmacy Sales</h3>
      <a href="<?= route('pharmacy.pos') ?>" class="btn btn-sm btn-primary">Open Register</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Receipt #</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Cashier</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentSales)): ?>
            <tr><td colspan="5" class="empty">No sales recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentSales as $s): ?>
              <tr>
                <td>
                  <strong class="mono"><?= e($s['receipt_no']) ?></strong>
                  <div class="dim" style="font-size: 11px;"><?= date('H:i', strtotime($s['created_at'])) ?></div>
                </td>
                <td>
                  <?php if ($s['first_name']): ?>
                    <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                  <?php else: ?>
                    <span><?= e($s['customer_name'] ?: 'Walk-in') ?></span>
                  <?php endif; ?>
                  <?php if ($s['is_prescription']): ?>
                    <span class="badge badge-warning" style="font-size: 10px; padding: 2px 6px;">Rx POM</span>
                  <?php endif; ?>
                </td>
                <td>
                  <strong style="color: var(--success);"><?= money($s['total_minor']) ?></strong>
                </td>
                <td>
                  <span class="muted"><?= e($s['cashier_name']) ?></span>
                </td>
                <td>
                  <a href="<?= route('pharmacy.receipt', ['id' => $s['uuid'] ?? $s['id']]) ?>" target="_blank" class="btn btn-sm btn-ghost" title="Print Receipt">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
