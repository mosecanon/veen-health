<?php $this->layout('layouts.app', ['title' => 'Queue Management']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / Queue</div>
    <h1>Patient Flow & Ticketing Engine</h1>
    <p class="muted">Manage real-time routing from reception to triage, consultation, laboratory, pharmacy, and cashier.</p>
  </div>
  <div style="display: flex; gap: 10px;">
    <a href="<?= route('queue.kiosk') ?>" class="btn btn-amber btn-lg">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
      <span>Open Ticket Kiosk</span>
    </a>
    <?php if (!empty($displays)): ?>
      <a href="<?= route('display.show', ['token' => $displays[0]['token']]) ?>" target="_blank" class="btn btn-lg">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Open Waiting Hall Display</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Queues Summary Grid -->
<div class="grid grid-3" style="margin-bottom: 24px;">
  <?php foreach ($queues as $q): ?>
    <div class="card" style="border-top: 4px solid <?= e($q['color']) ?>;">
      <div class="card-head">
        <div>
          <h3 style="display: flex; align-items: center; gap: 8px;">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: <?= e($q['color']) ?>;"></span>
            <span><?= e($q['name']) ?></span>
          </h3>
          <span class="dim mono" style="font-size: 12px;">Code prefix: <strong><?= e($q['prefix']) ?></strong></span>
        </div>
        <span class="badge" style="background: <?= e($q['color']) ?>20; color: <?= e($q['color']) ?>;">
          <?= $q['waiting_count'] ?> waiting
        </span>
      </div>
      <div class="card-body">
        <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 13px;">
          <div><span class="muted">Currently Serving:</span> <strong><?= $q['serving_count'] ?></strong></div>
          <div><span class="muted">Completed Today:</span> <strong><?= $q['completed_count'] ?></strong></div>
        </div>
        <a href="<?= route('queue.kiosk', ['queue_id' => $q['id']]) ?>" class="btn btn-sm btn-block">
          <span>+ Issue <?= e($q['prefix']) ?>-Ticket</span>
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Service Points / Rooms Table -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-head">
    <div>
      <h3>Rooms & Service Points</h3>
      <span class="muted" style="font-size: 12.5px;">Each room has a calling console where clinicians call patients by ticket code.</span>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Room / Service Point</th>
          <th>Queue Department</th>
          <th>Assigned Staff</th>
          <th>Active Ticket Now</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($servicePoints as $sp): ?>
          <tr>
            <td>
              <strong style="font-size: 14px;"><?= e($sp['name']) ?></strong>
              <?php if ($sp['announce_as']): ?>
                <div class="dim" style="font-size: 11px;">Audio calls as: "<?= e($sp['announce_as']) ?>"</div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge" style="background: <?= e($sp['queue_color']) ?>20; color: <?= e($sp['queue_color']) ?>;">
                <?= e($sp['queue_name']) ?> (<?= e($sp['queue_prefix']) ?>)
              </span>
            </td>
            <td>
              <span class="muted"><?= e($sp['staff_name'] ?: 'Any logged-in clinician') ?></span>
            </td>
            <td>
              <?php if ($sp['current_ticket_code']): ?>
                <span class="badge badge-brand mono" style="font-size: 13px; font-weight: 700;"><?= e($sp['current_ticket_code']) ?></span>
              <?php else: ?>
                <span class="dim">Idle</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= route('queue.console', ['id' => $sp['id']]) ?>" class="btn btn-sm btn-primary">
                <span>Enter Room Console &rarr;</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Connected TV Screens -->
<div class="card">
  <div class="card-head">
    <div>
      <h3>Connected Waiting Area TV Displays</h3>
      <span class="muted" style="font-size: 12.5px;">Open this URL on waiting hall Smart TVs, Android TV boxes, or kiosk displays with audio connected.</span>
    </div>
  </div>
  <div class="card-body">
    <div class="grid grid-2">
      <?php foreach ($displays as $d): ?>
        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <strong style="font-size: 16px;"><?= e($d['name']) ?></strong>
            <span class="badge badge-success">Audio Active</span>
          </div>
          <div class="muted" style="font-size: 12.5px; margin-bottom: 12px;">
            Language: <strong><?= e($d['voice_lang']) ?></strong> • Chime: <strong><?= $d['chime'] ? 'Yes' : 'No' ?></strong> • Repeats: <strong><?= $d['repeat_count'] ?>x</strong>
          </div>
          <div class="mono" style="background: rgba(0,0,0,0.3); padding: 8px 10px; border-radius: 8px; font-size: 12px; margin-bottom: 12px; overflow-x: auto; color: var(--brand);">
            <?= url('display/' . $d['token']) ?>
          </div>
          <a href="<?= route('display.show', ['token' => $d['token']]) ?>" target="_blank" class="btn btn-primary btn-block">
            <span>Launch TV Screen in New Tab</span>
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
