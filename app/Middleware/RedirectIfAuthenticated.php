<?php
namespace App\Middleware;

use Closure;
use Veen\Core\Auth;
use Veen\Core\Request;
use Veen\Core\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check()) {
            return Response::redirect(Auth::user()->role === 'super_admin' ? route('platform.dashboard') : route('dashboard'));
        }
        return $next($request);
    }
}
