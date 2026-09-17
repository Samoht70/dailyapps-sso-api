<?php

namespace Technical\Oidc\Listeners;

use Illuminate\Auth\Events\Login;
use Technical\Oidc\Models\SsoSession;

class OpenSsoSession
{
    /**
     * The session id becomes the `sid` claim of every id_token minted while it
     * lives, which is what lets a logout push name the session to close.
     */
    public function handle(Login $event): void
    {
        $session = SsoSession::query()->create([
            'user_id' => $event->user->getKey(),
            'laravel_session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'started_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(config('oidc.session.absolute_lifetime_minutes')),
        ]);

        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        $event->user->forceFill(['last_authenticated_at' => now()])->save();
    }
}
