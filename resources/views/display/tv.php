<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($display['name']) ?> — <?= e($tenant['name']) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/apple-touch-icon.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style>
    body {
      background: #040812;
      color: #f1f5f9;
      margin: 0;
      padding: 0;
      overflow: hidden;
      font-family: var(--font);
      height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .tv-header {
      height: 90px;
      padding: 0 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(7, 11, 20, 0.85));
      border-bottom: 2px solid rgba(20, 184, 166, 0.3);
      backdrop-filter: blur(20px);
    }
    .tv-brand {
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .tv-logo {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: conic-gradient(from 200deg, var(--brand), var(--brand-2), var(--brand-3), var(--brand));
      color: #04111a;
      display: grid;
      place-items: center;
      font-family: var(--font-display);
      font-weight: 800;
      font-size: 26px;
      box-shadow: 0 0 20px rgba(20, 184, 166, 0.4);
    }
    .tv-title {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    .tv-subtitle {
      font-size: 14px;
      color: var(--text-muted);
    }
    .tv-clock {
      font-family: var(--font-display);
      font-size: 34px;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: #f8fafc;
      text-align: right;
    }
    .tv-date {
      font-size: 13px;
      color: var(--text-muted);
    }
    .tv-body {
      flex: 1;
      display: grid;
      grid-template-columns: 2.2fr 1fr;
      gap: 24px;
      padding: 24px 40px;
      min-height: 0;
    }
    .tv-main-panel {
      display: flex;
      flex-direction: column;
      gap: 16px;
      min-height: 0;
    }
    .tv-panel-title {
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--text-dim);
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .tv-panel-title .dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--brand);
      box-shadow: 0 0 10px var(--brand);
    }
    .tv-cards-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 18px;
      flex: 1;
      align-content: start;
      overflow: hidden;
    }
    .tv-card {
      background: rgba(15, 23, 42, 0.7);
      border: 2px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 24px 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
      transition: all 0.4s var(--ease);
      backdrop-filter: blur(14px);
    }
    .tv-card.flash {
      border-color: #f59e0b;
      box-shadow: 0 0 40px rgba(245, 158, 11, 0.5), inset 0 0 30px rgba(245, 158, 11, 0.2);
      animation: pulseGlow 1.2s ease-in-out infinite;
    }
    @keyframes pulseGlow {
      0%, 100% { transform: scale(1); border-color: #f59e0b; }
      50% { transform: scale(1.02); border-color: #14b8a6; }
    }
    .tv-ticket-code {
      font-family: var(--font-display);
      font-size: 64px;
      font-weight: 800;
      color: #38bdf8;
      line-height: 1;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }
    .tv-room-badge {
      font-size: 20px;
      font-weight: 700;
      color: #f8fafc;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .tv-room-badge .arrow {
      color: var(--brand-2);
      font-size: 24px;
    }
    .tv-patient-name {
      font-size: 14px;
      color: var(--text-muted);
      margin-top: 6px;
    }
    .tv-side-panel {
      background: rgba(15, 23, 42, 0.5);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 22px;
      display: flex;
      flex-direction: column;
      min-height: 0;
      backdrop-filter: blur(14px);
    }
    .tv-waiting-list {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 14px;
    }
    .tv-waiting-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.06);
    }
    .tv-waiting-code {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 700;
      color: var(--brand);
    }
    .tv-footer-ticker {
      height: 52px;
      background: #020617;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      overflow: hidden;
      white-space: nowrap;
      position: relative;
    }
    .tv-ticker-tag {
      background: var(--brand);
      color: #04111a;
      font-weight: 800;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      padding: 0 20px;
      height: 100%;
      display: grid;
      place-items: center;
      z-index: 10;
    }
    .tv-ticker-content {
      display: inline-block;
      padding-left: 100%;
      animation: ticker 35s linear infinite;
      font-size: 15px;
      color: var(--text-muted);
      font-weight: 500;
    }
    @keyframes ticker {
      0% { transform: translate3d(0, 0, 0); }
      100% { transform: translate3d(-100%, 0, 0); }
    }
    #audioModal {
      position: fixed;
      inset: 0;
      background: rgba(4, 8, 18, 0.85);
      backdrop-filter: blur(8px);
      z-index: 999;
      display: grid;
      place-items: center;
    }
  </style>
</head>
<body>

  <!-- Audio unlock banner / modal (Browsers require user gesture to play sound) -->
  <div id="audioModal">
    <div style="background: var(--surface-solid); border: 2px solid var(--brand); border-radius: 20px; padding: 36px 44px; text-align: center; max-width: 520px; box-shadow: var(--shadow-glow);">
      <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(20, 184, 166, 0.15); color: var(--brand); display: grid; place-items: center; margin: 0 auto 20px;">
        <svg style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
      </div>
      <h2 style="font-size: 26px; margin-bottom: 10px;">Connect TV Audio Calling</h2>
      <p class="muted" style="margin-bottom: 24px; font-size: 14px;">
        Click below to activate the patient announcement voice and chime system. Ensure this display is connected to your waiting room speakers or TV amplifier.
      </p>
      <button type="button" class="btn btn-primary btn-lg" onclick="unlockAudio()" style="font-size: 16px; padding: 14px 32px; width: 100%;">
        🔊 Activate Audio System & Enter Fullscreen
      </button>
    </div>
  </div>

  <!-- Header -->
  <header class="tv-header">
    <div class="tv-brand">
      <?php if (!empty($tenant['logo'])): ?>
        <img src="<?= asset($tenant['logo']) ?>" class="tv-logo" style="object-fit: contain; background: transparent;" alt="Logo">
      <?php else: ?>
        <img src="<?= asset('img/logo-mark.png') ?>" class="tv-logo" style="object-fit: contain; background: transparent; padding: 2px;" alt="Veen Health">
      <?php endif; ?>
      <div>
        <div class="tv-title"><?= e($tenant['name']) ?></div>
        <div class="tv-subtitle"><?= e($display['name']) ?> • Patient Calling Board</div>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 24px;">
      <div id="audioStatus" style="font-size: 13px; color: var(--brand); display: flex; align-items: center; gap: 8px;">
        <span class="tenant-dot"></span>
        <span id="audioStatusText">Audio Initializing</span>
      </div>
      <div>
        <div class="tv-clock" id="clock">00:00:00</div>
        <div class="tv-date"><?= date('l, d F Y') ?></div>
      </div>
    </div>
  </header>

  <!-- Body -->
  <div class="tv-body">
    <!-- Main Left: Currently Called Tickets -->
    <div class="tv-main-panel">
      <div class="tv-panel-title">
        <span class="dot"></span>
        <span>NOW SERVING / PROCEED TO ROOM</span>
      </div>

      <div class="tv-cards-grid" id="servingGrid">
        <?php if (empty($serving)): ?>
          <div class="tv-card" style="grid-column: 1 / -1; text-align: center; justify-content: center; padding: 60px 20px;">
            <div class="muted" style="font-size: 20px;">Waiting for next patient call...</div>
            <div class="dim" style="font-size: 14px; margin-top: 8px;">Numbers will appear here with voice and chime announcement.</div>
          </div>
        <?php else: ?>
          <?php foreach ($serving as $idx => $s): ?>
            <div class="tv-card <?= $idx === 0 ? 'flash' : '' ?>" id="card-<?= e($s['code']) ?>">
              <div>
                <span class="badge" style="background: <?= e($s['queue_color']) ?>; color: #fff; margin-bottom: 12px; font-size: 12px; font-weight: 700;">
                  <?= e($s['queue_name']) ?>
                </span>
                <div class="tv-ticket-code"><?= e($s['code']) ?></div>
                <div class="tv-patient-name"><?= e($s['first_name'] ? $s['first_name'] . ' ' . $s['last_name'] : 'Patient') ?></div>
              </div>
              <div class="tv-room-badge">
                <span class="arrow">&rarr;</span>
                <span><?= e($s['announce_as'] ?: $s['service_point_name']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Side: Next in Line -->
    <div class="tv-side-panel">
      <div class="tv-panel-title">
        <span>NEXT IN LINE</span>
      </div>
      <div class="tv-waiting-list" id="waitingList">
        <?php if (empty($waiting)): ?>
          <div class="empty" style="padding: 30px 10px;">
            <span class="dim">No pending tickets</span>
          </div>
        <?php else: ?>
          <?php foreach ($waiting as $w): ?>
            <div class="tv-waiting-row">
              <span class="tv-waiting-code"><?= e($w['code']) ?></span>
              <span class="badge" style="background: <?= e($w['queue_color']) ?>20; color: <?= e($w['queue_color']) ?>;">
                <?= e($w['queue_name']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Marquee Ticker -->
  <footer class="tv-footer-ticker" style="display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; overflow: hidden; flex: 1; height: 100%;">
      <div class="tv-ticker-tag">NOTICE</div>
      <div class="tv-ticker-content">
        <?= e($display['ticker_text'] ?: 'Welcome to ' . $tenant['name'] . '. Please watch the screen and listen for audio calls.') ?>
        &nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;
        Hospital Emergency Contact: <?= e($tenant['phone'] ?: 'Dial Front Desk') ?>
      </div>
    </div>
    <div style="padding: 0 24px; font-size: 13px; font-weight: 700; white-space: nowrap; background: rgba(7, 11, 20, 0.95); z-index: 20; height: 100%; display: flex; align-items: center; border-left: 1px solid rgba(255, 255, 255, 0.1);">
      <span class="muted" style="margin-right: 6px; font-weight: 500; font-size: 12px;">Powered by</span>
      <a href="https://www.veen.africa" target="_blank" rel="noopener noreferrer" style="color: var(--brand); text-decoration: none;">Veen Africa</a>
    </div>
  </footer>

  <!-- Audio Synthesis & Polling JavaScript Engine -->
  <script>
    let audioUnlocked = false;
    let audioCtx = null;
    let lastEventId = <?= (int) $initialEventId ?>;
    const pollUrl = '<?= route('display.poll', ['token' => $display['token']]) ?>';
    const voiceLang = '<?= e($display['voice_lang'] ?: 'en-GB') ?>';
    let announcementQueue = [];
    let isSpeaking = false;

    // Clock
    function updateClock() {
      const d = new Date();
      document.getElementById('clock').innerText = d.toTimeString().split(' ')[0];
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Audio Unlock
    function unlockAudio() {
      try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        audioCtx = new AudioContext();
        if (audioCtx.state === 'suspended') {
          audioCtx.resume();
        }
      } catch (e) {
        console.error(e);
      }

      audioUnlocked = true;
      document.getElementById('audioModal').style.display = 'none';
      document.getElementById('audioStatusText').innerText = 'Audio System Connected';

      // Play test chime
      playChime(() => {
        speakText('Audio system online. Welcome to <?= addslashes($tenant['name']) ?>.');
      });

      // Try entering fullscreen
      if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
      }
    }

    // Modern 3-tone Chime Synthesizer (C5 -> E5 -> G5)
    function playChime(callback) {
      if (!audioCtx) {
        if (callback) callback();
        return;
      }
      const tones = [523.25, 659.25, 783.99]; // C5, E5, G5
      let time = audioCtx.currentTime;

      tones.forEach((freq, idx) => {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, time + (idx * 0.16));

        gain.gain.setValueAtTime(0.001, time + (idx * 0.16));
        gain.gain.exponentialRampToValueAtTime(0.35, time + (idx * 0.16) + 0.04);
        gain.gain.exponentialRampToValueAtTime(0.0001, time + (idx * 0.16) + 0.45);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start(time + (idx * 0.16));
        osc.stop(time + (idx * 0.16) + 0.5);
      });

      setTimeout(() => {
        if (callback) callback();
      }, 700);
    }

    // Web Speech API Voice Calling
    function speakText(text, callback) {
      if (!('speechSynthesis' in window)) {
        if (callback) callback();
        return;
      }
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = voiceLang;
      utter.rate = 0.9;
      utter.pitch = 1.0;

      utter.onend = () => {
        if (callback) callback();
      };
      utter.onerror = () => {
        if (callback) callback();
      };

      window.speechSynthesis.speak(utter);
    }

    function processQueue() {
      if (isSpeaking || announcementQueue.length === 0) return;
      isSpeaking = true;
      const item = announcementQueue.shift();

      playChime(() => {
        speakText(item.speech_text, () => {
          setTimeout(() => {
            isSpeaking = false;
            processQueue();
          }, 600);
        });
      });
    }

    // Live Polling Loop
    async function pollEvents() {
      try {
        const res = await fetch(`${pollUrl}?since_id=${lastEventId}`);
        if (!res.ok) throw new Error('Poll failed');
        const data = await res.json();

        if (data.max_event_id > lastEventId) {
          lastEventId = data.max_event_id;
        }

        // Process new announcements
        if (data.announcements && data.announcements.length > 0) {
          data.announcements.forEach(a => {
            announcementQueue.push(a);
          });
          processQueue();
        }

        // Update Serving Grid DOM
        if (data.serving) {
          renderServing(data.serving);
        }

        // Update Waiting List DOM
        if (data.waiting) {
          renderWaiting(data.waiting);
        }
      } catch (err) {
        console.warn('Poll error:', err);
      } finally {
        setTimeout(pollEvents, 2500);
      }
    }

    function renderServing(list) {
      const grid = document.getElementById('servingGrid');
      if (!list || list.length === 0) {
        grid.innerHTML = `
          <div class="tv-card" style="grid-column: 1 / -1; text-align: center; justify-content: center; padding: 60px 20px;">
            <div class="muted" style="font-size: 20px;">Waiting for next patient call...</div>
            <div class="dim" style="font-size: 14px; margin-top: 8px;">Numbers will appear here with voice and chime announcement.</div>
          </div>`;
        return;
      }

      grid.innerHTML = list.map((s, idx) => `
        <div class="tv-card ${idx === 0 ? 'flash' : ''}" id="card-${s.code}">
          <div>
            <span class="badge" style="background: ${s.queue_color}; color: #fff; margin-bottom: 12px; font-size: 12px; font-weight: 700;">
              ${escapeHtml(s.queue_name)}
            </span>
            <div class="tv-ticket-code">${escapeHtml(s.code)}</div>
            <div class="tv-patient-name">${escapeHtml(s.customer_name || 'Patient')}</div>
          </div>
          <div class="tv-room-badge">
            <span class="arrow">&rarr;</span>
            <span>${escapeHtml(s.service_point_name)}</span>
          </div>
        </div>
      `).join('');
    }

    function renderWaiting(list) {
      const container = document.getElementById('waitingList');
      if (!list || list.length === 0) {
        container.innerHTML = '<div class="empty" style="padding: 30px 10px;"><span class="dim">No pending tickets</span></div>';
        return;
      }

      container.innerHTML = list.map(w => `
        <div class="tv-waiting-row">
          <span class="tv-waiting-code">${escapeHtml(w.code)}</span>
          <span class="badge" style="background: ${w.queue_color}20; color: ${w.queue_color};">
            ${escapeHtml(w.queue_name)}
          </span>
        </div>
      `).join('');
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[m]);
    }

    // Start Poller
    setTimeout(pollEvents, 2000);
  </script>
</body>
</html>
