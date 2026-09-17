<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    |
    | By default, Passport's models will utilize your application's default
    | database connection. If you wish to use a different connection you
    | may specify the configured name of the database connection here.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Short-lived access tokens are what makes a revocation effective within the
    | minute the product promises. The refresh token rotates on every use, so a
    | stolen one is usable once at most before its family is revoked.
    |
    */

    'access_token_minutes' => (int) env('PASSPORT_ACCESS_TOKEN_MINUTES', 15),

    'refresh_token_hours' => (int) env('PASSPORT_REFRESH_TOKEN_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Proof Key for Code Exchange
    |--------------------------------------------------------------------------
    |
    | The OAuth2 server only requires PKCE of public clients. Here it is required
    | of every client, confidential ones included, and only the S256 challenge
    | method is accepted — "plain" proves nothing.
    |
    */

    'code_challenge_method' => 'S256',

];
