<?php
namespace App\Controllers;

use App\Models\CccProfile;
use App\Models\CccVisit;
use App\Models\Patient;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Cache;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class CccClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'active'));
        $suppression = trim((string) $request->query('suppression', 'all'));
        $pmtct = trim((string) $request->query('pmtct', 'all'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(20, (int) $request->query('per_page', 20));
        $offset = ($page - 1) * $perPage;

        $params = [$tenantId];
        $where = "WHERE cp.tenant_id = ?";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND cp.status = ?";
            $params[] = $status;
        }

        if ($suppression === 'suppressed') {
            $where .= " AND cp.viral_load_suppressed = 1";
        } elseif ($suppression === 'unsuppressed') {
            $where .= " AND cp.viral_load_suppressed = 0";
        }

        if ($pmtct === '1') {
            $where .= " AND cp.pmtct_active = 1";
        }

        if ($q !== '') {
            $where .= " AND (cp.ccc_number LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ? OR p.phone LIKE ? OR cp.current_regimen LIKE ?)";
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search, $search, $search);
        }

        $totalRecords = (int) (DB::selectOne(
            "SELECT COUNT(*) AS c 
             FROM ccc_profiles cp 
             JOIN patients p ON p.id = cp.patient_id 
             $where", 
            $params
        )['c'] ?? 0);

        $lastPage = max(1, (int) ceil($totalRecords / $perPage));

        $queryParams = array_merge($params, [$perPage, $offset]);
        $clients = DB::select(
            "SELECT cp.*, 
                    p.first_name, p.last_name, p.mrn, p.sex, p.dob, p.phone, p.uuid AS patient_uuid,
                    (SELECT MAX(cv.visit_date) FROM ccc_visits cv WHERE cv.ccc_profile_id = cp.id) AS last_visit_date,
                    (SELECT cv.next_appointment_date FROM ccc_visits cv WHERE cv.ccc_profile_id = cp.id ORDER BY cv.id DESC LIMIT 1) AS next_appointment_date
             FROM ccc_profiles cp
             JOIN patients p ON p.id = cp.patient_id
             $where
             ORDER BY cp.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        // Stats with Caching
        $cacheVer = (int) Cache::get("ccc_stats_ver_{$tenantId}", 1);
        $cacheKey = "ccc_stats_{$tenantId}_v{$cacheVer}";
        $stats = Cache::remember($cacheKey, 60, function () use ($tenantId) {
            $active = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM ccc_profiles WHERE tenant_id = ? AND status = 'active'", [$tenantId])['c'] ?? 0);
            $suppressed = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM ccc_profiles WHERE tenant_id = ? AND status = 'active' AND viral_load_suppressed = 1", [$tenantId])['c'] ?? 0);
            $pmtctCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM ccc_profiles WHERE tenant_id = ? AND pmtct_active = 1", [$tenantId])['c'] ?? 0);
            $secondLine = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM ccc_profiles WHERE tenant_id = ? AND line_of_treatment != 'first_line'", [$tenantId])['c'] ?? 0);
            $suppressionRate = $active > 0 ? round(($suppressed / $active) * 100, 1) : 100.0;

            return [
                'activeClients'        => $active,
                'suppressedClients'    => $suppressed,
                'suppressionRate'      => $suppressionRate,
                'pmtctCount'           => $pmtctCount,
                'secondLineCount'      => $secondLine,
            ];
        });

        // Candidate patients not yet enrolled in CCC
        $candidatePatients = DB::select(
            "SELECT p.id, p.uuid, p.first_name, p.last_name, p.mrn, p.sex, p.dob, p.phone
             FROM patients p
             WHERE p.tenant_id = ? 
               AND p.id NOT IN (SELECT cp2.patient_id FROM ccc_profiles cp2 WHERE cp2.tenant_id = ?)
             ORDER BY p.id DESC
             LIMIT 250",
            [$tenantId, $tenantId]
        );

        return view('clinic.ccc.index', [
            'title'             => 'Comprehensive Care Centre (CCC) - HIV Clinic',
            'clients'           => $clients,
            'total'             => $totalRecords,
            'page'              => $page,
            'lastPage'          => $lastPage,
            'perPage'           => $perPage,
            'q'                 => $q,
            'status'            => $status,
            'suppression'       => $suppression,
            'pmtct'             => $pmtct,
            'stats'             => $stats,
            'candidatePatients' => $candidatePatients,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $user = Auth::user();

        $patientId = (int) $request->input('patient_id');
        if (!$patientId) {
            return back()->withInput()->with('error', 'Please select a patient to enroll into the CCC clinic.');
        }

        // Verify patient belongs to tenant
        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$patientId, $tenantId]);
        if (!$patient) {
            return back()->withInput()->with('error', 'Patient not found in this hospital.');
        }

        // Check if already enrolled
        $existing = DB::selectOne("SELECT id, ccc_number FROM ccc_profiles WHERE patient_id = ? AND tenant_id = ?", [$patientId, $tenantId]);
        if ($existing) {
            return back()->withInput()->with('error', "Patient is already enrolled in CCC with Number {$existing['ccc_number']}.");
        }

        // Generate CCC Number: CCC-YYYY-XXXX
        $seq = Sequence::next('ccc_number');
        $cccNumber = 'CCC-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $enrollmentDate = $request->input('enrollment_date') ?: date('Y-m-d');
        $artStartDate = $request->input('art_start_date') ?: $enrollmentDate;
        $baselineWho = $request->input('who_stage_baseline', 'stage_1');
        $baselineCd4 = $request->input('baseline_cd4') !== '' ? (int)$request->input('baseline_cd4') : null;
        $baselineVl = $request->input('baseline_viral_load') !== '' ? (int)$request->input('baseline_viral_load') : null;
        $currentRegimen = trim((string) $request->input('current_regimen', 'TDF+3TC+DTG'));
        $lineOfTreatment = $request->input('line_of_treatment', 'first_line');
        $pmtct = !empty($request->input('pmtct_active')) ? 1 : 0;

        // Suppression criteria: VL < 1000 copies/mL or target not detected
        $isSuppressed = ($baselineVl !== null && $baselineVl < 1000) ? 1 : 0;

        $profile = CccProfile::create([
            'branch_id'              => $user['branch_id'] ?? 1,
            'patient_id'             => $patientId,
            'ccc_number'             => $cccNumber,
            'enrollment_date'        => $enrollmentDate,
            'entry_point'            => $request->input('entry_point', 'opd'),
            'who_stage_baseline'     => $baselineWho,
            'current_who_stage'      => $baselineWho,
            'baseline_cd4'           => $baselineCd4,
            'current_cd4'            => $baselineCd4,
            'baseline_viral_load'    => $baselineVl,
            'current_viral_load'     => $baselineVl,
            'viral_load_suppressed'  => $isSuppressed,
            'current_regimen'        => $currentRegimen,
            'line_of_treatment'      => $lineOfTreatment,
            'art_start_date'         => $artStartDate,
            'tb_screening_status'    => $request->input('tb_screening_status', 'negative'),
            'pmtct_active'           => $pmtct,
            'treatment_buddy_name'   => trim((string) $request->input('treatment_buddy_name', '')),
            'treatment_buddy_phone'  => trim((string) $request->input('treatment_buddy_phone', '')),
            'status'                 => 'active',
        ]);

        // Record initial enrollment visit
        CccVisit::create([
            'branch_id'                     => $user['branch_id'] ?? 1,
            'ccc_profile_id'                => $profile->id,
            'patient_id'                    => $patientId,
            'doctor_user_id'                => Auth::id(),
            'visit_date'                    => $enrollmentDate,
            'cd4_count'                     => $baselineCd4,
            'viral_load_copies_ml'          => $baselineVl,
            'who_stage'                     => $baselineWho,
            'adherence_pill_count_percent'  => 100.0,
            'regimen_dispensed'             => $currentRegimen,
            'months_dispensed'              => (int) $request->input('months_dispensed', 3),
            'tb_screened_this_visit'        => 1,
            'next_appointment_date'         => date('Y-m-d', strtotime('+3 months', strtotime($enrollmentDate))),
            'clinical_notes'                => 'Initial CCC enrollment and baseline ARV dispensing.',
        ]);

        // Invalidate stats cache
        $ver = (int) Cache::get("ccc_stats_ver_{$tenantId}", 1);
        Cache::set("ccc_stats_ver_{$tenantId}", $ver + 1, 86400);

        audit('ccc.client_enrolled', 'CccProfile', $profile->id, [
            'ccc_number' => $cccNumber,
            'patient_id' => $patientId,
        ]);

        return redirect(route('clinic.ccc.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', "Patient successfully enrolled in CCC with Number {$cccNumber}.");
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = CccProfile::find($id);
        if (!$profile) {
            abort(404, 'CCC profile not found');
        }

        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$profile->patient_id, $tenantId]);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        $visits = DB::select(
            "SELECT cv.*, u.name AS clinician_name
             FROM ccc_visits cv
             LEFT JOIN users u ON u.id = cv.doctor_user_id
             WHERE cv.ccc_profile_id = ? AND cv.tenant_id = ?
             ORDER BY cv.visit_date DESC, cv.id DESC",
            [$profile->id, $tenantId]
        );

        return view('clinic.ccc.show', [
            'title'   => "CCC Client: {$profile->ccc_number} ({$patient['first_name']} {$patient['last_name']})",
            'profile' => $profile,
            'patient' => $patient,
            'visits'  => $visits,
        ]);
    }

    public function storeVisit(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $user = Auth::user();
        $profile = CccProfile::find($id);
        if (!$profile) {
            abort(404, 'CCC profile not found');
        }

        $visitDate = $request->input('visit_date') ?: date('Y-m-d');
        $weight = $request->input('weight_kg') !== '' ? (float) $request->input('weight_kg') : null;
        $height = $request->input('height_cm') !== '' ? (float) $request->input('height_cm') : null;
        
        $bmi = null;
        if ($weight && $height && $height > 0) {
            $heightM = $height / 100.0;
            $bmi = round($weight / ($heightM * $heightM), 1);
        }

        $cd4 = $request->input('cd4_count') !== '' ? (int) $request->input('cd4_count') : null;
        $vl = $request->input('viral_load_copies_ml') !== '' ? (int) $request->input('viral_load_copies_ml') : null;
        $whoStage = $request->input('who_stage', $profile->current_who_stage);
        $adherence = $request->input('adherence_pill_count_percent') !== '' ? (float) $request->input('adherence_pill_count_percent') : 100.0;
        $regimenDispensed = trim((string) $request->input('regimen_dispensed', $profile->current_regimen));
        $monthsDispensed = max(1, (int) $request->input('months_dispensed', 3));
        $nextAppt = $request->input('next_appointment_date') ?: date('Y-m-d', strtotime("+{$monthsDispensed} months", strtotime($visitDate)));
        $tbScreened = !empty($request->input('tb_screened_this_visit')) ? 1 : 0;
        $notes = trim((string) $request->input('clinical_notes', ''));

        $visit = CccVisit::create([
            'branch_id'                    => $user['branch_id'] ?? 1,
            'ccc_profile_id'               => $profile->id,
            'patient_id'                   => $profile->patient_id,
            'doctor_user_id'               => Auth::id(),
            'visit_date'                   => $visitDate,
            'weight_kg'                    => $weight,
            'height_cm'                    => $height,
            'bmi'                          => $bmi,
            'cd4_count'                    => $cd4,
            'viral_load_copies_ml'         => $vl,
            'who_stage'                    => $whoStage,
            'adherence_pill_count_percent' => $adherence,
            'regimen_dispensed'            => $regimenDispensed,
            'months_dispensed'             => $monthsDispensed,
            'tb_screened_this_visit'       => $tbScreened,
            'next_appointment_date'        => $nextAppt,
            'clinical_notes'               => $notes,
        ]);

        // Update profile current metrics
        $updateProfile = [
            'current_who_stage' => $whoStage,
            'current_regimen'   => $regimenDispensed,
        ];
        if ($cd4 !== null) {
            $updateProfile['current_cd4'] = $cd4;
        }
        if ($vl !== null) {
            $updateProfile['current_viral_load'] = $vl;
            $updateProfile['viral_load_suppressed'] = ($vl < 1000) ? 1 : 0;
        }
        $profile->update($updateProfile);

        // Invalidate stats cache
        $ver = (int) Cache::get("ccc_stats_ver_{$tenantId}", 1);
        Cache::set("ccc_stats_ver_{$tenantId}", $ver + 1, 86400);

        audit('ccc.visit_recorded', 'CccVisit', $visit->id, [
            'ccc_number' => $profile->ccc_number,
            'visit_date' => $visitDate,
        ]);

        return redirect(route('clinic.ccc.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', "Follow-up clinical encounter and {$monthsDispensed}-month ARV refill recorded.");
    }

    public function card(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = CccProfile::find($id);
        if (!$profile) {
            abort(404, 'CCC profile not found');
        }

        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$profile->patient_id, $tenantId]);
        $visits = DB::select(
            "SELECT * FROM ccc_visits WHERE ccc_profile_id = ? AND tenant_id = ? ORDER BY visit_date DESC LIMIT 12",
            [$profile->id, $tenantId]
        );

        return view('clinic.ccc.card', [
            'title'   => "CCC Patient Passport: {$profile->ccc_number}",
            'profile' => $profile,
            'patient' => $patient,
            'visits'  => $visits,
        ]);
    }
}
