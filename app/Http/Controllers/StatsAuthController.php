<?php

namespace App\Http\Controllers;

use Application\Auth\Command\Login\LoginCommand;
use Application\Auth\Command\Login\LoginHandler;
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

    public function store(Request $request, LoginHandler $handler, CookieJar $cookies): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $login = (string) $credentials['login'];
        $password = (string) $credentials['password'];

        try {
            $token = $handler->handle(new LoginCommand(
                login: $login,
                password: $password,
            ));
        } catch (RuntimeException) {
            return back()
                ->withErrors(['login' => 'Invalid credentials.'])
                ->withInput(['login' => $login]);
        }

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
}
