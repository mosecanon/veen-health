<?php
namespace App\Middleware;

use Closure;
use Veen\Core\Auth;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

/**
 * Requires a logged-in user and boots the tenant context from the user.
 * A user can never act inside another hospital: tenant is derived from the
 * user record, never from request input.
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                throw new HttpException(401);
            }
            return Response::redirect(route('login'))->with('error', 'Please sign in to continue.');
        }

        if ($user->tenant_id) {
            $tenant = TenantManager::find((int) $user->tenant_id);
            if (!$tenant || $tenant['status'] === 'suspended') {
                Auth::logout();
                return Response::redirect(route('login'))->with('error', 'Your hospital account is suspended. Contact Veen Africa support.');
            }
            TenantManager::set($tenant);
        } elseif ($user->role === 'super_admin') {
            // Super admin can impersonate/manage any tenant via session
            $superTenantId = \Veen\Core\Session::get('super_tenant_id');
            if ($superTenantId) {
                $tenant = TenantManager::find((int) $superTenantId);
                if ($tenant) {
                    TenantManager::set($tenant);
                }
            }
        }

        return $next($request);
    }
}
