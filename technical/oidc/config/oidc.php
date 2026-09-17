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

    /*
    |--------------------------------------------------------------------------
    | Latency Budgets
    |--------------------------------------------------------------------------
    |
    | The plan aims for /oauth/token under 300 ms at the p95 and a switch from
    | one application to another under 5 s. A p95 is only readable from a load
    | run, but a single request past its budget can be said so on the spot —
    | which is what turns the goal into something an operator sees.
    |
    */

    'performance' => [
        'budgets_ms' => [
            'oauth/token' => (int) env('SSO_BUDGET_TOKEN_MS', 300),
            'oauth/authorize' => (int) env('SSO_BUDGET_AUTHORIZE_MS', 1000),
        ],
    ],

    'session' => [
        'absolute_lifetime_minutes' => (int) env('SSO_SESSION_ABSOLUTE_MINUTES', 480),
        'inactivity_minutes' => (int) env('SSO_SESSION_INACTIVITY_MINUTES', 60),
    ],

];
