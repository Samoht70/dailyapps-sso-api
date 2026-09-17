<?php

use Illuminate\Support\Facades\Route;
use Technical\Oidc\Http\Controllers\LogoutController;
use Technical\Oidc\Livewire\Account;
use Technical\Oidc\Livewire\ForgotPassword;
use Technical\Oidc\Livewire\Login;
use Technical\Oidc\Livewire\ResetPassword;

Route::view('/oidc/ping', 'oidc::ping')->name('oidc.ping');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/password/forgot', ForgotPassword::class)->name('password.forgot');
    Route::get('/password/reset/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/account', Account::class)->name('account');
    Route::post('/logout', LogoutController::class)->name('logout');
});
