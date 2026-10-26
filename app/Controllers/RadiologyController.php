<?php
namespace App\Controllers;

use App\Models\RadiologyOrder;
use App\Models\Admission;
use App\Models\Patient;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class RadiologyController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $modality = trim((string) $request->query('modality', 'all'));
        $status = trim((string) $request->query('status', 'all'));
        $transport = trim((string) $request->query('transport', 'all'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(20, (int) $request->query('per_page', 20));
        $offset = ($page - 1) * $perPage;

        $params = [$tenantId];
        $where = "WHERE ro.tenant_id = ?";

        if ($modality !== '' && $modality !== 'all') {
            $where .= " AND ro.modality = ?";
            $params[] = $modality;
        }

        if ($status !== '' && $status !== 'all') {
            $where .= " AND ro.status = ?";
            $params[] = $status;
        }

        if ($transport !== '' && $transport !== 'all') {
            $where .= " AND ro.transport_status = ?";
            $params[] = $transport;
        }

        if ($q !== '') {
            $where .= " AND (ro.order_number LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ? OR ro.body_part LIKE ?)";
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search, $search);
        }

        $totalRecords = (int) (DB::selectOne(
            "SELECT COUNT(*) AS c 
             FROM radiology_orders ro 
             JOIN patients p ON p.id = ro.patient_id 
             $where", 
            $params
        )['c'] ?? 0);

        $lastPage = max(1, (int) ceil($totalRecords / $perPage));

        $queryParams = array_merge($params, [$perPage, $offset]);
        $orders = DB::select(
            "SELECT ro.*, 
                    p.first_name, p.last_name, p.mrn, p.sex, p.dob, p.phone, p.uuid AS patient_uuid,
                    adm.admission_no AS admission_number, b.bed_number, w.name AS ward_name,
                    u.name AS ordering_doctor_name
             FROM radiology_orders ro
             JOIN patients p ON p.id = ro.patient_id
             LEFT JOIN admissions adm ON adm.id = ro.admission_id
             LEFT JOIN beds b ON b.id = adm.bed_id
             LEFT JOIN wards w ON w.id = b.ward_id
             LEFT JOIN users u ON u.id = ro.doctor_user_id
             $where
             ORDER BY 
                CASE ro.priority WHEN 'stat' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END,
                ro.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        // Stats
        $stats = [
            'total'          => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM radiology_orders WHERE tenant_id = ?", [$tenantId])['c'] ?? 0),
            'mriTotal'       => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM radiology_orders WHERE tenant_id = ? AND modality = 'mri'", [$tenantId])['c'] ?? 0),
            'inpatientsInTransit' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM radiology_orders WHERE tenant_id = ? AND transport_status IN ('transporting', 'in_scan')", [$tenantId])['c'] ?? 0),
            'pendingReport'  => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM radiology_orders WHERE tenant_id = ? AND status = 'in_progress'", [$tenantId])['c'] ?? 0),
            'completed'      => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM radiology_orders WHERE tenant_id = ? AND status = 'completed'", [$tenantId])['c'] ?? 0),
        ];

        // Active Inpatients for quick scan requisition
        $activeInpatients = DB::select(
            "SELECT adm.id AS admission_id, adm.admission_no AS admission_number, b.bed_number, w.name AS ward_name,
                    p.id AS patient_id, p.uuid AS patient_uuid, p.first_name, p.last_name, p.mrn, p.sex, p.dob
             FROM admissions adm
             JOIN beds b ON b.id = adm.bed_id
             JOIN wards w ON w.id = b.ward_id
             JOIN patients p ON p.id = adm.patient_id
             WHERE adm.tenant_id = ? AND adm.status = 'admitted'
             ORDER BY adm.id DESC LIMIT 100",
            [$tenantId]
        );

        // All patients for general radiology order
        $recentPatients = DB::select(
            "SELECT id, uuid, first_name, last_name, mrn, phone, sex, dob 
             FROM patients 
             WHERE tenant_id = ? 
             ORDER BY id DESC LIMIT 100",
            [$tenantId]
        );

        return view('radiology.index', [
            'title'            => 'Radiology & MRI Diagnostic Center',
            'orders'           => $orders,
            'total'            => $totalRecords,
            'page'             => $page,
            'lastPage'         => $lastPage,
            'perPage'          => $perPage,
            'q'                => $q,
            'modality'         => $modality,
            'status'           => $status,
            'transport'        => $transport,
            'stats'            => $stats,
            'activeInpatients' => $activeInpatients,
            'recentPatients'   => $recentPatients,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $user = Auth::user();

        $patientId = $request->input('patient_id') ?: null;
        $admissionId = $request->input('admission_id') ?: null;

        // If admission_id is selected, resolve patient_id automatically
        if ($admissionId) {
            $adm = DB::selectOne("SELECT * FROM admissions WHERE id = ? AND tenant_id = ?", [(int)$admissionId, $tenantId]);
            if ($adm) {
                $patientId = $adm['patient_id'];
            }
        }

        if (!$patientId) {
            return back()->withInput()->with('error', 'Please select a valid patient or inpatient admission.');
        }

        // Validate patient exists
        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [(int)$patientId, $tenantId]);
        if (!$patient) {
            return back()->withInput()->with('error', 'Patient not found in this hospital tenant.');
        }

        $modality = $request->input('modality', 'mri');
        $bodyPart = trim((string) $request->input('body_part', 'Brain'));
        $clinicalIndication = trim((string) $request->input('clinical_indication', ''));

        if (!$clinicalIndication) {
            return back()->withInput()->with('error', 'Clinical indication / reason for scan is required.');
        }

        // Sequential Order Number: MRI-YYYY-XXXX or RAD-YYYY-XXXX
        $seq = Sequence::next('radiology_order');
        $prefix = ($modality === 'mri') ? 'MRI' : 'RAD';
        $orderNumber = $prefix . '-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        // MRI Safety Checklist Flags
        $hasPacemaker = !empty($request->input('has_pacemaker')) ? 1 : 0;
        $hasImplants = !empty($request->input('has_metallic_implants')) ? 1 : 0;
        $hasClips = !empty($request->input('has_aneurysm_clips')) ? 1 : 0;
        $hasClaustrophobia = !empty($request->input('has_claustrophobia')) ? 1 : 0;
        $sedationReq = !empty($request->input('sedation_required')) ? 1 : 0;
        $safetyCleared = !empty($request->input('safety_checklist_cleared')) ? 1 : 0;
        $egfr = $request->input('egfr_renal_value') !== '' ? (float) $request->input('egfr_renal_value') : null;

        // Modality pricing defaults
        $pricing = [
            'mri'         => 18000.00,
            'ct_scan'     => 12000.00,
            'x_ray'       => 2500.00,
            'ultrasound'  => 3500.00,
            'mammography' => 5000.00,
        ];
        $cost = (float) ($request->input('cost') ?: ($pricing[$modality] ?? 5000.00));

        $order = RadiologyOrder::create([
            'branch_id'                => $user['branch_id'] ?? 1,
            'patient_id'               => (int) $patientId,
            'admission_id'             => $admissionId ? (int)$admissionId : null,
            'order_number'             => $orderNumber,
            'doctor_user_id'           => Auth::id(),
            'modality'                 => $modality,
            'body_part'                => $bodyPart,
            'contrast_type'            => $request->input('contrast_type', 'without_contrast'),
            'clinical_indication'      => $clinicalIndication,
            'provisional_diagnosis'    => trim((string) $request->input('provisional_diagnosis', '')),
            'has_pacemaker'            => $hasPacemaker,
            'has_metallic_implants'    => $hasImplants,
            'has_aneurysm_clips'       => $hasClips,
            'has_claustrophobia'       => $hasClaustrophobia,
            'egfr_renal_value'         => $egfr,
            'sedation_required'        => $sedationReq,
            'safety_checklist_cleared' => $safetyCleared,
            'scheduled_at'             => $request->input('scheduled_at') ?: date('Y-m-d H:i:s'),
            'transport_status'         => $admissionId ? 'ordered' : 'scheduled',
            'porter_notes'             => trim((string) $request->input('porter_notes', '')),
            'priority'                 => $request->input('priority', 'routine'),
            'status'                   => 'ordered',
            'cost'                     => $cost,
        ]);

        audit('radiology.order_created', 'RadiologyOrder', $order->id, [
            'order_number' => $orderNumber,
            'modality'     => $modality,
            'patient_id'   => $patientId,
            'admission_id' => $admissionId,
        ]);

        return redirect(route('radiology.show', ['id' => $order->uuid ?? $order->id]))
            ->with('success', "Radiology order {$orderNumber} successfully requisitioned.");
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $order = RadiologyOrder::find($id);
        if (!$order) {
            abort(404, 'Radiology order not found');
        }

        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$order->patient_id, $tenantId]);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        $admission = null;
        if (!empty($order->admission_id)) {
            $admission = DB::selectOne(
                "SELECT adm.*, b.bed_number, w.name AS ward_name, w.floor_number AS floor
                 FROM admissions adm
                 LEFT JOIN beds b ON b.id = adm.bed_id
                 LEFT JOIN wards w ON w.id = b.ward_id
                 WHERE adm.id = ? AND adm.tenant_id = ?",
                [$order->admission_id, $tenantId]
            );
        }

        $orderingDoctor = DB::selectOne("SELECT name, email FROM users WHERE id = ?", [$order->doctor_user_id]);

        return view('radiology.show', [
            'title'          => "Radiology Order: {$order->order_number} ({$order->modality})",
            'order'          => $order,
            'patient'        => $patient,
            'admission'      => $admission,
            'orderingDoctor' => $orderingDoctor,
        ]);
    }

    public function updateTransport(Request $request, int|string $id): Response
    {
        $order = RadiologyOrder::find($id);
        if (!$order) {
            abort(404, 'Radiology order not found');
        }

        $transportStatus = $request->input('transport_status', $order->transport_status);
        $porterNotes = trim((string) $request->input('porter_notes', $order->porter_notes));

        $updateData = [
            'transport_status' => $transportStatus,
            'porter_notes'     => $porterNotes,
        ];

        // Status transition helper
        if ($transportStatus === 'in_scan' && $order->status === 'ordered') {
            $updateData['status'] = 'in_progress';
        }

        $order->update($updateData);

        audit('radiology.transport_updated', 'RadiologyOrder', $order->id, [
            'transport_status' => $transportStatus,
            'porter_notes'     => $porterNotes,
        ]);

        return redirect(route('radiology.show', ['id' => $order->uuid ?? $order->id]))
            ->with('success', "Patient transport status updated to: " . strtoupper(str_replace('_', ' ', $transportStatus)));
    }

    public function complete(Request $request, int|string $id): Response
    {
        $order = RadiologyOrder::find($id);
        if (!$order) {
            abort(404, 'Radiology order not found');
        }

        $findings = trim((string) $request->input('findings', ''));
        $impression = trim((string) $request->input('impression', ''));
        $radiologistName = trim((string) ($request->input('radiologist_name') ?: (Auth::user()['name'] ?? 'Radiologist on Duty')));

        if (!$findings || !$impression) {
            return back()->withInput()->with('error', 'Diagnostic findings and clinical impression are required.');
        }

        $order->update([
            'radiologist_user_id' => Auth::id(),
            'radiologist_name'    => $radiologistName,
            'findings'            => $findings,
            'impression'          => $impression,
            'status'              => 'completed',
            'transport_status'    => ($order->admission_id && $order->transport_status !== 'returned_to_ward') ? 'completed' : $order->transport_status,
            'completed_at'        => date('Y-m-d H:i:s'),
        ]);

        audit('radiology.scan_completed', 'RadiologyOrder', $order->id, [
            'radiologist' => $radiologistName,
        ]);

        return redirect(route('radiology.show', ['id' => $order->uuid ?? $order->id]))
            ->with('success', "Scan {$order->order_number} diagnostic report signed and finalized.");
    }

    public function report(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $order = RadiologyOrder::find($id);
        if (!$order) {
            abort(404, 'Radiology order not found');
        }

        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$order->patient_id, $tenantId]);
        $admission = null;
        if (!empty($order->admission_id)) {
            $admission = DB::selectOne(
                "SELECT adm.*, b.bed_number, w.name AS ward_name 
                 FROM admissions adm 
                 LEFT JOIN beds b ON b.id = adm.bed_id
                 LEFT JOIN wards w ON w.id = b.ward_id 
                 WHERE adm.id = ?",
                [$order->admission_id]
            );
        }
        $doctor = DB::selectOne("SELECT name FROM users WHERE id = ?", [$order->doctor_user_id]);

        return view('radiology.report', [
            'title'     => "Radiology Report: {$order->order_number}",
            'order'     => $order,
            'patient'   => $patient,
            'admission' => $admission,
            'doctor'    => $doctor,
        ]);
    }
}
