<?php

use Illuminate\Support\Facades\Schedule;

/**
 * Expired and revoked tokens pile up fast with fifteen applications refreshing
 * every fifteen minutes. Purged nightly, well away from working hours.
 */
Schedule::command('passport:purge --hours=24')
    ->dailyAt('03:10')
    ->onOneServer()
    ->withoutOverlapping();
