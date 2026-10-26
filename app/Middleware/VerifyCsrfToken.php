<?php
namespace App\Middleware;

use Closure;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Session;

class VerifyCsrfToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN') ?? '');
        if ($token === '' || !hash_equals(Session::token(), $token)) {
            throw new HttpException(419);
        }
        return $next($request);
    }
}
