<?php

use Functional\Users\Http\Controllers\ProfileController;
use Functional\Users\Http\Controllers\UserTransitionsController;
use Functional\Users\Rest\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware(['auth:api', 'throttle:api'])->group(function (): void {
    Route::get('/me', [ProfileController::class, 'show'])->name('me.show');
    Route::patch('/me', [ProfileController::class, 'update'])->name('me.update');
    Route::put('/me/password', [ProfileController::class, 'updatePassword'])->name('me.password');

    Rest::resource('users', UsersController::class);

    Route::post('/users/{user}/disable', [UserTransitionsController::class, 'disable'])
        ->name('users.disable');
    Route::post('/users/{user}/enable', [UserTransitionsController::class, 'enable'])
        ->name('users.enable');
    Route::post('/users/{user}/resend-invitation', [UserTransitionsController::class, 'resendInvitation'])
        ->name('users.resend-invitation');
});
