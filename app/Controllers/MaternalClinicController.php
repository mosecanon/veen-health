<?php
namespace App\Controllers;

use App\Models\MaternalProfile;
use App\Models\MaternalVisit;
use App\Models\Patient;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Cache;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class MaternalClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $filter = trim((string) $request->query('filter', 'active')); // active, delivered, high_risk, 1st, 2nd, 3rd
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(20, (int) $request->query('per_page', 20)); // Minimum 20 records per page as requested
        $offset = ($page - 1) * $perPage;

        $version = (int) Cache::get("maternal_anc_ver_{$tenantId}", 1);

        $params = [$tenantId];
        $where = "WHERE mp.tenant_id = ?";

        if ($filter === 'active') {
            $where .= " AND mp.status = 'active'";
        } elseif ($filter === 'delivered') {
            $where .= " AND mp.status = 'delivered'";
        } elseif ($filter === 'high_risk') {
            $where .= " AND mp.status = 'active' AND mp.high_risk_pregnancy = 1";
        } elseif ($filter === '1st') {
            $where .= " AND mp.status = 'active' AND DATEDIFF(CURDATE(), mp.lnmp) < 98";
        } elseif ($filter === '2nd') {
            $where .= " AND mp.status = 'active' AND DATEDIFF(CURDATE(), mp.lnmp) >= 98 AND DATEDIFF(CURDATE(), mp.lnmp) < 196";
        } elseif ($filter === '3rd') {
            $where .= " AND mp.status = 'active' AND DATEDIFF(CURDATE(), mp.lnmp) >= 196";
        }

        if ($q !== '') {
            $where .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ? OR mp.anc_number LIKE ? OR p.phone LIKE ?)";
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search, $search);
        }

        $filterHash = md5($q . '_' . $filter);

        // 1. Total records count (cached for instant pagination)
        $totalRecords = (int) Cache::remember(
            "maternal_anc_cnt_{$tenantId}_v{$version}_{$filterHash}",
            180,
            function () use ($where, $params) {
                $row = DB::selectOne(
                    "SELECT COUNT(*) AS c
                     FROM maternal_profiles mp
                     JOIN patients p ON p.id = mp.patient_id
                     $where",
                    $params
                );
                return (int) ($row['c'] ?? 0);
            }
        );

        $lastPage = max(1, (int) ceil($totalRecords / $perPage));

        // 2. Fetch paginated maternal profiles with latest visit metrics (cached)
        $cacheRosterKey = "maternal_anc_roster_{$tenantId}_v{$version}_p{$page}_l{$perPage}_{$filterHash}";
        $profiles = Cache::remember($cacheRosterKey, 180, function () use ($where, $params, $perPage, $offset) {
            $queryParams = array_merge($params, [$perPage, $offset]);
            $list = DB::select(
                "SELECT mp.*, p.uuid AS patient_uuid, p.first_name, p.last_name, p.mrn, p.dob, p.phone,
                        lv.visit_date AS last_visit_date, lv.visit_number AS last_visit_number,
                        lv.systolic_bp AS last_systolic, lv.diastolic_bp AS last_diastolic,
                        lv.weight_kg AS last_weight, lv.fundal_height_cm AS last_fundal_height,
                        lv.fetal_heart_rate_bpm AS last_fhr, lv.next_visit_date,
                        (SELECT COUNT(*) FROM maternal_visits mv WHERE mv.maternal_profile_id = mp.id) AS total_anc_visits
                 FROM maternal_profiles mp
                 JOIN patients p ON p.id = mp.patient_id
                 LEFT JOIN maternal_visits lv ON lv.id = (
                     SELECT id FROM maternal_visits WHERE maternal_profile_id = mp.id ORDER BY visit_date DESC, id DESC LIMIT 1
                 )
                 $where
                 ORDER BY mp.status ASC, mp.edd ASC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            // Enhance with dynamic gestational age and trimester
            foreach ($list as &$p) {
                $ga = MaternalProfile::calculateGestationalAge($p['lnmp']);
                $p['gestational_age'] = $ga['formatted'];
                $p['gestational_weeks'] = $ga['weeks'];
                $p['current_trimester'] = $ga['trimester'];
                $p['days_to_edd'] = (int) floor((strtotime($p['edd']) - time()) / 86400);
            }
            unset($p);

            return $list;
        });

        // 3. Stats (cached)
        $stats = Cache::remember("maternal_anc_stats_{$tenantId}_v{$version}", 180, function () use ($tenantId) {
            $activeCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM maternal_profiles WHERE tenant_id = ? AND status = 'active'", [$tenantId])['c'] ?? 0);
            $highRiskCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM maternal_profiles WHERE tenant_id = ? AND status = 'active' AND high_risk_pregnancy = 1", [$tenantId])['c'] ?? 0);
            $deliveredCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM maternal_profiles WHERE tenant_id = ? AND status = 'delivered'", [$tenantId])['c'] ?? 0);
            $totalVisitsCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM maternal_visits WHERE tenant_id = ?", [$tenantId])['c'] ?? 0);

            $allActive = DB::select("SELECT lnmp FROM maternal_profiles WHERE tenant_id = ? AND status = 'active'", [$tenantId]);
            $trimesterCounts = ['first' => 0, 'second' => 0, 'third' => 0];
            foreach ($allActive as $act) {
                $ga = MaternalProfile::calculateGestationalAge($act['lnmp']);
                $trimesterCounts[$ga['trimester']]++;
            }

            return [
                'activeCount'      => $activeCount,
                'highRiskCount'    => $highRiskCount,
                'deliveredCount'   => $deliveredCount,
                'totalVisitsCount' => $totalVisitsCount,
                'trimesterCounts'  => $trimesterCounts,
            ];
        });

        // 4. Eligible female patients for new pregnancy enrollment (female, not currently active in ANC)
        // Ordered by newest first (p.id DESC) so newly registered patients are immediately visible at the top!
        $candidates = Cache::remember("maternal_anc_candidates_{$tenantId}_v{$version}", 180, function () use ($tenantId) {
            return DB::select(
                "SELECT p.id, p.uuid, p.mrn, p.first_name, p.last_name, p.dob, p.phone
                 FROM patients p
                 LEFT JOIN maternal_profiles mp ON mp.patient_id = p.id AND mp.tenant_id = p.tenant_id AND mp.status = 'active'
                 WHERE p.tenant_id = ? AND p.deleted_at IS NULL AND LOWER(p.sex) IN ('female', 'f') AND mp.id IS NULL
                 ORDER BY p.id DESC LIMIT 250",
                [$tenantId]
            );
        });

        // If an explicit patient was targeted for enrollment from Patient profile, ensure she is present
        $targetPatientId = trim((string) $request->query('enroll_patient_id', ''));
        if ($targetPatientId !== '') {
            $isUuid = strlen($targetPatientId) === 36 && str_contains($targetPatientId, '-');
            $specificPatient = $isUuid
                ? DB::selectOne("SELECT p.id, p.uuid, p.mrn, p.first_name, p.last_name, p.dob, p.phone FROM patients p WHERE p.uuid = ? AND p.tenant_id = ? AND p.deleted_at IS NULL", [$targetPatientId, $tenantId])
                : DB::selectOne("SELECT p.id, p.uuid, p.mrn, p.first_name, p.last_name, p.dob, p.phone FROM patients p WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL", [(int)$targetPatientId, $tenantId]);

            if ($specificPatient) {
                $alreadyInList = false;
                foreach ($candidates as $c) {
                    if ($c['id'] == $specificPatient['id']) {
                        $alreadyInList = true;
                        break;
                    }
                }
                if (!$alreadyInList) {
                    array_unshift($candidates, $specificPatient);
                }
            }
        }

        return view('clinic.maternal.index', [
            'title'            => 'Clinic for Pregnant Women (Antenatal Care - ANC)',
            'profiles'         => $profiles,
            'total'            => $totalRecords,
            'page'             => $page,
            'lastPage'         => $lastPage,
            'perPage'          => $perPage,
            'q'                => $q,
            'filter'           => $filter,
            'activeCount'      => $stats['activeCount'],
            'highRiskCount'    => $stats['highRiskCount'],
            'deliveredCount'   => $stats['deliveredCount'],
            'totalVisitsCount' => $stats['totalVisitsCount'],
            'trimesterCounts'  => $stats['trimesterCounts'],
            'candidates'       => $candidates,
            'targetPatientId'  => $targetPatientId,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');

        // Resolve by profile uuid/id or patient uuid/id
        $profile = $isUuid
            ? DB::selectOne("SELECT mp.*, p.uuid AS patient_uuid, p.first_name, p.last_name, p.mrn, p.dob, p.phone, p.allergies
                             FROM maternal_profiles mp
                             JOIN patients p ON p.id = mp.patient_id
                             WHERE (mp.uuid = ? OR p.uuid = ?) AND mp.tenant_id = ?
                             ORDER BY mp.id DESC LIMIT 1", [$id, $id, $tenantId])
            : DB::selectOne("SELECT mp.*, p.uuid AS patient_uuid, p.first_name, p.last_name, p.mrn, p.dob, p.phone, p.allergies
                             FROM maternal_profiles mp
                             JOIN patients p ON p.id = mp.patient_id
                             WHERE (mp.id = ? OR p.id = ?) AND mp.tenant_id = ?
                             ORDER BY mp.id DESC LIMIT 1", [(int)$id, (int)$id, $tenantId]);

        if (!$profile) {
            // Check if patient exists and is female, then offer quick enrollment
            $patient = $isUuid
                ? DB::selectOne("SELECT * FROM patients WHERE uuid = ? AND tenant_id = ?", [$id, $tenantId])
                : DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [(int)$id, $tenantId]);

            if ($patient && in_array(strtolower($patient['sex']), ['female', 'f'], true)) {
                return redirect(route('clinic.maternal.index') . '?enroll_patient_id=' . ($patient['uuid'] ?? $patient['id']))
                    ->with('info', "Mother {$patient['first_name']} {$patient['last_name']} is not yet enrolled in the ANC clinic. Please complete baseline details below.");
            }

            abort(404, 'Maternal / ANC profile not found');
        }

        // Calculate dynamic gestational age and trimester
        $ga = MaternalProfile::calculateGestationalAge($profile['lnmp']);
        $profile['gestational_age'] = $ga['formatted'];
        $profile['gestational_weeks'] = $ga['weeks'];
        $profile['current_trimester'] = $ga['trimester'];
        $profile['days_to_edd'] = (int) floor((strtotime($profile['edd']) - time()) / 86400);

        // Fetch all longitudinal visits since first trimester
        $visits = DB::select(
            "SELECT v.*, u.name AS doctor_name
             FROM maternal_visits v
             LEFT JOIN users u ON u.id = v.doctor_user_id
             WHERE v.maternal_profile_id = ? AND v.tenant_id = ?
             ORDER BY v.visit_date ASC, v.id ASC",
            [$profile['id'], $tenantId]
        );

        // Group visits by trimester
        $visitsByTrimester = [
            'first'  => [],
            'second' => [],
            'third'  => [],
        ];
        foreach ($visits as $v) {
            $t = $v['trimester'] ?? 'first';
            $visitsByTrimester[$t][] = $v;
        }

        // Preventive Care Cumulative Counts
        $totalIfas = array_sum(array_column($visits, 'ifas_tablets_dispensed'));
        $dewormed = count(array_filter($visits, fn($v) => !empty($v['deworming_given']))) > 0;
        $netGiven = count(array_filter($visits, fn($v) => !empty($v['mosquito_net_given']))) > 0;
        $iptpDoses = array_filter(array_column($visits, 'iptp_sp_dose'), fn($d) => $d && $d !== 'none');
        $ttDoses = array_filter(array_column($visits, 'tetanus_toxoid_dose'), fn($d) => $d && $d !== 'none');

        // Prepare chart series (dates, gestational weeks, weight, systolic, diastolic, fundal height)
        $chartData = [
            'labels'        => [],
            'weights'       => [],
            'systolic'      => [],
            'diastolic'     => [],
            'fundalHeights' => [],
            'fhr'           => [],
        ];
        foreach ($visits as $v) {
            $label = 'W' . $v['gestational_age_weeks'] . ' (' . date('d M', strtotime($v['visit_date'])) . ')';
            $chartData['labels'][] = $label;
            $chartData['weights'][] = (float) $v['weight_kg'];
            $chartData['systolic'][] = (int) $v['systolic_bp'];
            $chartData['diastolic'][] = (int) $v['diastolic_bp'];
            $chartData['fundalHeights'][] = $v['fundal_height_cm'] ? (float) $v['fundal_height_cm'] : null;
            $chartData['fhr'][] = $v['fetal_heart_rate_bpm'] ? (int) $v['fetal_heart_rate_bpm'] : null;
        }

        return view('clinic.maternal.show', [
            'title'             => 'Antenatal Chart: ' . $profile['first_name'] . ' ' . $profile['last_name'] . ' (' . $profile['anc_number'] . ')',
            'profile'           => $profile,
            'visits'            => $visits,
            'visitsByTrimester' => $visitsByTrimester,
            'totalIfas'         => $totalIfas,
            'dewormed'          => $dewormed,
            'netGiven'          => $netGiven,
            'iptpDoses'         => array_unique($iptpDoses),
            'ttDoses'           => array_unique($ttDoses),
            'chartData'         => $chartData,
        ]);
    }

    public function storeProfile(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientInput = trim((string) $request->input('patient_id'));
        $patient = Patient::find($patientInput);

        if (!$patient) {
            return back()->with('error', 'Please select a valid patient for ANC enrollment.');
        }

        $lnmp = trim((string) $request->input('lnmp'));
        if (empty($lnmp)) {
            return back()->withInput()->with('error', 'Last Normal Menstrual Period (LNMP) is required to calculate gestational age and EDD.');
        }

        // Calculate EDD
        $edd = $request->input('edd') ?: MaternalProfile::calculateEdd($lnmp);

        // Check active pregnancy
        $activePregnancy = DB::selectOne("SELECT id FROM maternal_profiles WHERE patient_id = ? AND tenant_id = ? AND status = 'active'", [$patient->id, $tenantId]);
        if ($activePregnancy) {
            return redirect(route('clinic.maternal.show', ['id' => $activePregnancy['id']]))
                ->with('info', "Patient {$patient->first_name} already has an active pregnancy recorded.");
        }

        // Generate ANC Number
        $ancSeq = Sequence::next('anc_number');
        $ancNumber = 'ANC-' . date('Y') . '-' . str_pad((string) $ancSeq, 4, '0', STR_PAD_LEFT);

        $highRisk = !empty($request->input('high_risk_pregnancy')) ? 1 : 0;
        $riskFactors = trim((string) $request->input('risk_factors', ''));

        // Auto-detect high risk factors based on obstetric history
        $cSections = (int) $request->input('c_sections', 0);
        $miscarriages = (int) $request->input('miscarriages', 0);
        $stillbirths = (int) $request->input('stillbirths', 0);
        $rhesus = $request->input('rhesus_factor');

        if ($cSections >= 2 || $stillbirths > 0 || $rhesus === 'negative') {
            $highRisk = 1;
            if ($cSections >= 2 && !str_contains($riskFactors, 'Prior C-Sections')) $riskFactors .= ' Prior C-Sections (' . $cSections . ').';
            if ($stillbirths > 0 && !str_contains($riskFactors, 'Previous Stillbirth')) $riskFactors .= ' Previous Stillbirth (' . $stillbirths . ').';
            if ($rhesus === 'negative' && !str_contains($riskFactors, 'Rhesus Negative')) $riskFactors .= ' Rhesus Negative Mother (Isoimmunization risk).';
        }

        $profile = MaternalProfile::create([
            'patient_id'               => $patient->id,
            'anc_number'               => $ancNumber,
            'gravida'                  => max(1, (int) $request->input('gravida', 1)),
            'parity'                   => (int) $request->input('parity', 0),
            'living_children'          => (int) $request->input('living_children', 0),
            'miscarriages'             => $miscarriages,
            'stillbirths'              => $stillbirths,
            'c_sections'               => $cSections,
            'lnmp'                     => $lnmp,
            'edd'                      => $edd,
            'blood_group'              => trim((string) $request->input('blood_group', '')),
            'rhesus_factor'            => $rhesus ?: null,
            'hb_baseline'              => $request->input('hb_baseline') !== '' ? (float) $request->input('hb_baseline') : null,
            'hiv_status'               => $request->input('hiv_status', 'unknown'),
            'on_pmtct_art'             => !empty($request->input('on_pmtct_art')) ? 1 : 0,
            'syphilis_vdrl'            => $request->input('syphilis_vdrl', 'pending'),
            'hep_b_surface_ag'         => $request->input('hep_b_surface_ag', 'pending'),
            'blood_sugar_fasting'      => $request->input('blood_sugar_fasting') !== '' ? (float) $request->input('blood_sugar_fasting') : null,
            'urinalysis_baseline'      => trim((string) $request->input('urinalysis_baseline', '')),
            'status'                   => 'active',
            'birth_plan_hospital'      => trim((string) $request->input('birth_plan_hospital', '')),
            'birth_plan_transport'     => trim((string) $request->input('birth_plan_transport', '')),
            'birth_plan_donor'         => trim((string) $request->input('birth_plan_donor', '')),
            'birth_plan_contact_phone' => trim((string) $request->input('birth_plan_contact_phone', '')),
            'high_risk_pregnancy'      => $highRisk,
            'risk_factors'             => $riskFactors ?: null,
        ]);

        audit('clinic.maternal.profile_created', 'MaternalProfile', $profile->id, [
            'patient_id' => $patient->id,
            'anc_number' => $ancNumber,
            'edd'        => $edd,
        ]);

        $this->bustMaternalCache($tenantId);

        return redirect(route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', "Antenatal profile initialized for {$patient->first_name} {$patient->last_name} with ANC Number: {$ancNumber}.");
    }

    public function updateProfile(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = MaternalProfile::find($id);
        if (!$profile) {
            abort(404, 'Maternal profile not found');
        }

        $lnmp = trim((string) $request->input('lnmp')) ?: $profile->lnmp;
        $edd = $request->input('edd') ?: MaternalProfile::calculateEdd($lnmp);

        $profile->update([
            'gravida'                  => max(1, (int) $request->input('gravida', $profile->gravida)),
            'parity'                   => (int) $request->input('parity', $profile->parity),
            'living_children'          => (int) $request->input('living_children', $profile->living_children),
            'miscarriages'             => (int) $request->input('miscarriages', $profile->miscarriages),
            'stillbirths'              => (int) $request->input('stillbirths', $profile->stillbirths),
            'c_sections'               => (int) $request->input('c_sections', $profile->c_sections),
            'lnmp'                     => $lnmp,
            'edd'                      => $edd,
            'blood_group'              => trim((string) $request->input('blood_group', $profile->blood_group)),
            'rhesus_factor'            => $request->input('rhesus_factor', $profile->rhesus_factor),
            'hb_baseline'              => $request->input('hb_baseline') !== '' ? (float) $request->input('hb_baseline') : $profile->hb_baseline,
            'hiv_status'               => $request->input('hiv_status', $profile->hiv_status),
            'on_pmtct_art'             => !empty($request->input('on_pmtct_art')) ? 1 : 0,
            'syphilis_vdrl'            => $request->input('syphilis_vdrl', $profile->syphilis_vdrl),
            'hep_b_surface_ag'         => $request->input('hep_b_surface_ag', $profile->hep_b_surface_ag),
            'blood_sugar_fasting'      => $request->input('blood_sugar_fasting') !== '' ? (float) $request->input('blood_sugar_fasting') : $profile->blood_sugar_fasting,
            'urinalysis_baseline'      => trim((string) $request->input('urinalysis_baseline', $profile->urinalysis_baseline)),
            'high_risk_pregnancy'      => !empty($request->input('high_risk_pregnancy')) ? 1 : 0,
            'risk_factors'             => trim((string) $request->input('risk_factors', $profile->risk_factors)),
        ]);

        $this->bustMaternalCache($tenantId);

        return redirect(route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', 'Antenatal profile and baseline investigations updated.');
    }

    public function storeVisit(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = MaternalProfile::find($id);
        if (!$profile) {
            abort(404, 'Maternal profile not found');
        }

        $visitDate = $request->input('visit_date') ?: date('Y-m-d');
        
        // Auto-calculate Gestational age at visit from LNMP
        $gaCalc = MaternalProfile::calculateGestationalAge($profile->lnmp, $visitDate);
        $gestWeeks = $request->input('gestational_age_weeks') !== '' ? (float) $request->input('gestational_age_weeks') : (float) $gaCalc['weeks'];
        $trimester = $request->input('trimester') ?: $gaCalc['trimester'];

        $systolic = (int) $request->input('systolic_bp', 120);
        $diastolic = (int) $request->input('diastolic_bp', 80);
        $weight = (float) $request->input('weight_kg');

        if ($weight <= 0) {
            return back()->withInput()->with('error', 'Maternal weight in kg is required.');
        }

        // Count previous visits for sequence
        $prevVisitsCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM maternal_visits WHERE maternal_profile_id = ?", [$profile->id])['c'] ?? 0);
        $visitNumber = $prevVisitsCount + 1;

        // Screen for Pre-eclampsia or danger signs
        $dangerList = [];
        if (!empty($request->input('danger_severe_headache'))) $dangerList[] = 'Severe Headache';
        if (!empty($request->input('danger_blurred_vision'))) $dangerList[] = 'Blurred Vision';
        if (!empty($request->input('danger_epigastric_pain'))) $dangerList[] = 'Epigastric Pain';
        if (!empty($request->input('danger_vaginal_bleeding'))) $dangerList[] = 'Vaginal Bleeding';
        if (!empty($request->input('danger_leaking_liquor'))) $dangerList[] = 'Leaking Liquor';
        if (!empty($request->input('danger_decreased_fetal_movement'))) $dangerList[] = 'Reduced Fetal Movement';
        if ($systolic >= 140 || $diastolic >= 90) {
            $dangerList[] = 'Elevated Blood Pressure (' . $systolic . '/' . $diastolic . ' mmHg - Pre-eclampsia alert)';
        }
        $rawDanger = trim((string) $request->input('danger_signs_screened', ''));
        if ($rawDanger) {
            $dangerList[] = $rawDanger;
        }
        $dangerSigns = implode(', ', array_unique($dangerList));

        $uProtein = match((string)$request->input('urine_protein')) {
            '1+', '1_plus' => '1_plus',
            '2+', '2_plus' => '2_plus',
            '3+', '3_plus', '4+' => '3_plus',
            'trace'        => 'trace',
            default        => 'nil',
        };
        $uGlucose = match((string)$request->input('urine_glucose')) {
            '1+', '1_plus' => '1_plus',
            '2+', '2_plus' => '2_plus',
            'trace'        => 'trace',
            default        => 'nil',
        };
        $fMovement = match((string)$request->input('fetal_movement')) {
            'present', 'active' => 'active',
            'reduced'           => 'reduced',
            'absent'            => 'absent',
            default             => 'not_felt_yet',
        };
        $iptpDose = match((string)$request->input('iptp_sp_dose')) {
            'sp1', 'dose_1', '1' => 'dose_1',
            'sp2', 'dose_2', '2' => 'dose_2',
            'sp3', 'dose_3', '3' => 'dose_3',
            'sp4', 'dose_4', '4' => 'dose_4',
            'sp5', 'dose_5', '5' => 'dose_5',
            default              => 'none',
        };
        $ttDose = match((string)$request->input('tetanus_toxoid_dose')) {
            'tt1', '1' => 'tt1',
            'tt2', '2' => 'tt2',
            'tt3', '3' => 'tt3',
            'tt4', '4' => 'tt4',
            'tt5', '5' => 'tt5',
            default    => 'none',
        };

        $visit = MaternalVisit::create([
            'maternal_profile_id'    => $profile->id,
            'patient_id'             => $profile->patient_id,
            'doctor_user_id'         => Auth::id(),
            'visit_date'             => $visitDate,
            'visit_number'           => $visitNumber,
            'trimester'              => $trimester,
            'gestational_age_weeks'  => $gestWeeks,
            'weight_kg'              => $weight,
            'systolic_bp'            => $systolic,
            'diastolic_bp'           => $diastolic,
            'pulse_rate'             => $request->input('pulse_rate') !== '' ? (int) $request->input('pulse_rate') : null,
            'temperature_c'          => $request->input('temperature_c') !== '' ? (float) $request->input('temperature_c') : null,
            'fundal_height_cm'       => $request->input('fundal_height_cm') !== '' ? (float) $request->input('fundal_height_cm') : null,
            'fetal_presentation'     => $request->input('fetal_presentation', 'not_applicable'),
            'fetal_lie'              => $request->input('fetal_lie', 'not_applicable'),
            'fetal_heart_rate_bpm'   => $request->input('fetal_heart_rate_bpm') !== '' ? (int) $request->input('fetal_heart_rate_bpm') : null,
            'fetal_movement'         => $fMovement,
            'oedema'                 => $request->input('oedema', 'none'),
            'urine_protein'          => $uProtein,
            'urine_glucose'          => $uGlucose,
            'ultrasound_findings'    => trim((string) $request->input('ultrasound_findings', '')),
            'ifas_tablets_dispensed' => (int) $request->input('ifas_tablets_dispensed', 30),
            'iptp_sp_dose'           => $iptpDose,
            'tetanus_toxoid_dose'    => $ttDose,
            'deworming_given'        => !empty($request->input('deworming_given')) ? 1 : 0,
            'mosquito_net_given'     => !empty($request->input('mosquito_net_given')) ? 1 : 0,
            'danger_signs_screened'  => $dangerSigns ?: null,
            'clinical_notes'         => trim((string) $request->input('clinical_notes', '')),
            'next_visit_date'        => $request->input('next_visit_date') ?: null,
        ]);

        audit('clinic.maternal.visit_logged', 'MaternalVisit', $visit->id, [
            'maternal_profile_id' => $profile->id,
            'visit_number'        => $visitNumber,
            'trimester'           => $trimester,
            'bp'                  => "{$systolic}/{$diastolic}",
        ]);

        $this->bustMaternalCache($tenantId);

        return redirect(route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', "Antenatal Visit #{$visitNumber} (" . ucfirst($trimester) . " Trimester, {$gestWeeks} weeks) recorded successfully.");
    }

    public function updateBirthPlan(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = MaternalProfile::find($id);
        if (!$profile) {
            abort(404, 'Maternal profile not found');
        }

        $hospital = trim((string) ($request->input('birth_plan_hospital') ?: $request->input('chosen_delivery_facility', '')));
        $transport = trim((string) ($request->input('birth_plan_transport') ?: $request->input('transport_arrangement', '')));
        $donor = trim((string) ($request->input('birth_plan_donor') ?: $request->input('designated_blood_donor_name', '')));
        $phone = trim((string) ($request->input('birth_plan_contact_phone') ?: $request->input('designated_blood_donor_phone', '')));

        $profile->update([
            'birth_plan_hospital'      => $hospital ?: $profile->birth_plan_hospital,
            'birth_plan_transport'     => $transport ?: $profile->birth_plan_transport,
            'birth_plan_donor'         => $donor ?: $profile->birth_plan_donor,
            'birth_plan_contact_phone' => $phone ?: $profile->birth_plan_contact_phone,
        ]);

        $this->bustMaternalCache($tenantId);

        return redirect(route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', 'Individualized Birth Preparedness & Complication Readiness plan updated.');
    }

    public function concludeDelivery(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = MaternalProfile::find($id);
        if (!$profile) {
            abort(404, 'Maternal profile not found');
        }

        $deliveryDate = $request->input('delivery_date') ?: date('Y-m-d');
        $status = $request->input('status', 'delivered');
        $notes = trim((string) $request->input('delivery_notes', ''));

        $profile->update([
            'status'        => $status,
            'delivery_date' => $deliveryDate,
            'risk_factors'  => $notes ? ($profile->risk_factors . " [Delivery Outcome: {$notes}]") : $profile->risk_factors,
        ]);

        audit('clinic.maternal.concluded', 'MaternalProfile', $profile->id, [
            'status'        => $status,
            'delivery_date' => $deliveryDate,
        ]);

        $this->bustMaternalCache($tenantId);

        return redirect(route('clinic.maternal.show', ['id' => $profile->uuid ?? $profile->id]))
            ->with('success', "Pregnancy episode marked as {$status}.");
    }

    private function bustMaternalCache(int $tenantId): void
    {
        $v = (int) Cache::get("maternal_anc_ver_{$tenantId}", 1);
        Cache::set("maternal_anc_ver_{$tenantId}", $v + 1, 86400);
    }

    public function card(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $profile = MaternalProfile::find($id);
        if (!$profile) {
            abort(404, 'Maternal profile not found');
        }

        $patient = DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ?", [$profile->patient_id, $tenantId]);
        $visits = DB::select("SELECT * FROM maternal_visits WHERE maternal_profile_id = ? AND tenant_id = ? ORDER BY visit_date ASC", [$profile->id, $tenantId]);

        return view('clinic.maternal.card', [
            'title'   => 'Maternal Antenatal Health Booklet: ' . $patient['first_name'] . ' ' . $patient['last_name'],
            'profile' => $profile,
            'patient' => $patient,
            'visits'  => $visits,
        ]);
    }
}
