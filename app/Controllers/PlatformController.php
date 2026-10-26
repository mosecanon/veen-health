<?php
namespace App\Controllers;

use App\Models\Tenant;
use App\Services\TenantProvisioner;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Session;
use Veen\Core\Tenancy\TenantManager;

class PlatformController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = DB::select(
            "SELECT t.*, p.name AS plan_name, c.name AS country_name,
                    (SELECT COUNT(*) FROM branches b WHERE b.tenant_id = t.id) AS branch_count,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count,
                    (SELECT COUNT(*) FROM patients pt WHERE pt.tenant_id = t.id) AS patient_count
             FROM tenants t
             LEFT JOIN plans p ON p.id = t.plan_id
             LEFT JOIN countries c ON c.code = t.country_code
             ORDER BY t.id DESC"
        );

        $countries = DB::select('SELECT * FROM countries ORDER BY name ASC');
        $plans = DB::select('SELECT * FROM plans ORDER BY id ASC');

        $metrics = [
            'total_tenants' => count($tenants),
            'active_tenants' => count(array_filter($tenants, fn($t) => $t['status'] === 'active')),
            'trial_tenants' => count(array_filter($tenants, fn($t) => $t['status'] === 'trial')),
            'total_patients' => array_sum(array_column($tenants, 'patient_count')),
        ];

        return view('platform.index', [
            'title' => 'Veen Africa Platform Administration',
            'tenants' => $tenants,
            'countries' => $countries,
            'plans' => $plans,
            'metrics' => $metrics,
            'activeTenantId' => Session::get('super_tenant_id'),
        ]);
    }

    public function createTenant(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'           => 'required|string|max:120',
            'slug'           => 'nullable|string|max:60',
            'country_code'   => 'required|string|max:2',
            'plan_id'        => 'required|integer',
            'billing_cycle'  => 'nullable|string|in:free_trial,monthly,yearly,one_time',
            'trial_days'     => 'nullable|integer|min:1|max:365',
            'email'          => 'nullable|email|max:120',
            'phone'          => 'nullable|string|max:30',
            'admin_name'     => 'required|string|max:100',
            'admin_email'    => 'required|email|max:120',
            'admin_password' => 'required|string|min:6',
        ]);

        $adminEmail = strtolower(trim($data['admin_email']));
        $existingUser = DB::selectOne('SELECT id FROM users WHERE email = ?', [$adminEmail]);
        if ($existingUser) {
            return back()->with('error', "An administrator account with email '{$data['admin_email']}' is already in use. Please enter a different email.")
                         ->withErrors([
                             'admin_email' => ["An administrator account with email '{$data['admin_email']}' is already in use. Please enter a different email."]
                         ])->withInput($request->all());
        }

        // Auto-generate or sanitize slug
        $rawSlug = !empty($data['slug']) ? $data['slug'] : $data['name'];
        $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $rawSlug), '-'));
        if (empty($baseSlug)) {
            $baseSlug = 'clinic-' . bin2hex(random_bytes(3));
        }

        // Auto-increment slug if exists to prevent collision
        $slug = $baseSlug;
        $counter = 1;
        while (DB::selectOne('SELECT id FROM tenants WHERE slug = ?', [$slug])) {
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        try {
            $tenant = TenantProvisioner::provision([
                'name'           => trim($data['name']),
                'slug'           => $slug,
                'country_code'   => $data['country_code'],
                'plan_id'        => (int) $data['plan_id'],
                'billing_cycle'  => $data['billing_cycle'] ?? 'free_trial',
                'trial_days'     => (int) ($data['trial_days'] ?? 14),
                'email'          => $data['email'] ?? null,
                'phone'          => $data['phone'] ?? null,
                'admin_name'     => trim($data['admin_name']),
                'admin_email'    => $adminEmail,
                'admin_password' => $data['admin_password'],
            ]);

            return redirect(route('platform.dashboard'))->with('success', "Hospital '{$data['name']}' (#{$tenant['id']}) provisioned successfully with slug '{$slug}'!");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to onboard hospital: " . $e->getMessage())
                         ->withErrors(['general' => [$e->getMessage()]])
                         ->withInput($request->all());
        }
    }

    public function toggleStatus(Request $request, int|string $id): Response
    {
        $tenant = DB::selectOne('SELECT * FROM tenants WHERE uuid = ? OR id = ?', [$id, $id]);
        if (!$tenant) abort(404);

        $newStatus = $tenant['status'] === 'active' ? 'suspended' : 'active';
        DB::statement('UPDATE tenants SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $tenant['id']]);
        TenantManager::clearCache((int) $tenant['id']);

        return back()->with('info', "Tenant {$tenant['name']} status changed to {$newStatus}.");
    }

    public function updateModules(Request $request, int|string $id): Response
    {
        $tenant = DB::selectOne('SELECT * FROM tenants WHERE uuid = ? OR id = ?', [$id, $id]);
        if (!$tenant) abort(404);

        $settings = json_decode($tenant['settings'] ?? '{}', true) ?: [];

        $modules = [
            'consultations' => (bool) $request->input('module_consultations', 0),
            'pharmacy'      => (bool) $request->input('module_pharmacy', 0),
            'lab'           => (bool) $request->input('module_lab', 0),
            'inpatient'     => (bool) $request->input('module_inpatient', 0),
            'billing'       => (bool) $request->input('module_billing', 0),
            'queue'         => (bool) $request->input('module_queue', 0),
            'appointments'  => (bool) $request->input('module_appointments', 0),
            'reports'       => (bool) $request->input('module_reports', 0),
        ];

        $settings['modules'] = $modules;

        DB::statement('UPDATE tenants SET settings = ?, updated_at = NOW() WHERE id = ?', [
            json_encode($settings),
            $tenant['id']
        ]);

        TenantManager::clearCache((int) $tenant['id']);

        return back()->with('success', "Feature modules updated for {$tenant['name']}.");
    }

    public function switchTenant(Request $request, int|string $id): Response
    {
        $tenant = TenantManager::find($id);
        if (!$tenant) {
            return back()->with('error', 'Hospital not found.');
        }

        Session::put('super_tenant_id', (int) $tenant['id']);
        TenantManager::set($tenant);

        return redirect(route('dashboard'))->with('success', "Switched into '{$tenant['name']}' workspace.");
    }

    public function exitTenant(Request $request): Response
    {
        Session::forget('super_tenant_id');
        TenantManager::set(null);

        return redirect(route('platform.dashboard'))->with('info', 'Returned to Veen Africa Platform Admin.');
    }

    public function plans(Request $request): Response
    {
        $plans = DB::select(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM tenants t WHERE t.plan_id = p.id) AS subscribers_count
             FROM plans p
             ORDER BY p.id ASC"
        );

        return view('platform.plans', [
            'title' => 'Subscription Plans — Veen Health SaaS',
            'plans' => $plans,
        ]);
    }

    public function storePlan(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'            => 'required|string|max:80',
            'code'            => 'required|string|max:40',
            'billing_cycle'   => 'required|string|in:free_trial,monthly,yearly,one_time',
            'price_usd'       => 'required|numeric|min:0',
            'trial_days'      => 'nullable|integer|min:0|max:365',
            'max_users'       => 'nullable|integer|min:1',
            'max_branches'    => 'nullable|integer|min:1',
            'features'        => 'nullable|array',
        ]);

        $code = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $data['code'])));
        $features = $data['features'] ?? ['patients', 'queue', 'pharmacy', 'lab', 'ipd', 'billing'];

        DB::statement(
            'INSERT INTO plans (code, name, price_usd_cents, billing_cycle, trial_days, max_users, max_branches, features, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $code,
                $data['name'],
                (int) round($data['price_usd'] * 100),
                $data['billing_cycle'],
                (int) ($data['trial_days'] ?? 14),
                !empty($data['max_users']) ? (int) $data['max_users'] : null,
                !empty($data['max_branches']) ? (int) $data['max_branches'] : null,
                json_encode($features),
            ]
        );

        return redirect(route('platform.plans'))->with('success', "Plan '{$data['name']}' created successfully.");
    }

    public function flushGlobalCache(Request $request): Response
    {
        \Veen\Core\Cache::flush();
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        if ($request->wantsJson()) {
            return Response::json([
                'success' => true,
                'message' => 'Global SaaS fleet cache successfully flushed.',
                'driver' => \Veen\Core\Cache::driver(),
            ]);
        }

        return back()->with('success', 'Global SaaS fleet cache successfully flushed and reset (' . strtoupper(\Veen\Core\Cache::driver()) . ').');
    }
}
