<?php

use Functional\Organizations\Http\Controllers\OrganizationTransitionsController;
use Functional\Organizations\Rest\Controllers\OrganizationsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:api')->group(function (): void {
    Rest::resource('organizations', OrganizationsController::class);

    Route::post('/organizations/{organization}/suspend', [OrganizationTransitionsController::class, 'suspend'])
        ->name('organizations.suspend');
    Route::post('/organizations/{organization}/activate', [OrganizationTransitionsController::class, 'activate'])
        ->name('organizations.activate');
});
