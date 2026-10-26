<?php $this->layout('layouts.app', ['title' => $sp['name'] . ' — Staff Console']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('queue.index') ?>">Queue</a> / Room Console</div>
    <h1><?= e($sp['name']) ?></h1>
    <p class="muted">
      Department: <span class="badge" style="background: <?= e($sp['queue_color']) ?>20; color: <?= e($sp['queue_color']) ?>;"><?= e($sp['queue_name']) ?></span>
      • Staff: <strong><?= e(auth()->name) ?></strong> (<?= e(auth()->role_label) ?>)
    </p>
  </div>
  <div style="display: flex; gap: 10px;">
    <?php
      $disp = \Veen\Core\Database\DB::selectOne('SELECT token FROM displays WHERE tenant_id = ? LIMIT 1', [tenant('id')]);
      if ($disp):
    ?>
    <a href="<?= route('display.show', ['token' => $disp['token']]) ?>" target="_blank" class="btn btn-sm btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      <span>TV Audio Display</span>
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-3" style="align-items: start;">
  <!-- Left 2 Cols: Main Active Ticket & Action Panel -->
  <div class="span-2" style="display: flex; flex-direction: column; gap: 20px;">
    <?php if ($currentTicket): ?>
      <!-- Active Ticket Card -->
      <div class="card" style="border: 2px solid var(--brand); box-shadow: var(--shadow-glow);">
        <div class="card-head" style="background: rgba(20, 184, 166, 0.08);">
          <div style="display: flex; align-items: center; gap: 10px;">
            <span class="tenant-dot"></span>
            <strong>NOW IN ROOM / CALLING</strong>
          </div>
          <div>
            <?php if ($currentTicket['status'] === 'called'): ?>
              <span class="badge badge-brand">Called (Waiting for patient to enter)</span>
            <?php else: ?>
              <span class="badge badge-success">Currently Serving</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
              <div class="dim" style="font-size: 13px; text-transform: uppercase; letter-spacing: .08em;">Ticket Number</div>
              <div class="mono" style="font-size: 54px; font-weight: 800; color: var(--brand); line-height: 1.05;">
                <?= e($currentTicket['code']) ?>
              </div>
              <div class="muted" style="font-size: 13px; margin-top: 4px;">
                Called at <?= date('H:i:s', strtotime($currentTicket['called_at'])) ?> • Call count: <strong><?= $currentTicket['call_count'] ?></strong>
              </div>
            </div>

            <!-- Patient Info Box -->
            <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; min-width: 260px;">
              <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">PATIENT DETAILS</div>
              <?php if ($currentTicket['first_name']): ?>
                <strong style="font-size: 16px;"><?= e($currentTicket['first_name'] . ' ' . $currentTicket['last_name']) ?></strong>
                <div class="mono" style="font-size: 12px; color: var(--brand);"><?= e($currentTicket['mrn']) ?></div>
                <div class="dim" style="font-size: 12px; margin-top: 4px;">
                  <?= ucfirst($currentTicket['sex']) ?> <?php if ($age = age_from_dob($currentTicket['dob'])): ?>• <?= $age ?> yrs<?php endif; ?>
                  <?php if ($currentTicket['phone']): ?>• <?= e($currentTicket['phone']) ?><?php endif; ?>
                </div>
                <?php if ($currentTicket['allergies']): ?>
                  <div style="margin-top: 6px;"><span class="badge badge-danger">⚠️ <?= e($currentTicket['allergies']) ?></span></div>
                <?php endif; ?>
              <?php else: ?>
                <strong style="font-size: 16px;"><?= e($currentTicket['customer_name'] ?: 'Walk-in Patient') ?></strong>
                <div class="dim" style="font-size: 12px;">No MRN registered</div>
              <?php endif; ?>

              <?php if ($currentTicket['notes']): ?>
                <div style="margin-top: 8px; font-size: 12px; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 6px;">
                  <em>Note: <?= e($currentTicket['notes']) ?></em>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="divider"></div>

          <!-- Calling & Service Action Buttons -->
          <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <!-- Recall Audio Button -->
            <form method="POST" action="<?= route('queue.recall', ['id' => $currentTicket['id']]) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-amber btn-lg" title="Play chime and voice announcement again on waiting TV">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <span>Recall Audio Announcement</span>
              </button>
            </form>

            <?php if ($currentTicket['status'] === 'called'): ?>
              <!-- Start Service -->
              <form method="POST" action="<?= route('queue.start', ['id' => $currentTicket['id']]) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-lg">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Patient Entered &rarr; Start Service</span>
                </button>
              </form>
            <?php endif; ?>

            <!-- Complete Service -->
            <form method="POST" action="<?= route('queue.complete', ['id' => $currentTicket['id']]) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-success btn-lg">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Complete Service</span>
              </button>
            </form>

            <!-- Skip / No Show -->
            <form method="POST" action="<?= route('queue.skip', ['id' => $currentTicket['id']]) ?>" onsubmit="return confirm('Mark ticket as skipped/no-show?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-lg">
                <span>Skip / No Show</span>
              </button>
            </form>
          </div>

          <div class="divider"></div>

          <!-- Transfer to next room -->
          <div>
            <h4 style="margin-bottom: 8px;">Transfer Patient to Another Department</h4>
            <form method="POST" action="<?= route('queue.transfer', ['id' => $currentTicket['id']]) ?>" style="display: flex; gap: 10px; max-width: 500px;">
              <?= csrf_field() ?>
              <select name="target_service_point_id" class="input" required>
                <option value="">-- Choose destination room / window --</option>
                <?php foreach ($otherServicePoints as $osp): ?>
                  <option value="<?= $osp['id'] ?>"><?= e($osp['queue_name']) ?> &rarr; <?= e($osp['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-amber">
                <span>Transfer</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Room Idle: Ready for Next Ticket -->
      <div class="card card-pad" style="text-align: center; padding: 48px 24px;">
        <div style="width: 68px; height: 68px; border-radius: 50%; background: rgba(20, 184, 166, 0.15); color: var(--brand); display: grid; place-items: center; margin: 0 auto 16px;">
          <svg style="width: 34px; height: 34px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
        </div>
        <h2 style="font-size: 26px;">Room is Currently Idle</h2>
        <p class="muted" style="max-width: 440px; margin: 0 auto 24px;">
          <?= count($waitingTickets) ?> patient(s) waiting in the <strong><?= e($sp['queue_name']) ?></strong> line.
        </p>

        <form method="POST" action="<?= route('queue.call_next', ['id' => $sp['id']]) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-primary btn-lg" style="font-size: 18px; padding: 18px 36px; box-shadow: var(--shadow-glow);" <?= empty($waitingTickets) ? 'disabled' : '' ?>>
            <svg style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
            <span>CALL NEXT PATIENT</span>
          </button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Today's Completed Patients at this Counter -->
    <div class="card">
      <div class="card-head">
        <h3>Completed Patients Today (<?= count($completedToday) ?>)</h3>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Ticket</th>
              <th>Patient</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($completedToday)): ?>
              <tr><td colspan="3" class="empty">No completed visits in this room yet today.</td></tr>
            <?php else: ?>
              <?php foreach ($completedToday as $c): ?>
                <tr>
                  <td><strong class="mono" style="color: var(--brand);"><?= e($c['code']) ?></strong></td>
                  <td><?= e($c['first_name'] ? $c['first_name'] . ' ' . $c['last_name'] : ($c['customer_name'] ?: 'Walk-in')) ?></td>
                  <td><span class="badge badge-success">Completed</span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Col: Waiting Line for this Queue -->
  <div class="card">
    <div class="card-head">
      <div>
        <h3>Waiting Queue</h3>
        <span class="muted" style="font-size: 12px;"><?= count($waitingTickets) ?> in line</span>
      </div>
    </div>
    <div style="max-height: 600px; overflow-y: auto; padding: 12px;">
      <?php if (empty($waitingTickets)): ?>
        <div class="empty">
          <p>No patients waiting right now.</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php foreach ($waitingTickets as $idx => $wt): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px 14px;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dim" style="font-size: 12px; font-weight: 700;">#<?= $idx + 1 ?></span>
                <div>
                  <strong class="mono" style="font-size: 16px; color: var(--brand);"><?= e($wt['code']) ?></strong>
                  <div style="font-size: 12px; color: var(--text-muted);">
                    <?= e($wt['first_name'] ? $wt['first_name'] . ' ' . $wt['last_name'] : ($wt['customer_name'] ?: 'Walk-in')) ?>
                  </div>
                </div>
              </div>
              <?php if ((int) $wt['priority'] === 2): ?>
                <span class="badge badge-danger">EMERGENCY</span>
              <?php elseif ((int) $wt['priority'] === 1): ?>
                <span class="badge badge-warning">PRIORITY</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
