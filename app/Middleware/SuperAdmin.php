<?php
namespace App\Middleware;

use Closure;
use Veen\Core\Auth;
use Veen\Core\HttpException;
use Veen\Core\Request;

class SuperAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::hasRole('super_admin')) {
            throw new HttpException(403);
        }
        return $next($request);
    }
}
