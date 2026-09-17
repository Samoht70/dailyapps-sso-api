<?php

namespace Technical\Oidc\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Technical\Oidc\Actions\EndSsoSession;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Models\SsoSession;

class LogoutController
{
    public function __construct(private readonly EndSsoSession $endSession) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->endSsoSession($request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function endSsoSession(Request $request): void
    {
        $id = $request->session()->get(SsoSession::SESSION_KEY);
        $session = $id === null ? null : SsoSession::query()->find($id);

        if ($session !== null) {
            ($this->endSession)($session, LogoutReason::Logout);
        }
    }
}
