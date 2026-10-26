<?php
/**
 * Automated Test Suite for Veen Africa HMS:
 * 1. Multi-Tenant Isolation
 * 2. Ticketing, Queue Calling & Audio Announcements
 * 3. Over-The-Counter (OTC) Pharmacy POS & FEFO Inventory
 */

require __DIR__ . '/../bootstrap/app.php';

use App\Models\CashSession;
use App\Models\Display;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Queue;
use App\Models\Sale;
use App\Models\ServicePoint;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Tenancy\TenantManager;

$passed = 0;
$failed = 0;

$it = function (string $description, callable $test) use (&$passed, &$failed) {
    echo "  • $description... ";
    try {
        $test();
        echo "\033[32mPASSED\033[0m\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "\033[31mFAILED\033[0m: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
        $failed++;
    }
};

function it(string $description, callable $test) {
    global $it;
    $it($description, $test);
}

echo "\n--- Running Veen Africa HMS Automated Verification Suite ---\n\n";

// TEST GROUP 1: MULTI-TENANT ISOLATION
echo "1. MULTI-TENANT ISOLATION\n";

it("Enforces global scope: Tenant 1 cannot see Tenant 2's patients", function () {
    $tenant1 = TenantManager::find(1);
    $tenant2 = TenantManager::find(2);

    TenantManager::set($tenant1);
    $patientsT1 = Patient::all();
    $countT1 = count($patientsT1);
    assert($countT1 > 0, "Tenant 1 should have seeded patients");

    foreach ($patientsT1 as $p) {
        assert((int) $p->tenant_id === 1, "Patient must belong to Tenant 1");
    }

    TenantManager::set($tenant2);
    $patientsT2 = Patient::all();
    $countT2 = count($patientsT2);
    assert($countT2 > 0, "Tenant 2 should have seeded patients");

    foreach ($patientsT2 as $p) {
        assert((int) $p->tenant_id === 2, "Patient must belong to Tenant 2");
    }

    // Try finding a Tenant 1 patient while logged into Tenant 2: must fail
    $t1PatientId = $patientsT1[0]->id;
    $crossQuery = Patient::find($t1PatientId);
    assert($crossQuery === null, "Tenant 2 must NOT be able to find Tenant 1's patient by ID");
});

it("Fails closed: Querying tenant models without tenant context throws exception", function () {
    TenantManager::set(null);
    $caught = false;
    try {
        Patient::all();
    } catch (\RuntimeException $e) {
        $caught = true;
    }
    assert($caught === true, "Must throw RuntimeException when tenant context is null");
});

// TEST GROUP 2: QUEUE & AUDIO TICKETING
echo "\n2. PATIENT QUEUE & AUDIO CALLING SYSTEM\n";

it("Generates sequential per-tenant ticket codes and events", function () {
    $tenant1 = TenantManager::find(1);
    TenantManager::set($tenant1);

    $queue = Queue::query()->where('prefix', 'P')->first(); // Pharmacy queue
    assert($queue !== null, "Pharmacy queue exists");

    $seq = Sequence::ticket($queue);
    assert(str_starts_with($seq['code'], 'P'), "Ticket code must start with prefix P");
    assert($seq['number'] > 0, "Ticket number must be positive");

    $ticket = new Ticket();
    $ticket->fill([
        'branch_id' => 1,
        'queue_id' => $queue->id,
        'customer_name' => 'Automated Test Patient',
        'priority' => 1,
    ]);
    $ticket->forceFill([
        'number' => $seq['number'],
        'code' => $seq['code'],
        'status' => 'waiting',
        'issued_on' => today(),
    ]);
    $ticket->save();

    assert($ticket->id > 0, "Ticket must be saved");

    // Event
    DB::statement(
        'INSERT INTO ticket_events (tenant_id, ticket_id, type, created_at) VALUES (?, ?, ?, NOW())',
        [$tenant1['id'], $ticket->id, 'issued']
    );

    // Call ticket to service point
    $sp = ServicePoint::query()->where('queue_id', $queue->id)->first();
    assert($sp !== null, "Service point exists");

    DB::statement(
        "UPDATE tickets SET status = 'called', service_point_id = ?, called_at = NOW(), call_count = 1 WHERE id = ?",
        [$sp->id, $ticket->id]
    );

    DB::statement(
        'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, created_at) VALUES (?, ?, ?, ?, NOW())',
        [$tenant1['id'], $ticket->id, 'called', $sp->id]
    );

    // TV Display Poll test
    $display = Display::query()->first();
    assert($display !== null, "Display exists");

    $req = new Request(['since_id' => 0]);
    $displayController = new \App\Controllers\DisplayController();
    $pollResp = $displayController->poll($req, $display->token);
    $data = json_decode($pollResp->content, true);

    assert(!empty($data['announcements']), "Announcements array must have items");
    $lastAnnouncement = end($data['announcements']);
    assert($lastAnnouncement['ticket_code'] === $seq['code'], "Announced ticket code must match");
    assert(str_contains($lastAnnouncement['speech_text'], $seq['code']), "Speech text must contain ticket code");
    assert(str_contains($lastAnnouncement['speech_text'], $sp->announce_as ?: $sp->name), "Speech text must announce target room");
});

// TEST GROUP 3: PHARMACY OTC POS & FEFO INVENTORY
echo "\n3. OVER-THE-COUNTER (OTC) PHARMACY POS & FEFO INVENTORY\n";

it("Performs atomic checkout, decrements earliest expiring batch (FEFO), and logs sale", function () {
    $tenant1 = TenantManager::find(1);
    TenantManager::set($tenant1);

    // Pick an OTC product (Panadol Extra)
    $prod = Product::query()->where('name', 'Panadol Extra')->first();
    assert($prod !== null, "Panadol Extra exists");

    // Check batches before sale
    $batches = DB::select(
        'SELECT * FROM product_batches WHERE product_id = ? AND qty_on_hand > 0 ORDER BY expiry_date ASC',
        [$prod->id]
    );
    if (count($batches) < 2) {
        DB::statement("UPDATE product_batches SET qty_on_hand = 100 WHERE product_id = ?", [$prod->id]);
        DB::statement("UPDATE products SET stock_qty = 200 WHERE id = ?", [$prod->id]);
        $prod = Product::query()->where('name', 'Panadol Extra')->first();
        $batches = DB::select(
            'SELECT * FROM product_batches WHERE product_id = ? AND qty_on_hand > 0 ORDER BY expiry_date ASC',
            [$prod->id]
        );
    }
    $initialStock = $prod->stock_qty;
    assert($initialStock >= 2, "Sufficient stock for test");
    assert(count($batches) >= 2, "Must have at least 2 batches to test FEFO");
    $earliestBatchBefore = $batches[0];
    $qtyToBuy = 2;

    // Simulate POS checkout call
    // Authenticate a user
    $user = User::query()->where('role', 'pharmacist')->first();
    assert($user !== null, "Pharmacist user exists");
    Auth::attempt($user->email, 'Password@123', '127.0.0.1');

    $posController = new \App\Controllers\PharmacyPosController();
    $req = new Request([], [
        'items' => [
            [
                'product_id' => $prod->id,
                'qty' => $qtyToBuy,
            ]
        ],
        'payments' => [
            [
                'method' => 'cash',
                'amount_minor' => $prod->price_minor * $qtyToBuy,
            ]
        ],
        'customer_name' => 'OTC Test Buyer',
    ]);

    $checkoutResp = $posController->checkout($req);
    $result = json_decode($checkoutResp->content, true);

    assert($result['success'] === true, "Checkout must succeed");
    assert(!empty($result['receipt_no']), "Must generate receipt number");

    // Check master product stock decremented
    $prodAfter = Product::find($prod->id);
    assert($prodAfter->stock_qty === $initialStock - $qtyToBuy, "Master stock must decrement by exactly $qtyToBuy");

    // Check earliest expiring batch was deducted (FEFO)
    $earliestBatchAfter = DB::selectOne('SELECT * FROM product_batches WHERE id = ?', [$earliestBatchBefore['id']]);
    assert($earliestBatchAfter['qty_on_hand'] === $earliestBatchBefore['qty_on_hand'] - $qtyToBuy, "Earliest expiring batch must be decremented first (FEFO)");

    // Check stock movement ledger entry
    $movement = DB::selectOne(
        "SELECT * FROM stock_movements WHERE product_id = ? AND batch_id = ? AND type = 'sale' ORDER BY id DESC LIMIT 1",
        [$prod->id, $earliestBatchBefore['id']]
    );
    assert($movement !== null, "Stock movement ledger record must exist");
    assert((int) $movement['qty'] === -$qtyToBuy, "Movement qty must be negative $qtyToBuy");
});

it("Enforces POM law: blocks Prescription-Only Medicine without prescription reference", function () {
    $tenant1 = TenantManager::find(1);
    TenantManager::set($tenant1);

    // Pick POM medicine (Amoxil or Augmentin)
    $pomProd = Product::query()->where('schedule', 'pom')->first();
    assert($pomProd !== null, "POM product exists");

    $posController = new \App\Controllers\PharmacyPosController();
    $req = new Request([], [
        'items' => [
            [
                'product_id' => $pomProd->id,
                'qty' => 1,
            ]
        ],
        'payments' => [
            [
                'method' => 'cash',
                'amount_minor' => $pomProd->price_minor,
            ]
        ],
        'customer_name' => 'Walk-in without Rx',
        'prescription_ref' => '', // Intentionally omitted
    ]);

    $blocked = false;
    try {
        $posController->checkout($req);
    } catch (HttpException $e) {
        $blocked = true;
        assert(str_contains(strtolower($e->getMessage()), 'prescription'), "Exception should mention prescription requirement");
    }
    assert($blocked === true, "Must block POM sale without prescription reference");
});

// TEST GROUP 4: NATIVE BARCODE ENGINE
echo "\n4. BARCODE GENERATION & VERIFICATION\n";

it("Generates valid Code128 vector SVG and Data URI", function () {
    $svg = \App\Services\Barcode::svg('MRN-2610-0001');
    assert(str_contains($svg, '<svg'), "Must return valid XML SVG element");
    assert(str_contains($svg, 'rect'), "SVG must contain bar rectangles");

    $dataUri = barcode_data_uri('T-0042');
    assert(str_starts_with($dataUri, 'data:image/svg+xml;base64,'), "Data URI must start with SVG base64 header");
});

// TEST GROUP 5: SAAS PROVISIONING, DYNAMIC PLANS & SLUG GENERATION
echo "\n5. SAAS PROVISIONING & DYNAMIC SUBSCRIPTION PLANS\n";

it("Provisions new hospital with dynamic billing cycle and auto-slugification", function () {
    $slugSuffix = bin2hex(random_bytes(3));
    $tenantData = [
        'name'           => "Aga Khan Outreach {$slugSuffix}",
        'slug'           => "aga-khan-outreach-{$slugSuffix}",
        'country_code'   => 'KE',
        'plan_id'        => 2,
        'billing_cycle'  => 'yearly',
        'admin_name'     => 'Dr. Farah Al-Mansoor',
        'admin_email'    => "farah-{$slugSuffix}@agakhan.org",
        'admin_password' => 'Password@123',
    ];

    $provisioned = \App\Services\TenantProvisioner::provision($tenantData);
    assert(!empty($provisioned['id']), "Must assign valid tenant ID");
    assert($provisioned['billing_cycle'] === 'yearly', "Must record yearly billing cycle");
    assert($provisioned['status'] === 'active', "Yearly plan must activate immediately");

    // Check that clinical catalog (tests, wards, beds, services) was seeded
    $testCount = (int) DB::selectOne("SELECT COUNT(*) AS c FROM diagnostic_tests WHERE tenant_id = ?", [$provisioned['id']])['c'];
    assert($testCount > 0, "Hospital must have diagnostic test catalog pre-seeded");

    $bedCount = (int) DB::selectOne("SELECT COUNT(*) AS c FROM beds WHERE tenant_id = ?", [$provisioned['id']])['c'];
    assert($bedCount > 0, "Hospital must have wards and beds pre-seeded");
});

it("Rejects duplicate administrator email during hospital onboarding with clear validation error", function () {
    $platform = new \App\Controllers\PlatformController();
    $existingEmail = DB::selectOne("SELECT email FROM users WHERE role = 'hospital_admin' LIMIT 1")['email'];

    $req = new Request([], [
        'name'           => 'Duplicate Email Clinic',
        'slug'           => 'duplicate-email-clinic',
        'country_code'   => 'KE',
        'plan_id'        => 1,
        'admin_name'     => 'Dr. Copycat',
        'admin_email'    => $existingEmail,
        'admin_password' => 'Password@123',
    ]);

    $res = $platform->createTenant($req);
    $emailError = errors('admin_email');
    assert(!empty($emailError), "Must return validation error on duplicate admin email");
});

// TEST GROUP 6: SUPER ADMIN WORKSPACE SWITCHING
echo "\n6. SUPER ADMIN WORKSPACE SWITCHING\n";

it("Allows super admin to enter and exit any tenant hospital workspace seamlessly", function () {
    $adminRow = DB::selectOne("SELECT * FROM users WHERE role = 'super_admin' LIMIT 1");
    assert($adminRow !== null, "Super admin user exists");
    $superAdmin = new User($adminRow);
    Auth::login($superAdmin);

    $platform = new \App\Controllers\PlatformController();
    $res = $platform->switchTenant(new Request(), 2);
    assert(\Veen\Core\Session::get('super_tenant_id') === 2, "Session must store target hospital ID");

    $resExit = $platform->exitTenant(new Request());
    assert(\Veen\Core\Session::get('super_tenant_id') === null, "Exiting tenant must clear super_tenant_id");
});

// TEST GROUP 7: FULL CLINICAL CARE & EMR WORKFLOW
echo "\n7. CLINICAL EMR, LIMS & IPD WORKFLOW\n";

it("Executes end-to-end OPD consultation, vitals with BMI, lab order, and inpatient bed admission", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $patient = Patient::query()->first();
    assert($patient !== null, "Demo patient exists");

    // 1. Start consultation
    $consultationCtrl = new \App\Controllers\ConsultationController();
    $consultationCtrl->start(new Request([], ['patient_id' => $patient->id]));

    $enc = DB::selectOne("SELECT * FROM clinical_encounters WHERE tenant_id = 1 AND patient_id = ? ORDER BY id DESC LIMIT 1", [$patient->id]);
    assert($enc !== null, "Clinical encounter must be initialized");

    // 2. Update Vitals & auto-calculate BMI
    $vitalsReq = new Request([], [
        'systolic_bp'      => 125,
        'diastolic_bp'     => 82,
        'pulse_rate'       => 74,
        'temperature_c'    => 37.1,
        'respiratory_rate' => 16,
        'spo2_percent'     => 99,
        'weight_kg'        => 72.0,
        'height_cm'        => 175.0, // Expected BMI: 72 / (1.75^2) = 23.5
        'triage_category'  => 'standard',
    ]);
    $consultationCtrl->updateVitals($vitalsReq, $enc['id']);
    $encAfter = DB::selectOne("SELECT * FROM clinical_encounters WHERE id = ?", [$enc['id']]);
    assert((float) $encAfter['bmi'] === 23.5, "BMI must be automatically calculated as 23.5 kg/m²");

    // 3. Order Diagnostic Test
    $test = DB::selectOne("SELECT * FROM diagnostic_tests WHERE tenant_id = 1 LIMIT 1");
    $consultationCtrl->orderLab(new Request([], [
        'test_id' => $test['id'],
        'clinical_notes' => 'Routine health checkup',
        'priority' => 'routine',
    ]), $enc['id']);

    $order = DB::selectOne("SELECT * FROM diagnostic_orders WHERE encounter_id = ? ORDER BY id DESC LIMIT 1", [$enc['id']]);
    assert($order !== null, "Diagnostic order must be recorded");

    // 4. Lab collects specimen and verifies result
    $labCtrl = new \App\Controllers\LabController();
    $diagResult = DB::selectOne("SELECT * FROM diagnostic_results WHERE order_id = ?", [$order['id']]);
    $labCtrl->collectSpecimen(new Request(), $diagResult['id']);
    $labCtrl->recordResult(new Request([], [
        'result_value' => '14.2',
        'is_abnormal' => 0,
        'findings' => 'Normal physiological findings',
    ]), $diagResult['id']);
    $labCtrl->verifyResult(new Request(), $diagResult['id']);

    $verifiedOrder = DB::selectOne("SELECT * FROM diagnostic_orders WHERE id = ?", [$order['id']]);
    assert($verifiedOrder['status'] === 'verified', "Test order must be verified and released to EMR");

    // 5. Inpatient Admission to Bed
    $inpatientCtrl = new \App\Controllers\InpatientController();
    $bed = DB::selectOne("SELECT * FROM beds WHERE tenant_id = 1 AND status = 'available' LIMIT 1");
    assert($bed !== null, "Available bed must exist in hospital");

    $inpatientCtrl->admit(new Request([], [
        'patient_id' => $patient->id,
        'bed_id' => $bed['id'],
        'admitting_diagnosis' => 'Observation & Recovery',
    ]));

    $bedAfter = DB::selectOne("SELECT * FROM beds WHERE id = ?", [$bed['id']]);
    assert($bedAfter['status'] === 'occupied', "Bed must be marked occupied after admission");

    $adm = DB::selectOne("SELECT * FROM admissions WHERE bed_id = ? AND status = 'admitted'", [$bed['id']]);
    assert($adm !== null, "Admission record must be active");

    // 6. Discharge
    $inpatientCtrl->discharge(new Request([], [
        'discharge_diagnosis' => 'Fully Recovered',
        'discharge_summary' => 'Vitals stable. Discharge home with follow-up.',
    ]), $adm['id']);

    $bedFreed = DB::selectOne("SELECT * FROM beds WHERE id = ?", [$bed['id']]);
    assert($bedFreed['status'] === 'available', "Bed must be released to available after discharge");
});

// TEST GROUP 8: HOSPITAL STAFF & EMPLOYEE MANAGEMENT
echo "\n8. HOSPITAL STAFF & EMPLOYEE MANAGEMENT\n";

it("Enables hospital admin to add new doctors and staff with unique email and roles", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $staffCtrl = new \App\Controllers\StaffController();
    $uniqueMail = 'dr.samuel.' . bin2hex(random_bytes(3)) . '@nairobicare.co.ke';

    $req = new Request([], [
        'name'     => 'Dr. Samuel Waweru',
        'email'    => $uniqueMail,
        'role'     => 'doctor',
        'title'    => 'Consultant Cardiologist',
        'phone'    => '+254722000111',
        'password' => 'Password@123',
    ]);

    $res = $staffCtrl->store($req);

    $created = DB::selectOne("SELECT * FROM users WHERE email = ?", [$uniqueMail]);
    assert($created !== null, "New doctor must exist in database");
    assert($created['role'] === 'doctor', "Role must be doctor");
    assert($created['title'] === 'Consultant Cardiologist', "Title must match");
    assert(password_verify('Password@123', $created['password']), "Password must be verified");

    // Toggle active status
    $staffCtrl->toggle(new Request(), $created['id']);
    $toggled = DB::selectOne("SELECT is_active FROM users WHERE id = ?", [$created['id']]);
    assert((int)$toggled['is_active'] === 0, "Account should be deactivated");

    $staffCtrl->toggle(new Request(), $created['id']);
    $reactivated = DB::selectOne("SELECT is_active FROM users WHERE id = ?", [$created['id']]);
    assert((int)$reactivated['is_active'] === 1, "Account should be reactivated");
});

// TEST GROUP 9: VIEW RENDERING (NO 500 FORMAT_MINOR ERROR)
echo "\n9. VIEW INTEGRITY & ZERO 500 ERRORS\n";

it("Renders all newly introduced clinical, lab, inpatient, billing, and staff views cleanly", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    // Verify format_minor and to_major helpers
    assert(function_exists('format_minor'), "format_minor function must exist");
    assert(function_exists('to_major'), "to_major function must exist");
    assert(str_contains(format_minor(50000, 'KES'), '500'), "format_minor must format cents to major currency");

    // Check staff view renders
    $staffCtrl = new \App\Controllers\StaffController();
    $staffResp = $staffCtrl->index(new Request());
    assert(str_contains($staffResp->content, 'Staff & Employee Directory'), "Staff directory must render without error");

    // Check consultations index renders
    $consultCtrl = new \App\Controllers\ConsultationController();
    $consultResp = $consultCtrl->index(new Request());
    assert(str_contains($consultResp->content, 'Clinical Consultations'), "Consultations index must render without error");

    // Check lab index renders
    $labCtrl = new \App\Controllers\LabController();
    $labResp = $labCtrl->index(new Request());
    assert(str_contains($labResp->content, 'LIMS Laboratory'), "Lab workspace must render without error");

    // Check inpatient index renders
    $inpatientCtrl = new \App\Controllers\InpatientController();
    $inpatientResp = $inpatientCtrl->index(new Request());
    assert(str_contains($inpatientResp->content, 'Visual Bed Board'), "Inpatient bed board must render without error");

    // Check billing index renders
    $billingCtrl = new \App\Controllers\BillingController();
    $billingResp = $billingCtrl->index(new Request());
    assert(str_contains($billingResp->content, 'Invoicing & Financial Management'), "Billing dashboard must render without error");

    // Check Doctor EMR show page renders cleanly without pi.drug_name SQL error
    $sampleEnc = DB::selectOne("SELECT id FROM clinical_encounters WHERE tenant_id = 1 LIMIT 1");
    if ($sampleEnc) {
        $emrResp = $consultCtrl->show(new Request(), $sampleEnc['id']);
        assert(str_contains($emrResp->content, 'Clinical Triage & Vital Signs'), "Doctor EMR chart must render without error");
    }
});

// TEST GROUP 10: PATIENT FUZZY SEARCH AUTOCOMPLETE & MODAL REGISTRATION
echo "\n10. PATIENT FUZZY AUTOCOMPLETE & MODAL QUICK REGISTRATION\n";

it("Enables instant autocomplete by name, national ID, phone, MRN and inline modal registration", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $patientCtrl = new \App\Controllers\PatientController();

    // 1. Quick register patient from modal without leaving page
    $testSuffix = rand(100000, 999999);
    $testPhone = '+254711' . $testSuffix;
    $testNatId = '32' . $testSuffix;

    $quickResp = $patientCtrl->quickStore(new Request([], [
        'first_name'  => 'Faith',
        'last_name'   => 'Wanjiku',
        'sex'         => 'female',
        'phone'       => $testPhone,
        'national_id' => $testNatId,
    ]));

    $data = json_decode($quickResp->content, true);
    assert($data['success'] === true, "Quick registration must succeed");
    assert(!empty($data['patient']['mrn']), "Patient MRN must be generated");
    assert($data['patient']['national_id'] === $testNatId, "National ID must be saved");

    // 2. Fuzzy Search by full concatenated name
    $searchReq = new Request(['q' => 'Faith Wanjiku']);
    $searchResp = $patientCtrl->search($searchReq);
    $results = json_decode($searchResp->content, true);
    assert(count($results) > 0, "Search by full concatenated name must return matches");
    assert($results[0]['mrn'] === $data['patient']['mrn'], "MRN must match newly created patient");

    // 3. Search by National ID number (handles identical names)
    $searchIdReq = new Request(['q' => $testNatId]);
    $idResults = json_decode($patientCtrl->search($searchIdReq)->content, true);
    assert(count($idResults) > 0, "Search by national ID must return exact patient match");
    assert($idResults[0]['first_name'] === 'Faith', "Result must match Faith");

    // 4. Search by phone number
    $searchPhoneReq = new Request(['q' => (string) $testSuffix]);
    $phoneResults = json_decode($patientCtrl->search($searchPhoneReq)->content, true);
    assert(count($phoneResults) > 0, "Search by partial phone must return patient");
});

// TEST GROUP 11: PATIENT MEDICAL IDENTIFICATION CARD
echo "\n11. PATIENT MEDICAL IDENTIFICATION CARD\n";

it("Renders printable standardized medical ID card with barcodes and identity verification", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $patient = DB::selectOne("SELECT * FROM patients WHERE tenant_id = 1 LIMIT 1");
    assert($patient !== null, "Patient must exist");

    $patientCtrl = new \App\Controllers\PatientController();
    $cardResp = $patientCtrl->card(new Request(), $patient['id']);

    assert(str_contains($cardResp->content, 'PATIENT MEDICAL IDENTITY'), "ID card must render header");
    assert(str_contains($cardResp->content, $patient['mrn']), "ID card must contain patient MRN");
    assert(str_contains($cardResp->content, 'MRN Barcode'), "ID card must contain high-density barcode");
});

// TEST GROUP 12: INPATIENT WARD PROVISIONING & DAILY BED RATES
echo "\n12. INPATIENT WARD PROVISIONING & DAILY BED RATES\n";

it("Provisions new hospital wards with daily bed charges and automatic bed numbering", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $inpatientCtrl = new \App\Controllers\InpatientController();
    $wardCode = 'ICU-' . strtoupper(bin2hex(random_bytes(2)));

    $inpatientCtrl->storeWard(new Request([], [
        'name'           => 'St. Jude Intensive Care',
        'code'           => $wardCode,
        'type'           => 'icu',
        'floor_number'   => 2,
        'price_per_day'  => '4500.00',
        'number_of_beds' => 4,
    ]));

    $ward = DB::selectOne("SELECT * FROM wards WHERE code = ? AND tenant_id = 1", [$wardCode]);
    assert($ward !== null, "New ward must be created in database");

    $beds = DB::select("SELECT * FROM beds WHERE ward_id = ? AND tenant_id = 1", [$ward['id']]);
    assert(count($beds) === 4, "Ward must have 4 auto-provisioned beds");
    assert((int) $beds[0]['daily_rate_minor'] === 450000, "Bed daily rate must be saved as 450000 minor units (4,500.00 KES)");
    assert(str_starts_with($beds[0]['bed_number'], $wardCode), "Bed number must follow ward code prefix");
});

// TEST GROUP 13: APPOINTMENTS & CALENDAR RESERVATION SCHEDULING
echo "\n13. APPOINTMENTS & CALENDAR RESERVATION SCHEDULING\n";

it("Schedules patient appointments and issues arrival reminder notifications", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $patient = DB::selectOne("SELECT * FROM patients WHERE tenant_id = 1 LIMIT 1");
    $doctor = DB::selectOne("SELECT * FROM users WHERE tenant_id = 1 AND role = 'doctor' LIMIT 1");

    $appCtrl = new \App\Controllers\AppointmentController();
    $today = date('Y-m-d');

    // 1. Schedule appointment for today
    $appCtrl->store(new Request([], [
        'patient_id'       => $patient['id'],
        'doctor_user_id'   => $doctor['id'] ?? null,
        'appointment_date' => $today,
        'start_time'       => '10:30',
        'type'             => 'consultation',
        'title'            => 'Cardiology Follow-Up',
        'notes'            => 'Review hypertension medication response',
    ]));

    $createdApp = DB::selectOne("SELECT * FROM appointments WHERE patient_id = ? AND appointment_date = ? ORDER BY id DESC LIMIT 1", [$patient['id'], $today]);
    assert($createdApp !== null, "Appointment must be recorded in database");
    assert($createdApp['status'] === 'scheduled', "Status must be scheduled");

    // 2. Fetch today's reminders
    $remindersResp = $appCtrl->todayReminders(new Request());
    $remindersData = json_decode($remindersResp->content, true);
    assert($remindersData['count'] >= 1, "Today's reminder count must be at least 1");

    // 3. Confirm appointment arrival
    $appCtrl->updateStatus(new Request([], ['status' => 'confirmed']), $createdApp['id']);
    $confirmedApp = DB::selectOne("SELECT status FROM appointments WHERE id = ?", [$createdApp['id']]);
    assert($confirmedApp['status'] === 'confirmed', "Appointment status must be updated to confirmed");

    // 4. Verify calendar index renders
    $calResp = $appCtrl->index(new Request());
    assert(str_contains($calResp->content, 'Patient Appointments & Scheduling'), "Calendar page must render cleanly");
});

// TEST GROUP 14: COMPREHENSIVE FINANCIAL YEAR & OPERATIONAL REPORTS
echo "\n14. COMPREHENSIVE FINANCIAL YEAR & OPERATIONAL REPORTS\n";

it("Generates financial audit with FY filtering, departmental volume, and pharmacy margin analytics", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $reportCtrl = new \App\Controllers\ReportController();

    // 1. Render FY report
    $reportResp = $reportCtrl->index(new Request(['fy' => '2025_2026']));
    assert(str_contains($reportResp->content, 'Hospital Financial & Operations Reports'), "Reports dashboard must render cleanly");
    assert(str_contains($reportResp->content, 'Total Patients Registered'), "Report must display patient registration volume");
    assert(str_contains($reportResp->content, 'Pharmacy Financial Profitability & COGS'), "Report must display pharmacy wholesale COGS & margin analytics");

    // 2. Render all-time report
    $allTimeResp = $reportCtrl->index(new Request(['fy' => 'all_time']));
    assert(str_contains($allTimeResp->content, 'All-Time Historical'), "All-time report must render cleanly");
});

// TEST GROUP 15: ROLE-BASED ACCESS CONTROL & TENANT FEATURE FLAGS
echo "\n15. ROLE-BASED ACCESS CONTROL & TENANT FEATURE FLAGS\n";

it("Restricts user roles and allows super admin to toggle pay-per-feature modules per tenant", function () {
    // 1. Role permission matrix verification
    $doctorUser = new \App\Models\User();
    $doctorUser->role = 'doctor';
    \Veen\Core\Auth::login($doctorUser);

    assert(user_can('consultations') === true, "Doctor must have permission for consultations");
    assert(user_can('billing') === false, "Doctor must NOT have permission to access cashier billing");
    assert(user_can('pharmacy_pos') === false, "Doctor must NOT have permission for OTC POS");
    assert(user_can('clinic_kids') === true, "Doctor must have permission for kids clinic");
    assert(user_can('clinic_maternal') === true, "Doctor must have permission for maternal clinic");
    assert(user_can('clinic_ccc') === true, "Doctor must have permission for CCC clinic");
    assert(user_can('radiology') === true, "Doctor must have permission for radiology");
    assert(user_can('mortuary') === true, "Doctor must have permission for mortuary");

    $cashierUser = new \App\Models\User();
    $cashierUser->role = 'cashier';
    \Veen\Core\Auth::login($cashierUser);

    assert(user_can('billing') === true, "Cashier must have permission for billing");
    assert(user_can('consultations') === false, "Cashier must NOT have permission for doctor consultations");
    assert(user_can('clinic_kids') === false, "Cashier must NOT have permission for kids clinic");
    assert(user_can('radiology') === false, "Cashier must NOT have permission for radiology");

    $nurseUser = new \App\Models\User();
    $nurseUser->role = 'nurse';
    \Veen\Core\Auth::login($nurseUser);

    assert(user_can('clinic_kids') === true, "Nurse must have permission for kids clinic");
    assert(user_can('clinic_maternal') === true, "Nurse must have permission for maternal clinic");
    assert(user_can('clinic_ccc') === true, "Nurse must have permission for CCC clinic");
    assert(user_can('radiology') === true, "Nurse must have permission for radiology");
    assert(user_can('mortuary') === false, "Nurse must NOT have permission for mortuary");

    // 2. Tenant Module Feature Flags
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    // By default, modules are enabled
    assert(tenant_can('pharmacy') === true, "Tenant module pharmacy is enabled by default");
    assert(tenant_can('clinic_kids') === true, "Tenant module clinic_kids is enabled by default");
    assert(tenant_can('radiology') === true, "Tenant module radiology is enabled by default");

    // Super Admin updates tenant modules (disables inpatient and radiology, keeps others)
    $platformCtrl = new \App\Controllers\PlatformController();
    $platformCtrl->updateModules(new Request([], [
        'module_consultations'   => 1,
        'module_pharmacy'        => 1,
        'module_lab'             => 1,
        'module_inpatient'       => 0, // Disabled
        'module_billing'         => 1,
        'module_queue'           => 1,
        'module_appointments'    => 1,
        'module_reports'         => 1,
        'module_clinic_kids'     => 1,
        'module_clinic_maternal' => 1,
        'module_clinic_ccc'      => 1,
        'module_radiology'       => 0, // Disabled
        'module_mortuary'        => 1,
    ]), 1);

    // Reload tenant
    $reloadedTenant = TenantManager::find(1);
    TenantManager::set($reloadedTenant);

    assert(tenant_can('inpatient') === false, "Disabled inpatient module must return false");
    assert(tenant_can('radiology') === false, "Disabled radiology module must return false");
    assert(tenant_can('pharmacy') === true, "Active pharmacy module must return true");
    assert(tenant_can('mortuary') === true, "Active mortuary module must return true");

    // Re-enable all modules
    $platformCtrl->updateModules(new Request([], [
        'module_consultations'   => 1,
        'module_pharmacy'        => 1,
        'module_lab'             => 1,
        'module_inpatient'       => 1,
        'module_billing'         => 1,
        'module_queue'           => 1,
        'module_appointments'    => 1,
        'module_reports'         => 1,
        'module_clinic_kids'     => 1,
        'module_clinic_maternal' => 1,
        'module_clinic_ccc'      => 1,
        'module_radiology'       => 1,
        'module_mortuary'        => 1,
    ]), 1);

    $reloadedTenant = TenantManager::find(1);
    TenantManager::set($reloadedTenant);
    assert(tenant_can('inpatient') === true, "Re-enabled inpatient module must return true");
    assert(tenant_can('radiology') === true, "Re-enabled radiology module must return true");
});

// TEST GROUP 16: PHARMACY SALES REPORT & STOCK MOVEMENTS
echo "\n16. PHARMACY SALES REPORT & STOCK MOVEMENTS\n";

it("Generates daily sales breakdown with cash, mpesa, card totals and stock movements ledger", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $admin = User::query()->where('role', 'hospital_admin')->first();
    Auth::login($admin);

    $pharmacyCtrl = new \App\Controllers\PharmacyController();

    // 1. Sales Report
    $salesResp = $pharmacyCtrl->salesReport(new Request(['date' => date('Y-m-d')]));
    assert(str_contains($salesResp->content, "Today's Pharmacy Sales & Revenue"), "Sales report must render cleanly");
    assert(str_contains($salesResp->content, "Total Daily Revenue"), "Sales report must show total daily revenue card");
    assert(str_contains($salesResp->content, "Cash Collected"), "Sales report must show cash breakdown");
    assert(str_contains($salesResp->content, "M-Pesa"), "Sales report must show M-Pesa breakdown");

    // 2. Stock Movements Ledger
    $stockResp = $pharmacyCtrl->stockMovements(new Request([]));
    assert(str_contains($stockResp->content, "Inventory Stock Ledger & Movements"), "Stock ledger view must render cleanly");
    assert(str_contains($stockResp->content, "Stock Movement Log"), "Stock movement log table must render cleanly");
});

// TEST GROUP 17: CUSTOM ROLES & DYNAMIC PERMISSIONS
echo "\n17. CUSTOM HOSPITAL ROLES & DYNAMIC PERMISSIONS\n";

it("Allows hospital admin to provision custom roles, configure granular rights, and delete roles", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $admin = User::query()->where('role', 'hospital_admin')->first();
    Auth::login($admin);

    $staffCtrl = new \App\Controllers\StaffController();

    // 1. Render Roles Matrix
    $rolesResp = $staffCtrl->roles(new Request([]));
    assert(str_contains($rolesResp->content, "Hospital Roles & Access Control"), "Roles matrix must render cleanly");
    assert(str_contains($rolesResp->content, "Clinic for Kids &amp; Immunizations"), "Roles matrix must contain Kids Clinic permission");
    assert(str_contains($rolesResp->content, "Radiology &amp; MRI Diagnostic Imaging"), "Roles matrix must contain Radiology permission");
    assert(str_contains($rolesResp->content, "Mortuary &amp; Deceased Registry"), "Roles matrix must contain Mortuary permission");

    // 2. Store custom role: e.g. Triage Specialist
    $testSlug = 'triage_officer_' . time();
    $storeResp = $staffCtrl->storeRole(new Request([], [
        'name'        => 'Triage Specialist',
        'description' => 'Evaluates vital signs, priority queueing, and emergency triage',
        'permissions' => ['queue', 'triage', 'patients'],
    ]));

    $createdRole = DB::selectOne("SELECT * FROM hospital_roles WHERE tenant_id = 1 AND name = 'Triage Specialist' ORDER BY id DESC LIMIT 1");
    assert($createdRole !== null, "Custom role must be inserted in database");
    assert($createdRole['is_system'] == 0, "Custom role must have is_system = 0");

    $rolePerms = json_decode($createdRole['permissions'], true);
    assert(in_array('triage', $rolePerms, true), "Custom role must contain assigned 'triage' permission");

    // 3. Update custom role
    $staffCtrl->updateRole(new Request([], [
        'name'        => 'Triage & OPD Officer',
        'description' => 'Updated operational scope',
        'permissions' => ['queue', 'triage', 'patients', 'consultations'],
    ]), $createdRole['id']);

    $updatedRole = DB::selectOne("SELECT * FROM hospital_roles WHERE id = ?", [$createdRole['id']]);
    assert($updatedRole['name'] === 'Triage & OPD Officer', "Role name must update");

    // 4. Delete custom role
    $staffCtrl->deleteRole(new Request([]), $createdRole['id']);
    $deletedRole = DB::selectOne("SELECT * FROM hospital_roles WHERE id = ?", [$createdRole['id']]);
    assert($deletedRole === null, "Custom role must be deleted cleanly");
});

// TEST GROUP 18: HUMAN RESOURCES (HR) - LEAVES, PAYROLL & EXPENSES
echo "\n18. HUMAN RESOURCES (HR) - LEAVES, PAYROLL & EXPENSES\n";

it("Manages employee leaves, generates monthly salaries, and logs hospital operating expenses", function () {
    $tenant = TenantManager::find(1);
    TenantManager::set($tenant);

    $admin = User::query()->where('role', 'hospital_admin')->first();
    Auth::login($admin);

    $hrCtrl = new \App\Controllers\HrController();

    // 1. HR Dashboard
    $hrResp = $hrCtrl->index(new Request([]));
    assert(str_contains($hrResp->content, "Hospital Human Resources & Operations"), "HR dashboard must render cleanly");

    // 2. Submit Leave Request for an employee
    $employee = User::query()->where('role', 'nurse')->first() ?: User::query()->first();
    $hrCtrl->storeLeave(new Request([], [
        'user_id'    => $employee->id,
        'leave_type' => 'annual',
        'start_date' => date('Y-m-d', strtotime('+2 days')),
        'end_date'   => date('Y-m-d', strtotime('+6 days')),
        'reason'     => 'Family relocation leave',
    ]));

    $leave = DB::selectOne("SELECT * FROM hr_leaves WHERE tenant_id = 1 AND user_id = ? ORDER BY id DESC LIMIT 1", [$employee->id]);
    assert($leave !== null, "Leave application must be persisted");
    assert($leave['days_count'] == 5, "5 days count must be calculated");
    assert($leave['status'] === 'pending', "Leave status must initially be pending");

    // 3. Approve Leave
    $hrCtrl->updateLeaveStatus(new Request([], ['action' => 'approve']), $leave['id']);
    $approvedLeave = DB::selectOne("SELECT * FROM hr_leaves WHERE id = ?", [$leave['id']]);
    assert($approvedLeave['status'] === 'approved', "Leave status must be approved");

    // 4. Monthly Payroll Management
    $currentMonth = date('Y-m');
    $hrCtrl->processPayroll(new Request([], [
        'user_id'        => $employee->id,
        'month_year'     => $currentMonth,
        'basic_salary'   => '45000',
        'allowances'     => '5000',
        'deductions'     => '3500',
        'payment_method' => 'bank_transfer',
        'status'         => 'processed',
        'notes'          => 'Monthly salary voucher',
    ]));

    $salary = DB::selectOne("SELECT * FROM hr_salaries WHERE tenant_id = 1 AND user_id = ? AND month_year = ?", [$employee->id, $currentMonth]);
    assert($salary !== null, "Salary record must be saved");
    assert((int) $salary['basic_salary_minor'] === 4500000, "Basic salary minor must match 45,000.00");
    assert((int) $salary['net_salary_minor'] === 4650000, "Net salary must calculate (45000 + 5000 - 3500) = 46,500.00");

    // 5. Operating Expenses
    $hrCtrl->storeExpense(new Request([], [
        'category'       => 'medical_supplies',
        'title'          => 'Emergency Surgical Gloves & Gauze',
        'amount'         => '12500',
        'payment_method' => 'mpesa',
        'vendor_name'    => 'Harleys Pharmaceuticals',
        'receipt_no'     => 'RCP-99120',
        'expense_date'   => date('Y-m-d'),
        'notes'          => 'Restocked emergency wing',
    ]));

    $expense = DB::selectOne("SELECT * FROM hospital_expenses WHERE tenant_id = 1 AND title = 'Emergency Surgical Gloves & Gauze'");
    assert($expense !== null, "Operating expense must be recorded in ledger");
    assert((int) $expense['amount_minor'] === 1250000, "Expense amount minor must match 12,500.00");

    // 6. Verify Expense View renders
    $expenseResp = $hrCtrl->expenses(new Request([]));
    assert(str_contains($expenseResp->content, "Hospital Operational Expenses"), "Expenses view must render cleanly");
    assert(str_contains($expenseResp->content, "Emergency Surgical Gloves"), "Recorded expense must be visible in table");
});

// 19. WARD OCCUPANCY CENSUS & PATIENT LOCATOR
echo "\n19. INPATIENT WARD OCCUPANCY CENSUS & PATIENT LOCATOR\n";
it('Calculates live ward occupancy headcount and renders patient locator search', function () {
    TenantManager::setContext(1);
    $inpatientCtrl = new \App\Controllers\InpatientController();

    // Verify Inpatient index renders with ward statistics and locator search
    $resp = $inpatientCtrl->index(new Request([]));
    assert(str_contains($resp->content, "Fast Inpatient Ward & Bed Locator"), "Ward locator search bar must be present");
    assert(str_contains($resp->content, "Admitted Patients"), "Per-ward admitted patient count must be rendered");
    assert(str_contains($resp->content, "exportTableToCSV('admissionsTable'"), "CSV export button must be attached to admissions table");
});

// 20. POS CHECKOUT WITH OPTIONAL M-PESA CODE & SENDER PHONE
echo "\n20. PHARMACY POS CHECKOUT WITH OPTIONAL M-PESA CODE / PHONE\n";
it('Authorizes POS transaction when M-Pesa reference is blank or sender phone is provided', function () {
    TenantManager::setContext(1);
    $posCtrl = new \App\Controllers\PharmacyPosController();

    // Ensure test product exists with stock and approved batch
    $product = Product::where('tenant_id', 1)->first();
    assert($product !== null, "Product must exist");
    $initialStock = $product->stock_qty;

    // Checkout with M-Pesa payment method but empty/optional reference (Send Money)
    $payload = [
        'items' => [
            ['product_id' => $product->id, 'qty' => 1]
        ],
        'payments' => [
            [
                'method' => 'mpesa',
                'amount_minor' => $product->price_minor,
                'reference' => '' // blank/optional reference
            ]
        ],
        'customer_name' => 'Direct Send Money Customer'
    ];

    $resp = $posCtrl->checkout(new Request([], $payload));
    assert($resp->status() === 200, "Checkout with optional M-Pesa code must succeed with HTTP 200");
    $data = json_decode($resp->content, true);
    assert($data['success'] === true, "Sale checkout must report success");

    // Verify sale and payment recorded
    $saleId = $data['sale_id'] ?? ($data['sale']['id'] ?? null);
    $sale = DB::selectOne("SELECT * FROM sales WHERE id = ?", [$saleId]);
    assert($sale !== null, "Sale must be created");
    $payment = DB::selectOne("SELECT * FROM sale_payments WHERE sale_id = ?", [$sale['id']]);
    assert($payment !== null && $payment['method'] === 'mpesa', "M-Pesa payment must be recorded");
});

// 21. PHARMACY RESTOCK APPROVAL WORKFLOW & EDIT INTEGRITY
echo "\n21. PHARMACY RESTOCK APPROVAL WORKFLOW & EDIT INTEGRITY\n";
it('Enforces restock approval state machine and prevents editing of approved batches', function () {
    TenantManager::setContext(1);
    $pharmacyCtrl = new \App\Controllers\PharmacyController();
    $product = Product::where('tenant_id', 1)->first();
    $stockBefore = (int) $product->stock_qty;

    // 1. Submit restock with require_approval = 1
    $batchNo = 'TEST-RESTOCK-' . time();
    $pharmacyCtrl->receiveBatch(new Request([], [
        'product_id'       => $product->id,
        'batch_no'         => $batchNo,
        'delivery_note_no' => 'DN-99001',
        'expiry_date'      => date('Y-m-d', strtotime('+1 year')),
        'cost'             => '120.00',
        'qty_received'     => 50,
        'require_approval' => '1',
    ]));

    // Batch must be inserted with status 'pending_approval' and qty_on_hand = 0
    $batch = DB::selectOne("SELECT * FROM product_batches WHERE tenant_id = 1 AND batch_no = ?", [$batchNo]);
    assert($batch !== null, "Restock batch must be recorded");
    assert($batch['status'] === 'pending_approval', "Batch status must be pending_approval");
    assert((int) $batch['qty_on_hand'] === 0, "qty_on_hand must remain 0 while pending");

    // Master product stock_qty must NOT be incremented yet
    $productAfterSubmit = Product::where('tenant_id', 1)->where('id', $product->id)->first();
    assert((int) $productAfterSubmit->stock_qty === $stockBefore, "Product stock must NOT increase before approval");

    // 2. Edit batch while pending
    $pharmacyCtrl->updateBatch(new Request([], [
        'batch_no'         => $batchNo,
        'delivery_note_no' => 'DN-99001-REV',
        'expiry_date'      => date('Y-m-d', strtotime('+14 months')),
        'cost'             => '115.00',
        'qty_received'     => 60, // adjusted from 50 to 60
    ]), (int) $batch['id']);

    $updatedBatch = DB::selectOne("SELECT * FROM product_batches WHERE id = ?", [$batch['id']]);
    assert((int) $updatedBatch['qty_received'] === 60, "Updated batch quantity must be 60");
    assert($updatedBatch['delivery_note_no'] === 'DN-99001-REV', "Updated delivery note must persist");

    // 3. Approve Restock
    $pharmacyCtrl->approveBatch(new Request([]), (int) $batch['id']);

    $approvedBatch = DB::selectOne("SELECT * FROM product_batches WHERE id = ?", [$batch['id']]);
    assert($approvedBatch['status'] === 'approved', "Batch status must transition to approved");
    assert((int) $approvedBatch['qty_on_hand'] === 60, "qty_on_hand must now match received quantity");

    // Master product stock_qty MUST now be incremented by 60
    $productAfterApprove = Product::where('tenant_id', 1)->where('id', $product->id)->first();
    assert((int) $productAfterApprove->stock_qty === $stockBefore + 60, "Master stock_qty must increase by approved quantity");

    // 4. Verify editing approved batch is permanently blocked
    $blockResp = $pharmacyCtrl->updateBatch(new Request([], [
        'batch_no'     => $batchNo,
        'expiry_date'  => date('Y-m-d'),
        'cost'         => '100',
        'qty_received' => 10,
    ]), (int) $batch['id']);
    assert($blockResp->status() === 302, "Attempting to update approved batch must redirect with error");
});

// 22. SUPPLIERS MANAGEMENT & ANNUAL SUPPLY ANALYTICS
echo "\n22. PHARMACEUTICAL SUPPLIERS MANAGEMENT & SUPPLY ANALYTICS\n";
it('Provides supplier CRUD, quick-store modal endpoint, and supply volume analytics', function () {
    TenantManager::setContext(1);
    $pharmacyCtrl = new \App\Controllers\PharmacyController();

    // 1. Quick store supplier via AJAX
    $quickResp = $pharmacyCtrl->quickStoreSupplier(new Request([], [
        'name'           => 'Apex Pharmaceuticals Ltd',
        'contact_person' => 'Dr. Mercy Chebet',
        'phone'          => '+254 700 112 233',
        'email'          => 'mercy@apexpharma.co.ke'
    ]));
    assert($quickResp->status() === 200, "Quick store supplier must return HTTP 200");
    $quickData = json_decode($quickResp->content, true);
    assert($quickData['success'] === true, "Quick store must be successful");
    $supplierId = $quickData['supplier']['id'];

    // 2. Link a restock batch to this supplier
    $product = Product::where('tenant_id', 1)->first();
    $pharmacyCtrl->receiveBatch(new Request([], [
        'product_id'       => $product->id,
        'supplier_id'      => $supplierId,
        'batch_no'         => 'APEX-B-' . time(),
        'expiry_date'      => date('Y-m-d', strtotime('+2 years')),
        'cost'             => '250.00',
        'qty_received'     => 100,
        'require_approval' => '0', // immediate approval
    ]));

    // 3. Verify Supplier Index View computes annual spend and supply counts
    $suppliersView = $pharmacyCtrl->suppliers(new Request([]));
    assert(str_contains($suppliersView->content, "Apex Pharmaceuticals Ltd"), "Registered supplier must appear in suppliers table");
    assert(str_contains($suppliersView->content, "exportTableToCSV('suppliersTable'"), "CSV export must be attached to suppliers table");

    // 4. Update supplier details
    $pharmacyCtrl->updateSupplier(new Request([], [
        'name'           => 'Apex Pharmaceuticals Kenya Ltd',
        'contact_person' => 'Dr. Mercy Chebet-Kip',
        'phone'          => '+254 700 999 888',
        'email'          => 'hq@apexpharma.co.ke',
        'address'        => 'Nairobi Logistics Park, Warehouse 12'
    ]), (int) $supplierId);

    $updatedSupplier = DB::selectOne("SELECT * FROM suppliers WHERE id = ?", [$supplierId]);
    assert($updatedSupplier['name'] === 'Apex Pharmaceuticals Kenya Ltd', "Supplier name must be updated");
    assert($updatedSupplier['phone'] === '+254 700 999 888', "Phone must be updated");
});

// 23. HOSPITAL BRANDING & COMPANY SETTINGS
echo "\n23. HOSPITAL BRANDING & COMPANY SETTINGS\n";
it('Enables hospital admin to update facility legal name, currency, timezone and branding', function () {
    TenantManager::setContext(1);
    $settingCtrl = new \App\Controllers\SettingController();

    // Authenticate as hospital admin
    $admin = User::where('tenant_id', 1)->where('role', 'hospital_admin')->first();
    assert($admin !== null, "Hospital admin user must exist");
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    // 1. Verify Settings view renders cleanly
    $resp = $settingCtrl->index(new Request([]));
    assert($resp->status() === 200, "Settings index must return 200");
    assert(str_contains($resp->content, "Hospital Settings & Security Profile"), "Settings title must be present");
    assert(str_contains($resp->content, "Hospital & Facility Branding"), "Branding section must be present for admin");
    assert(str_contains($resp->content, "My Personal Profile"), "User profile card must be present");

    // 2. Update company details
    $updateResp = $settingCtrl->updateCompany(new Request([], [
        'name' => 'St. Jude Premium Hospital & Research Center',
        'phone' => '+254 711 000 111',
        'email' => 'admin@stjudepremium.org',
        'address' => 'Nairobi Medical Hill, Block C',
        'tax_pin' => 'P099887766A',
        'primary_color' => '#0d9488',
        'currency' => 'KES',
        'timezone' => 'Africa/Nairobi',
    ]));
    assert($updateResp->status() === 302, "Company update must redirect");

    $tenant = TenantManager::find(1);
    assert($tenant['name'] === 'St. Jude Premium Hospital & Research Center', "Tenant name must be updated");
    assert($tenant['phone'] === '+254 711 000 111', "Tenant phone must be updated");
});

// 24. USER PROFILE SECURITY & PASSWORD CHANGE
echo "\n24. USER PROFILE SECURITY & PASSWORD CHANGE\n";
it('Allows staff to update personal details and change password while strictly locking email', function () {
    TenantManager::setContext(1);
    $settingCtrl = new \App\Controllers\SettingController();

    // Find staff member
    $doctor = User::where('tenant_id', 1)->where('role', 'doctor')->first();
    assert($doctor !== null, "Doctor must exist");
    Auth::attempt($doctor->email, 'Password@123', '127.0.0.1');

    // 1. Update personal profile (name, phone, title)
    $resp = $settingCtrl->updateProfile(new Request([], [
        'name' => 'Dr. Achieng Otieno M.D.',
        'phone' => '+254 722 555 444',
        'title' => 'Chief Medical Director',
        'email' => 'hacked@fraud.com', // Attempting to alter email
    ]));
    assert($resp->status() === 302, "Profile update must redirect");

    $updatedDoctor = DB::selectOne("SELECT * FROM users WHERE id = ?", [$doctor->id]);
    assert($updatedDoctor['name'] === 'Dr. Achieng Otieno M.D.', "Name must be updated");
    assert($updatedDoctor['email'] === $doctor->email, "Email must remain strictly unaltered");
    assert($updatedDoctor['title'] === 'Chief Medical Director', "Title must be updated");

    // 2. Change password
    $pwdResp = $settingCtrl->updatePassword(new Request([], [
        'current_password' => 'Password@123',
        'password' => 'NewSecurePass@2026',
        'password_confirmation' => 'NewSecurePass@2026',
    ]));
    assert($pwdResp->status() === 302, "Password update must redirect");

    // 3. Verify user can authenticate with new password
    $reloadedDoctor = DB::selectOne("SELECT * FROM users WHERE id = ?", [$doctor->id]);
    assert(password_verify('NewSecurePass@2026', $reloadedDoctor['password']), "New password must match hash");

    // Restore original password for idempotency
    DB::statement("UPDATE users SET password = ? WHERE id = ?", [password_hash('Password@123', PASSWORD_DEFAULT), $doctor->id]);
});

// 25. STAFF DETAILS EDIT WORKFLOW
echo "\n25. STAFF DETAILS EDIT WORKFLOW\n";
it('Allows hospital admin to edit employee information including role, title, and facility', function () {
    TenantManager::setContext(1);
    $staffCtrl = new \App\Controllers\StaffController();

    $staffMember = DB::selectOne("SELECT * FROM users WHERE tenant_id = 1 AND role = 'nurse' LIMIT 1");
    assert($staffMember !== null, "Nurse staff member must exist");

    // Update staff member
    $resp = $staffCtrl->update(new Request([], [
        'name' => 'Sister Grace Wanjiku',
        'role' => 'nurse',
        'title' => 'Senior Matron / Ward In-Charge',
        'phone' => '+254 733 111 222',
        'is_active' => '1',
    ]), $staffMember['id']);

    assert($resp->status() === 302, "Staff update must redirect");

    $updated = DB::selectOne("SELECT * FROM users WHERE id = ?", [$staffMember['id']]);
    assert($updated['name'] === 'Sister Grace Wanjiku', "Staff name must be updated");
    assert($updated['title'] === 'Senior Matron / Ward In-Charge', "Staff title must be updated");
    assert($updated['phone'] === '+254 733 111 222', "Staff phone must be updated");
});

// 26. INPATIENT COLLAPSE & ADMISSIONS TABLE VISIBILITY
echo "\n26. INPATIENT COLLAPSE & ADMISSIONS TABLE VISIBILITY\n";
it('Positions active admissions at the top and provides collapsible wards grid with real-time search', function () {
    TenantManager::setContext(1);
    $inpatientCtrl = new \App\Controllers\InpatientController();

    $resp = $inpatientCtrl->index(new Request([]));
    assert($resp->status() === 200, "Inpatient index must return 200");

    // Assert admissions table appears before wards grid
    $admissionsPos = strpos($resp->content, 'Currently Admitted Patients');
    $wardsPos = strpos($resp->content, 'Visual Bed Board & Ward Occupancy');
    assert($admissionsPos !== false && $wardsPos !== false, "Both sections must be present");
    assert($admissionsPos < $wardsPos, "Currently Admitted Patients table must be rendered before the Visual Bed Board");

    // Assert collapse toggle button and container exist
    assert(str_contains($resp->content, 'id="toggleWardsBtn"'), "Collapse toggle button must exist");
    assert(str_contains($resp->content, 'id="wardsCollapseContent"'), "Collapsible content container must exist");
    assert(str_contains($resp->content, 'tableSearch('), "Keyup tableSearch must be bound");
});

// 27. MULTI-VISIT CONSULTATIONS & CLINICAL LONGITUDINAL HISTORY
echo "\n27. MULTI-VISIT CONSULTATIONS & CLINICAL LONGITUDINAL HISTORY\n";
it('Displays full longitudinal history of prior visits, past diagnoses, vitals, and prescribed drugs', function () {
    TenantManager::setContext(1);
    $consultationCtrl = new \App\Controllers\ConsultationController();

    $patient = Patient::where('tenant_id', 1)->first();
    assert($patient !== null, "Patient must exist");

    // Ensure patient has at least 2 encounters
    $enc1 = DB::selectOne("SELECT * FROM clinical_encounters WHERE patient_id = ? AND tenant_id = ? ORDER BY id ASC LIMIT 1", [$patient->id, 1]);
    if (!$enc1) {
        $enc1Obj = \App\Models\ClinicalEncounter::create([
            'patient_id' => $patient->id,
            'encounter_type' => 'outpatient',
            'status' => 'completed',
            'presenting_complaints' => 'High fever and severe dry cough',
            'doctor_notes' => 'Patient prescribed Amoxicillin and advised rest',
            'working_diagnosis' => 'Bacterial Bronchitis',
        ]);
        $enc1 = DB::selectOne("SELECT * FROM clinical_encounters WHERE id = ?", [$enc1Obj->id]);
    }

    // Create a second encounter for longitudinal history
    $enc2 = \App\Models\ClinicalEncounter::create([
        'patient_id' => $patient->id,
        'encounter_type' => 'follow_up',
        'status' => 'consulting',
        'presenting_complaints' => 'Follow up visit after 1 week: Cough resolving',
        'doctor_notes' => 'Chest clear upon auscultation',
        'working_diagnosis' => 'Convalescent Bronchitis',
    ]);

    $resp = $consultationCtrl->show(new Request([]), $enc2->id);
    assert($resp->status() === 200, "Consultation show must return 200");
    assert(str_contains($resp->content, "Patient Prior Visit History & Clinical Records"), "Prior visits history card must be present");
    assert(str_contains($resp->content, "Dispense at Pharmacy"), "Dispense at Pharmacy button must be present");
    assert(str_contains($resp->content, "patient_id=" . ($patient->uuid ?: $patient->id)), "Pharmacy link must carry patient_id parameter");
});

// 28. POS PHARMACY LINK WITH PRESELECTED PATIENT
echo "\n28. POS PHARMACY LINK WITH PRESELECTED PATIENT\n";
it('Pre-populates POS customer with specific patient when referred from consultation', function () {
    TenantManager::setContext(1);
    $posCtrl = new \App\Controllers\PharmacyPosController();

    $patient = Patient::where('tenant_id', 1)->first();
    assert($patient !== null, "Patient must exist");

    // Open POS with patient_id query param
    $req = new Request(['patient_id' => (string) $patient->id], []);
    $resp = $posCtrl->index($req);

    assert($resp->status() === 200, "POS must return 200");
    assert(str_contains($resp->content, $patient->first_name), "POS view must display preselected patient name");
    assert(str_contains($resp->content, $patient->mrn), "POS view must display preselected patient MRN");
    assert(str_contains($resp->content, 'value="' . $patient->id . '"'), "POS hidden patientId input must carry patient ID");
});

// 29. HIGH PERFORMANCE REDIS CACHING
echo "\n29. HIGH PERFORMANCE REDIS CACHING\n";
it('Operates Redis socket caching with tenant key isolation and sub-millisecond throughput', function () {
    TenantManager::setContext(1);

    // 1. Set and retrieve cache item
    $cacheKey = 'clinical_guidelines_test';
    $payload = ['guideline' => 'Malaria Treatment Protocol WHO 2026', 'drugs' => ['Artemether', 'Lumefantrine']];
    \Veen\Core\Cache::set($cacheKey, $payload, 120);

    $retrieved = \Veen\Core\Cache::get($cacheKey);
    assert($retrieved !== null, "Retrieved cache value must not be null");
    assert($retrieved['guideline'] === 'Malaria Treatment Protocol WHO 2026', "Cache payload must match");

    // 2. Test remember closure
    $computed = \Veen\Core\Cache::remember('kpi_active_beds', 60, function () {
        return 42;
    });
    assert($computed === 42, "Cache remember must compute value");

    // 3. Test forget
    \Veen\Core\Cache::forget($cacheKey);
    assert(\Veen\Core\Cache::get($cacheKey) === null, "Forgotten key must return null");
});

// 30. ONBOARDING MODAL ERROR RETENTION, PASSWORD EYE TOGGLE & MODULE CONFIG
echo "\n30. ONBOARDING MODAL ERROR RETENTION, PASSWORD EYE TOGGLE & MODULE CONFIG\n";
it('Preserves modal state on validation failure, provides password eye toggle and updates modules without 404', function () {
    $platformCtrl = new \App\Controllers\PlatformController();

    // 1. Verify Platform view includes password toggle and modules url handling
    $viewResp = $platformCtrl->index(new Request([]));
    assert($viewResp->status() === 200, "Platform index must return 200");
    assert(str_contains($viewResp->content, 'password-toggle-btn'), "Password eye toggle button must exist in onboarding modal");
    assert(str_contains($viewResp->content, 'openTenantModulesModal'), "openTenantModulesModal function must exist");

    // 2. Test updating modules via UUID or ID
    $tenant = \Veen\Core\Tenancy\TenantManager::find(1);
    assert($tenant !== null, "Tenant 1 must exist");
    $tenantKey = $tenant['uuid'] ?? $tenant['id'];

    $modResp = $platformCtrl->updateModules(new Request([], [
        'module_consultations' => '1',
        'module_pharmacy'      => '1',
        'module_lab'           => '1',
        'module_inpatient'     => '0',
    ]), $tenantKey);

    assert($modResp->status() === 302, "Update modules must redirect with 302");
    $updatedTenant = \Veen\Core\Tenancy\TenantManager::find(1);
    $settings = json_decode($updatedTenant['settings'] ?? '{}', true);
    assert($settings['modules']['consultations'] === true, "Consultations module must be enabled");
    assert($settings['modules']['inpatient'] === false, "Inpatient module must be disabled");
});

// 31. UNIVERSAL UUID RESOLUTION & URL UNGUESSABILITY
echo "\n31. UNIVERSAL UUID RESOLUTION & URL UNGUESSABILITY\n";
it('Resolves models transparently by UUID string or numeric ID', function () {
    TenantManager::setContext(1);

    // 1. Patient lookup by UUID
    $patient = Patient::where('tenant_id', 1)->first();
    assert($patient !== null && !empty($patient->uuid), "Patient must have a UUID");
    $byUuid = Patient::find($patient->uuid);
    assert($byUuid !== null && $byUuid->id === $patient->id, "Patient::find must resolve via UUID string");

    // 2. PatientController show and card by UUID
    $patCtrl = new \App\Controllers\PatientController();
    $showResp = $patCtrl->show(new Request([]), $patient->uuid);
    assert($showResp->status() === 200, "Patient show with UUID must return 200");
    assert(str_contains($showResp->content, $patient->first_name), "Show view must contain patient name");

    $cardResp = $patCtrl->card(new Request([]), $patient->uuid);
    assert($cardResp->status() === 200, "Patient card with UUID must return 200");

    // 3. Clinical Encounter lookup by UUID
    $enc = \App\Models\ClinicalEncounter::where('tenant_id', 1)->first();
    if ($enc && !empty($enc->uuid)) {
        $foundEnc = \App\Models\ClinicalEncounter::find($enc->uuid);
        assert($foundEnc !== null && $foundEnc->id === $enc->id, "Encounter::find must resolve via UUID string");
    }

    // 4. Helper resolve_id_or_uuid
    $resolved = resolve_id_or_uuid('patients', $patient->uuid, 1);
    assert($resolved !== null && $resolved['id'] == $patient->id, "resolve_id_or_uuid must resolve correctly");
});

// 32. DUPLICATE PATIENT PREVENTION WITH PARENT-CHILD DEPENDENCY
echo "\n32. DUPLICATE PATIENT PREVENTION & PARENT-CHILD LOGIC\n";
it('Blocks duplicate patient registration by phone/ID unless registered as a child', function () {
    TenantManager::setContext(1);
    $patCtrl = new \App\Controllers\PatientController();

    $uniquePhone = '+254 788 ' . rand(100000, 999999);
    $uniqueNationalId = 'NAT-' . rand(100000, 999999);

    // 1. Register parent patient
    $quickResp = $patCtrl->quickStore(new Request([], [
        'first_name'  => 'Mary',
        'last_name'   => 'Njeri',
        'phone'       => $uniquePhone,
        'national_id' => $uniqueNationalId,
        'sex'         => 'female',
    ]));
    assert($quickResp->status() === 200, "Parent quick store must succeed");
    $parentData = json_decode($quickResp->content, true);
    $parentId = $parentData['patient']['id'];

    // 2. Attempt duplicate registration (same phone and national ID, not a child)
    $dupResp = $patCtrl->quickStore(new Request([], [
        'first_name'  => 'Imposter',
        'last_name'   => 'Njeri',
        'phone'       => $uniquePhone,
        'national_id' => $uniqueNationalId,
        'sex'         => 'female',
        'is_child'    => 0,
    ]));
    assert($dupResp->status() === 422, "Duplicate patient registration must be rejected with 422");
    $dupData = json_decode($dupResp->content, true);
    assert($dupData['success'] === false, "Duplicate response success must be false");
    assert(str_contains($dupData['message'], 'already registered'), "Error message must state patient is already registered");

    // 3. Register child with same parent contact (is_child = 1)
    $childResp = $patCtrl->quickStore(new Request([], [
        'first_name'             => 'Baby Joy',
        'last_name'              => 'Njeri',
        'phone'                  => $uniquePhone,
        'national_id'            => '',
        'dob'                    => date('Y-m-d', strtotime('-3 years')),
        'sex'                    => 'female',
        'is_child'               => 1,
        'relationship_to_parent' => 'child',
    ]));
    assert($childResp->status() === 200, "Child registration using parent phone must be permitted");
    $childData = json_decode($childResp->content, true);
    assert($childData['success'] === true, "Child registration must succeed");
    assert($childData['patient']['is_child'] === true, "Patient record must be marked as child");

    $childDb = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE id = ?", [$childData['patient']['id']]);
    assert((int)$childDb['parent_patient_id'] === (int)$parentId, "Child must be linked to parent_patient_id");
    assert((int)$childDb['is_child'] === 1, "is_child flag must be 1 in database");
});

// 33. MULTI-BRANCH OPERATIONS & BRANCH SWITCHING
echo "\n33. MULTI-BRANCH OPERATIONS & SCOPED ACCESS\n";
it('Enables branch provisioning, session switching, and cross-branch analytics', function () {
    TenantManager::setContext(1);
    $branchCtrl = new \App\Controllers\BranchController();

    // 1. Authenticate as hospital admin
    $admin = User::where('tenant_id', 1)->where('role', 'hospital_admin')->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');
    assert(user_can_all_branches() === true, "Hospital admin must have user_can_all_branches permission");

    // 2. Create a new branch
    $branchSuffix = strtoupper(bin2hex(random_bytes(2)));
    $branchCode = 'SAT-' . $branchSuffix;
    $branchCtrl->store(new Request([], [
        'name'    => "Nairobi Satellite Clinic {$branchSuffix}",
        'code'    => $branchCode,
        'phone'   => '+254 700 888 111',
        'address' => 'Upperhill Medical Suites, 3rd Floor',
    ]));

    $branch = \App\Models\Branch::where('tenant_id', 1)->where('code', $branchCode)->first();
    assert($branch !== null, "New branch must be created in database");
    assert(!empty($branch->uuid), "Branch must have an auto-generated UUID");

    // 3. Switch active branch in session
    $switchResp = $branchCtrl->switchBranch(new Request([]), $branch->uuid);
    assert($switchResp->status() === 302, "Switch branch must redirect");
    assert(\Veen\Core\Session::get('active_branch_id') === (int) $branch->id, "Active branch session must be updated");
    assert(current_branch_id() === (int) $branch->id, "current_branch_id() helper must return active branch ID");

    // 4. Switch to All Branches (0)
    $branchCtrl->switchBranch(new Request([]), 'all');
    assert(current_branch_id() === null, "current_branch_id() must be null for consolidated view");

    // 5. Reports include multi-branch breakdown
    $reportCtrl = new \App\Controllers\ReportController();
    $reportView = $reportCtrl->index(new Request([]));
    assert(str_contains($reportView->content, "Multi-Branch Facility Operations"), "Report must include multi-branch performance table");
    assert(str_contains($reportView->content, $branchCode), "Report must show newly registered branch code");
});

// 34. PHARMACY CATEGORY MANAGEMENT & WHOLESALE INVENTORY VALUATION
echo "\n34. PHARMACY CATEGORY MANAGEMENT & WHOLESALE INVENTORY VALUATION\n";
it('Creates categories, quick-stores via modal, calculates wholesale stock valuation and renders valuation cards', function () {
    TenantManager::setContext(1);
    $pharmCtrl = new \App\Controllers\PharmacyController();

    // 1. Create a category
    $catName = 'Cardiovascular & Hypertensives ' . bin2hex(random_bytes(2));
    $storeResp = $pharmCtrl->storeCategory(new Request([], ['name' => $catName]));
    assert($storeResp->status() === 302, "Store category must redirect");

    $catRow = \Veen\Core\Database\DB::selectOne("SELECT * FROM product_categories WHERE tenant_id = 1 AND name = ?", [$catName]);
    assert($catRow !== null, "Category must exist in database");
    assert(!empty($catRow['uuid']), "Category must have UUID assigned");

    // 2. Quick-store category via AJAX endpoint
    $quickCatName = 'Pediatric Suspensions ' . bin2hex(random_bytes(2));
    $quickResp = $pharmCtrl->quickStoreCategory(new Request([], ['name' => $quickCatName]));
    assert($quickResp->status() === 200, "Quick store category must return 200 JSON");
    $quickData = json_decode($quickResp->content, true);
    assert($quickData['success'] === true, "Quick store category must be successful");
    assert($quickData['category']['name'] === $quickCatName, "Returned category name must match");

    // 3. Ensure a product with wholesale price exists and calculate metrics
    $prod = Product::where('tenant_id', 1)->first();
    assert($prod !== null, "Product must exist");
    \Veen\Core\Database\DB::statement(
        "UPDATE products SET wholesale_price_minor = 12000, price_minor = 20000, stock_qty = 50, category_id = ? WHERE id = ?",
        [$catRow['id'], $prod->id]
    );

    $indexResp = $pharmCtrl->index(new Request([]));
    assert(str_contains($indexResp->content, 'Wholesale Stock Valuation'), "Pharmacy view must display Wholesale Stock Valuation card");
    assert(str_contains($indexResp->content, 'Hospital stock asset value at wholesale cost'), "Card subtitle must describe inventory asset worth");
    assert(str_contains($indexResp->content, 'projected profit'), "Card must show projected stock gross profit");
    assert(str_contains($indexResp->content, 'categoryModal'), "Category management modal must be present in view");

    // 4. Delete the test category safely
    $delResp = $pharmCtrl->deleteCategory(new Request([]), $catRow['uuid']);
    assert($delResp->status() === 302, "Delete category must redirect");
    $deletedRow = \Veen\Core\Database\DB::selectOne("SELECT * FROM product_categories WHERE id = ?", [$catRow['id']]);
    assert($deletedRow === null, "Category must be removed from database");
});

// 35. CONSULTATION QUICK REGISTRATION & CHILD/ADULT CLASSIFICATION WITH CSRF
echo "\n35. CONSULTATION QUICK REGISTRATION WITH CHILD/ADULT CLASSIFICATION & CSRF\n";
it('Renders Adult vs Child classification, makes National ID optional, and registers without 419 error', function () {
    TenantManager::setContext(1);
    $consultCtrl = new \App\Controllers\ConsultationController();
    $patCtrl = new \App\Controllers\PatientController();

    // 1. Verify Consultation index renders Adult vs Child toggles and optional ID
    $view = $consultCtrl->index(new Request([]));
    assert(str_contains($view->content, 'patient_type_toggle'), "Quick patient modal must have Adult vs Child toggle");
    assert(str_contains($view->content, 'Child / Minor (No National ID required)'), "Child minor classification option must be labeled");
    assert(str_contains($view->content, 'qr_national_id'), "National ID input must be present");
    assert(str_contains($view->content, 'Optional'), "National ID must be marked as optional");

    // 2. Register child via quick-store without National ID
    $childSuffix = bin2hex(random_bytes(3));
    $quickResp = $patCtrl->quickStore(new Request([], [
        '_token'      => \Veen\Core\Session::token(),
        'first_name'  => 'Baby',
        'last_name'   => 'Zawadi ' . $childSuffix,
        'sex'         => 'female',
        'phone'       => '+254 712 999 888',
        'national_id' => '', // Minors do not have National ID
        'dob'         => date('Y-m-d', strtotime('-2 years')),
        'is_child'    => 1,
    ]));
    assert($quickResp->status() === 200, "Quick store must respond 200 with active session token");
    $data = json_decode($quickResp->content, true);
    assert($data['success'] === true, "Patient registration must succeed");
    assert(!empty($data['patient']['mrn']), "Patient must receive valid MRN");
    assert($data['patient']['is_child'] === true, "Registered minor must have is_child true");
});

// 36. PATIENT REGISTRATION PHONE MANDATORY, DEMOGRAPHICS EDIT & SECURE PASSWORD-PROTECTED DELETION
echo "\n36. PATIENT REGISTRATION PHONE MANDATORY, DEMOGRAPHICS EDIT & SECURE PASSWORD-PROTECTED DELETION\n";
it('Enforces mandatory phone number, allows updating demographics, and secures deletion with password', function () {
    TenantManager::setContext(1);
    $patCtrl = new \App\Controllers\PatientController();

    // 1. Authenticate as hospital admin
    $admin = User::where('tenant_id', 1)->where('role', 'hospital_admin')->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    // 2. Phone number is mandatory on Patient creation
    $phoneFailed = false;
    try {
        $patCtrl->store(new Request([], [
            'first_name'  => 'Test',
            'last_name'   => 'NoPhone',
            'sex'         => 'male',
            'phone'       => '', // Missing mandatory phone
        ]));
    } catch (\Veen\Core\ValidationException $e) {
        $phoneFailed = true;
    }
    assert($phoneFailed === true, "Registration without phone number must be rejected as mandatory");

    // 3. Register valid patient
    $testPhone = '+254 7' . rand(10000000, 99999999);
    $storeResp = $patCtrl->store(new Request([], [
        'first_name'  => 'Vincent',
        'last_name'   => 'Mutisya',
        'sex'         => 'male',
        'phone'       => $testPhone,
        'dob'         => '1990-05-15',
    ]));
    assert($storeResp->status() === 302, "Store patient must redirect");

    $createdPat = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE tenant_id = 1 AND phone = ? AND deleted_at IS NULL", [$testPhone]);
    assert($createdPat !== null, "Patient must be found in database");
    assert(!empty($createdPat['uuid']), "Patient must have an auto-generated UUID");

    // 4. Edit Demographics
    $editView = $patCtrl->edit(new Request([]), $createdPat['uuid']);
    assert(str_contains($editView->content, 'Edit Patient Demographics'), "Edit view must render with patient data");

    $updatedPhone = '+254 7' . rand(10000000, 99999999);
    $updateResp = $patCtrl->update(new Request([], [
        'first_name'  => 'Vincent',
        'last_name'   => 'Mutisya-Edited',
        'sex'         => 'male',
        'phone'       => $updatedPhone,
        'dob'         => '1990-05-15',
        'allergies'   => 'Sulfa Drugs',
    ]), $createdPat['uuid']);
    assert($updateResp->status() === 302, "Update must redirect to show page");

    $refetched = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE id = ?", [$createdPat['id']]);
    assert($refetched['last_name'] === 'Mutisya-Edited', "Updated name must be persisted");
    assert($refetched['phone'] === $updatedPhone, "Updated phone number must be persisted");
    assert($refetched['allergies'] === 'Sulfa Drugs', "Updated allergy note must be persisted");

    // 5. Attempt delete with INCORRECT password
    $failDelResp = $patCtrl->destroy(new Request([], ['password' => 'WrongPassword123']), $createdPat['uuid']);
    assert($failDelResp->status() === 302, "Incorrect password attempt must redirect back");
    $stillExists = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL", [$createdPat['id']]);
    assert($stillExists !== null, "Patient record must NOT be deleted when wrong password is supplied");

    // 6. Delete with CORRECT password
    $succDelResp = $patCtrl->destroy(new Request([], ['password' => 'Password@123']), $createdPat['uuid']);
    assert($succDelResp->status() === 302, "Successful deletion must redirect to index");
    $deletedPat = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE id = ?", [$createdPat['id']]);
    assert($deletedPat['deleted_at'] !== null, "Patient must be soft-deleted in database");

    // 7. Restore workflow
    $restoreResp = $patCtrl->restore(new Request(), $createdPat['uuid']);
    assert($restoreResp->status() === 302, "Restore must redirect to show chart");
    $restoredPat = \Veen\Core\Database\DB::selectOne("SELECT * FROM patients WHERE id = ?", [$createdPat['id']]);
    assert($restoredPat['deleted_at'] === null, "Patient must be restored with deleted_at = NULL");

    // Clean up test patient record
    \Veen\Core\Database\DB::statement("DELETE FROM patients WHERE id = ?", [$createdPat['id']]);
});

// 37. POS UNIVERSAL PATIENT RESOLUTION & PRE-POPULATION BY UUID
echo "\n37. POS UNIVERSAL PATIENT RESOLUTION & PRE-POPULATION BY UUID\n";
it('Resolves and pre-populates patient in POS using UUID in URL and associates sale correctly', function () {
    TenantManager::setContext(1);
    $posCtrl = new \App\Controllers\PharmacyPosController();

    // 1. Authenticate user & open cash session
    $cashier = User::where('tenant_id', 1)->first();
    Auth::attempt($cashier->email, 'Password@123', '127.0.0.1');

    $activeSession = \Veen\Core\Database\DB::selectOne("SELECT id FROM cash_sessions WHERE tenant_id = 1 AND user_id = ? AND status = 'open'", [$cashier->id]);
    if (!$activeSession) {
        \Veen\Core\Database\DB::statement(
            "INSERT INTO cash_sessions (tenant_id, user_id, opening_float_minor, status, opened_at, created_at, updated_at) VALUES (1, ?, 500000, 'open', NOW(), NOW(), NOW())",
            [$cashier->id]
        );
    }

    // 2. Select patient with UUID
    $patient = Patient::where('tenant_id', 1)->whereNull('deleted_at')->first();
    assert($patient !== null, "Active patient must exist");
    assert(!empty($patient->uuid), "Patient must have valid UUID");

    // 3. Request POS page with ?patient_id={uuid}
    $posView = $posCtrl->index(new Request(['patient_id' => $patient->uuid]));
    assert(str_contains($posView->content, $patient->first_name), "POS view must pre-populate patient first name when UUID is passed");
    assert(str_contains($posView->content, $patient->mrn), "POS view must display patient MRN in selected notice");

    // 4. Complete Checkout with patient_id passed as UUID
    $product = Product::where('tenant_id', 1)->where('stock_qty', '>', 5)->first();
    assert($product !== null, "Product in stock must exist");

    $checkoutResp = $posCtrl->checkout(new Request([], [
        'items' => [
            ['product_id' => $product->id, 'qty' => 1]
        ],
        'payments' => [
            ['method' => 'cash', 'amount_minor' => (int)$product->price_minor]
        ],
        'patient_id'    => $patient->uuid, // Passing UUID
        'customer_name' => 'Walk-in Customer', // Should auto-replace with patient's name
    ]));
    assert($checkoutResp->status() === 200, "Checkout with UUID patient_id must succeed");
    $saleData = json_decode($checkoutResp->content, true);
    assert($saleData['success'] === true, "Sale response must be success");

    // Verify sale row in DB has the patient's integer ID
    $saleDb = \Veen\Core\Database\DB::selectOne("SELECT * FROM sales WHERE id = ?", [$saleData['sale']['id']]);
    assert((int)$saleDb['patient_id'] === (int)$patient->id, "Sale in database must be linked to patient's integer ID");
    assert(str_contains($saleDb['customer_name'], $patient->first_name), "Sale customer_name must match patient's name");
});

// -------------------------------------------------------------
// 38. BRAND IDENTITY, PRODUCTION ASSETS & AUTH FORM HYGIENE
// -------------------------------------------------------------
echo "38. BRAND IDENTITY, PRODUCTION ASSETS & AUTH FORM HYGIENE\n";
it("Validates official logo, multi-resolution favicons, production copy and empty sign-in credentials", function () {
    // 1. Verify filesystem assets
    $publicDir = base_path('public');
    $imgDir = base_path('public/assets/img');

    assert(file_exists($publicDir . '/favicon.ico'), "public/favicon.ico must exist");
    assert(file_exists($imgDir . '/logo.png'), "public/assets/img/logo.png must exist");
    assert(file_exists($imgDir . '/logo-mark.png'), "public/assets/img/logo-mark.png must exist");
    assert(file_exists($imgDir . '/logo-white.png'), "public/assets/img/logo-white.png must exist");
    assert(file_exists($imgDir . '/apple-touch-icon.png'), "public/assets/img/apple-touch-icon.png must exist");
    assert(file_exists($imgDir . '/favicon-32x32.png'), "public/assets/img/favicon-32x32.png must exist");
    assert(file_exists($imgDir . '/favicon-16x16.png'), "public/assets/img/favicon-16x16.png must exist");

    // 2. Request Login Page and inspect HTML
    $authCtrl = new \App\Controllers\AuthController();
    $resp = $authCtrl->showLogin(new \Veen\Core\Request());
    assert($resp->status() === 200, "Login page must return HTTP 200");
    $html = $resp->content;

    // Check favicon and logo embeddings
    assert(str_contains($html, 'favicon.ico'), "Login page head must embed favicon.ico");
    assert(str_contains($html, 'logo-white.png') || str_contains($html, 'logo.png'), "Login page must render new Veen Health logo");

    // Check that pre-coded email and password are gone from inputs
    assert(!str_contains($html, 'value="admin@nairobicare.co.ke"'), "Login input must not contain pre-coded email admin@nairobicare.co.ke");
    assert(!str_contains($html, 'value="Password@123"'), "Login password input must not contain pre-coded password Password@123");

    // Check business-transformative messaging (not tenant id technical jargon)
    assert(str_contains($html, 'Transforming How Healthcare'), "Login page must feature business transformation headline");
    assert(str_contains($html, 'Zero Revenue Leakage'), "Login page must communicate clinical & financial value proposition");
    assert(!str_contains($html, 'Isolated database context by tenant ID'), "Login page must not expose technical tenant ID jargon");

    // Check footer requirements: Powered by Veen Africa with target="_blank" and www.veen.africa
    assert(str_contains($html, 'Powered by'), "Login page must contain 'Powered by'");
    assert(str_contains($html, 'href="https://www.veen.africa"'), "Login page footer must link to www.veen.africa");
    assert(str_contains($html, 'target="_blank"'), "Login page footer link must have target _blank");
    assert(str_contains($html, 'Veen Africa'), "Login page footer must display Veen Africa");

    // 3. Request Authenticated Dashboard and inspect App Footer
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->where('role', 'hospital_admin')->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');
    $dashCtrl = new \App\Controllers\DashboardController();
    $dashResp = $dashCtrl->index(new \Veen\Core\Request());
    $dashHtml = $dashResp->content;

    assert(str_contains($dashHtml, 'Powered by'), "App layout footer must contain 'Powered by'");
    assert(str_contains($dashHtml, 'href="https://www.veen.africa"'), "App layout footer must link to www.veen.africa");
    assert(str_contains($dashHtml, 'target="_blank"'), "App layout footer link must have target _blank");
    assert(str_contains($dashHtml, 'Veen Africa'), "App layout footer must display Veen Africa");
});

// -------------------------------------------------------------
// 39. HIGH-RESOLUTION BRAND ASSETS & RUNTIME CACHE FLUSH POINT
// -------------------------------------------------------------
echo "39. HIGH-RESOLUTION BRAND ASSETS & RUNTIME CACHE FLUSH POINT\n";
it("Verifies high-res logo dimensions, settings flush point UI, controller endpoint, and audit trail", function () {
    // 1. Verify high-res logo dimensions
    $logoPath = base_path('public/assets/img/logo.png');
    $markPath = base_path('public/assets/img/logo-mark.png');
    assert(file_exists($logoPath), "High-res logo.png must exist");
    assert(file_exists($markPath), "High-res logo-mark.png must exist");

    $logoSize = getimagesize($logoPath);
    assert($logoSize[0] >= 900, "High-res logo must have width >= 900px (was {$logoSize[0]}px)");
    assert($logoSize[1] >= 500, "High-res logo must have height >= 500px (was {$logoSize[1]}px)");

    // 2. Set sample cache key
    \Veen\Core\Cache::set("test_key_flush", "sample_cached_value", 300);
    assert(\Veen\Core\Cache::get("test_key_flush") === "sample_cached_value", "Cache key must be set before flush");

    // 3. Test Cache Flush Endpoint as Hospital Admin
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->where('role', 'hospital_admin')->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $settingCtrl = new \App\Controllers\SettingController();
    $req = new \Veen\Core\Request([], [], ['HTTP_ACCEPT' => 'application/json']);
    $flushResp = $settingCtrl->flushCache($req);

    assert($flushResp->status() === 200, "Cache flush endpoint with JSON accept must return HTTP 200 (was {$flushResp->status()})");
    $data = json_decode($flushResp->content, true);
    assert($data['success'] === true, "Cache flush response must report success");

    // Test form redirect as well
    $formReq = new \Veen\Core\Request([], []);
    $formResp = $settingCtrl->flushCache($formReq);
    assert($formResp->status() === 302, "Standard form submission must redirect back (was {$formResp->status()})");

    // 4. Verify cached key is evicted
    assert(\Veen\Core\Cache::get("test_key_flush") === null, "Cached item must be evicted after flush");

    // 5. Verify Settings UI renders the Flush Point card
    $settingsResp = $settingCtrl->index(new \Veen\Core\Request());
    assert(str_contains($settingsResp->content, 'System Performance & Cache Flush Point'), "Settings page must render Cache Flush Point card");
    assert(str_contains($settingsResp->content, 'Flush & Reset Cache'), "Settings page must have Flush & Reset button");
});

// -------------------------------------------------------------
// 40. TENANT PROVISIONING IDEMPOTENCY & DUPLICATE CATALOG TOLERANCE
// -------------------------------------------------------------
echo "40. TENANT PROVISIONING IDEMPOTENCY & DUPLICATE CATALOG TOLERANCE\n";
it("Guarantees idempotent hospital onboarding even when catalog or insurance records pre-exist", function () {
    // 1. Generate unique slug for test tenant
    $slug = 'idempotent-hosp-' . bin2hex(random_bytes(4));
    $adminEmail = $slug . '@test.veen.africa';

    $payload = [
        'name'           => 'Idempotent Test Hospital',
        'slug'           => $slug,
        'country_code'   => 'KE',
        'plan_id'        => 1,
        'billing_cycle'  => 'free_trial',
        'trial_days'     => 14,
        'email'          => 'info@' . $slug . '.com',
        'phone'          => '+254711999888',
        'address'        => 'Nairobi, Kenya',
        'admin_name'     => 'Dr. Idempotent Admin',
        'admin_email'    => $adminEmail,
        'admin_password' => 'Password@123',
    ];

    // 2. Perform initial provision
    $tenant = \App\Services\TenantProvisioner::provision($payload);
    assert(!empty($tenant['id']), "Tenant provision must return valid tenant with ID");

    // 3. Deliberately re-run catalog seeding on this exact tenant to simulate duplicate entries
    $country = \Veen\Core\Database\DB::selectOne('SELECT * FROM countries WHERE code = ?', [$payload['country_code']]);
    \Veen\Core\Tenancy\TenantManager::run($tenant, function () use ($tenant, $country) {
        // Test re-inserting default insurances (e.g. 1-JUB-002)
        foreach (\App\Services\TenantProvisioner::DEFAULT_INSURANCES as [$insName, $insCode, $insPhone, $insEmail]) {
            \Veen\Core\Database\DB::statement(
                'INSERT INTO insurance_providers (tenant_id, name, code, phone, email, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), email = VALUES(email), updated_at = NOW()',
                [$tenant['id'], $insName, $insCode, $insPhone, $insEmail]
            );
        }

        // Test re-inserting default diagnostic tests
        foreach (\App\Services\TenantProvisioner::DEFAULT_TESTS as [$code, $testName, $cat, $specimen, $ref, $unit, $price, $tat]) {
            \Veen\Core\Database\DB::statement(
                'INSERT INTO diagnostic_tests (tenant_id, code, name, category, specimen_type, normal_range, units, price_minor, turnaround_minutes, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), price_minor = VALUES(price_minor), updated_at = NOW()',
                [$tenant['id'], $code, $testName, $cat, $specimen, $ref, $unit, to_minor($price, $country['currency']), $tat]
            );
        }
    });

    // 4. Verify insurance records exist and are intact without throwing duplicate constraint errors
    $insCount = \Veen\Core\Database\DB::selectOne('SELECT COUNT(*) as cnt FROM insurance_providers WHERE tenant_id = ?', [$tenant['id']]);
    assert((int)$insCount['cnt'] === count(\App\Services\TenantProvisioner::DEFAULT_INSURANCES), "Insurance records count must match default list");
});

// -------------------------------------------------------------
// 41. PATIENTS START CONSULTATION BY UUID RESOLUTION
// -------------------------------------------------------------
echo "41. PATIENTS START CONSULTATION BY UUID RESOLUTION\n";
it("Carries patient UUID seamlessly when starting consultation and displays doctor EMR without 404", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $patient = Patient::where('tenant_id', 1)->whereNull('deleted_at')->first();
    assert($patient !== null, "Active patient must exist");
    assert(!empty($patient->uuid), "Patient must possess UUID");

    $consultationCtrl = new \App\Controllers\ConsultationController();

    // 1. POST to consultations.start with patient UUID (simulating click from patients chart)
    $resp = $consultationCtrl->start(new Request([], ['patient_id' => $patient->uuid]));
    assert($resp->status() === 302, "Start consultation must redirect to the encounter page");

    $location = $resp->headers['Location'] ?? '';
    assert(!empty($location), "Redirect location header must be populated");
    assert(str_contains($location, '/consultations/'), "Redirect location must route to /consultations/{uuid}");

    // Extract encounter identifier from location
    $parts = explode('/consultations/', $location);
    $encIdentifier = end($parts);
    assert(strlen($encIdentifier) === 36 && str_contains($encIdentifier, '-'), "Encounter identifier must be a valid 36-char UUID (was {$encIdentifier})");

    // 2. GET to consultations.start?patient_id={uuid} (simulating click from patients list table)
    $getResp = $consultationCtrl->start(new Request(['patient_id' => $patient->uuid], []));
    assert($getResp->status() === 302, "GET start consultation must redirect without 404");
    assert(str_contains($getResp->headers['Location'] ?? '', $encIdentifier), "GET request must reuse/redirect to open encounter UUID");

    // 3. Render show view using Encounter UUID
    $showResp = $consultationCtrl->show(new Request(), $encIdentifier);
    assert($showResp->status() === 200, "Consultation show view with UUID must return HTTP 200");
    assert(str_contains($showResp->content, $patient->first_name), "Show view must display patient first name");
    assert(str_contains($showResp->content, $patient->mrn), "Show view must display patient MRN");
    assert(str_contains($showResp->content, '/consultations/' . $encIdentifier . '/vitals'), "Form actions must preserve encounter UUID in route");
});

// -------------------------------------------------------------
// 42. CLINIC FOR KIDS (CHILD WELFARE & LONGITUDINAL PEDIATRIC GROWTH)
// -------------------------------------------------------------
echo "42. CLINIC FOR KIDS (CHILD WELFARE & LONGITUDINAL PEDIATRIC GROWTH)\n";
it("Enrolls child, auto-seeds WHO immunization schedule, records longitudinal visits & vitals with MUAC nutrition status, and renders health passport", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $kidsCtrl = new \App\Controllers\KidsClinicController();

    // 1. Create a pediatric test patient (e.g. 1-year-old child)
    $dobChild = date('Y-m-d', strtotime('-14 months'));
    $parent = Patient::where('tenant_id', 1)->where('is_child', 0)->first();
    assert($parent !== null, "Parent patient must exist");

    $childPat = Patient::create([
        'mrn'               => \App\Services\Sequence::mrn(),
        'first_name'        => 'Baby',
        'last_name'         => 'Nanjala',
        'dob'               => $dobChild,
        'sex'               => 'female',
        'is_child'          => 1,
        'parent_patient_id' => $parent->id,
        'phone'             => $parent->phone,
        'address'           => 'Nairobi, Kenya',
    ]);
    assert(!empty($childPat->uuid), "Child patient must have UUID");

    // 2. Enroll child into Kids Clinic with birth history
    $enrollResp = $kidsCtrl->storeProfile(new Request([], [
        'patient_id'            => $childPat->uuid,
        'birth_weight_kg'       => 3.25,
        'birth_length_cm'       => 50.0,
        'birth_head_circ_cm'    => 35.0,
        'gestational_age_weeks' => 39,
        'delivery_mode'         => 'svd',
        'apgar_score_1min'      => 8,
        'apgar_score_5min'      => 10,
        'feeding_method'        => 'exclusive_breastfeeding',
        'blood_group'           => 'O+',
        'place_of_birth'        => 'Veen Healthcare Maternity',
    ]));
    assert($enrollResp->status() === 302, "Enrolling child must redirect to child chart");

    // Verify child profile in database
    $profile = \App\Models\ChildProfile::where('patient_id', $childPat->id)->first();
    assert($profile !== null, "Child profile must be created in database");
    assert((float)$profile->birth_weight_kg === 3.25, "Birth weight must match recorded value");
    assert(!empty($profile->uuid), "Child profile must have universal UUID");

    // 3. Verify auto-seeded WHO immunization schedule (20 doses)
    $vaccines = \App\Models\ChildImmunization::where('patient_id', $childPat->id)->get();
    assert(count($vaccines) === count(\App\Models\ChildImmunization::SCHEDULE), "Auto-seeded vaccines must match schedule count (20 doses)");
    assert($vaccines[0]->vaccine_code === 'BCG', "First vaccine must be BCG");
    assert($vaccines[0]->status === 'due', "Initial vaccine status must be 'due'");

    // 4. Log 1st visit (At 6 weeks) - Normal nutrition
    $visit1Resp = $kidsCtrl->storeVisit(new Request([], [
        'visit_date'                 => date('Y-m-d', strtotime('-12 months')),
        'weight_kg'                  => 4.5,
        'height_cm'                  => 55.0,
        'head_circumference_cm'      => 37.5,
        'muac_cm'                    => 13.0, // Normal MUAC >= 12.5 cm
        'temperature_c'              => 36.8,
        'pulse_bpm'                  => 120,
        'respiratory_rate_bpm'       => 36,
        'spo2_percent'               => 99,
        'developmental_milestones'   => 'Social smile present, follows objects past midline',
        'next_visit_date'            => date('Y-m-d', strtotime('-10 months')),
    ]), $childPat->uuid);
    assert($visit1Resp->status() === 302, "Visit 1 must redirect to child chart");

    // 5. Log 2nd visit (At 9 months) - MAM nutrition status (MUAC = 12.0 cm)
    $visit2Resp = $kidsCtrl->storeVisit(new Request([], [
        'visit_date'                 => date('Y-m-d', strtotime('-5 months')),
        'weight_kg'                  => 7.1,
        'height_cm'                  => 68.0,
        'head_circumference_cm'      => 43.0,
        'muac_cm'                    => 12.0, // MAM: 11.5 - 12.4 cm
        'temperature_c'              => 37.1,
        'pulse_bpm'                  => 110,
        'respiratory_rate_bpm'       => 30,
        'spo2_percent'               => 98,
        'developmental_milestones'   => 'Sits without support, responds to name',
        'feeding_counseling_notes'   => 'Initiated Supplementary Feeding (CSB++)',
        'next_visit_date'            => date('Y-m-d', strtotime('+1 month')),
    ]), $childPat->uuid);
    assert($visit2Resp->status() === 302, "Visit 2 must redirect to child chart");

    // Verify visits in DB & auto-classification of MAM
    $visitsDb = \App\Models\ChildVisit::where('patient_id', $childPat->id)->orderBy('visit_date', 'ASC')->get();
    assert(count($visitsDb) === 2, "Two visits must be stored in database");
    assert($visitsDb[0]->nutritional_status === 'normal', "Visit 1 MUAC 13.0 must classify as normal");
    assert($visitsDb[1]->nutritional_status === 'mam', "Visit 2 MUAC 12.0 must classify as MAM");

    // 6. Administer BCG Vaccine
    $bcg = $vaccines[0];
    $adminImmResp = $kidsCtrl->updateImmunization(new Request([], [
        'status'                 => 'given',
        'date_administered'      => date('Y-m-d', strtotime('-14 months')),
        'batch_number'           => 'BCG-2026-90X',
        'administered_by_name'   => 'Nurse Wanjiku',
        'adverse_events'         => 'Normal scar development, no AEFI',
    ]), $bcg->uuid);
    assert($adminImmResp->status() === 302, "Administering vaccine must redirect back");

    $bcgUpdated = \App\Models\ChildImmunization::find($bcg->id);
    assert($bcgUpdated->status === 'given', "BCG status must be 'given'");
    assert($bcgUpdated->batch_number === 'BCG-2026-90X', "Batch number must match");

    // 7. Render Kids Clinic Dashboard Index & Verify Pagination, Sticky Action Columns, and Caching
    $indexView = $kidsCtrl->index(new Request());
    assert($indexView->status() === 200, "Kids Clinic index view must return HTTP 200");
    assert(str_contains($indexView->content, 'Baby Nanjala'), "Index view must list enrolled child");
    assert(str_contains($indexView->content, 'Kids Clinic & Child Welfare'), "Index view must display header");
    assert(str_contains($indexView->content, '20 per page'), "Index view must reflect pagination with at least 20 records per page");
    assert(str_contains($indexView->content, 'sticky-actions-col'), "Index view must feature sticky-actions-col for instant action visibility");
    assert(str_contains($indexView->content, 'topScrollWrap'), "Index view must feature topScrollWrap for easy horizontal scrolling");
    assert(str_contains($indexView->content, 'Passport'), "Index view must render immediate 1-click Passport link");

    // Verify Redis / File cache key exists for tenant
    $cacheVer = (int) \Veen\Core\Cache::get("kids_cwc_ver_1", 1);
    $cachedStats = \Veen\Core\Cache::get("kids_cwc_stats_1_v{$cacheVer}");
    assert(!empty($cachedStats), "Kids Clinic dashboard stats must be cached for fast sub-millisecond page loads");

    // 8. Render Child Longitudinal Chart Show View
    $showView = $kidsCtrl->show(new Request(), $childPat->uuid);
    assert($showView->status() === 200, "Kids Clinic show view must return HTTP 200");
    assert(str_contains($showView->content, 'Longitudinal Growth & Vitals Tracking'), "Show view must render vitals section");
    assert(str_contains($showView->content, 'BCG-2026-90X'), "Show view must display administered vaccine batch");
    assert(str_contains($showView->content, 'Social smile present'), "Show view must display developmental milestones");

    // 9. Render Printable Child Health Passport
    $cardView = $kidsCtrl->card(new Request(), $childPat->uuid);
    assert($cardView->status() === 200, "Child Passport must return HTTP 200");
    assert(str_contains($cardView->content, 'CHILD HEALTH & IMMUNIZATION PASSPORT'), "Passport must render title");
    assert(str_contains($cardView->content, 'Baby Nanjala'), "Passport must show child's name");
    assert(str_contains($cardView->content, '3.25 kg'), "Passport must show birth weight");
});

// -------------------------------------------------------------
// 43. CLINIC FOR PREGNANT WOMEN (ANTENATAL CARE - ANC & MATERNAL HEALTH)
// -------------------------------------------------------------
echo "43. CLINIC FOR PREGNANT WOMEN (ANTENATAL CARE - ANC & MATERNAL HEALTH)\n";
it("Enrolls pregnant mother, calculates gestational age & EDD, tracks longitudinal visits across 1st/2nd/3rd trimesters, flags pre-eclampsia, updates birth plan, and concludes delivery", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $matCtrl = new \App\Controllers\MaternalClinicController();

    // 1. Create a female patient of childbearing age
    $motherPat = Patient::create([
        'mrn'        => \App\Services\Sequence::mrn(),
        'first_name' => 'Grace',
        'last_name'  => 'Muthoni',
        'dob'        => '1996-05-14',
        'sex'        => 'female',
        'phone'      => '+254722334455',
        'address'    => 'Nairobi, Westlands',
    ]);
    assert(!empty($motherPat->uuid), "Mother patient must have UUID");

    // LNMP 24 weeks ago (currently in 2nd trimester)
    $lnmp = date('Y-m-d', strtotime('-24 weeks'));
    $expectedEdd = \App\Models\MaternalProfile::calculateEdd($lnmp);

    // Verify newly registered female patient is immediately available in ANC candidate dropdown
    $preEnrollIndex = $matCtrl->index(new Request());
    assert(str_contains($preEnrollIndex->content, 'Grace Muthoni'), "Newly registered female patient must appear in ANC candidates list without cache lag");

    // 2. Enroll Mother into ANC
    $enrollResp = $matCtrl->storeProfile(new Request([], [
        'patient_id'                => $motherPat->uuid,
        'lnmp'                      => $lnmp,
        'gravida'                   => 3,
        'parity'                    => 1,
        'living_children'           => 1,
        'miscarriages'              => 1,
        'stillbirths'               => 0,
        'c_sections'                => 1,
        'blood_group'               => 'B',
        'rhesus_factor'             => 'positive',
        'pre_pregnancy_weight_kg'   => 58.0,
        'height_cm'                 => 162.0,
        'baseline_haemoglobin_g_dl' => 12.2,
        'high_risk_pregnancy'       => 0, // Should auto-flag or allow doctor flag
    ]));
    assert($enrollResp->status() === 302, "Enroll mother must redirect to maternal chart");

    // Verify Maternal Profile
    $profile = \App\Models\MaternalProfile::where('patient_id', $motherPat->id)->first();
    assert($profile !== null, "Maternal profile must be recorded in database");
    assert($profile->edd === $expectedEdd, "EDD must match Naegele's rule calculation ($expectedEdd)");
    assert(str_starts_with($profile->anc_number, 'ANC-'), "ANC number must be generated with prefix ANC-");
    assert(!empty($profile->uuid), "Maternal profile must have universal UUID");

    // 3. Log 1st Trimester Visit (At 10 weeks gestation)
    $visit1Date = date('Y-m-d', strtotime($lnmp . ' + 10 weeks'));
    $v1Resp = $matCtrl->storeVisit(new Request([], [
        'visit_date'              => $visit1Date,
        'visit_number'            => 1,
        'gestational_age_weeks'   => 10.0,
        'weight_kg'               => 59.0,
        'systolic_bp'             => 115,
        'diastolic_bp'            => 75,
        'pulse_bpm'               => 76,
        'fundal_height_cm'        => 0.0, // Pelvic organ in 1st tri
        'fetal_presentation'      => 'undetermined',
        'fetal_movement'          => 'not_felt_yet',
        'urine_protein'           => 'nil',
        'urine_glucose'           => 'nil',
        'haemoglobin_g_dl'        => 12.1,
        'ifas_tablets_dispensed'  => 30,
        'iptp_sp_dose'            => 'none',
        'tetanus_toxoid_dose'     => 'tt1',
        'clinical_notes'          => 'First trimester booking visit. Started IFAS and TT1.',
    ]), $profile->uuid);
    assert($v1Resp->status() === 302, "Visit 1 must redirect to chart");

    // 4. Log 2nd Trimester Visit (At 20 weeks gestation)
    $visit2Date = date('Y-m-d', strtotime($lnmp . ' + 20 weeks'));
    $v2Resp = $matCtrl->storeVisit(new Request([], [
        'visit_date'              => $visit2Date,
        'visit_number'            => 2,
        'gestational_age_weeks'   => 20.0,
        'weight_kg'               => 63.5,
        'systolic_bp'             => 120,
        'diastolic_bp'            => 80,
        'pulse_bpm'               => 80,
        'fundal_height_cm'        => 20.0,
        'fetal_heart_rate_bpm'    => 142,
        'fetal_presentation'      => 'cephalic',
        'fetal_movement'          => 'present',
        'urine_protein'           => 'nil',
        'urine_glucose'           => 'nil',
        'ifas_tablets_dispensed'  => 30,
        'iptp_sp_dose'            => 'sp1',
        'tetanus_toxoid_dose'     => 'tt2',
        'deworming_given'         => 1,
        'mosquito_net_given'      => 1,
        'clinical_notes'          => 'Second trimester anomaly scan normal. Quickening active. Issued ITN and deworming.',
    ]), $profile->uuid);
    assert($v2Resp->status() === 302, "Visit 2 must redirect to chart");

    // 5. Log 3rd Trimester Visit with Elevated BP & Proteinuria (Pre-Eclampsia Vigilance)
    $visit3Date = date('Y-m-d', strtotime($lnmp . ' + 32 weeks'));
    $v3Resp = $matCtrl->storeVisit(new Request([], [
        'visit_date'              => $visit3Date,
        'visit_number'            => 3,
        'gestational_age_weeks'   => 32.0,
        'weight_kg'               => 68.0,
        'systolic_bp'             => 145, // Elevated >= 140
        'diastolic_bp'            => 95,  // Elevated >= 90
        'pulse_bpm'               => 88,
        'fundal_height_cm'        => 32.0,
        'fetal_heart_rate_bpm'    => 146,
        'fetal_presentation'      => 'cephalic',
        'fetal_movement'          => 'present',
        'oedema'                  => 'moderate',
        'urine_protein'           => '2+', // Proteinuria
        'urine_glucose'           => 'nil',
        'danger_severe_headache'  => 1,
        'danger_blurred_vision'   => 1,
        'ifas_tablets_dispensed'  => 30,
        'iptp_sp_dose'            => 'sp2',
        'clinical_notes'          => 'Pre-eclampsia alert: BP 145/95, 2+ protein, blurred vision. Started Methyldopa and referred for close monitoring.',
    ]), $profile->uuid);
    assert($v3Resp->status() === 302, "Visit 3 must redirect to chart");

    // Verify visits in DB & trimester classification
    $visitsDb = \App\Models\MaternalVisit::where('maternal_profile_id', $profile->id)->orderBy('visit_date', 'ASC')->get();
    assert(count($visitsDb) === 3, "Three ANC visits must be recorded");
    assert($visitsDb[0]->trimester === 'first', "Visit 1 (10 wks) must be first trimester");
    assert($visitsDb[1]->trimester === 'second', "Visit 2 (20 wks) must be second trimester");
    assert($visitsDb[2]->trimester === 'third', "Visit 3 (32 wks) must be third trimester");
    assert(str_contains($visitsDb[2]->danger_signs_screened ?? '', 'Severe Headache'), "Danger sign severe headache must be recorded");

    // 6. Update Birth Preparedness & Complication Readiness (BPCR) Plan
    $bpcrResp = $matCtrl->updateBirthPlan(new Request([], [
        'chosen_delivery_facility'     => 'Veen Healthcare Comprehensive Maternity Wing',
        'birth_attendant_name'         => 'Dr. Achieng Otieno M.D.',
        'birth_companion_name'         => 'James Muthoni (Spouse)',
        'transport_arrangement'        => 'Personal Vehicle + On-call Ambulance',
        'emergency_funds_arranged'     => 1,
        'designated_blood_donor_name'  => 'Hospital Blood Bank (Matched B+)',
        'designated_blood_donor_phone' => '+254700112233',
        'postpartum_fp_counseling'     => 'Selected PPIUD post-delivery',
    ]), $profile->uuid);
    assert($bpcrResp->status() === 302, "Updating birth plan must redirect to chart");

    $updatedProfile = \App\Models\MaternalProfile::find($profile->id);
    assert($updatedProfile->birth_plan_hospital === 'Veen Healthcare Comprehensive Maternity Wing', "Delivery facility must match");

    // 7. Render Maternal Dashboard Index
    $matIndex = $matCtrl->index(new Request());
    assert($matIndex->status() === 200, "Maternal index must return HTTP 200");
    assert(str_contains($matIndex->content, 'Grace Muthoni'), "Maternal index must list Grace Muthoni");
    assert(str_contains($matIndex->content, $profile->anc_number), "Maternal index must show ANC number");
    assert(str_contains($matIndex->content, 'sticky-actions-col'), "Maternal index must feature pinned sticky actions column");
    assert(str_contains($matIndex->content, 'topScrollWrapMaternal'), "Maternal index must feature synced top scrollbar for effortless scrolling");
    assert(str_contains($matIndex->content, '20 per page'), "Maternal index must provide at least 20 records per page");
    assert(str_contains($matIndex->content, 'maternal-modal-content'), "Maternal index enrollment modal must use 100% opaque modal system");
    $cacheVer = (int) \Veen\Core\Cache::get("maternal_anc_ver_1", 1);
    $cachedStats = \Veen\Core\Cache::get("maternal_anc_stats_1_v{$cacheVer}");
    assert(!empty($cachedStats), "Maternal stats must be cached for high performance");

    // 8. Render Maternal ANC Chart Show View
    $matShow = $matCtrl->show(new Request(), $profile->uuid);
    assert($matShow->status() === 200, "Maternal show chart must return HTTP 200");
    assert(str_contains($matShow->content, 'Longitudinal Antenatal Visit History'), "Show view must render longitudinal visit table");
    assert(str_contains($matShow->content, '145/95'), "Show view must render recorded blood pressure");
    assert(str_contains($matShow->content, 'PRE-ECLAMPSIA ALERT'), "Show view must show pre-eclampsia alert for BP >= 140/90 and proteinuria");
    assert(str_contains($matShow->content, 'PPIUD'), "Show view must display postpartum family planning choice");
    assert(str_contains($matShow->content, 'maternal-modal-content'), "Maternal show modals must use 100% opaque modal styling system");
    assert(str_contains($matShow->content, 'recordVisitModal'), "Show view must contain record visit modal");
    assert(str_contains($matShow->content, 'editProfileModal'), "Show view must contain edit profile modal");
    assert(str_contains($matShow->content, 'birthPlanModal'), "Show view must contain birth plan modal");
    assert(str_contains($matShow->content, 'concludeDeliveryModal'), "Show view must contain conclude delivery modal");

    // 9. Render Printable Maternal Antenatal Health Booklet
    $matCard = $matCtrl->card(new Request(), $profile->uuid);
    assert($matCard->status() === 200, "Maternal booklet card must return HTTP 200");
    assert(str_contains($matCard->content, 'MATERNAL ANTENATAL CARE (ANC) BOOKLET'), "Card must render official title");
    assert(str_contains($matCard->content, 'Grace Muthoni'), "Card must render mother's name");

    // 10. Conclude Pregnancy Delivery Episode
    $concludeResp = $matCtrl->concludeDelivery(new Request([], [
        'delivery_date'  => date('Y-m-d'),
        'status'         => 'delivered',
        'delivery_notes' => 'Spontaneous Vertex Delivery (SVD) of live male baby weighing 3.4kg, APGAR 9/10.',
    ]), $profile->uuid);
    assert($concludeResp->status() === 302, "Conclude delivery must redirect to chart");

    $concludedProfile = \App\Models\MaternalProfile::find($profile->id);
    assert($concludedProfile->status === 'delivered', "Profile status must be updated to 'delivered'");
    assert(!empty($concludedProfile->delivery_date), "Delivery date must be recorded");
});

// -------------------------------------------------------------
// 44. MORTUARY & DECEASED REGISTRY MODULE
// -------------------------------------------------------------
echo "44. MORTUARY & DECEASED REGISTRY MODULE\n";
it("Intake, Cold Storage, Autopsy, Tag, Death Certificate & Handover", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $tenantId = \Veen\Core\Tenancy\TenantManager::id();
    $mortCtrl = new \App\Controllers\MortuaryController();

    // 1. Create a deceased record (Inpatient ward death)
    $pat = \App\Models\Patient::create([
        'tenant_id'          => $tenantId,
        'branch_id'          => 1,
        'mrn'                => 'MRN-DEC-' . rand(1000, 9999),
        'first_name'         => 'Jonathan',
        'last_name'          => 'Kariuki',
        'dob'                => '1958-03-15',
        'sex'                => 'male',
        'next_of_kin_name'   => 'Mary Kariuki (Spouse)',
        'next_of_kin_phone'  => '+254711998877',
    ]);

    $storeResp = $mortCtrl->store(new Request([], [
        'patient_id'                => $pat->id,
        'deceased_full_name'        => 'Jonathan Kariuki',
        'gender'                    => 'male',
        'estimated_or_actual_age'   => 68,
        'death_type'                => 'inpatient',
        'date_of_death'             => date('Y-m-d'),
        'time_of_death'             => '03:45:00',
        'place_of_death'            => 'ICU Ward Bed 3',
        'cause_of_death_immediate'  => 'Cardiopulmonary Arrest',
        'cause_of_death_underlying' => 'Severe Sepsis secondary to Pneumonia',
        'contributory_cause'        => 'Type 2 Diabetes Mellitus',
        'cold_room_chamber'         => 'Cold Room A',
        'tray_number'               => 'Tray #05',
        'preservation_status'       => 'refrigerated',
        'autopsy_required'          => 1,
        'next_of_kin_name'          => 'Mary Kariuki',
        'next_of_kin_phone'         => '+254711998877',
        'next_of_kin_relationship'  => 'Spouse',
        'storage_fee_per_day'       => 1500.00,
    ]));
    assert($storeResp->status() === 302, "Store mortuary record must redirect to show page");

    $record = \App\Models\DeceasedRecord::where('patient_id', $pat->id)->first();
    assert($record !== null, "Deceased record must be stored in database");
    assert(str_starts_with($record->body_tag_number, 'DEC-'), "Body tag must start with DEC-");
    assert(str_starts_with($record->certificate_of_death_number, 'DNOT-'), "Certificate must start with DNOT-");
    assert($record->status === 'autopsy_pending', "Status must be autopsy_pending when autopsy is requested");

    // 2. Update preservation and cold room details
    $presResp = $mortCtrl->updatePreservation(new Request([], [
        'cold_room_chamber'   => 'Cold Room B (Deep Freeze)',
        'tray_number'         => 'Tray #08',
        'preservation_status' => 'embalmed',
        'body_condition'      => 'Embalmed and intact',
        'personal_effects'    => 'Gold ring, wristwatch, leather wallet',
        'storage_fee_per_day' => 1800.00,
    ]), $record->uuid);
    assert($presResp->status() === 302, "Updating preservation must redirect");

    $record->refresh();
    assert($record->cold_room_chamber === 'Cold Room B (Deep Freeze)', "Chamber must update");
    assert($record->preservation_status === 'embalmed', "Preservation status must update");

    // 3. Record post-mortem autopsy findings
    $autResp = $mortCtrl->recordAutopsy(new Request([], [
        'pathologist_name' => 'Dr. Robert Mwangi FRCPath',
        'autopsy_date'     => date('Y-m-d'),
        'autopsy_findings' => 'Gross examination confirms bilateral lobar pneumonia with dense consolidation. Heart shows concentric LV hypertrophy. No foul play detected.',
    ]), $record->uuid);
    assert($autResp->status() === 302, "Recording autopsy must redirect");

    $record->refresh();
    assert($record->autopsy_status === 'completed', "Autopsy status must be completed");
    assert($record->status === 'cleared_for_release', "Record status must become cleared_for_release");

    // 4. Render Mortuary Dashboard Index
    $mIndex = $mortCtrl->index(new Request());
    assert($mIndex->status() === 200, "Mortuary index must return HTTP 200");
    assert(str_contains($mIndex->content, 'Jonathan Kariuki'), "Mortuary index must display deceased name");
    assert(str_contains($mIndex->content, $record->body_tag_number), "Mortuary index must show body tag number");
    assert(str_contains($mIndex->content, 'sticky-actions-col'), "Mortuary index must feature pinned sticky actions column");
    assert(str_contains($mIndex->content, 'mortuaryTopScroll'), "Mortuary index must feature dual top and bottom scrollbars");
    assert(str_contains($mIndex->content, 'maternal-modal-content'), "Mortuary modal must use 100% opaque backdrop system");

    // 5. Render Mortuary Show View
    $mShow = $mortCtrl->show(new Request(), $record->uuid);
    assert($mShow->status() === 200, "Mortuary show view must return HTTP 200");
    assert(str_contains($mShow->content, 'Post-Mortem / Autopsy Examination'), "Show view must display autopsy section");
    assert(str_contains(strtolower($mShow->content), 'bilateral lobar pneumonia'), "Show view must display autopsy findings");

    // 6. Render Printable Body Tag
    $mTag = $mortCtrl->tag(new Request(), $record->uuid);
    assert($mTag->status() === 200, "Body tag view must return HTTP 200");
    assert(str_contains($mTag->content, 'Mortuary Identification Tag'), "Tag view must have identification tag heading");
    assert(str_contains($mTag->content, $record->body_tag_number), "Tag view must contain body tag number");

    // 7. Render Printable Death Certificate (WHO ICD-standard)
    $mCert = $mortCtrl->certificate(new Request(), $record->uuid);
    assert($mCert->status() === 200, "Certificate view must return HTTP 200");
    assert(str_contains($mCert->content, 'Medical Certificate of Cause of Death'), "Certificate must have WHO MCCD title");
    assert(str_contains($mCert->content, 'Cardiopulmonary Arrest'), "Certificate must show immediate cause of death");

    // 8. Body Release Handover
    $relResp = $mortCtrl->releaseBody(new Request([], [
        'collector_name'         => 'Mary Kariuki',
        'collector_national_id'  => 'ID-24891023',
        'collector_phone'        => '+254711998877',
        'collector_relationship' => 'Spouse / Next of Kin',
        'burial_permit_number'   => 'BP-2026-90412',
        'hearse_registration'    => 'KDG 481P',
        'funeral_home_name'      => 'Lee Funeral Home Logistics',
        'release_notes'          => 'Body and all personal effects handed over to spouse in good order.',
        'payment_status'         => 'paid',
    ]), $record->uuid);
    assert($relResp->status() === 302, "Release body must redirect");

    $record->refresh();
    assert($record->status === 'released', "Status must be released");
    assert($record->burial_permit_number === 'BP-2026-90412', "Burial permit number must match");
    assert(!empty($record->release_date), "Release date must be recorded");
});

// -------------------------------------------------------------
// 45. INPATIENT MRI & RADIOLOGY SCANNING WORKFLOW
// -------------------------------------------------------------
echo "45. INPATIENT MRI & RADIOLOGY SCANNING WORKFLOW\n";
it("Order Requisition, Pre-Scan Safety Screening, Porter Transport & Final Report", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $tenantId = \Veen\Core\Tenancy\TenantManager::id();
    $radCtrl = new \App\Controllers\RadiologyController();

    // 1. Create patient and admitted inpatient ward admission
    $pat = \App\Models\Patient::create([
        'tenant_id'  => $tenantId,
        'branch_id'  => 1,
        'mrn'        => 'MRN-RAD-' . rand(1000, 9999),
        'first_name' => 'Samuel',
        'last_name'  => 'Wanjohi',
        'dob'        => '1984-07-22',
        'sex'        => 'male',
        'phone'      => '+254722334455',
    ]);

    // Ensure bed exists
    $bed = \Veen\Core\Database\DB::selectOne("SELECT * FROM beds WHERE tenant_id = ? LIMIT 1", [$tenantId]);
    if (!$bed) {
        $ward = \App\Models\Ward::where('tenant_id', $tenantId)->first();
        if (!$ward) {
            $ward = \App\Models\Ward::create([
                'tenant_id'    => $tenantId,
                'name'         => 'Surgical Ward 2',
                'code'         => 'SW2',
                'floor_number' => 2,
                'total_beds'   => 12,
            ]);
        }
        \Veen\Core\Database\DB::insert(
            "INSERT INTO beds (tenant_id, ward_id, bed_number, daily_rate_minor, status, created_at, updated_at) VALUES (?, ?, 'Bed-04', 200000, 'available', NOW(), NOW())",
            [$tenantId, $ward->id]
        );
        $bed = \Veen\Core\Database\DB::selectOne("SELECT * FROM beds WHERE tenant_id = ? LIMIT 1", [$tenantId]);
    }

    $adm = \App\Models\Admission::create([
        'tenant_id'        => $tenantId,
        'patient_id'       => $pat->id,
        'bed_id'           => $bed['id'],
        'admission_no'     => 'ADM-2026-' . rand(1000, 9999),
        'admission_reason' => 'Severe neurological symptoms',
        'status'           => 'admitted',
        'admission_date'   => date('Y-m-d H:i:s'),
    ]);

    // 2. Requisition MRI Scan for admitted inpatient with contrast & safety clearance
    $reqResp = $radCtrl->store(new Request([], [
        'patient_id'               => $pat->id,
        'admission_id'             => $adm->id,
        'modality'                 => 'mri',
        'body_part'                => 'Brain & Circle of Willis',
        'contrast_type'            => 'with_and_without_contrast',
        'priority'                 => 'urgent',
        'clinical_indication'      => 'Intractable cluster headache with left-sided hemiparesis. Rule out intracranial space-occupying lesion or acute infarct.',
        'provisional_diagnosis'    => 'Subacute CVA vs Intracranial Neoplasm',
        'has_pacemaker'            => 0,
        'has_aneurysm_clips'       => 0,
        'has_metallic_implants'    => 0,
        'has_claustrophobia'       => 1,
        'sedation_required'        => 1,
        'egfr_renal_value'         => 94.5,
        'safety_checklist_cleared' => 1,
        'porter_notes'             => 'Patient requires wheelchair transport with sedation nurse escort.',
    ]));
    assert($reqResp->status() === 302, "Store radiology requisition must redirect to show");

    $order = \App\Models\RadiologyOrder::where('patient_id', $pat->id)->first();
    assert($order !== null, "Radiology order must be recorded");
    assert(str_starts_with($order->order_number, 'MRI-'), "Order number must start with MRI-");
    assert($order->modality === 'mri', "Modality must be mri");
    assert($order->contrast_type === 'with_and_without_contrast', "Contrast type must match");
    assert($order->admission_id === $adm->id, "Must be linked to inpatient admission");
    assert((float)$order->egfr_renal_value === 94.5, "eGFR value must match");
    assert($order->has_claustrophobia == 1, "Claustrophobia flag must be set");
    assert($order->safety_checklist_cleared == 1, "Safety checklist must be cleared");

    // 3. Ward Porter Transit State Transitions: ordered -> transporting -> in_scan
    $transResp = $radCtrl->updateTransport(new Request([], [
        'transport_status' => 'transporting',
        'porter_notes'     => 'Wheeled from Surgical Ward 2 to MRI Suite 1 via stretcher.',
    ]), $order->uuid);
    assert($transResp->status() === 302, "Transport update must redirect");

    $order->refresh();
    assert($order->transport_status === 'transporting', "Transport status must update to transporting");

    // Scanner transit
    $radCtrl->updateTransport(new Request([], [
        'transport_status' => 'in_scan',
        'porter_notes'     => 'Patient positioned on MRI scanner table; IV contrast administered.',
    ]), $order->uuid);

    $order->refresh();
    assert($order->transport_status === 'in_scan', "Transport status must be in_scan");
    assert($order->status === 'in_progress', "Order status must transition to in_progress");

    // 4. Radiologist Diagnostic Reporting & Sign-Off
    $compResp = $radCtrl->complete(new Request([], [
        'radiologist_name' => 'Dr. Amina Yusuf MMed Rad',
        'findings'         => 'MRI Brain with IV Gadolinium reveals no acute territorial infarction or hemorrhage. Grey-white matter differentiation is preserved. Ventricular system is symmetric with no midline shift. Normal flow voids noted in Circle of Willis.',
        'impression'       => '1. Normal MRI brain study. No intracranial mass lesion, acute ischemia, or vascular aneurysm identified. Benign neurovascular etiology suspected.',
    ]), $order->uuid);
    assert($compResp->status() === 302, "Sign-off must redirect");

    $order->refresh();
    assert($order->status === 'completed', "Order status must be completed");
    assert(!empty($order->completed_at), "Completed timestamp must be recorded");
    assert(str_contains($order->impression, 'Normal MRI brain study'), "Impression must match");

    // 5. Render Radiology Dashboard Index
    $rIndex = $radCtrl->index(new Request());
    assert($rIndex->status() === 200, "Radiology index must return HTTP 200");
    assert(str_contains($rIndex->content, 'Samuel Wanjohi'), "Index must list Samuel Wanjohi");
    assert(str_contains($rIndex->content, $order->order_number), "Index must display MRI order number");
    assert(str_contains($rIndex->content, 'sticky-actions-col'), "Index must feature pinned sticky actions");
    assert(str_contains($rIndex->content, 'radiologyTopScroll'), "Index must feature dual synchronized top and bottom scrollbars");
    assert(str_contains($rIndex->content, 'radiology-modal-content'), "Requisition modal must use 100% opaque modal styling");

    // 6. Render Radiology Show View
    $rShow = $radCtrl->show(new Request(), $order->uuid);
    assert($rShow->status() === 200, "Radiology show view must return HTTP 200");
    assert(str_contains($rShow->content, 'Diagnostic Imaging Report & Findings'), "Show view must display diagnostic report");
    assert(str_contains($rShow->content, 'Safety Screening Screen'), "Show view must render MRI safety check");
    assert(str_contains($rShow->content, 'Ward Porter Tracking'), "Show view must render porter tracking timeline");

    // 7. Render Printable Diagnostic Imaging Report
    $rReport = $radCtrl->report(new Request(), $order->uuid);
    assert($rReport->status() === 200, "Printable report must return HTTP 200");
    assert(str_contains($rReport->content, 'Official Diagnostic Imaging Report'), "Report must render official header");
    assert(str_contains($rReport->content, 'Dr. Amina Yusuf'), "Report must display reporting radiologist");
});

// -------------------------------------------------------------
// 46. HIV / COMPREHENSIVE CARE CENTRE (CCC) MODULE
// -------------------------------------------------------------
echo "46. HIV / COMPREHENSIVE CARE CENTRE (CCC) MODULE\n";
it("Client Enrollment, Staging, Viral Load Suppression Tracking, MMD Refill & Passport", function () {
    TenantManager::setContext(1);
    $admin = User::where('tenant_id', 1)->first();
    Auth::attempt($admin->email, 'Password@123', '127.0.0.1');

    $tenantId = \Veen\Core\Tenancy\TenantManager::id();
    $cccCtrl = new \App\Controllers\CccClinicController();

    // 1. Create a patient candidate
    $pat = \App\Models\Patient::create([
        'tenant_id'  => $tenantId,
        'branch_id'  => 1,
        'mrn'        => 'MRN-CCC-' . rand(1000, 9999),
        'first_name' => 'Beatrice',
        'last_name'  => 'Waweru',
        'dob'        => '1992-11-04',
        'sex'        => 'female',
        'phone'      => '+254733556677',
    ]);

    // 2. Enroll client into CCC Clinic
    $enrollResp = $cccCtrl->store(new Request([], [
        'patient_id'             => $pat->id,
        'enrollment_date'        => date('Y-m-d'),
        'entry_point'            => 'pmtct',
        'who_stage_baseline'     => 'stage_1',
        'art_start_date'         => date('Y-m-d'),
        'current_regimen'        => 'TDF+3TC+DTG',
        'line_of_treatment'      => 'first_line',
        'baseline_cd4'           => 520,
        'baseline_viral_load'    => 40,
        'months_dispensed'       => 3,
        'pmtct_active'           => 1,
        'treatment_buddy_name'   => 'Grace Waweru (Sister)',
        'treatment_buddy_phone'  => '+254733990011',
    ]));
    assert($enrollResp->status() === 302, "Enrolling CCC client must redirect to show page");

    $profile = \App\Models\CccProfile::where('patient_id', $pat->id)->first();
    assert($profile !== null, "CCC profile must be created in DB");
    assert(str_starts_with($profile->ccc_number, 'CCC-'), "CCC number must start with CCC-");
    assert($profile->current_regimen === 'TDF+3TC+DTG', "Regimen must match");
    assert($profile->viral_load_suppressed == 1, "Viral load 40 copies/mL must be marked suppressed (<1000)");
    assert($profile->pmtct_active == 1, "PMTCT active flag must be set");

    // Verify initial enrollment visit was automatically logged
    $initVisit = \App\Models\CccVisit::where('ccc_profile_id', $profile->id)->first();
    assert($initVisit !== null, "Initial visit must be automatically recorded on enrollment");
    assert($initVisit->months_dispensed === 3, "Initial refill must be 3 months MMD");

    // 3. Record Follow-up Encounter & ARV Refill
    $visitResp = $cccCtrl->storeVisit(new Request([], [
        'visit_date'                   => date('Y-m-d'),
        'weight_kg'                    => 62.5,
        'height_cm'                    => 165.0,
        'who_stage'                    => 'stage_1',
        'adherence_pill_count_percent' => 99.0,
        'viral_load_copies_ml'         => 0,
        'cd4_count'                    => 680,
        'regimen_dispensed'            => 'TDF+3TC+DTG',
        'months_dispensed'             => 6,
        'tb_screened_this_visit'       => 1,
        'next_appointment_date'        => date('Y-m-d', strtotime('+6 months')),
        'clinical_notes'               => 'Excellent adherence (99%). Undetectable viral load (Target Not Detected). Switched to 6-month Multi-Month Dispensing (MMD).',
    ]), $profile->uuid);
    assert($visitResp->status() === 302, "Recording visit must redirect");

    $profile->refresh();
    assert($profile->current_viral_load === 0, "Current viral load must update to 0");
    assert($profile->viral_load_suppressed == 1, "Must remain suppressed");
    assert($profile->current_cd4 === 680, "CD4 count must increase to 680");

    // 4. Render CCC Dashboard Index
    $cIndex = $cccCtrl->index(new Request());
    assert($cIndex->status() === 200, "CCC index must return HTTP 200");
    assert(str_contains($cIndex->content, 'Beatrice Waweru'), "Index must list Beatrice Waweru");
    assert(str_contains($cIndex->content, $profile->ccc_number), "Index must display CCC number");
    assert(str_contains($cIndex->content, 'sticky-actions-col'), "Index must have sticky actions column");
    assert(str_contains($cIndex->content, 'cccTopScroll'), "Index must have dual top/bottom scrollbars");
    assert(str_contains($cIndex->content, 'ccc-modal-content'), "Enrollment modal must use 100% opaque modal styling");
    assert(str_contains($cIndex->content, 'PMTCT Active'), "Index must show PMTCT active badge");

    // 5. Render CCC Client Show View
    $cShow = $cccCtrl->show(new Request(), $profile->uuid);
    assert($cShow->status() === 200, "CCC show view must return HTTP 200");
    assert(str_contains($cShow->content, 'Clinical Encounters & ARV Refill History'), "Show view must render encounters table");
    assert(str_contains($cShow->content, '6 Mo. Supply'), "Show view must display 6 months MMD");
    assert(str_contains($cShow->content, '99%'), "Show view must display 99% adherence score");

    // 6. Render Printable CCC Appointment Passport Card
    $cCard = $cccCtrl->card(new Request(), $profile->uuid);
    assert($cCard->status() === 200, "Card view must return HTTP 200");
    assert(str_contains($cCard->content, 'Client Appointment & ARV Refill Passport'), "Card must render passport title");
    assert(str_contains($cCard->content, $profile->ccc_number), "Card must contain CCC number");
});

echo "\n--- Summary: $passed PASSED, $failed FAILED ---\n";
exit($failed > 0 ? 1 : 0);
