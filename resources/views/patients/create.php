<?php $this->layout('layouts.app', ['title' => 'Register Patient']) ?>

<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= route('dashboard') ?>">Dashboard</a> / <a href="<?= route('patients.index') ?>">Patients</a> / Register</div>
    <h1>Register New Patient</h1>
    <p class="muted">Creates an electronic medical record with sequential MRN for <?= e(tenant('name')) ?>.</p>
  </div>
</div>

<div class="card" style="max-width: 900px;">
  <div class="card-head">
    <h3>Patient Demographics</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route('patients.store') ?>">
      <?= csrf_field() ?>

      <div class="form-grid">
        <div class="field">
          <label for="first_name">First Name *</label>
          <input type="text" id="first_name" name="first_name" class="input <?= errors('first_name') ? 'is-invalid' : '' ?>" value="<?= e(old('first_name')) ?>" required autofocus placeholder="e.g. Grace">
          <?php if ($err = errors('first_name')): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="last_name">Last / Family Name *</label>
          <input type="text" id="last_name" name="last_name" class="input <?= errors('last_name') ? 'is-invalid' : '' ?>" value="<?= e(old('last_name')) ?>" required placeholder="e.g. Wambui">
          <?php if ($err = errors('last_name')): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="sex">Biological Sex *</label>
          <select id="sex" name="sex" class="input" required>
            <option value="female" <?= old('sex') === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="male" <?= old('sex') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="other" <?= old('sex') === 'other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <div class="field" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 8px;">
          <div>
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" class="input" value="<?= e(old('dob')) ?>" oninput="document.getElementById('patient_age').value = window.calculateAge(this.value)">
          </div>
          <div>
            <label for="patient_age">Age (Years)</label>
            <input type="number" id="patient_age" name="age" min="0" max="130" class="input" placeholder="Age" style="text-align: center;" oninput="if(this.value && !document.getElementById('dob').value) document.getElementById('dob').value = window.estimateDobFromAge(this.value)">
          </div>
        </div>

        <div class="field">
          <label for="phone">Phone Number *</label>
          <input type="text" id="phone" name="phone" class="input <?= errors('phone') ? 'is-invalid' : '' ?>" value="<?= e(old('phone')) ?>" required placeholder="e.g. +254 712 345678">
          <?php if ($err = errors('phone')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="national_id">National ID / Passport</label>
          <input type="text" id="national_id" name="national_id" class="input <?= errors('national_id') ? 'is-invalid' : '' ?>" value="<?= e(old('national_id')) ?>" placeholder="e.g. 12345678">
          <?php if ($err = errors('national_id')): ?><div class="error" style="color: #ef4444; font-size: 12px; margin-top: 4px;"><?= e($err) ?></div><?php endif; ?>
        </div>

        <!-- Parent-Child Dependent Registration -->
        <div class="field full" style="background: rgba(20, 184, 166, 0.05); border: 1px solid rgba(20, 184, 166, 0.25); padding: 14px 18px; border-radius: 8px;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0; font-weight: 600; font-size: 14px;">
            <input type="checkbox" id="is_child" name="is_child" value="1" <?= old('is_child') ? 'checked' : '' ?> onchange="document.getElementById('childFields').style.display = this.checked ? 'grid' : 'none'" style="width: 18px; height: 18px;">
            <span>👶 Register as Child / Minor (shares Parent or Guardian contact/phone)</span>
          </label>
          <div id="childFields" style="display: <?= old('is_child') ? 'grid' : 'none' ?>; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
            <div>
              <label for="relationship_to_parent">Relationship to Guardian / Parent</label>
              <select id="relationship_to_parent" name="relationship_to_parent" class="input">
                <option value="child" <?= old('relationship_to_parent', 'child') === 'child' ? 'selected' : '' ?>>Child (Son / Daughter)</option>
                <option value="ward" <?= old('relationship_to_parent') === 'ward' ? 'selected' : '' ?>>Ward</option>
                <option value="dependent" <?= old('relationship_to_parent') === 'dependent' ? 'selected' : '' ?>>Dependent Minor</option>
              </select>
            </div>
            <div>
              <label for="parent_patient_id">Existing Parent MRN or Patient ID (Optional)</label>
              <input type="text" id="parent_patient_id" name="parent_patient_id" class="input" value="<?= e(old('parent_patient_id')) ?>" placeholder="Auto-linked if parent phone matches">
            </div>
          </div>
        </div>

        <div class="field">
          <label for="blood_group">Blood Group</label>
          <select id="blood_group" name="blood_group" class="input">
            <option value="">Unknown</option>
            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
              <option value="<?= $bg ?>" <?= old('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="allergies">Known Allergies</label>
          <input type="text" id="allergies" name="allergies" class="input" value="<?= e(old('allergies')) ?>" placeholder="e.g. Penicillin, Sulfa drugs">
        </div>

        <div class="field full">
          <label for="address">Residential Address</label>
          <input type="text" id="address" name="address" class="input" value="<?= e(old('address')) ?>" placeholder="Street, Estate, City">
        </div>

        <div class="field">
          <label for="emergency_contact_name">Emergency Contact Name</label>
          <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="input" value="<?= e(old('emergency_contact_name')) ?>">
        </div>

        <div class="field">
          <label for="emergency_contact_phone">Emergency Contact Phone</label>
          <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="input" value="<?= e(old('emergency_contact_phone')) ?>">
        </div>
      </div>

      <div class="form-actions">
        <a href="<?= route('patients.index') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <span>Complete Registration</span>
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </button>
      </div>
    </form>
  </div>
</div>
