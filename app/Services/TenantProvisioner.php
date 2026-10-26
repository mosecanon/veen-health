<?php
namespace App\Services;

use App\Models\Branch;
use App\Models\Display;
use App\Models\ProductCategory;
use App\Models\Queue;
use App\Models\ServicePoint;
use Veen\Core\Database\DB;
use Veen\Core\Tenancy\TenantManager;

/**
 * Creates a fully-working hospital workspace: tenant, main branch, admin user,
 * default queues/rooms/display and pharmacy categories.
 */
class TenantProvisioner
{
    public const DEFAULT_QUEUES = [
        ['Reception & Triage', 'T', '#f59e0b', ['Triage Desk 1', 'Triage Desk 2']],
        ['Consultation', 'C', '#14b8a6', ['Consultation Room 1', 'Consultation Room 2', 'Consultation Room 3']],
        ['Laboratory', 'L', '#8b5cf6', ['Lab Sample Room']],
        ['Pharmacy', 'P', '#10b981', ['Pharmacy Window 1', 'Pharmacy Window 2']],
        ['Cashier', 'B', '#3b82f6', ['Cashier Counter 1']],
    ];

    public const DEFAULT_CATEGORIES = ['Analgesics', 'Antibiotics', 'Antimalarials', 'Cough & Cold', 'Vitamins & Supplements', 'First Aid', 'Personal Care', 'Chronic Care'];

    public const DEFAULT_TESTS = [
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

    public const DEFAULT_SERVICES = [
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

    public const DEFAULT_WARDS = [
        ['Male Medical Ward', 'MMW', 'general_male', 1, 6, 1500],
        ['Female Medical Ward', 'FMW', 'general_female', 1, 6, 1500],
        ['Pediatric Ward', 'PED', 'pediatric', 2, 4, 1200],
        ['Maternity & Postnatal Ward', 'MAT', 'maternity', 2, 4, 2000],
        ['Intensive Care Unit', 'ICU', 'icu', 3, 2, 15000],
    ];

    public const DEFAULT_INSURANCES = [
        ['National Health Insurance / Social Health Fund', 'NHIF-001', '+254202723255', 'claims@sha.go.ke'],
        ['Jubilee Health Insurance', 'JUB-002', '+254709949000', 'claims@jubileekenya.com'],
        ['AAR Insurance Kenya', 'AAR-003', '+254703063000', 'care@aar.co.ke'],
        ['Old Mutual Health', 'UAP-004', '+254711010000', 'healthcare@oldmutual.co.ke'],
        ['Britam Health Plan', 'BRT-005', '+254705100100', 'medclaims@britam.com'],
    ];

    /**
     * @param array $data name, slug, country_code, plan_id?, billing_cycle?, trial_days?, email?, phone?, admin_name, admin_email, admin_password
     */
    public static function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $country = DB::selectOne('SELECT * FROM countries WHERE code = ?', [$data['country_code']])
                ?? throw new \InvalidArgumentException('Unknown country code: ' . ($data['country_code'] ?? ''));

            $now = now();
            $billingCycle = $data['billing_cycle'] ?? 'free_trial';
            $trialDays = max(1, (int) ($data['trial_days'] ?? 14));

            if ($billingCycle === 'free_trial') {
                $status = 'trial';
                $trialEndsAt = date('Y-m-d', strtotime("+$trialDays days"));
                $subscriptionExpiresAt = null;
            } elseif ($billingCycle === 'monthly') {
                $status = 'active';
                $trialEndsAt = null;
                $subscriptionExpiresAt = date('Y-m-d', strtotime('+1 month'));
            } elseif ($billingCycle === 'yearly') {
                $status = 'active';
                $trialEndsAt = null;
                $subscriptionExpiresAt = date('Y-m-d', strtotime('+1 year'));
            } else { // one_time
                $status = 'active';
                $trialEndsAt = null;
                $subscriptionExpiresAt = '2099-12-31';
            }

            $tenantUuid = function_exists('generate_uuid') ? generate_uuid() : null;
            DB::statement(
                'INSERT INTO tenants (uuid, name, slug, country_code, currency, timezone, locale, plan_id, status, billing_cycle, trial_ends_at, subscription_expires_at, email, phone, address, primary_color, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantUuid,
                    $data['name'], $data['slug'], $country['code'], $country['currency'], $country['timezone'], $country['locale'],
                    $data['plan_id'] ?? null, $status, $billingCycle, $trialEndsAt, $subscriptionExpiresAt,
                    $data['email'] ?? null, $data['phone'] ?? null, $data['address'] ?? null, $data['primary_color'] ?? '#14b8a6', $now, $now,
                ]
            );
            $tenantId = (int) DB::connection()->lastInsertId();
            $tenant = TenantManager::find($tenantId);

            return TenantManager::run($tenant, function () use ($tenant, $data, $country) {
                $branch = Branch::create(['name' => $data['branch_name'] ?? 'Main Hospital', 'code' => 'MAIN', 'is_main' => 1, 'phone' => $data['phone'] ?? null]);

                DB::statement(
                    'INSERT INTO users (tenant_id, branch_id, name, email, password, role, title, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
                    [$tenant['id'], $branch->id, $data['admin_name'], strtolower(trim($data['admin_email'])), password_hash($data['admin_password'], PASSWORD_DEFAULT), 'hospital_admin', 'Administrator']
                );

                $queueIds = [];
                foreach (self::DEFAULT_QUEUES as $i => [$name, $prefix, $color, $rooms]) {
                    $q = Queue::create(['branch_id' => $branch->id, 'name' => $name, 'prefix' => $prefix, 'color' => $color, 'sort_order' => $i, 'is_active' => 1]);
                    $queueIds[] = $q->id;
                    foreach ($rooms as $room) {
                        ServicePoint::create(['branch_id' => $branch->id, 'queue_id' => $q->id, 'name' => $room, 'announce_as' => $room, 'is_active' => 1]);
                    }
                }

                $voice = match ($country['locale']) { 'sw' => 'sw-KE', 'fr' => 'fr-FR', default => 'en-GB' };
                $display = new Display();
                $display->fill([
                    'branch_id' => $branch->id, 'name' => 'Main Waiting Area', 'voice_enabled' => 1, 'voice_lang' => $voice,
                    'announcement_template' => 'Ticket {code}, please proceed to {room}', 'chime' => 1, 'repeat_count' => 2,
                    'ticker_text' => 'Welcome to ' . $tenant['name'] . '. Please keep your ticket and watch the screen for your number.',
                ])->forceFill(['token' => bin2hex(random_bytes(20))])->save();

                // Pharmacy Categories
                foreach (self::DEFAULT_CATEGORIES as $cat) {
                    $existingCat = ProductCategory::where('name', $cat)->first();
                    if (!$existingCat) {
                        ProductCategory::create(['name' => $cat]);
                    }
                }

                // Clinical Catalog: Diagnostic Tests
                $scale = $country['currency'] === 'KES' ? 1.0 : ($country['currency'] === 'NGN' ? 10.0 : 0.01);
                foreach (self::DEFAULT_TESTS as [$code, $testName, $cat, $specimen, $ref, $unit, $price, $tat]) {
                    DB::statement(
                        'INSERT INTO diagnostic_tests (tenant_id, code, name, category, specimen_type, normal_range, units, price_minor, turnaround_minutes, is_active, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), price_minor = VALUES(price_minor), updated_at = NOW()',
                        [$tenant['id'], $code, $testName, $cat, $specimen, $ref, $unit, to_minor($price * $scale, $country['currency']), $tat]
                    );
                }

                // Billable Services
                foreach (self::DEFAULT_SERVICES as [$cat, $code, $srvName, $price]) {
                    DB::statement(
                        'INSERT INTO billable_services (tenant_id, category, code, name, price_minor, is_active, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), price_minor = VALUES(price_minor), updated_at = NOW()',
                        [$tenant['id'], $cat, $code, $srvName, to_minor($price * $scale, $country['currency'])]
                    );
                }

                // Wards & Beds
                foreach (self::DEFAULT_WARDS as [$wardName, $wardCode, $wType, $floor, $bedCount, $rate]) {
                    DB::statement(
                        'INSERT INTO wards (tenant_id, branch_id, name, code, type, floor_number, is_active, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = NOW()',
                        [$tenant['id'], $branch->id, $wardName, $wardCode, $wType, $floor]
                    );
                    $wardRow = DB::selectOne('SELECT id FROM wards WHERE tenant_id = ? AND code = ?', [$tenant['id'], $wardCode]);
                    $wardId = (int) ($wardRow['id'] ?? DB::connection()->lastInsertId());
                    for ($b = 1; $b <= $bedCount; $b++) {
                        $bedNo = $wardCode . '-' . str_pad((string) $b, 2, '0', STR_PAD_LEFT);
                        DB::statement(
                            'INSERT INTO beds (tenant_id, ward_id, bed_number, daily_rate_minor, status, created_at, updated_at)
                             VALUES (?, ?, ?, ?, "available", NOW(), NOW())
                             ON DUPLICATE KEY UPDATE daily_rate_minor = VALUES(daily_rate_minor), updated_at = NOW()',
                            [$tenant['id'], $wardId, $bedNo, to_minor($rate * $scale, $country['currency'])]
                        );
                    }
                }

                // Insurance Providers
                foreach (self::DEFAULT_INSURANCES as [$insName, $insCode, $insPhone, $insEmail]) {
                    DB::statement(
                        'INSERT INTO insurance_providers (tenant_id, name, code, phone, email, is_active, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), email = VALUES(email), updated_at = NOW()',
                        [$tenant['id'], $insName, $insCode, $insPhone, $insEmail]
                    );
                }

                // Seed Default Hospital Roles & Granular Permissions
                $defaultRoles = [
                    ['Medical Doctor / Physician', 'doctor', 'Clinical consultations, EMR notes, ICD-10 diagnoses, vitals, lab orders, and prescriptions.', ['clinical_emr','consultations','appointments','inpatient','patients','lab_view','reports_view','clinic_kids','clinic_maternal','clinic_ccc','radiology','mortuary']],
                    ['Staff Nurse', 'nurse', 'Patient triage, vital signs, queue management, and inpatient bed care.', ['queue','triage','patients','appointments','inpatient','consultations_view','clinic_kids','clinic_maternal','clinic_ccc','radiology']],
                    ['Pharmacist / Dispenser', 'pharmacist', 'Pharmacy OTC Point of Sale, prescription dispensing, inventory batches, and cash register.', ['pharmacy','pharmacy_pos','pharmacy_inventory','patients','reports_view']],
                    ['Hospital Cashier', 'cashier', 'Patient invoicing, cash and mobile money collection, receipts, and insurance claims.', ['billing','invoices','payments','claims','queue','patients','reports_view']],
                    ['Laboratory Technologist', 'lab_tech', 'LIMS test processing, specimen collection, and result verification.', ['lab','laboratory','specimen','results','patients']],
                    ['Hospital Administrator', 'hospital_admin', 'Full administrative management of hospital staff, wards, finance, HR, and configuration.', ['clinical_emr','consultations','pharmacy','pharmacy_pos','pharmacy_inventory','lab','laboratory','inpatient','billing','queue','appointments','hr','expenses','reports','reports_view','staff_admin','clinic_kids','clinic_maternal','clinic_ccc','radiology','mortuary']],
                ];
                foreach ($defaultRoles as [$rName, $rSlug, $rDesc, $rPerms]) {
                    DB::statement(
                        'INSERT INTO hospital_roles (tenant_id, name, slug, description, is_system, permissions, created_at, updated_at)
                         VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), permissions = VALUES(permissions), updated_at = NOW()',
                        [$tenant['id'], $rName, $rSlug, $rDesc, json_encode($rPerms)]
                    );
                }

                return $tenant;
            });
        });
    }
}
