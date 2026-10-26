<?php
namespace App\Controllers;

use App\Models\Branch;
use App\Models\User;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $roleFilter = $request->query('role');

        $query = "SELECT u.*, b.name AS branch_name
                  FROM users u
                  LEFT JOIN branches b ON b.id = u.branch_id
                  WHERE u.tenant_id = ?";
        $params = [$tenantId];

        if ($roleFilter) {
            $query .= " AND u.role = ?";
            $params[] = $roleFilter;
        }

        $query .= " ORDER BY CASE u.role
                    WHEN 'hospital_admin' THEN 1
                    WHEN 'doctor' THEN 2
                    WHEN 'nurse' THEN 3
                    WHEN 'pharmacist' THEN 4
                    WHEN 'lab_tech' THEN 5
                    WHEN 'cashier' THEN 6
                    ELSE 7 END ASC, u.name ASC";

        $staff = DB::select($query, $params);
        $branches = DB::select("SELECT * FROM branches WHERE tenant_id = ? ORDER BY name ASC", [$tenantId]);
        $roles = DB::select("SELECT * FROM hospital_roles WHERE tenant_id = ? ORDER BY id ASC", [$tenantId]);

        // Counts by role
        $counts = [
            'all' => count($staff),
            'doctor' => 0,
            'nurse' => 0,
            'pharmacist' => 0,
            'lab_tech' => 0,
            'cashier' => 0,
            'hospital_admin' => 0,
        ];
        $allStaff = DB::select("SELECT role, COUNT(*) as c FROM users WHERE tenant_id = ? GROUP BY role", [$tenantId]);
        foreach ($allStaff as $row) {
            $counts[$row['role']] = (int) $row['c'];
        }

        return view('staff.index', [
            'title' => 'Hospital Staff & Employee Directory',
            'staff' => $staff,
            'branches' => $branches,
            'roles' => $roles,
            'counts' => $counts,
            'currentRole' => $roleFilter,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $data = $this->validate($request, [
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|max:120',
            'role'       => 'required|string|max:50',
            'title'      => 'nullable|string|max:80',
            'phone'      => 'nullable|string|max:30',
            'branch_id'  => 'nullable|integer',
            'password'   => 'nullable|string|min:6',
        ]);

        $email = strtolower(trim($data['email']));

        // Check uniqueness in global users table
        $existing = DB::selectOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return back()->withErrors(['email' => ["The email '{$email}' is already in use by another staff member."]])->withInput($request->all());
        }

        $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        if (!$branchId) {
            $mainBranch = DB::selectOne("SELECT id FROM branches WHERE tenant_id = ? ORDER BY is_main DESC, id ASC LIMIT 1", [$tenantId]);
            $branchId = $mainBranch['id'] ?? null;
        }

        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $title = match ($data['role']) {
                'doctor' => 'Physician',
                'nurse' => 'Staff Nurse',
                'pharmacist' => 'Pharmacist',
                'lab_tech' => 'Lab Technologist',
                'cashier' => 'Cashier / Billing',
                'receptionist' => 'Front Desk',
                default => 'Staff Member',
            };
        }

        $password = !empty($data['password']) ? $data['password'] : 'Password@123';
        $userUuid = generate_uuid();
        $canAccessAllBranches = !empty($request->input('can_access_all_branches')) ? 1 : 0;

        DB::statement(
            "INSERT INTO users (uuid, tenant_id, branch_id, can_access_all_branches, name, email, password, role, title, phone, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $userUuid,
                $tenantId,
                $branchId,
                $canAccessAllBranches,
                trim($data['name']),
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $data['role'],
                $title,
                $data['phone'] ?? null,
            ]
        );

        return redirect(route('staff.index'))->with('success', "Employee '{$data['name']}' ({$title}) added successfully. Default password: {$password}");
    }

    public function update(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $user = DB::selectOne("SELECT * FROM users WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$user) abort(404, 'Staff member not found');

        $data = $request->validate([
            'name' => 'required|min:2|max:150',
            'role' => 'required',
            'title' => 'nullable|max:60',
            'phone' => 'nullable|max:30',
            'branch_id' => 'nullable|numeric',
        ]);

        $name = trim($data['name']);
        $role = $data['role'];
        $title = !empty($data['title']) ? trim($data['title']) : null;
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : ($user['branch_id'] ?? null);
        $isActive = $request->input('is_active') !== null ? 1 : 0;
        $canAccessAllBranches = !empty($request->input('can_access_all_branches')) ? 1 : 0;

        // If updating own account, cannot deactivate
        if ($user['id'] == auth()->id) {
            $isActive = 1;
        }

        DB::statement(
            "UPDATE users SET name = ?, role = ?, title = ?, phone = ?, branch_id = ?, can_access_all_branches = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?",
            [$name, $role, $title, $phone, $branchId, $canAccessAllBranches, $isActive, $user['id'], $tenantId]
        );

        audit('staff.updated', 'User', (int) $user['id'], ['name' => $name, 'role' => $role, 'title' => $title]);

        return redirect(route('staff.index'))->with('success', "Staff details for '{$name}' updated successfully.");
    }

    public function toggle(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $user = DB::selectOne("SELECT * FROM users WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$user) abort(404);

        if ($user['id'] == auth()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        DB::statement("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $user['id']]);

        audit('staff.toggled', 'User', $user['id'], ['active' => $newStatus]);

        $stateText = $newStatus ? 'activated' : 'deactivated';
        return back()->with('info', "Staff account for '{$user['name']}' has been {$stateText}.");
    }

    public function resetPassword(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $user = DB::selectOne("SELECT * FROM users WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$user) abort(404);

        $defaultPass = 'Password@123';
        DB::statement("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [password_hash($defaultPass, PASSWORD_DEFAULT), $user['id']]);

        return back()->with('success', "Password for {$user['name']} has been reset to: {$defaultPass}");
    }

    public function roles(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $roles = DB::select(
            "SELECT hr.*, 
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = hr.tenant_id AND u.role = hr.slug) AS users_count
             FROM hospital_roles hr
             WHERE hr.tenant_id = ?
             ORDER BY hr.is_system DESC, hr.name ASC",
            [$tenantId]
        );

        $availablePermissions = [
            'clinical_emr'       => 'Doctor EMR & Patient Consultations',
            'consultations'      => 'General Clinical Consultations',
            'appointments'       => 'Appointment Scheduling & Calendar',
            'patients'           => 'Patient Master Index & Medical Cards',
            'queue'              => 'Queue Ticketing & Audio Calling',
            'triage'             => 'Nursing Vitals & Triage',
            'inpatient'          => 'Inpatient Wards & Bed Admissions',
            'lab'                => 'Laboratory Orders & Diagnostics',
            'lab_results'        => 'LIMS Test Results & Verification',
            'pharmacy'           => 'Pharmacy Inventory & Batch Control',
            'pharmacy_pos'       => 'Over-The-Counter (OTC) POS Checkout',
            'billing'            => 'Patient Invoicing & Cashier Desk',
            'claims'             => 'Insurance Claims & Adjudication',
            'reports_view'       => 'Operational & Financial Reports',
            'hr'                 => 'Human Resources, Leaves & Payroll',
            'expenses'           => 'Hospital Operating Expenses',
            'staff_admin'        => 'Staff Employee Accounts Management',
        ];

        return view('staff.roles', [
            'title' => 'Custom Hospital Roles & Permissions',
            'roles' => $roles,
            'availablePermissions' => $availablePermissions,
        ]);
    }

    public function storeRole(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $data = $this->validate($request, [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
        ]);

        $name = trim($data['name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name)));
        if (empty($slug)) $slug = 'custom_role_' . time();

        // Check if role slug already exists in this hospital
        $exists = DB::selectOne("SELECT id FROM hospital_roles WHERE tenant_id = ? AND slug = ?", [$tenantId, $slug]);
        if ($exists) {
            return back()->with('error', "A role with key '{$slug}' already exists.");
        }

        $permissionsJson = json_encode(array_values($data['permissions']));

        DB::statement(
            "INSERT INTO hospital_roles (tenant_id, name, slug, description, is_system, permissions, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())",
            [$tenantId, $name, $slug, trim($data['description'] ?? ''), $permissionsJson]
        );

        audit('role.created', 'HospitalRole', null, ['name' => $name, 'slug' => $slug]);

        return back()->with('success', "Role '{$name}' created with custom granular permissions.");
    }

    public function updateRole(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $role = DB::selectOne("SELECT * FROM hospital_roles WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$role) abort(404);

        $data = $this->validate($request, [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
        ]);

        $permissionsJson = json_encode(array_values($data['permissions']));

        DB::statement(
            "UPDATE hospital_roles 
             SET name = ?, description = ?, permissions = ?, updated_at = NOW() 
             WHERE id = ? AND tenant_id = ?",
            [trim($data['name']), trim($data['description'] ?? ''), $permissionsJson, $id, $tenantId]
        );

        audit('role.updated', 'HospitalRole', $id, ['name' => $data['name']]);

        return back()->with('success', "Permissions for role '{$data['name']}' updated successfully.");
    }

    public function deleteRole(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $role = DB::selectOne("SELECT * FROM hospital_roles WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$role) abort(404);

        if ($role['is_system']) {
            return back()->with('error', 'Standard system roles cannot be deleted.');
        }

        $activeStaff = DB::selectOne("SELECT COUNT(*) as c FROM users WHERE tenant_id = ? AND role = ?", [$tenantId, $role['slug']]);
        if (!empty($activeStaff['c'])) {
            return back()->with('error', "Cannot delete role '{$role['name']}': {$activeStaff['c']} staff members currently have this role.");
        }

        DB::statement("DELETE FROM hospital_roles WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);

        audit('role.deleted', 'HospitalRole', $id, ['slug' => $role['slug']]);

        return back()->with('info', "Custom role '{$role['name']}' removed.");
    }
}