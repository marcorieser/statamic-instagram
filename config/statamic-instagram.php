<?php

return [
    'cache' => [
        'key_prefix' => 'instagram_',
        'duration' => 60 * 60 * 24, // 1 day
    ],

    'access_token' => env('STATAMIC_INSTAGRAM_ACCESS_TOKEN'),
];
