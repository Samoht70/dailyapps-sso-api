<?php

namespace Technical\Oidc\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Technical\Oidc\Models\SsoSession;

class LogoutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $this->revokeSsoSession($request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function revokeSsoSession(Request $request): void
    {
        $id = $request->session()->get(SsoSession::SESSION_KEY);

        SsoSession::query()->find($id)?->revoke();
    }
}
