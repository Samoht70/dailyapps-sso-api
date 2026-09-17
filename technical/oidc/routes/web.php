<?php

use Illuminate\Support\Facades\Route;
use Technical\Oidc\Http\Controllers\DiscoveryController;
use Technical\Oidc\Http\Controllers\LogoutController;
use Technical\Oidc\Livewire\AcceptInvitation;
use Technical\Oidc\Livewire\Account;
use Technical\Oidc\Livewire\ForgotPassword;
use Technical\Oidc\Livewire\Login;
use Technical\Oidc\Livewire\ResetPassword;

Route::get('/.well-known/openid-configuration', DiscoveryController::class)->name('openid.discovery');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/password/forgot', ForgotPassword::class)->name('password.forgot');
    Route::get('/password/reset/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/invitations/{token}', AcceptInvitation::class)->name('invitations.accept');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/account', Account::class)->name('account');
    Route::post('/logout', LogoutController::class)->name('logout');
});
