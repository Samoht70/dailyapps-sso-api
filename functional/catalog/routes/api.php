<?php

use Functional\Catalog\Rest\Controllers\ApplicationRolesController;
use Functional\Catalog\Rest\Controllers\ApplicationsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:api')->group(function (): void {
    Rest::resource('applications', ApplicationsController::class);
    Rest::resource('application-roles', ApplicationRolesController::class);
});
