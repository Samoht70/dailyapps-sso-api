<?php

use Functional\Users\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::get('/me', [ProfileController::class, 'show'])->name('me.show');
    Route::patch('/me', [ProfileController::class, 'update'])->name('me.update');
    Route::put('/me/password', [ProfileController::class, 'updatePassword'])->name('me.password');
});
