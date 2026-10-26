<?php
namespace App\Controllers;

use App\Models\Branch;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Session;
use Veen\Core\Tenancy\TenantManager;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $user = Auth::user();

        // Enforce privilege: only hospital_admin, super_admin, or can_access_all_branches
        if (!user_can_all_branches() && $user['role'] !== 'hospital_admin' && $user['role'] !== 'super_admin') {
            abort(403, 'You do not have administrative privileges to manage branches.');
        }

        $branches = DB::select(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id AND u.is_active = 1) AS staff_count,
                    (SELECT COUNT(*) FROM patients p WHERE p.branch_id = b.id AND p.deleted_at IS NULL) AS patient_count
             FROM branches b
             WHERE b.tenant_id = ?
             ORDER BY b.is_main DESC, b.name ASC",
            [$tenantId]
        );

        $activeBranchId = Session::get('active_branch_id');

        return view('branches.index', [
            'title'          => 'Hospital Branches & Multi-Branch Facilities',
            'branches'       => $branches,
            'activeBranchId' => $activeBranchId,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can_all_branches()) abort(403);

        $data = $this->validate($request, [
            'name'    => 'required|string|max:100',
            'code'    => 'required|string|max:30',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'is_main' => 'nullable',
        ]);

        $code = strtoupper(trim($data['code']));
        // Check uniqueness of code within this tenant
        $exists = DB::selectOne("SELECT id FROM branches WHERE tenant_id = ? AND code = ?", [$tenantId, $code]);
        if ($exists) {
            return back()->withInput()->withErrors(['code' => "Branch code '{$code}' already exists for your hospital."]);
        }

        $isMain = !empty($data['is_main']) ? 1 : 0;
        if ($isMain) {
            DB::statement("UPDATE branches SET is_main = 0 WHERE tenant_id = ?", [$tenantId]);
        }

        $branch = Branch::create([
            'name'      => trim($data['name']),
            'code'      => $code,
            'phone'     => trim((string) ($data['phone'] ?? '')) ?: null,
            'address'   => trim((string) ($data['address'] ?? '')) ?: null,
            'is_main'   => $isMain,
            'is_active' => 1,
        ]);

        audit('branch.created', 'Branch', $branch->id, ['name' => $branch->name, 'code' => $branch->code]);

        return redirect(route('branches.index'))->with('success', "Branch '{$branch->name}' registered successfully.");
    }

    public function update(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can_all_branches()) abort(403);

        $branch = DB::selectOne("SELECT * FROM branches WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$branch) abort(404, 'Branch not found');

        $data = $this->validate($request, [
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:30',
            'phone'     => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:255',
            'is_main'   => 'nullable',
            'is_active' => 'nullable',
        ]);

        $code = strtoupper(trim($data['code']));
        $dup = DB::selectOne("SELECT id FROM branches WHERE tenant_id = ? AND code = ? AND id != ?", [$tenantId, $code, $branch['id']]);
        if ($dup) {
            return back()->withInput()->withErrors(['code' => "Branch code '{$code}' is already assigned to another branch."]);
        }

        $isMain = !empty($data['is_main']) ? 1 : 0;
        if ($isMain && !$branch['is_main']) {
            DB::statement("UPDATE branches SET is_main = 0 WHERE tenant_id = ?", [$tenantId]);
        }

        $isActive = isset($data['is_active']) ? (int) !empty($data['is_active']) : (int) $branch['is_active'];

        DB::statement(
            "UPDATE branches SET name = ?, code = ?, phone = ?, address = ?, is_main = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?",
            [
                trim($data['name']),
                $code,
                trim((string) ($data['phone'] ?? '')) ?: null,
                trim((string) ($data['address'] ?? '')) ?: null,
                $isMain,
                $isActive,
                $branch['id'],
                $tenantId
            ]
        );

        audit('branch.updated', 'Branch', $branch['id'], ['name' => $data['name']]);

        return redirect(route('branches.index'))->with('success', "Branch '{$data['name']}' details updated.");
    }

    public function toggle(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can_all_branches()) abort(403);

        $branch = DB::selectOne("SELECT * FROM branches WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$branch) abort(404);

        if ($branch['is_main'] && $branch['is_active']) {
            return back()->with('error', 'Cannot deactivate the primary Main Hospital branch.');
        }

        $newActive = $branch['is_active'] ? 0 : 1;
        DB::statement("UPDATE branches SET is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?", [$newActive, $branch['id'], $tenantId]);

        audit('branch.toggled', 'Branch', $branch['id'], ['active' => $newActive]);

        $stateText = $newActive ? 'activated' : 'deactivated';
        return redirect(route('branches.index'))->with('info', "Branch '{$branch['name']}' {$stateText}.");
    }

    public function switchBranch(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can_all_branches()) {
            return back()->with('error', 'You are scoped to your assigned branch and cannot switch.');
        }

        if ($id === 'all' || (string)$id === '0') {
            Session::put('active_branch_id', 0);
            return back()->with('info', 'Consolidated view: Viewing all hospital branches combined.');
        }

        $branch = DB::selectOne("SELECT * FROM branches WHERE (uuid = ? OR id = ?) AND tenant_id = ?", [$id, $id, $tenantId]);
        if (!$branch) {
            return back()->with('error', 'Selected branch was not found.');
        }

        Session::put('active_branch_id', (int) $branch['id']);

        return back()->with('success', "Switched active workspace to branch: {$branch['name']} ({$branch['code']}).");
    }
}
