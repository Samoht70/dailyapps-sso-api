<?php

use Functional\Licensing\Http\Controllers\MyApplicationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/me/applications', MyApplicationsController::class)
    ->name('me.applications');
