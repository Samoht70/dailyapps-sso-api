<?php

use Illuminate\Support\Facades\Route;

Route::view('/oidc/ping', 'oidc::ping')->name('oidc.ping');
