<?php
namespace App\Controllers;

use App\Models\ChildImmunization;
use App\Models\ChildProfile;
use App\Models\ChildVisit;
use App\Models\Patient;
use App\Models\User;
use Veen\Core\Auth;
use Veen\Core\Cache;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class KidsClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $nutritionFilter = trim((string) $request->query('nutrition', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(20, (int) $request->query('per_page', 20)); // Minimum 20 records per page as requested
        $offset = ($page - 1) * $perPage;

        // Current cache version for this tenant
        $version = (int) Cache::get("kids_cwc_ver_{$tenantId}", 1);

        // Query active pediatric patients enrolled in Kids Clinic or children in patient registry
        $params = [$tenantId];
        $where = "WHERE p.tenant_id = ? AND p.deleted_at IS NULL AND (p.is_child = 1 OR cp.id IS NOT NULL)";

        if ($q !== '') {
            $where .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ? OR p.phone LIKE ?)";
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search);
        }

        if ($nutritionFilter !== '') {
            $where .= " AND lv.nutritional_status = ?";
            $params[] = $nutritionFilter;
        }

        $filterHash = md5($q . '_' . $nutritionFilter);

        // 1. Total records count (cached for faster pagination)
        $totalRecords = (int) Cache::remember(
            "kids_cwc_cnt_{$tenantId}_v{$version}_{$filterHash}",
            180,
            function () use ($where, $params) {
                $row = DB::selectOne(
                    "SELECT COUNT(DISTINCT p.id) AS c
                     FROM patients p
                     LEFT JOIN child_profiles cp ON cp.patient_id = p.id AND cp.tenant_id = p.tenant_id
                     LEFT JOIN child_visits lv ON lv.id = (
                         SELECT id FROM child_visits WHERE patient_id = p.id AND tenant_id = p.tenant_id ORDER BY visit_date DESC, id DESC LIMIT 1
                     )
                     $where",
                    $params
                );
                return (int) ($row['c'] ?? 0);
            }
        );

        $lastPage = max(1, (int) ceil($totalRecords / $perPage));

        // 2. Fetch children roster with latest visit metrics and immunization counts (cached)
        $cacheRosterKey = "kids_cwc_roster_{$tenantId}_v{$version}_p{$page}_l{$perPage}_{$filterHash}";
        $children = Cache::remember($cacheRosterKey, 180, function () use ($tenantId, $where, $params, $perPage, $offset) {
            $queryParams = array_merge([$tenantId, $tenantId, $tenantId], $params, [$perPage, $offset]);
            return DB::select(
                "SELECT p.id AS patient_id, p.uuid AS patient_uuid, p.mrn, p.first_name, p.last_name, p.dob, p.sex, p.phone,
                        cp.id AS profile_id, cp.uuid AS profile_uuid, cp.birth_weight_kg, cp.delivery_mode, cp.feeding_method,
                        lv.visit_date AS last_visit_date, lv.weight_kg AS last_weight_kg, lv.height_cm AS last_height_cm,
                        lv.muac_cm AS last_muac_cm, lv.nutritional_status AS last_nutrition, lv.next_visit_date,
                        (SELECT COUNT(*) FROM child_visits v WHERE v.patient_id = p.id AND v.tenant_id = ?) AS total_visits,
                        (SELECT COUNT(*) FROM child_immunizations i WHERE i.patient_id = p.id AND i.tenant_id = ? AND i.status = 'given') AS vaccines_given,
                        (SELECT COUNT(*) FROM child_immunizations i WHERE i.patient_id = p.id AND i.tenant_id = ? AND i.status IN ('due', 'overdue')) AS vaccines_pending
                 FROM patients p
                 LEFT JOIN child_profiles cp ON cp.patient_id = p.id AND cp.tenant_id = p.tenant_id
                 LEFT JOIN child_visits lv ON lv.id = (
                     SELECT id FROM child_visits WHERE patient_id = p.id AND tenant_id = p.tenant_id ORDER BY visit_date DESC, id DESC LIMIT 1
                 )
                 $where
                 ORDER BY p.id DESC
                 LIMIT ? OFFSET ?",
                $queryParams
            );
        });

        // 3. Dashboard Stats (cached)
        $stats = Cache::remember("kids_cwc_stats_{$tenantId}_v{$version}", 180, function () use ($tenantId) {
            return [
                'totalEnrolled'         => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM child_profiles WHERE tenant_id = ?", [$tenantId])['c'] ?? 0),
                'totalChildrenRegistry' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND is_child = 1", [$tenantId])['c'] ?? 0),
                'samCount'              => (int) (DB::selectOne("SELECT COUNT(DISTINCT patient_id) AS c FROM child_visits WHERE tenant_id = ? AND nutritional_status = 'sam'", [$tenantId])['c'] ?? 0),
                'mamCount'              => (int) (DB::selectOne("SELECT COUNT(DISTINCT patient_id) AS c FROM child_visits WHERE tenant_id = ? AND nutritional_status = 'mam'", [$tenantId])['c'] ?? 0),
                'pendingVaccines'       => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM child_immunizations WHERE tenant_id = ? AND status IN ('due', 'overdue')", [$tenantId])['c'] ?? 0),
            ];
        });

        // 4. Candidate children eligible for enrollment (cached)
        $unenrolledKids = Cache::remember("kids_cwc_unenrolled_{$tenantId}_v{$version}", 180, function () use ($tenantId) {
            return DB::select(
                "SELECT p.id, p.uuid, p.mrn, p.first_name, p.last_name, p.dob, p.sex, p.phone
                 FROM patients p
                 LEFT JOIN child_profiles cp ON cp.patient_id = p.id AND cp.tenant_id = p.tenant_id
                 WHERE p.tenant_id = ? AND p.deleted_at IS NULL AND (p.is_child = 1 OR TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) <= 12) AND cp.id IS NULL
                 ORDER BY p.id DESC LIMIT 30",
                [$tenantId]
            );
        });

        return view('clinic.kids.index', [
            'title'                 => 'Kids Clinic & Child Welfare (CWC)',
            'children'              => $children,
            'total'                 => $totalRecords,
            'page'                  => $page,
            'lastPage'              => $lastPage,
            'perPage'               => $perPage,
            'q'                     => $q,
            'nutritionFilter'       => $nutritionFilter,
            'totalEnrolled'         => $stats['totalEnrolled'],
            'totalChildrenRegistry' => $stats['totalChildrenRegistry'],
            'samCount'              => $stats['samCount'],
            'mamCount'              => $stats['mamCount'],
            'pendingVaccines'       => $stats['pendingVaccines'],
            'unenrolledKids'        => $unenrolledKids,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');

        // Resolve patient by patient uuid/id or child_profile uuid/id
        $patient = $isUuid
            ? DB::selectOne("SELECT * FROM patients WHERE (uuid = ? OR id IN (SELECT patient_id FROM child_profiles WHERE uuid = ?)) AND tenant_id = ?", [$id, $id, $tenantId])
            : DB::selectOne("SELECT * FROM patients WHERE (id = ? OR id IN (SELECT patient_id FROM child_profiles WHERE id = ?)) AND tenant_id = ?", [(int)$id, (int)$id, $tenantId]);

        if (!$patient) {
            abort(404, 'Child patient record not found');
        }

        // Child Profile (birth details, feeding)
        $profile = DB::selectOne("SELECT * FROM child_profiles WHERE patient_id = ? AND tenant_id = ?", [$patient['id'], $tenantId]);

        // Auto-create child profile if not created yet
        if (!$profile) {
            $newProfile = ChildProfile::create([
                'patient_id'     => $patient['id'],
                'feeding_method' => 'exclusive_breastfeeding',
                'delivery_mode'  => 'svd',
            ]);
            $profile = DB::selectOne("SELECT * FROM child_profiles WHERE id = ?", [$newProfile->id]);

            // Seed default immunization schedule
            $this->seedImmunizationSchedule($patient['id'], $profile['id'], $tenantId);
        } else {
            // Check if immunizations were initialized
            $existingImmCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM child_immunizations WHERE patient_id = ? AND tenant_id = ?", [$patient['id'], $tenantId])['c'] ?? 0);
            if ($existingImmCount === 0) {
                $this->seedImmunizationSchedule($patient['id'], $profile['id'], $tenantId);
            }
        }

        // Parent / Guardian info
        $parent = null;
        if (!empty($patient['parent_patient_id'])) {
            $parent = DB::selectOne("SELECT id, uuid, mrn, first_name, last_name, phone FROM patients WHERE id = ? AND tenant_id = ?", [$patient['parent_patient_id'], $tenantId]);
        }

        // Longitudinal Visits since first arrival at clinic
        $visits = DB::select(
            "SELECT v.*, u.name AS doctor_name
             FROM child_visits v
             LEFT JOIN users u ON u.id = v.doctor_user_id
             WHERE v.patient_id = ? AND v.tenant_id = ?
             ORDER BY v.visit_date ASC, v.id ASC",
            [$patient['id'], $tenantId]
        );

        // Immunizations list
        $immunizations = DB::select(
            "SELECT * FROM child_immunizations WHERE patient_id = ? AND tenant_id = ? ORDER BY id ASC",
            [$patient['id'], $tenantId]
        );

        // Prepare growth chart data series (dates, ages, weights, heights, head circ, muac)
        $chartData = [
            'labels'  => [],
            'weights' => [],
            'heights' => [],
            'muac'    => [],
            'headCirc'=> [],
        ];

        // Include birth metrics if present
        if (!empty($profile['birth_weight_kg'])) {
            $chartData['labels'][] = 'Birth';
            $chartData['weights'][] = (float) $profile['birth_weight_kg'];
            $chartData['heights'][] = $profile['birth_length_cm'] ? (float) $profile['birth_length_cm'] : null;
            $chartData['muac'][] = null;
            $chartData['headCirc'][] = $profile['birth_head_circ_cm'] ? (float) $profile['birth_head_circ_cm'] : null;
        }

        foreach ($visits as $v) {
            $label = date('d M Y', strtotime($v['visit_date'])) . ' (' . $v['age_months'] . 'm)';
            $chartData['labels'][] = $label;
            $chartData['weights'][] = (float) $v['weight_kg'];
            $chartData['heights'][] = $v['height_cm'] ? (float) $v['height_cm'] : null;
            $chartData['muac'][] = $v['muac_cm'] ? (float) $v['muac_cm'] : null;
            $chartData['headCirc'][] = $v['head_circumference_cm'] ? (float) $v['head_circumference_cm'] : null;
        }

        return view('clinic.kids.show', [
            'title'         => 'Child Chart: ' . $patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['mrn'] . ')',
            'patient'       => $patient,
            'profile'       => $profile,
            'parent'        => $parent,
            'visits'        => $visits,
            'immunizations' => $immunizations,
            'chartData'     => $chartData,
        ]);
    }

    public function storeProfile(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientInput = trim((string) $request->input('patient_id'));
        $patient = Patient::find($patientInput);

        if (!$patient) {
            return back()->with('error', 'Please select a valid child patient.');
        }

        // Ensure patient is marked as child
        if (!$patient->is_child) {
            $patient->update(['is_child' => 1]);
        }

        $existing = DB::selectOne("SELECT id FROM child_profiles WHERE patient_id = ? AND tenant_id = ?", [$patient->id, $tenantId]);
        if ($existing) {
            return redirect(route('clinic.kids.show', ['id' => $patient->uuid ?? $patient->id]))
                ->with('info', "Child profile for {$patient->first_name} is already registered.");
        }

        $profile = ChildProfile::create([
            'patient_id'                 => $patient->id,
            'birth_weight_kg'            => $request->input('birth_weight_kg') !== '' ? (float) $request->input('birth_weight_kg') : null,
            'birth_length_cm'            => $request->input('birth_length_cm') !== '' ? (float) $request->input('birth_length_cm') : null,
            'birth_head_circ_cm'         => $request->input('birth_head_circ_cm') !== '' ? (float) $request->input('birth_head_circ_cm') : null,
            'gestational_age_weeks'      => $request->input('gestational_age_weeks') !== '' ? (int) $request->input('gestational_age_weeks') : null,
            'delivery_mode'              => $request->input('delivery_mode', 'svd'),
            'apgar_score_1min'           => $request->input('apgar_score_1min') !== '' ? (int) $request->input('apgar_score_1min') : null,
            'apgar_score_5min'           => $request->input('apgar_score_5min') !== '' ? (int) $request->input('apgar_score_5min') : null,
            'place_of_birth'             => trim((string) $request->input('place_of_birth', '')),
            'birth_complications'        => trim((string) $request->input('birth_complications', '')),
            'feeding_method'             => $request->input('feeding_method', 'exclusive_breastfeeding'),
            'blood_group'                => trim((string) $request->input('blood_group', '')),
            'special_needs_or_allergies' => trim((string) $request->input('special_needs_or_allergies', '')),
        ]);

        $this->seedImmunizationSchedule($patient->id, $profile->id, $tenantId);
        $this->bustKidsCache($tenantId);

        audit('clinic.kids.profile_created', 'ChildProfile', $profile->id, ['patient_id' => $patient->id, 'mrn' => $patient->mrn]);

        return redirect(route('clinic.kids.show', ['id' => $patient->uuid ?? $patient->id]))
            ->with('success', "Child Welfare Clinic profile initialized for {$patient->first_name} {$patient->last_name}.");
    }

    public function updateProfile(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');

        $profile = $isUuid
            ? DB::selectOne("SELECT * FROM child_profiles WHERE uuid = ? AND tenant_id = ?", [$id, $tenantId])
            : DB::selectOne("SELECT * FROM child_profiles WHERE (id = ? OR patient_id = ?) AND tenant_id = ?", [(int)$id, [(int)$id], $tenantId]);

        if (!$profile) {
            abort(404, 'Child profile not found');
        }

        DB::statement(
            "UPDATE child_profiles SET 
                birth_weight_kg = ?, birth_length_cm = ?, birth_head_circ_cm = ?, gestational_age_weeks = ?,
                delivery_mode = ?, apgar_score_1min = ?, apgar_score_5min = ?, place_of_birth = ?,
                birth_complications = ?, feeding_method = ?, blood_group = ?, special_needs_or_allergies = ?,
                updated_at = NOW()
             WHERE id = ?",
            [
                $request->input('birth_weight_kg') !== '' ? (float) $request->input('birth_weight_kg') : null,
                $request->input('birth_length_cm') !== '' ? (float) $request->input('birth_length_cm') : null,
                $request->input('birth_head_circ_cm') !== '' ? (float) $request->input('birth_head_circ_cm') : null,
                $request->input('gestational_age_weeks') !== '' ? (int) $request->input('gestational_age_weeks') : null,
                $request->input('delivery_mode', 'svd'),
                $request->input('apgar_score_1min') !== '' ? (int) $request->input('apgar_score_1min') : null,
                $request->input('apgar_score_5min') !== '' ? (int) $request->input('apgar_score_5min') : null,
                trim((string) $request->input('place_of_birth', '')),
                trim((string) $request->input('birth_complications', '')),
                $request->input('feeding_method', 'exclusive_breastfeeding'),
                trim((string) $request->input('blood_group', '')),
                trim((string) $request->input('special_needs_or_allergies', '')),
                $profile['id']
            ]
        );

        $this->bustKidsCache($tenantId);

        $patient = DB::selectOne("SELECT uuid, id FROM patients WHERE id = ?", [$profile['patient_id']]);
        return redirect(route('clinic.kids.show', ['id' => $patient['uuid'] ?? $patient['id']]))
            ->with('success', 'Birth history and baseline child profile updated.');
    }

    public function storeVisit(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = Patient::find($id);
        if (!$patient) {
            abort(404, 'Child patient not found');
        }

        $profile = DB::selectOne("SELECT id FROM child_profiles WHERE patient_id = ? AND tenant_id = ?", [$patient->id, $tenantId]);
        $weight = (float) $request->input('weight_kg');
        if ($weight <= 0) {
            return back()->withInput()->with('error', 'Valid child weight in kg is required.');
        }

        $height = $request->input('height_cm') !== '' ? (float) $request->input('height_cm') : null;
        $headCirc = $request->input('head_circumference_cm') !== '' ? (float) $request->input('head_circumference_cm') : null;
        $muac = $request->input('muac_cm') !== '' ? (float) $request->input('muac_cm') : null;
        $visitDate = $request->input('visit_date') ?: date('Y-m-d');

        // Calculate age in months at time of visit
        $ageMonths = 0;
        if (!empty($patient->dob)) {
            $dobTime = strtotime($patient->dob);
            $visitTime = strtotime($visitDate);
            $diffYears = date('Y', $visitTime) - date('Y', $dobTime);
            $diffMonths = date('m', $visitTime) - date('m', $dobTime);
            $ageMonths = max(0, ($diffYears * 12) + $diffMonths);
        }

        // Automatic nutritional status classification based on MUAC (WHO standard for >= 6 months)
        $nutritionStatus = $request->input('nutritional_status');
        if (empty($nutritionStatus)) {
            if ($muac !== null && $ageMonths >= 6) {
                if ($muac < 11.5) {
                    $nutritionStatus = 'sam'; // Severe Acute Malnutrition
                } elseif ($muac < 12.5) {
                    $nutritionStatus = 'mam'; // Moderate Acute Malnutrition
                } else {
                    $nutritionStatus = 'normal';
                }
            } else {
                $nutritionStatus = 'normal';
            }
        }

        $visit = ChildVisit::create([
            'patient_id'               => $patient->id,
            'child_profile_id'         => $profile['id'] ?? null,
            'doctor_user_id'           => Auth::id(),
            'visit_date'               => $visitDate,
            'age_months'               => $ageMonths,
            'weight_kg'                => $weight,
            'height_cm'                => $height,
            'head_circumference_cm'    => $headCirc,
            'muac_cm'                  => $muac,
            'temperature_c'            => $request->input('temperature_c') !== '' ? (float) $request->input('temperature_c') : null,
            'pulse_rate'               => $request->input('pulse_rate') !== '' ? (int) $request->input('pulse_rate') : null,
            'respiratory_rate'         => $request->input('respiratory_rate') !== '' ? (int) $request->input('respiratory_rate') : null,
            'spo2_percent'             => $request->input('spo2_percent') !== '' ? (int) $request->input('spo2_percent') : null,
            'nutritional_status'       => $nutritionStatus,
            'developmental_milestones' => trim((string) $request->input('developmental_milestones', '')),
            'diet_and_feeding_notes'   => trim((string) $request->input('diet_and_feeding_notes', '')),
            'clinical_notes'           => trim((string) $request->input('clinical_notes', '')),
            'diagnoses'                => trim((string) $request->input('diagnoses', '')),
            'treatment_given'          => trim((string) $request->input('treatment_given', '')),
            'next_visit_date'          => $request->input('next_visit_date') ?: null,
        ]);

        audit('clinic.kids.visit_logged', 'ChildVisit', $visit->id, [
            'patient_id' => $patient->id,
            'weight_kg'  => $weight,
            'nutrition'  => $nutritionStatus,
        ]);

        $this->bustKidsCache($tenantId);

        return redirect(route('clinic.kids.show', ['id' => $patient->uuid ?? $patient->id]))
            ->with('success', "Clinic visit for {$patient->first_name} on " . date('d M Y', strtotime($visitDate)) . " recorded successfully.");
    }

    public function updateImmunization(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $imm = ChildImmunization::find($id);
        if (!$imm) {
            abort(404, 'Immunization record not found');
        }

        $status = $request->input('status', 'given');
        $adminDate = $request->input('administered_date') ?: date('Y-m-d');
        $batch = trim((string) $request->input('batch_number', ''));
        $adminBy = trim((string) $request->input('administered_by', Auth::user()['name'] ?? 'Clinic Nurse'));
        $adverseReaction = trim((string) $request->input('adverse_reaction', ''));
        $notes = trim((string) $request->input('notes', ''));

        $imm->update([
            'status'            => $status,
            'administered_date' => $status === 'given' ? $adminDate : null,
            'batch_number'      => $batch ?: null,
            'administered_by'   => $adminBy ?: null,
            'adverse_reaction'  => $adverseReaction ?: null,
            'notes'             => $notes ?: null,
            'next_due_date'     => $request->input('next_due_date') ?: null,
        ]);

        $patient = DB::selectOne("SELECT uuid, id FROM patients WHERE id = ?", [$imm->patient_id]);
        $this->bustKidsCache($tenantId);

        return redirect(route('clinic.kids.show', ['id' => $patient['uuid'] ?? $patient['id']]))
            ->with('success', "Vaccine '{$imm->vaccine_name}' status updated to '" . strtoupper($status) . "'.");
    }

    public function card(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = Patient::find($id);
        if (!$patient) {
            abort(404, 'Child patient not found');
        }

        $profile = DB::selectOne("SELECT * FROM child_profiles WHERE patient_id = ? AND tenant_id = ?", [$patient->id, $tenantId]);
        $parent = null;
        if (!empty($patient->parent_patient_id)) {
            $parent = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$patient->parent_patient_id, $tenantId]);
        }

        $visits = DB::select("SELECT * FROM child_visits WHERE patient_id = ? AND tenant_id = ? ORDER BY visit_date ASC", [$patient->id, $tenantId]);
        $immunizations = DB::select("SELECT * FROM child_immunizations WHERE patient_id = ? AND tenant_id = ? ORDER BY id ASC", [$patient->id, $tenantId]);

        return view('clinic.kids.card', [
            'title'         => 'Child Health Passport: ' . $patient->first_name . ' ' . $patient->last_name,
            'patient'       => $patient,
            'profile'       => $profile,
            'parent'        => $parent,
            'visits'        => $visits,
            'immunizations' => $immunizations,
        ]);
    }

    private function bustKidsCache(int $tenantId): void
    {
        $v = (int) Cache::get("kids_cwc_ver_{$tenantId}", 1);
        Cache::set("kids_cwc_ver_{$tenantId}", $v + 1, 86400);
    }

    private function seedImmunizationSchedule(int $patientId, ?int $profileId, int $tenantId): void
    {
        foreach (ChildImmunization::SCHEDULE as $item) {
            ChildImmunization::create([
                'patient_id'             => $patientId,
                'child_profile_id'       => $profileId,
                'vaccine_code'           => $item['code'],
                'vaccine_name'           => $item['name'],
                'target_age_description' => $item['age'],
                'dose_number'            => $item['dose'],
                'status'                 => 'due',
            ]);
        }
    }
}
