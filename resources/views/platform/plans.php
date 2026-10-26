<?php $this->layout('layouts.app', ['title' => 'Subscription Plans — Platform Fleet']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">Platform / SaaS Subscriptions</div>
    <h1>Dynamic Subscription Plans</h1>
    <p class="muted">Configure SaaS pricing tiers: Free Trial, Monthly, Yearly (Per Year), and One-Time Lifetime Licenses.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('platform.dashboard') ?>" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      <span>Back to Hospital Fleet</span>
    </a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('newPlanModal').classList.remove('hidden')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Create New Plan</span>
    </button>
  </div>
</div>

<div class="grid grid-3" style="margin-bottom: 32px;">
  <?php foreach ($plans as $p): ?>
    <?php $features = is_string($p['features']) ? json_decode($p['features'], true) : ($p['features'] ?? []); ?>
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--brand);">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <div>
            <h3 style="margin: 0; font-size: 18px;"><?= e($p['name']) ?></h3>
            <span class="mono dim" style="font-size: 12px;">Code: <?= e($p['code']) ?></span>
          </div>
          <span class="badge badge-brand"><?= strtoupper(str_replace('_', ' ', $p['billing_cycle'] ?? 'monthly')) ?></span>
        </div>

        <div style="margin: 16px 0; display: flex; align-items: baseline; gap: 4px;">
          <span style="font-size: 32px; font-weight: 800; color: var(--text);">
            $<?= number_format($p['price_usd_cents'] / 100, 2) ?>
          </span>
          <span class="muted" style="font-size: 13px;">
            / <?= $p['billing_cycle'] === 'yearly' ? 'year' : ($p['billing_cycle'] === 'one_time' ? 'one-time' : ($p['billing_cycle'] === 'free_trial' ? 'trial' : 'month')) ?>
          </span>
        </div>

        <?php if ($p['billing_cycle'] === 'free_trial' || !empty($p['trial_days'])): ?>
          <div style="margin-bottom: 14px; font-size: 12px; color: #10b981; font-weight: 600;">
            🎁 Includes <?= $p['trial_days'] ?> Days Full-Access Trial
          </div>
        <?php endif; ?>

        <div style="font-size: 13px; color: var(--muted); margin-bottom: 16px;">
          <div>• <?= $p['max_users'] ? "Up to {$p['max_users']} staff accounts" : 'Unlimited staff accounts' ?></div>
          <div>• <?= $p['max_branches'] ? "Up to {$p['max_branches']} branch facilities" : 'Unlimited facilities' ?></div>
        </div>

        <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); margin-bottom: 8px;">Included Features</div>
          <div style="display: flex; flex-wrap: wrap; gap: 4px;">
            <?php foreach (($features ?: ['all']) as $feat): ?>
              <span class="badge badge-info" style="font-size: 11px;"><?= e($feat) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <span class="muted" style="font-size: 12px;">
          <strong><?= (int) ($p['subscribers_count'] ?? 0) ?></strong> active hospitals
        </span>
        <span class="badge badge-success">Live</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modal to create new plan -->
<div id="newPlanModal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 540px; padding: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0;">Create SaaS Subscription Plan</h3>
      <button type="button" class="icon-btn" onclick="document.getElementById('newPlanModal').classList.add('hidden')">&times;</button>
    </div>

    <form method="POST" action="<?= route('platform.plans.store') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field">
          <label>Plan Name *</label>
          <input type="text" name="name" class="input" required placeholder="e.g. Clinic Starter or Annual Pro">
        </div>
        <div class="field">
          <label>Plan Code *</label>
          <input type="text" name="code" class="input" required placeholder="e.g. clinic_annual">
        </div>

        <div class="field">
          <label>Billing Cycle *</label>
          <select name="billing_cycle" class="input" required onchange="onCycleChange(this.value)">
            <option value="free_trial">Free Trial (0 USD)</option>
            <option value="monthly" selected>Per Month (Monthly recurring)</option>
            <option value="yearly">Per Year (Annual billing)</option>
            <option value="one_time">One-Time Lifetime License</option>
          </select>
        </div>

        <div class="field">
          <label>Price (USD) *</label>
          <input type="number" step="0.01" min="0" name="price_usd" class="input" required value="49.00">
        </div>

        <div class="field">
          <label>Trial Period (Days)</label>
          <input type="number" name="trial_days" class="input" value="14" min="0" max="365">
        </div>

        <div class="field">
          <label>Max Staff Users (Leave empty for unlimited)</label>
          <input type="number" name="max_users" class="input" placeholder="e.g. 20">
        </div>

        <div class="field">
          <label>Max Branches (Leave empty for unlimited)</label>
          <input type="number" name="max_branches" class="input" placeholder="e.g. 2">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('newPlanModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <span>Save Subscription Plan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function onCycleChange(val) {
  const priceInput = document.querySelector('input[name="price_usd"]');
  if (val === 'free_trial') {
    priceInput.value = '0.00';
  }
}
</script>