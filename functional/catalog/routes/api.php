<?php

use Functional\Catalog\Http\Controllers\ApplicationLifecycleController;
use Functional\Catalog\Http\Controllers\ClientSecretController;
use Functional\Catalog\Http\Controllers\RedirectUrisController;
use Functional\Catalog\Rest\Controllers\ApplicationRolesController;
use Functional\Catalog\Rest\Controllers\ApplicationsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware(['auth:api', 'throttle:api'])->group(function (): void {
    Rest::resource('applications', ApplicationsController::class);
    Rest::resource('application-roles', ApplicationRolesController::class);

    Route::post('/applications/{application}/publish', [ApplicationLifecycleController::class, 'publish'])
        ->name('applications.publish');
    Route::post('/applications/{application}/retire', [ApplicationLifecycleController::class, 'retire'])
        ->name('applications.retire');

    Route::post('/applications/{application}/client-secret', [ClientSecretController::class, 'rotate'])
        ->name('applications.client-secret.rotate');
    Route::delete('/applications/{application}/client-secret', [ClientSecretController::class, 'revoke'])
        ->name('applications.client-secret.revoke');

    Route::get('/applications/{application}/redirect-uris', [RedirectUrisController::class, 'show'])
        ->name('applications.redirect-uris.show');
    Route::put('/applications/{application}/redirect-uris', [RedirectUrisController::class, 'update'])
        ->name('applications.redirect-uris.update');
});
