<?php
namespace App\Controllers;

use App\Models\DeceasedRecord;
use App\Models\Admission;
use App\Models\Patient;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Cache;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class MortuaryController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(20, (int) $request->query('per_page', 20));
        $offset = ($page - 1) * $perPage;

        $params = [$tenantId];
        $where = "WHERE dr.tenant_id = ?";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND dr.status = ?";
            $params[] = $status;
        }

        if ($q !== '') {
            $where .= " AND (dr.body_tag_number LIKE ? OR dr.deceased_full_name LIKE ? OR dr.next_of_kin_name LIKE ? OR dr.next_of_kin_phone LIKE ? OR dr.certificate_of_death_number LIKE ?)";
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search, $search);
        }

        $totalRecords = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records dr $where", $params)['c'] ?? 0);
        $lastPage = max(1, (int) ceil($totalRecords / $perPage));

        $queryParams = array_merge($params, [$perPage, $offset]);
        $records = DB::select(
            "SELECT dr.*, p.mrn, p.uuid AS patient_uuid
             FROM deceased_records dr
             LEFT JOIN patients p ON p.id = dr.patient_id
             $where
             ORDER BY dr.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        // Calculate days in storage and storage charges for each record
        foreach ($records as &$r) {
            $days = DeceasedRecord::calculateStorageDays($r['date_of_death'], $r['release_date']);
            $r['storage_days'] = $days;
            $r['calculated_charges'] = $days * (float)$r['storage_fee_per_day'];
        }
        unset($r);

        // Stats
        $stats = [
            'totalInColdRoom'    => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records WHERE tenant_id = ? AND status = 'in_cold_room'", [$tenantId])['c'] ?? 0),
            'autopsiesPending'   => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records WHERE tenant_id = ? AND status = 'autopsy_pending'", [$tenantId])['c'] ?? 0),
            'clearedForRelease'  => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records WHERE tenant_id = ? AND status = 'cleared_for_release'", [$tenantId])['c'] ?? 0),
            'releasedCount'      => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records WHERE tenant_id = ? AND status = 'released'", [$tenantId])['c'] ?? 0),
            'broughtInDeadCount' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM deceased_records WHERE tenant_id = ? AND death_type = 'brought_in_dead'", [$tenantId])['c'] ?? 0),
        ];

        // Active Inpatient Admissions for fast transfer if death occurs in ward
        $activeAdmissions = DB::select(
            "SELECT a.id, a.uuid, a.admission_no AS admission_number, a.patient_id, b.bed_number, w.name AS ward_name,
                    p.first_name, p.last_name, p.mrn, p.sex, p.dob, p.next_of_kin_name, p.next_of_kin_phone
             FROM admissions a
             JOIN beds b ON b.id = a.bed_id
             JOIN wards w ON w.id = b.ward_id
             JOIN patients p ON p.id = a.patient_id
             WHERE a.tenant_id = ? AND a.status = 'admitted'
             ORDER BY a.id DESC LIMIT 100",
            [$tenantId]
        );

        return view('mortuary.index', [
            'title'            => 'Mortuary & Deceased Registry',
            'records'          => $records,
            'total'            => $totalRecords,
            'page'             => $page,
            'lastPage'         => $lastPage,
            'perPage'          => $perPage,
            'q'                => $q,
            'status'           => $status,
            'stats'            => $stats,
            'activeAdmissions' => $activeAdmissions,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $user = Auth::user();

        $patientId = $request->input('patient_id') ?: null;
        $admissionId = $request->input('admission_id') ?: null;
        $deathType = $request->input('death_type', 'inpatient');

        // Resolve name and demographics
        $deceasedName = trim((string) $request->input('deceased_full_name', ''));
        $gender = $request->input('gender', 'unknown');
        $age = $request->input('estimated_or_actual_age') !== '' ? (int) $request->input('estimated_or_actual_age') : null;
        $kinName = trim((string) $request->input('next_of_kin_name', ''));
        $kinPhone = trim((string) $request->input('next_of_kin_phone', ''));
        $kinRelation = trim((string) $request->input('next_of_kin_relationship', ''));

        if ($patientId) {
            $patient = is_numeric($patientId)
                ? DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [(int)$patientId, $tenantId])
                : DB::selectOne("SELECT * FROM patients WHERE uuid = ? AND tenant_id = ?", [$patientId, $tenantId]);
            if ($patient) {
                $patientId = $patient['id'];
                if (!$deceasedName) $deceasedName = $patient['first_name'] . ' ' . $patient['last_name'];
                if ($gender === 'unknown') $gender = $patient['sex'] ?: 'unknown';
                if ($age === null && !empty($patient['dob'])) $age = age_from_dob($patient['dob']);
                if (!$kinName) $kinName = $patient['next_of_kin_name'] ?? '';
                if (!$kinPhone) $kinPhone = $patient['next_of_kin_phone'] ?? '';
            }
        }

        if (!$deceasedName) {
            return back()->withInput()->with('error', 'Deceased person full name is required.');
        }

        // Concurrency-safe Body Tag Number: DEC-YYYY-0001
        $seq = Sequence::next('mortuary_tag');
        $bodyTagNumber = 'DEC-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $certNumber = 'DNOT-' . date('Ymd') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $autopsyReq = !empty($request->input('autopsy_required')) ? 1 : 0;
        $initialStatus = $autopsyReq ? 'autopsy_pending' : 'in_cold_room';

        $record = DeceasedRecord::create([
            'branch_id'                   => $user['branch_id'] ?? 1,
            'patient_id'                  => $patientId,
            'admission_id'                => $admissionId,
            'body_tag_number'             => $bodyTagNumber,
            'deceased_full_name'          => $deceasedName,
            'death_type'                  => $deathType,
            'date_of_death'               => $request->input('date_of_death') ?: date('Y-m-d'),
            'time_of_death'               => $request->input('time_of_death') ?: date('H:i:s'),
            'place_of_death'              => trim((string) $request->input('place_of_death', '')),
            'gender'                      => $gender,
            'estimated_or_actual_age'     => $age,
            'certifying_doctor_id'        => Auth::id(),
            'certifying_doctor_name'      => trim((string) ($request->input('certifying_doctor_name') ?: ($user['name'] ?? 'Attending Medical Officer'))),
            'certifying_doctor_license'   => trim((string) $request->input('certifying_doctor_license', '')),
            'cause_of_death_immediate'    => trim((string) $request->input('cause_of_death_immediate', 'Cardiopulmonary Arrest')),
            'cause_of_death_underlying'   => trim((string) $request->input('cause_of_death_underlying', '')),
            'contributory_cause'          => trim((string) $request->input('contributory_cause', '')),
            'certificate_of_death_number' => $certNumber,
            'next_of_kin_name'            => $kinName,
            'next_of_kin_phone'           => $kinPhone,
            'next_of_kin_relationship'    => $kinRelation,
            'cold_room_chamber'           => trim((string) $request->input('cold_room_chamber', 'Cold Room A')),
            'tray_number'                 => trim((string) $request->input('tray_number', 'Tray #' . str_pad((string)($seq % 20 + 1), 2, '0', STR_PAD_LEFT))),
            'preservation_status'         => $request->input('preservation_status', 'refrigerated'),
            'body_condition'              => trim((string) $request->input('body_condition', 'Intact')),
            'personal_effects'            => trim((string) $request->input('personal_effects', '')),
            'autopsy_required'            => $autopsyReq,
            'autopsy_status'              => $autopsyReq ? 'scheduled' : 'not_required',
            'status'                      => $initialStatus,
            'storage_fee_per_day'         => (float) $request->input('storage_fee_per_day', 1500.00),
            'total_charges'               => 0.00,
            'payment_status'              => 'pending',
        ]);

        // If inpatient admission linked, mark admission as deceased
        if ($admissionId) {
            $adm = Admission::find($admissionId);
            if ($adm) {
                $adm->update([
                    'status'         => 'deceased',
                    'discharge_date' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        audit('mortuary.body_admitted', 'DeceasedRecord', $record->id, [
            'body_tag_number' => $bodyTagNumber,
            'deceased_name'   => $deceasedName,
            'death_type'      => $deathType,
        ]);

        return redirect(route('mortuary.show', ['id' => $record->uuid ?? $record->id]))
            ->with('success', "Deceased record registered with Body Tag: {$bodyTagNumber} (Tray: {$record->tray_number}).");
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        $patient = null;
        if (!empty($record->patient_id)) {
            $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$record->patient_id, $tenantId]);
        }

        $storageDays = DeceasedRecord::calculateStorageDays($record->date_of_death, $record->release_date);
        $accruedFee = $storageDays * (float)$record->storage_fee_per_day;

        return view('mortuary.show', [
            'title'        => "Deceased Record: {$record->body_tag_number} ({$record->deceased_full_name})",
            'record'       => $record,
            'patient'      => $patient,
            'storageDays'  => $storageDays,
            'accruedFee'   => $accruedFee,
        ]);
    }

    public function updatePreservation(Request $request, int|string $id): Response
    {
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        $record->update([
            'cold_room_chamber'   => trim((string) $request->input('cold_room_chamber', $record->cold_room_chamber)),
            'tray_number'         => trim((string) $request->input('tray_number', $record->tray_number)),
            'preservation_status' => $request->input('preservation_status', $record->preservation_status),
            'body_condition'      => trim((string) $request->input('body_condition', $record->body_condition)),
            'personal_effects'    => trim((string) $request->input('personal_effects', $record->personal_effects)),
            'storage_fee_per_day' => (float) $request->input('storage_fee_per_day', $record->storage_fee_per_day),
        ]);

        return redirect(route('mortuary.show', ['id' => $record->uuid ?? $record->id]))
            ->with('success', 'Cold storage location and preservation details updated.');
    }

    public function recordAutopsy(Request $request, int|string $id): Response
    {
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        $findings = trim((string) $request->input('autopsy_findings', ''));
        $pathologist = trim((string) $request->input('pathologist_name', Auth::user()['name'] ?? 'Pathologist'));
        $autopsyDate = $request->input('autopsy_date') ?: date('Y-m-d');

        $record->update([
            'autopsy_required' => 1,
            'autopsy_status'   => 'completed',
            'autopsy_date'     => $autopsyDate,
            'pathologist_name' => $pathologist,
            'autopsy_findings' => $findings,
            'status'           => 'cleared_for_release',
        ]);

        audit('mortuary.autopsy_recorded', 'DeceasedRecord', $record->id, ['pathologist' => $pathologist]);

        return redirect(route('mortuary.show', ['id' => $record->uuid ?? $record->id]))
            ->with('success', 'Post-mortem examination findings recorded and body cleared for release.');
    }

    public function releaseBody(Request $request, int|string $id): Response
    {
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        $collectorName = trim((string) $request->input('collector_name', ''));
        $collectorId = trim((string) $request->input('collector_national_id', ''));
        $burialPermit = trim((string) $request->input('burial_permit_number', ''));

        if (!$collectorName || !$collectorId) {
            return back()->withInput()->with('error', 'Collector full name and National ID are mandatory for body handover.');
        }

        $releaseDate = date('Y-m-d H:i:s');
        $storageDays = DeceasedRecord::calculateStorageDays($record->date_of_death, $releaseDate);
        $totalCharges = $storageDays * (float)$record->storage_fee_per_day;

        $record->update([
            'status'                  => 'released',
            'release_date'            => $releaseDate,
            'collector_name'          => $collectorName,
            'collector_national_id'   => $collectorId,
            'collector_phone'         => trim((string) $request->input('collector_phone', '')),
            'collector_relationship'  => trim((string) $request->input('collector_relationship', '')),
            'burial_permit_number'    => $burialPermit,
            'hearse_registration'     => trim((string) $request->input('hearse_registration', '')),
            'funeral_home_name'       => trim((string) $request->input('funeral_home_name', '')),
            'release_notes'           => trim((string) $request->input('release_notes', '')),
            'total_charges'           => $totalCharges,
            'payment_status'          => $request->input('payment_status', 'paid'),
        ]);

        audit('mortuary.body_released', 'DeceasedRecord', $record->id, [
            'collector_name' => $collectorName,
            'burial_permit'  => $burialPermit,
        ]);

        return redirect(route('mortuary.show', ['id' => $record->uuid ?? $record->id]))
            ->with('success', "Body released to {$collectorName} under Burial Permit #{$burialPermit}.");
    }

    public function tag(Request $request, int|string $id): Response
    {
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        return view('mortuary.tag', [
            'title'  => "Body Tag: {$record->body_tag_number}",
            'record' => $record,
        ]);
    }

    public function certificate(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $record = DeceasedRecord::find($id);
        if (!$record) {
            abort(404, 'Deceased record not found');
        }

        $patient = null;
        if (!empty($record->patient_id)) {
            $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$record->patient_id, $tenantId]);
        }

        return view('mortuary.certificate', [
            'title'   => "Medical Certificate of Cause of Death: {$record->body_tag_number}",
            'record'  => $record,
            'patient' => $patient,
        ]);
    }
}
