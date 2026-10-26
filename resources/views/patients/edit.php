<?php $this->layout('layouts.app', ['title' => 'Edit Patient: ' . $patient['first_name'] . ' ' . $patient['last_name']]) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb">
      <a href="<?= route('dashboard') ?>">Dashboard</a> / 
      <a href="<?= route('patients.index') ?>">Patients</a> / 
      <a href="<?= route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></a> / 
      Edit
    </div>
    <h1>Edit Patient Demographics</h1>
    <p class="muted">Update personal details, contact information, or correct spelling errors for MRN: <strong class="mono" style="color: var(--brand);"><?= e($patient['mrn']) ?></strong>.</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost">← Cancel & View Patient</a>
  </div>
</div>

<div class="card" style="max-width: 900px;">
  <div class="card-head" style="display: flex; justify-content: space-between; align-items: center;">
    <h3>Edit Demographics & Identity</h3>
    <span class="badge badge-brand mono">MRN: <?= e($patient['mrn']) ?></span>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route('patients.update', ['id' => $patient['uuid'] ?? $patient['id']]) ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label for="first_name">First Name *</label>
          <input type="text" id="first_name" name="first_name" class="input <?= errors('first_name') ? 'is-invalid' : '' ?>" value="<?= e(old('first_name', $patient['first_name'])) ?>" required autofocus>
          <?php if ($err = errors('first_name')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="last_name">Last / Family Name *</label>
          <input type="text" id="last_name" name="last_name" class="input <?= errors('last_name') ? 'is-invalid' : '' ?>" value="<?= e(old('last_name', $patient['last_name'])) ?>" required>
          <?php if ($err = errors('last_name')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="other_names">Other Names / Middle Name</label>
          <input type="text" id="other_names" name="other_names" class="input" value="<?= e(old('other_names', $patient['other_names'])) ?>" placeholder="Middle name (optional)">
        </div>

        <div class="field">
          <label for="sex">Biological Sex *</label>
          <select id="sex" name="sex" class="input" required>
            <option value="female" <?= old('sex', $patient['sex']) === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="male" <?= old('sex', $patient['sex']) === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="other" <?= old('sex', $patient['sex']) === 'other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <div class="field" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 8px;">
          <div>
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" class="input" value="<?= e(old('dob', $patient['dob'])) ?>" oninput="document.getElementById('patient_age').value = window.calculateAge(this.value)">
          </div>
          <div>
            <label for="patient_age">Age (Years)</label>
            <input type="number" id="patient_age" name="age" min="0" max="130" class="input" placeholder="Age" style="text-align: center;" oninput="if(this.value && !document.getElementById('dob').value) document.getElementById('dob').value = window.estimateDobFromAge(this.value)">
          </div>
        </div>

        <div class="field">
          <label for="phone">Phone Number *</label>
          <input type="text" id="phone" name="phone" class="input <?= errors('phone') ? 'is-invalid' : '' ?>" value="<?= e(old('phone', $patient['phone'])) ?>" required placeholder="e.g. +254 712 345678">
          <?php if ($err = errors('phone')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="national_id">National ID / Passport</label>
          <input type="text" id="national_id" name="national_id" class="input <?= errors('national_id') ? 'is-invalid' : '' ?>" value="<?= e(old('national_id', $patient['national_id'])) ?>" placeholder="e.g. 12345678">
          <?php if ($err = errors('national_id')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="input" value="<?= e(old('email', $patient['email'])) ?>" placeholder="patient@example.com">
        </div>

        <div class="field">
          <label for="blood_group">Blood Group</label>
          <select id="blood_group" name="blood_group" class="input">
            <option value="">Unknown / Not Tested</option>
            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
              <option value="<?= $bg ?>" <?= old('blood_group', $patient['blood_group']) === $bg ? 'selected' : '' ?>><?= $bg ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label for="allergies">Known Allergies / Critical Alerts</label>
          <input type="text" id="allergies" name="allergies" class="input" value="<?= e(old('allergies', $patient['allergies'])) ?>" placeholder="e.g. Penicillin, Aspirin, Peanuts...">
        </div>

        <div class="field full">
          <label for="address">Residential Address</label>
          <input type="text" id="address" name="address" class="input" value="<?= e(old('address', $patient['address'])) ?>" placeholder="Estate, Ward, Town / City">
        </div>

        <div class="field">
          <label for="next_of_kin_name">Emergency Contact / Next of Kin Person</label>
          <input type="text" id="next_of_kin_name" name="next_of_kin_name" class="input" value="<?= e(old('next_of_kin_name', $patient['next_of_kin_name'] ?? '')) ?>" placeholder="Next of kin / Guardian">
        </div>

        <div class="field">
          <label for="next_of_kin_phone">Emergency Contact / Next of Kin Phone</label>
          <input type="text" id="next_of_kin_phone" name="next_of_kin_phone" class="input" value="<?= e(old('next_of_kin_phone', $patient['next_of_kin_phone'] ?? '')) ?>" placeholder="Next of kin contact">
        </div>
      </div>

      <div class="form-actions" style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center;">
        <a href="<?= route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]) ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Save Changes</span>
        </button>
      </div>
    </form>
  </div>
</div>
