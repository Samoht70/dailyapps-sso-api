<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Session Lifetimes
    |--------------------------------------------------------------------------
    |
    | Two independent limits. The inactivity one closes a session left open on a
    | machine that walked away; the absolute one closes it whatever happens, so
    | a session cannot be kept alive for ever by a background poll.
    |
    */

    'session' => [
        'absolute_lifetime_minutes' => (int) env('SSO_SESSION_ABSOLUTE_MINUTES', 480),
        'inactivity_minutes' => (int) env('SSO_SESSION_INACTIVITY_MINUTES', 60),
    ],

];
