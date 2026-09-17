<?php

use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;
use Technical\Audit\Rest\Controllers\SecurityEventsController;

Route::middleware('auth:api')->group(function (): void {
    Rest::resource('security-events', SecurityEventsController::class, [
        'only' => ['details', 'search'],
    ]);
});
