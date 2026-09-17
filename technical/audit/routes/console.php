<?php

use Illuminate\Support\Facades\Schedule;
use Technical\Audit\Models\SecurityEvent;

/**
 * `model:prune` walks the journal in chunks and deletes row by row, so the
 * retention of twelve months is applied without a mass delete that would skip
 * the model's own lifecycle.
 */
Schedule::command('model:prune', ['--model' => [SecurityEvent::class]])
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping();
