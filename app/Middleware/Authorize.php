<?php
namespace App\Middleware;

use Closure;
use Veen\Core\Auth;
use Veen\Core\HttpException;
use Veen\Core\Request;

/** Usage: 'can:pharmacy.sell' */
class Authorize
{
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        foreach ($permissions as $p) {
            if (Auth::can($p)) {
                return $next($request);
            }
        }
        throw new HttpException(403);
    }
}
