<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StatsBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $login = (string) config('services.stats.login', 'admin');
        $password = (string) config('services.stats.password', 'secret');

        if ($request->getUser() === $login && hash_equals($password, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="Statistics"',
        ]);
    }
}
