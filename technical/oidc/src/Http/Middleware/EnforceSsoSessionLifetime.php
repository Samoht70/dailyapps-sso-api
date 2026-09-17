<?php

namespace Technical\Oidc\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Technical\Oidc\Models\SsoSession;

class EnforceSsoSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $this->currentSsoSession($request);

        if ($session === null) {
            return $next($request);
        }

        if (! $session->isAlive() || $this->hasGoneQuiet($session)) {
            $session->revoke();
            $this->close($request);

            return redirect()->guest(route('login'));
        }

        $session->touchLastSeen();

        return $next($request);
    }

    private function currentSsoSession(Request $request): ?SsoSession
    {
        if (! Auth::check()) {
            return null;
        }

        $id = $request->session()->get(SsoSession::SESSION_KEY);

        return $id === null ? null : SsoSession::query()->find($id);
    }

    private function hasGoneQuiet(SsoSession $session): bool
    {
        return $session->last_seen_at->addMinutes(config('oidc.session.inactivity_minutes'))->isPast();
    }

    private function close(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
