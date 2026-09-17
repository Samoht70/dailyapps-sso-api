<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | The api limit answers `429` past this many calls a minute from one caller.
    | It guards the surface as a whole; guessing a password is held back by the
    | tighter, per-account lock of the sign-in screen.
    |
    */

    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),
    ],

];
