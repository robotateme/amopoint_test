<?php

namespace App\Http\Middleware;

use Application\Auth\Query\AuthenticateToken\AuthenticateTokenHandler;
use Application\Auth\Query\AuthenticateToken\AuthenticateTokenQuery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class StatsJwtAuth
{
    public function __construct(
        private AuthenticateTokenHandler $handler,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        $cookieToken = $request->cookie('stats_token', '');
        $token = $bearerToken !== null && $bearerToken !== ''
            ? $bearerToken
            : (is_string($cookieToken) ? $cookieToken : '');

        if ($token !== '' && $this->handler->handle(new AuthenticateTokenQuery($token))) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('stats.login');
    }
}
