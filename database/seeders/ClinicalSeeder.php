<?php
/**
 * Clinical Seeder for Veen Health:
 * Seeds diagnostic tests catalog, wards & beds, billable services, and insurance providers for tenants.
 */

use Veen\Core\Database\DB;
use Veen\Core\Tenancy\TenantManager;

return function (callable $say) {
    $tenants = DB::select('SELECT * FROM tenants');

    $tests = [
        ['FBC', 'Full Blood Count (Complete Blood Count)', 'hematology', 'Whole Blood (EDTA)', 'Hb: 12-16 g/dL, WBC: 4-10 x10^9/L', 'g/dL', 800, 30],
        ['MPS', 'Malaria Parasites (Blood Smear)', 'parasitology', 'Whole Blood', 'Negative (No parasites seen)', '', 400, 20],
        ['RBS', 'Random Blood Sugar (Glucose)', 'biochemistry', 'Fluoride Blood', '3.9 - 7.1 mmol/L', 'mmol/L', 350, 15],
        ['URN', 'Urinalysis (Dipstick & Microscopy)', 'biochemistry', 'Midstream Urine', 'Protein: Neg, Leuks: Neg, Nitrite: Neg', '', 500, 25],
        ['LFT', 'Liver Function Tests (ALT, AST, ALP, Bilirubin)', 'biochemistry', 'Serum', 'ALT: 7-56 U/L, AST: 10-40 U/L', 'U/L', 2200, 60],
        ['RFT', 'Renal Function Tests (Creatinine, Urea, Electrolytes)', 'biochemistry', 'Serum', 'Creatinine: 60-110 umol/L, Urea: 2.5-7.1 mmol/L', 'umol/L', 2000, 60],
        ['LIP', 'Lipid Profile (Total Cholesterol, HDL, LDL, Triglycerides)', 'biochemistry', 'Serum', 'Cholesterol: < 5.2 mmol/L', 'mmol/L', 1800, 45],
        ['WID', 'Widal Test (Typhoid Serology)', 'microbiology', 'Serum', 'O & H Titres < 1:80', 'titer', 700, 30],
        ['CXR', 'Chest X-Ray PA View', 'radiology', 'Imaging', 'Clear lung fields, Normal cardiothoracic ratio', '', 1800, 40],
        ['USS-ABD', 'Abdominal Ultrasound', 'ultrasound', 'Imaging', 'Liver, gallbladder, spleen, kidneys normal', '', 3000, 45],
        ['ECG', '12-Lead Electrocardiogram', 'ecg', 'Cardiology', 'Normal sinus rhythm, Rate: 60-100 bpm', 'bpm', 1500, 20],
    ];

    $services = [
        ['consultation', 'CONS-GP', 'General Practitioner Consultation', 1000],
        ['consultation', 'CONS-SPEC', 'Specialist Physician Consultation', 2500],
        ['consultation', 'CONS-EMERG', 'Emergency Room Triage & Review', 1500],
        ['procedure', 'PROC-WOUND', 'Wound Debridement & Dressing', 800],
        ['procedure', 'PROC-SUTURE', 'Minor Wound Suturing', 1800],
        ['procedure', 'PROC-NEB', 'Nebulization Treatment', 600],
        ['procedure', 'PROC-CATH', 'Urinary Catheterization', 1200],
        ['procedure', 'PROC-IV', 'Intravenous Cannulation & Infusion', 500],
        ['nursing', 'NURSE-CARE', 'Daily Nursing & Inpatient Observation', 800],
        ['bed_day', 'BED-GEN', 'General Ward Bed Accommodation (Daily)', 1500],
        ['bed_day', 'BED-PVT', 'Private Room Bed Accommodation (Daily)', 4500],
        ['bed_day', 'BED-ICU', 'Intensive Care Unit (ICU) Bed (Daily)', 15000],
    ];

    $wards = [
        ['Male Medical Ward', 'MMW', 'general_male', 1, 6, 1500],
        ['Female Medical Ward', 'FMW', 'general_female', 1, 6, 1500],
        ['Pediatric Ward', 'PED', 'pediatric', 2, 4, 1200],
        ['Maternity & Postnatal Ward', 'MAT', 'maternity', 2, 4, 2000],
        ['Intensive Care Unit', 'ICU', 'icu', 3, 2, 15000],
    ];

    $insurances = [
        ['Social Health Authority (SHA / NHIF)', 'SHA-001', '+254202723255', 'claims@sha.go.ke'],
        ['Jubilee Health Insurance', 'JUB-002', '+254709949000', 'claims@jubileekenya.com'],
        ['AAR Insurance Kenya', 'AAR-003', '+254703063000', 'care@aar.co.ke'],
        ['Old Mutual Health', 'UAP-004', '+254711010000', 'healthcare@oldmutual.co.ke'],
        ['Britam Health Plan', 'BRT-005', '+254705100100', 'medclaims@britam.com'],
    ];

    foreach ($tenants as $t) {
        TenantManager::run($t, function () use ($t, $tests, $services, $wards, $insurances) {
            $currency = $t['currency'];
            $scale = $t['country_code'] === 'NG' ? 10 : 1;

            // 1. Diagnostic Tests
            foreach ($tests as [$code, $name, $cat, $spec, $norm, $unit, $price, $tat]) {
                $pMinor = to_minor($price * $scale, $currency);
                DB::statement(
                    'INSERT INTO diagnostic_tests (tenant_id, code, name, category, specimen_type, normal_range, units, price_minor, turnaround_minutes, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE name = VALUES(name), price_minor = VALUES(price_minor)',
                    [$t['id'], $code, $name, $cat, $spec, $norm, $unit, $pMinor, $tat]
                );
            }

            // 2. Billable Services
            foreach ($services as [$cat, $code, $name, $price]) {
                $pMinor = to_minor($price * $scale, $currency);
                DB::statement(
                    'INSERT INTO billable_services (tenant_id, category, code, name, price_minor, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE name = VALUES(name), price_minor = VALUES(price_minor)',
                    [$t['id'], $cat, $code, $name, $pMinor]
                );
            }

            // 3. Wards & Beds
            foreach ($wards as [$wName, $code, $type, $floor, $bedCount, $rate]) {
                $rMinor = to_minor($rate * $scale, $currency);
                DB::statement(
                    'INSERT INTO wards (tenant_id, branch_id, name, code, type, floor_number, is_active, created_at, updated_at)
                     VALUES (?, 1, ?, ?, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE name = VALUES(name)',
                    [$t['id'], $wName, $code, $type, $floor]
                );
                $ward = DB::selectOne('SELECT id FROM wards WHERE tenant_id = ? AND code = ?', [$t['id'], $code]);
                if ($ward) {
                    for ($b = 1; $b <= $bedCount; $b++) {
                        $bedNo = $code . '-B' . str_pad((string) $b, 2, '0', STR_PAD_LEFT);
                        DB::statement(
                            'INSERT INTO beds (tenant_id, ward_id, bed_number, daily_rate_minor, status, created_at, updated_at)
                             VALUES (?, ?, ?, ?, "available", NOW(), NOW())
                             ON DUPLICATE KEY UPDATE daily_rate_minor = VALUES(daily_rate_minor)',
                            [$t['id'], $ward['id'], $bedNo, $rMinor]
                        );
                    }
                }
            }

            // 4. Insurance Providers
            foreach ($insurances as [$name, $code, $phone, $email]) {
                DB::statement(
                    'INSERT INTO insurance_providers (tenant_id, name, code, phone, email, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE name = VALUES(name)',
                    [$t['id'], $name, $code, $phone, $email]
                );
            }
        });
    }

    $say('  ✔ Clinical catalog: Diagnostic tests, Wards & Beds, Billable services & Insurances seeded for all hospitals.');
};
