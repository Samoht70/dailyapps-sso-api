<?php

use Functional\Licensing\Http\Controllers\MyApplicationsController;
use Functional\Licensing\Rest\Controllers\ApplicationAccessesController;
use Functional\Licensing\Rest\Controllers\LicensesController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:api')->group(function (): void {
    Route::get('/me/applications', MyApplicationsController::class)->name('me.applications');

    Rest::resource('licenses', LicensesController::class);
    Rest::resource('application-accesses', ApplicationAccessesController::class);
});
