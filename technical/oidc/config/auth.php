<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | The `web` guard serves the five screens; `api` serves the applications
    | calling with an access token. Merged into the application's auth config by
    | the layer provider, since OSDD leaves no config directory at the root.
    |
    */

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

];
