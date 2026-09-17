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

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    |
    | How long an invitation stands before it has to be sent again.
    |
    */

    'invitation' => [
        'lifetime_days' => (int) env('SSO_INVITATION_LIFETIME_DAYS', 7),
    ],

    'session' => [
        'absolute_lifetime_minutes' => (int) env('SSO_SESSION_ABSOLUTE_MINUTES', 480),
        'inactivity_minutes' => (int) env('SSO_SESSION_INACTIVITY_MINUTES', 60),
    ],

];
