<?php
namespace App\Controllers;

use App\Models\Admission;
use App\Models\ClinicalEncounter;
use App\Models\DiagnosticOrder;
use App\Models\DiagnosticResult;
use App\Models\DiagnosticTest;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\Ward;
use App\Services\Barcode;
use App\Services\Sequence;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class ConsultationController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();

        // 1. Waiting Queue / Encounters in progress
        $encounters = DB::select(
            "SELECT e.*, p.first_name, p.last_name, p.mrn, p.dob, p.sex, p.phone,
                    t.code AS ticket_number, t.service_point_id, u.name AS doctor_name
             FROM clinical_encounters e
             JOIN patients p ON p.id = e.patient_id
             LEFT JOIN tickets t ON t.id = e.ticket_id
             LEFT JOIN users u ON u.id = e.doctor_user_id
             WHERE e.tenant_id = ? AND e.status != 'completed'
             ORDER BY CASE e.triage_category
                 WHEN 'emergency' THEN 1
                 WHEN 'very_urgent' THEN 2
                 WHEN 'urgent' THEN 3
                 WHEN 'standard' THEN 4
                 ELSE 5 END ASC, e.id DESC",
            [$tenantId]
        );

        // 2. Recent completed encounters
        $recentCompleted = DB::select(
            "SELECT e.*, p.first_name, p.last_name, p.mrn, p.dob, p.sex, u.name AS doctor_name
             FROM clinical_encounters e
             JOIN patients p ON p.id = e.patient_id
             LEFT JOIN users u ON u.id = e.doctor_user_id
             WHERE e.tenant_id = ? AND e.status = 'completed'
             ORDER BY e.updated_at DESC LIMIT 15",
            [$tenantId]
        );

        return view('consultation.index', [
            'title' => 'OPD Consultation & Triage Workspace',
            'encounters' => $encounters,
            'recentCompleted' => $recentCompleted,
        ]);
    }

    public function start(Request $request): Response
    {
        $patientInput = trim((string) ($request->input('patient_id') ?: $request->query('patient_id', '')));
        $ticketId = $request->input('ticket_id') ? (int) $request->input('ticket_id') : ($request->query('ticket_id') ? (int) $request->query('ticket_id') : null);

        if (empty($patientInput)) {
            return redirect(route('consultations.index'))->with('error', 'Please select or search for a patient to start consultation.');
        }

        $tenantId = TenantManager::id();
        $patient = Patient::find($patientInput);
        if (!$patient) {
            $isUuid = strlen($patientInput) === 36 && str_contains($patientInput, '-');
            $row = $isUuid
                ? DB::selectOne("SELECT * FROM patients WHERE uuid = ? AND tenant_id = ? AND deleted_at IS NULL", [$patientInput, $tenantId])
                : DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL", [(int)$patientInput, $tenantId]);
            if ($row) {
                $patient = Patient::hydrate($row);
            }
        }

        if (!$patient) {
            abort(404, 'Patient not found');
        }

        if (empty($patient->uuid) && function_exists('generate_uuid')) {
            $patientUuid = generate_uuid();
            DB::statement("UPDATE patients SET uuid = ? WHERE id = ?", [$patientUuid, $patient->id]);
            $patient->uuid = $patientUuid;
        }

        $patientId = (int) $patient->id;

        // Check if patient already has an open encounter today
        $existing = DB::selectOne(
            "SELECT id, uuid FROM clinical_encounters WHERE tenant_id = ? AND patient_id = ? AND status != 'completed' ORDER BY id DESC LIMIT 1",
            [$tenantId, $patientId]
        );

        if ($existing) {
            $existingUuid = $existing['uuid'];
            if (empty($existingUuid) && function_exists('generate_uuid')) {
                $existingUuid = generate_uuid();
                DB::statement("UPDATE clinical_encounters SET uuid = ? WHERE id = ?", [$existingUuid, $existing['id']]);
            }
            return redirect(route('consultations.show', ['id' => $existingUuid ?: $existing['id']]));
        }

        $enc = ClinicalEncounter::create([
            'patient_id' => $patientId,
            'ticket_id' => $ticketId,
            'doctor_user_id' => auth()->id,
            'encounter_type' => 'outpatient',
            'status' => 'waiting_triage',
            'triage_category' => 'standard',
        ]);

        return redirect(route('consultations.show', ['id' => $enc->uuid ?: $enc->id]));
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');
        $encounter = DB::selectOne(
            "SELECT e.*, p.first_name, p.last_name, p.mrn, p.dob, p.sex, p.blood_group, p.allergies, p.phone,
                    p.uuid AS patient_uuid,
                    p.insurance_provider, p.insurance_number, t.code AS ticket_number
             FROM clinical_encounters e
             JOIN patients p ON p.id = e.patient_id
             LEFT JOIN tickets t ON t.id = e.ticket_id
             WHERE " . ($isUuid ? "e.uuid = ?" : "e.id = ?") . " AND e.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$encounter) abort(404, 'Encounter not found');

        if (empty($encounter['uuid']) && function_exists('generate_uuid')) {
            $encUuid = generate_uuid();
            DB::statement("UPDATE clinical_encounters SET uuid = ? WHERE id = ?", [$encUuid, $encounter['id']]);
            $encounter['uuid'] = $encUuid;
        }

        // Diagnostic Tests catalog & existing orders (use numeric encounter['id'])
        $testCatalog = DB::select("SELECT * FROM diagnostic_tests WHERE tenant_id = ? AND is_active = 1 ORDER BY category, name", [$tenantId]);
        $orders = DB::select(
            "SELECT o.*, o.order_no, o.status AS order_status,
                    r.id AS result_id, r.observed_value, r.unit, r.reference_range, r.is_abnormal,
                    t.name AS test_name, t.code AS test_code, t.category AS test_category
             FROM diagnostic_orders o
             LEFT JOIN diagnostic_results r ON r.order_id = o.id
             LEFT JOIN diagnostic_tests t ON t.id = o.test_id
             WHERE o.encounter_id = ? AND o.tenant_id = ?
             ORDER BY o.id DESC",
            [$encounter['id'], $tenantId]
        );

        // Prescriptions & items (use numeric encounter['id'])
        $prescriptions = DB::select(
            "SELECT p.*, p.prescription_no AS rx_number,
                    pi.id AS item_id, pi.medicine_name AS drug_name, pi.dosage AS dose,
                    pi.frequency, pi.duration_days, pi.instructions, pi.qty_prescribed AS quantity_prescribed,
                    pi.status AS item_status
             FROM prescriptions p
             LEFT JOIN prescription_items pi ON pi.prescription_id = p.id
             WHERE p.encounter_id = ? AND p.tenant_id = ?
             ORDER BY p.id DESC",
            [$encounter['id'], $tenantId]
        );

        // Pharmacy products catalog for Rx dropdown
        $pharmacyProducts = DB::select("SELECT id, name, generic_name, form, strength, stock_qty FROM products WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC", [$tenantId]);

        // Available Wards & Beds for admission
        $wards = DB::select("SELECT * FROM wards WHERE tenant_id = ? AND is_active = 1", [$tenantId]);

        // Patient longitudinal history: prior visits, diagnoses, notes, treatments, and prescriptions
        $pastVisits = DB::select(
            "SELECT e.*, u.name AS doctor_name,
                    (SELECT COUNT(*) FROM diagnostic_orders o WHERE o.encounter_id = e.id) AS lab_orders_count,
                    (SELECT COUNT(*) FROM prescriptions p WHERE p.encounter_id = e.id) AS rx_count
             FROM clinical_encounters e
             LEFT JOIN users u ON u.id = e.doctor_user_id
             WHERE e.patient_id = ? AND e.tenant_id = ? AND e.id != ?
             ORDER BY e.created_at DESC",
            [$encounter['patient_id'], $tenantId, $encounter['id']]
        );

        foreach ($pastVisits as &$pv) {
            $pv['prescriptions'] = DB::select(
                "SELECT p.prescription_no, pi.medicine_name, pi.dosage, pi.frequency, pi.duration_days, pi.instructions, pi.qty_prescribed
                 FROM prescriptions p
                 JOIN prescription_items pi ON pi.prescription_id = p.id
                 WHERE p.encounter_id = ? AND p.tenant_id = ?",
                [$pv['id'], $tenantId]
            );
            $pv['lab_results'] = DB::select(
                "SELECT t.name AS test_name, r.observed_value, r.unit, r.reference_range, r.is_abnormal, r.technician_notes
                 FROM diagnostic_orders o
                 JOIN diagnostic_tests t ON t.id = o.test_id
                 LEFT JOIN diagnostic_results r ON r.order_id = o.id
                 WHERE o.encounter_id = ? AND o.tenant_id = ?",
                [$pv['id'], $tenantId]
            );
        }
        unset($pv);

        return view('consultation.show', [
            'title' => 'Consultation: ' . $encounter['first_name'] . ' ' . $encounter['last_name'] . ' (' . $encounter['mrn'] . ')',
            'enc' => $encounter,
            'testCatalog' => $testCatalog,
            'orders' => $orders,
            'prescriptions' => $prescriptions,
            'pharmacyProducts' => $pharmacyProducts,
            'wards' => $wards,
            'pastVisits' => $pastVisits,
        ]);
    }

    public function updateVitals(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $enc = ClinicalEncounter::find($id);
        if (!$enc) abort(404);

        $sbp = $request->input('systolic_bp') !== '' ? (int) $request->input('systolic_bp') : null;
        $dbp = $request->input('diastolic_bp') !== '' ? (int) $request->input('diastolic_bp') : null;
        $pulse = $request->input('pulse_rate') !== '' ? (int) $request->input('pulse_rate') : null;
        $temp = $request->input('temperature_c') !== '' ? (float) $request->input('temperature_c') : null;
        $resp = $request->input('respiratory_rate') !== '' ? (int) $request->input('respiratory_rate') : null;
        $spo2 = $request->input('spo2_percent') !== '' ? (int) $request->input('spo2_percent') : null;
        $weight = $request->input('weight_kg') !== '' ? (float) $request->input('weight_kg') : null;
        $height = $request->input('height_cm') !== '' ? (float) $request->input('height_cm') : null;

        // Auto BMI calculation
        $bmi = null;
        if ($weight && $height && $height > 0) {
            $heightMeters = $height / 100.0;
            $bmi = round($weight / ($heightMeters * $heightMeters), 1);
        }

        $triageCat = $request->input('triage_category', 'standard');

        $enc->update([
            'systolic_bp' => $sbp,
            'diastolic_bp' => $dbp,
            'pulse_rate' => $pulse,
            'temperature_c' => $temp,
            'respiratory_rate' => $resp,
            'spo2_percent' => $spo2,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'bmi' => $bmi,
            'triage_category' => $triageCat,
            'status' => 'triaged',
            'triage_nurse_id' => auth()->id,
        ]);

        return back()->with('success', 'Vital signs and triage recorded successfully.');
    }

    public function updateNotes(Request $request, int|string $id): Response
    {
        $enc = ClinicalEncounter::find($id);
        if (!$enc) abort(404);

        $enc->update([
            'presenting_complaints' => $request->input('presenting_complaints'),
            'history_of_illness'    => $request->input('history_of_illness'),
            'physical_examination'  => $request->input('physical_examination'),
            'working_diagnosis'     => $request->input('working_diagnosis'),
            'confirmed_diagnosis'   => $request->input('confirmed_diagnosis'),
            'treatment_plan'        => $request->input('treatment_plan'),
            'doctor_notes'          => $request->input('doctor_notes'),
            'doctor_user_id'        => auth()->id,
            'status'                => 'consulting',
        ]);

        return back()->with('success', 'Clinical notes and diagnoses saved.');
    }

    public function orderLab(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $enc = ClinicalEncounter::find($id);
        if (!$enc) abort(404);

        $testId = (int) $request->input('test_id');
        $notes = $request->input('clinical_notes');
        $priority = $request->input('priority', 'routine');

        $test = DB::selectOne("SELECT * FROM diagnostic_tests WHERE id = ? AND tenant_id = ?", [$testId, $tenantId]);
        if (!$test) return back()->with('error', 'Invalid diagnostic test selected.');

        $orderNo = Sequence::next('order', 'LAB-');

        $order = DiagnosticOrder::create([
            'order_no'            => $orderNo,
            'patient_id'          => $enc->patient_id,
            'encounter_id'        => $enc->id,
            'doctor_user_id'      => auth()->id ?? 1,
            'test_id'             => $test['id'],
            'clinical_indication' => $notes,
            'priority'            => $priority,
            'status'              => 'ordered',
        ]);

        DiagnosticResult::create([
            'order_id'        => $order->id,
            'parameter_name'  => $test['name'],
            'observed_value'  => 'Pending',
            'reference_range' => $test['normal_range'],
            'unit'            => $test['units'],
            'is_abnormal'     => 0,
        ]);

        return back()->with('success', "Diagnostic test '{$test['name']}' ordered successfully (Order #{$orderNo}).");
    }

    public function addPrescription(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $enc = ClinicalEncounter::find($id);
        if (!$enc) abort(404);

        $productId = $request->input('product_id') ? (int) $request->input('product_id') : null;
        $drugName = trim($request->input('drug_name') ?: '');
        if ($productId) {
            $prod = DB::selectOne("SELECT * FROM products WHERE id = ? AND tenant_id = ?", [$productId, $tenantId]);
            if ($prod) {
                $drugName = $prod['name'] . ($prod['strength'] ? " ({$prod['strength']})" : '');
            }
        }

        if (empty($drugName)) {
            return back()->with('error', 'Please specify medication name.');
        }

        $rxNo = Sequence::next('prescription', 'RX-');
        $rx = Prescription::create([
            'prescription_no' => $rxNo,
            'patient_id'      => $enc->patient_id,
            'encounter_id'    => $enc->id,
            'doctor_user_id'  => auth()->id ?? 1,
            'notes'           => $request->input('clinical_indication'),
            'status'          => 'active',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'product_id'      => $productId,
            'medicine_name'   => $drugName,
            'dosage'          => $request->input('dose', '1 tab'),
            'frequency'       => $request->input('frequency', 'TDS (3x daily)'),
            'duration_days'   => (int) ($request->input('duration_days', 5)),
            'instructions'    => $request->input('instructions', 'Take after meals'),
            'qty_prescribed'  => (int) ($request->input('quantity_prescribed', 15)),
            'qty_dispensed'   => 0,
            'status'          => 'pending',
        ]);

        return back()->with('success', "Prescription {$rxNo} for '{$drugName}' sent directly to pharmacy.");
    }

    public function complete(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $enc = ClinicalEncounter::find($id);
        if (!$enc) abort(404);

        $enc->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        // If ticket was linked, mark ticket completed
        if ($enc->ticket_id) {
            DB::statement("UPDATE tickets SET status = 'completed', completed_at = NOW() WHERE id = ? AND tenant_id = ?", [$enc->ticket_id, $tenantId]);
        }

        return redirect(route('consultations.index'))->with('success', "Consultation encounter for patient #{$enc->patient_id} concluded successfully.");
    }
}