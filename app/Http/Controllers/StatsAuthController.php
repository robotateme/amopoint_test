<?php

namespace App\Http\Controllers;

use Application\Auth\Command\Login\LoginCommand;
use Application\Auth\Command\Login\LoginHandler;
use Application\Auth\LoginRateLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class StatsAuthController extends Controller
{
    public function create(): View
    {
        return view('stats.login');
    }

    public function store(
        Request $request,
        LoginHandler $handler,
        LoginRateLimiter $rateLimiter,
        CookieJar $cookies,
    ): RedirectResponse {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $login = (string) $credentials['login'];
        $password = (string) $credentials['password'];
        $rateLimitKey = $this->rateLimitKey($request, $login);
        $rateLimit = $rateLimiter->attempt($rateLimitKey);

        if (! $rateLimit->allowed) {
            return back()
                ->with('login_error', sprintf(
                    'Too many login attempts. Try again in %d seconds.',
                    max(1, $rateLimit->retryAfterSeconds),
                ))
                ->withInput(['login' => $login])
                ->setStatusCode(429);
        }

        try {
            $token = $handler->handle(new LoginCommand(
                login: $login,
                password: $password,
            ));
        } catch (RuntimeException) {
            return back()
                ->with('login_error', 'Invalid credentials.')
                ->withInput(['login' => $login]);
        }

        $rateLimiter->clear($rateLimitKey);

        return redirect()
            ->intended(route('stats.index'))
            ->withCookie($cookies->make(
                'stats_token',
                $token,
                (int) ceil(((int) config('services.stats.jwt_ttl', 3600)) / 60),
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'Lax',
            ));
    }

    public function destroy(): RedirectResponse
    {
        return redirect()
            ->route('stats.login')
            ->withoutCookie('stats_token');
    }

    private function rateLimitKey(Request $request, string $login): string
    {
        return 'stats-login:'.hash('sha256', implode('|', [
            $request->ip() ?? '0.0.0.0',
            mb_strtolower(trim($login)),
        ]));
    }
}
