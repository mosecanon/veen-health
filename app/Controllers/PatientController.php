<?php
namespace App\Controllers;

use App\Models\Patient;
use App\Models\Sale;
use App\Models\Ticket;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Session;
use Veen\Core\Tenancy\TenantManager;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $params = [$tenantId];
        $where = 'WHERE tenant_id = ? AND deleted_at IS NULL';

        if ($q !== '') {
            $where .= ' AND (mrn LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR national_id LIKE ?)';
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search, $search);
        }

        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM patients $where", $params)['c'] ?? 0);

        $limitParams = $params;
        $limitParams[] = $perPage;
        $limitParams[] = $offset;

        $patients = DB::select(
            "SELECT * FROM patients $where ORDER BY id DESC LIMIT ? OFFSET ?",
            $limitParams
        );

        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('patients.index', [
            'title' => 'Patient Registry',
            'patients' => $patients,
            'q' => $q,
            'page' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): Response
    {
        return view('patients.create', [
            'title' => 'Register New Patient',
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $data = $this->validate($request, [
            'first_name'    => 'required|string|max:80',
            'last_name'     => 'required|string|max:80',
            'dob'           => 'nullable|date',
            'sex'           => 'required|in:male,female,other',
            'phone'         => 'required|string|max:30',
            'email'         => 'nullable|email|max:120',
            'national_id'   => 'nullable|string|max:50',
            'blood_group'   => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies'     => 'nullable|string|max:255',
            'address'       => 'nullable|string|max:255',
            'next_of_kin_name'  => 'nullable|string|max:120',
            'next_of_kin_phone' => 'nullable|string|max:30',
            'emergency_contact_name'  => 'nullable|string|max:120',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ]);

        $phone = trim((string) ($data['phone'] ?? ''));
        $nationalId = trim((string) ($data['national_id'] ?? ''));
        $isChild = !empty($request->input('is_child'));
        $parentPatientId = $request->input('parent_patient_id') ?: null;
        $relationship = trim((string) $request->input('relationship_to_parent', 'child')) ?: 'child';

        // Check duplicate within this hospital tenant
        if (!empty($nationalId) || !empty($phone)) {
            $duplicate = null;
            if (!empty($nationalId) && !empty($phone)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND (national_id = ? OR phone = ?) LIMIT 1",
                    [$tenantId, $nationalId, $phone]
                );
            } elseif (!empty($nationalId)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND national_id = ? LIMIT 1",
                    [$tenantId, $nationalId]
                );
            } else {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND phone = ? LIMIT 1",
                    [$tenantId, $phone]
                );
            }

            if ($duplicate) {
                if (!$isChild && empty($parentPatientId)) {
                    $matchedField = ($duplicate['phone'] === $phone) ? "Phone number ({$phone})" : "National ID ({$nationalId})";
                    return back()
                        ->withInput($request->all())
                        ->withErrors([
                            'phone' => "A patient with this {$matchedField} is already registered: {$duplicate['first_name']} {$duplicate['last_name']} (MRN: {$duplicate['mrn']}). If registering a child/dependent, check 'Register as Child / Dependent'."
                        ]);
                }
                // Allowed as child of existing patient
                $parentPatientId = $parentPatientId ?: $duplicate['id'];
                $isChild = true;
            }
        }

        $branchId = Session::get('active_branch_id') ?: (Auth::user()['branch_id'] ?? 1);

        $patient = new Patient();
        $patient->fill($data);
        $patient->forceFill([
            'mrn' => Sequence::mrn(),
            'branch_id' => $branchId,
            'parent_patient_id' => $isChild ? $parentPatientId : null,
            'is_child' => $isChild ? 1 : 0,
            'relationship_to_parent' => $isChild ? $relationship : null,
        ]);
        $patient->save();

        audit('patient.created', 'Patient', $patient->id, ['mrn' => $patient->mrn, 'name' => $patient->first_name . ' ' . $patient->last_name]);

        return redirect(route('patients.show', ['id' => $patient->uuid ?? $patient->id]))
            ->with('success', "Patient registered successfully with MRN {$patient->mrn}");
    }

    private function findPatient(int|string $id, int $tenantId, bool $allowTrashed = false): ?array
    {
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');
        $trashSql = $allowTrashed ? '' : ' AND deleted_at IS NULL';
        $patient = $isUuid
            ? DB::selectOne("SELECT * FROM patients WHERE uuid = ? AND tenant_id = ? {$trashSql}", [$id, $tenantId])
            : DB::selectOne("SELECT * FROM patients WHERE id = ? AND tenant_id = ? {$trashSql}", [(int)$id, $tenantId]);

        if ($patient && empty($patient['uuid']) && function_exists('generate_uuid')) {
            $newUuid = generate_uuid();
            DB::statement("UPDATE patients SET uuid = ? WHERE id = ?", [$newUuid, $patient['id']]);
            $patient['uuid'] = $newUuid;
        }

        return $patient;
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = $this->findPatient($id, $tenantId, true);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        // Parent and dependent children
        $parent = null;
        if (!empty($patient['parent_patient_id'])) {
            $parent = DB::selectOne('SELECT id, uuid, mrn, first_name, last_name, phone FROM patients WHERE id = ? AND tenant_id = ?', [$patient['parent_patient_id'], $tenantId]);
        }
        $children = DB::select('SELECT id, uuid, mrn, first_name, last_name, dob, sex, relationship_to_parent FROM patients WHERE parent_patient_id = ? AND tenant_id = ? AND deleted_at IS NULL ORDER BY id ASC', [$patient['id'], $tenantId]);

        // Visit tickets
        $tickets = DB::select(
            "SELECT t.*, q.name AS queue_name, q.color AS queue_color, sp.name AS service_point_name
             FROM tickets t
             LEFT JOIN queues q ON q.id = t.queue_id
             LEFT JOIN service_points sp ON sp.id = t.service_point_id
             WHERE t.patient_id = ? AND t.tenant_id = ?
             ORDER BY t.id DESC LIMIT 10",
            [$patient['id'], $tenantId]
        );

        // Pharmacy purchases
        $sales = DB::select(
            "SELECT s.*, u.name AS cashier_name
             FROM sales s
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.patient_id = ? AND s.tenant_id = ?
             ORDER BY s.id DESC LIMIT 10",
            [$patient['id'], $tenantId]
        );

        return view('patients.show', [
            'title' => $patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['mrn'] . ')',
            'patient' => $patient,
            'parent' => $parent,
            'children' => $children,
            'tickets' => $tickets,
            'sales' => $sales,
        ]);
    }

    public function card(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = $this->findPatient($id, $tenantId);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        return view('patients.card', [
            'title' => 'Patient Card: ' . $patient['first_name'] . ' ' . $patient['last_name'],
            'patient' => $patient,
        ]);
    }

    public function edit(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = $this->findPatient($id, $tenantId);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        return view('patients.edit', [
            'title' => 'Edit Patient: ' . $patient['first_name'] . ' ' . $patient['last_name'],
            'patient' => $patient,
        ]);
    }

    public function update(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $patient = $this->findPatient($id, $tenantId);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        $data = $this->validate($request, [
            'first_name'    => 'required|string|max:80',
            'last_name'     => 'required|string|max:80',
            'other_names'   => 'nullable|string|max:80',
            'dob'           => 'nullable|date',
            'sex'           => 'required|in:male,female,other',
            'phone'         => 'required|string|max:30',
            'email'         => 'nullable|email|max:120',
            'national_id'   => 'nullable|string|max:50',
            'blood_group'   => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies'     => 'nullable|string|max:255',
            'address'       => 'nullable|string|max:255',
            'next_of_kin_name'  => 'nullable|string|max:120',
            'next_of_kin_phone' => 'nullable|string|max:30',
            'emergency_contact_name'  => 'nullable|string|max:120',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ]);

        $phone = trim((string) $data['phone']);
        $nationalId = trim((string) ($data['national_id'] ?? ''));

        // Check duplicate within this hospital tenant (excluding this patient)
        if (!empty($nationalId) || !empty($phone)) {
            $duplicate = null;
            if (!empty($nationalId) && !empty($phone)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND id != ? AND (national_id = ? OR phone = ?) LIMIT 1",
                    [$tenantId, $patient['id'], $nationalId, $phone]
                );
            } elseif (!empty($nationalId)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND id != ? AND national_id = ? LIMIT 1",
                    [$tenantId, $patient['id'], $nationalId]
                );
            } else {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND id != ? AND phone = ? LIMIT 1",
                    [$tenantId, $patient['id'], $phone]
                );
            }

            if ($duplicate && empty($patient['is_child'])) {
                $matchedField = ($duplicate['phone'] === $phone) ? "Phone number ({$phone})" : "National ID ({$nationalId})";
                return back()
                    ->withInput($request->all())
                    ->withErrors([
                        'phone' => "Another patient with this {$matchedField} is already registered: {$duplicate['first_name']} {$duplicate['last_name']} (MRN: {$duplicate['mrn']})."
                    ]);
            }
        }

        $kinName = trim((string)($data['next_of_kin_name'] ?? $data['emergency_contact_name'] ?? ''));
        $kinPhone = trim((string)($data['next_of_kin_phone'] ?? $data['emergency_contact_phone'] ?? ''));

        DB::statement(
            "UPDATE patients SET
                first_name = ?, last_name = ?, other_names = ?, dob = ?, sex = ?,
                phone = ?, email = ?, national_id = ?, blood_group = ?, allergies = ?,
                address = ?, next_of_kin_name = ?, next_of_kin_phone = ?,
                updated_at = NOW()
             WHERE id = ? AND tenant_id = ?",
            [
                $data['first_name'],
                $data['last_name'],
                trim((string)($data['other_names'] ?? '')),
                $data['dob'] ?: null,
                $data['sex'],
                $phone,
                $data['email'] ?: null,
                $nationalId ?: null,
                $data['blood_group'] ?: null,
                trim((string)($data['allergies'] ?? '')),
                trim((string)($data['address'] ?? '')),
                $kinName ?: null,
                $kinPhone ?: null,
                $patient['id'],
                $tenantId
            ]
        );

        audit('patient.updated', 'Patient', $patient['id'], [
            'mrn' => $patient['mrn'],
            'name' => $data['first_name'] . ' ' . $data['last_name']
        ]);

        return redirect(route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]))
            ->with('success', "Patient demographics for {$data['first_name']} {$data['last_name']} updated successfully.");
    }

    public function destroy(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        $canDelete = in_array($currentUser['role'] ?? '', ['hospital_admin', 'super_admin'], true) || user_can('patients.delete');
        if (!$canDelete) {
            abort(403, 'Unauthorized: You do not have permission to delete patient records.');
        }

        $password = (string) $request->input('password', '');
        $userRow = DB::selectOne("SELECT password FROM users WHERE id = ?", [$currentUser['id']]);
        if (!$userRow || !password_verify($password, $userRow['password'])) {
            return back()->withErrors(['delete_password' => 'Incorrect password entered. Patient record was not deleted.']);
        }

        $patient = $this->findPatient($id, $tenantId);
        if (!$patient) {
            abort(404, 'Patient not found or already deleted');
        }

        DB::statement(
            "UPDATE patients SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?",
            [$patient['id'], $tenantId]
        );

        audit('patient.deleted', 'Patient', $patient['id'], [
            'mrn' => $patient['mrn'],
            'name' => $patient['first_name'] . ' ' . $patient['last_name']
        ]);

        return redirect(route('patients.index'))
            ->with('success', "Patient record for {$patient['first_name']} {$patient['last_name']} ({$patient['mrn']}) deleted successfully.");
    }

    public function restore(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        $patient = $this->findPatient($id, $tenantId, true);
        if (!$patient) {
            abort(404, 'Patient not found');
        }

        DB::statement("UPDATE patients SET deleted_at = NULL WHERE id = ? AND tenant_id = ?", [$patient['id'], $tenantId]);
        audit('patient.restored', 'Patient', $patient['id'], ['mrn' => $patient['mrn']]);

        return redirect(route('patients.show', ['id' => $patient['uuid'] ?? $patient['id']]))
            ->with('success', "Patient {$patient['first_name']} {$patient['last_name']} ({$patient['mrn']}) restored successfully.");
    }

    public function quickStore(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $nationalId = trim((string) $request->input('national_id', ''));
        $isChild = !empty($request->input('is_child'));
        $parentPatientId = $request->input('parent_patient_id') ?: null;
        $relationship = trim((string) $request->input('relationship_to_parent', 'child')) ?: 'child';

        if (empty($firstName) || empty($lastName)) {
            return Response::json(['success' => false, 'message' => 'First and last name are required'], 422);
        }

        if (!empty($nationalId) || !empty($phone)) {
            $duplicate = null;
            if (!empty($nationalId) && !empty($phone)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND (national_id = ? OR phone = ?) LIMIT 1",
                    [$tenantId, $nationalId, $phone]
                );
            } elseif (!empty($nationalId)) {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND national_id = ? LIMIT 1",
                    [$tenantId, $nationalId]
                );
            } else {
                $duplicate = DB::selectOne(
                    "SELECT * FROM patients WHERE tenant_id = ? AND deleted_at IS NULL AND phone = ? LIMIT 1",
                    [$tenantId, $phone]
                );
            }

            if ($duplicate) {
                if (!$isChild && empty($parentPatientId)) {
                    $matchedField = ($duplicate['phone'] === $phone) ? "Phone ({$phone})" : "National ID ({$nationalId})";
                    return Response::json([
                        'success' => false,
                        'message' => "A patient with this {$matchedField} is already registered: {$duplicate['first_name']} {$duplicate['last_name']} (MRN: {$duplicate['mrn']}). Check 'Register as Child' if registering a dependent child."
                    ], 422);
                }
                $parentPatientId = $parentPatientId ?: $duplicate['id'];
                $isChild = true;
            }
        }

        $branchId = Session::get('active_branch_id') ?: (Auth::user()['branch_id'] ?? 1);

        $patient = new Patient();
        $patient->fill([
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'other_names' => trim((string) $request->input('other_names', '')),
            'sex'         => in_array($request->input('sex'), ['male', 'female', 'other']) ? $request->input('sex') : 'male',
            'phone'       => $phone,
            'national_id' => $nationalId,
            'dob'         => $request->input('dob') ?: null,
            'blood_group' => $request->input('blood_group') ?: null,
            'allergies'   => trim((string) $request->input('allergies', '')),
        ]);
        $patient->forceFill([
            'mrn' => Sequence::mrn(),
            'branch_id' => $branchId,
            'parent_patient_id' => $isChild ? $parentPatientId : null,
            'is_child' => $isChild ? 1 : 0,
            'relationship_to_parent' => $isChild ? $relationship : null,
        ]);
        $patient->save();

        audit('patient.quick_created', 'Patient', $patient->id, ['mrn' => $patient->mrn, 'name' => $patient->first_name . ' ' . $patient->last_name]);

        return Response::json([
            'success' => true,
            'patient' => [
                'id'          => $patient->id,
                'uuid'        => $patient->uuid,
                'mrn'         => $patient->mrn,
                'first_name'  => $patient->first_name,
                'last_name'   => $patient->last_name,
                'full_name'   => $patient->first_name . ' ' . $patient->last_name,
                'phone'       => $patient->phone,
                'national_id' => $patient->national_id,
                'sex'         => $patient->sex,
                'is_child'    => (bool) $patient->is_child,
            ],
            'message' => "Patient {$patient->first_name} {$patient->last_name} ({$patient->mrn}) created successfully."
        ]);
    }

    public function search(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            $rows = DB::select(
                "SELECT id, uuid, mrn, first_name, last_name, other_names, phone, national_id, dob, sex, blood_group
                 FROM patients
                 WHERE tenant_id = ? AND deleted_at IS NULL
                 ORDER BY id DESC LIMIT 15",
                [$tenantId]
            );
        } else {
            $search = '%' . $q . '%';
            $rows = DB::select(
                "SELECT id, uuid, mrn, first_name, last_name, other_names, phone, national_id, dob, sex, blood_group
                 FROM patients
                 WHERE tenant_id = ? AND deleted_at IS NULL AND (
                    mrn LIKE ? 
                    OR first_name LIKE ? 
                    OR last_name LIKE ? 
                    OR other_names LIKE ? 
                    OR CONCAT(first_name, ' ', last_name) LIKE ?
                    OR phone LIKE ? 
                    OR national_id LIKE ?
                 )
                 ORDER BY id DESC LIMIT 25",
                [$tenantId, $search, $search, $search, $search, $search, $search, $search]
            );
        }

        foreach ($rows as &$r) {
            $r['age'] = age_from_dob($r['dob']);
        }

        return Response::json($rows);
    }
}
