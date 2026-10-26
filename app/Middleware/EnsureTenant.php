<?php
namespace App\Middleware;

use Closure;
use Veen\Core\Auth;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

/** Ensures a hospital (tenant) context exists; super admins go to the platform console. */
class EnsureTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!TenantManager::id()) {
            if (Auth::hasRole('super_admin')) {
                // If a hospital exists, auto-select the first active one for super admin convenience,
                // or redirect to platform fleet dashboard.
                $first = \Veen\Core\Database\DB::selectOne("SELECT id FROM tenants WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                if ($first) {
                    \Veen\Core\Session::put('super_tenant_id', (int) $first['id']);
                    $tenant = TenantManager::find((int) $first['id']);
                    TenantManager::set($tenant);
                    return $next($request);
                }
                return Response::redirect(route('platform.dashboard'))->with('info', 'Please create a hospital first.');
            }
            return Response::redirect(route('login'));
        }
        return $next($request);
    }
}
