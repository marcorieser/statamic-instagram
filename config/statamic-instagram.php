<?php

return [
    'limit' => 12,

    'include_child_posts' => false,

    'cache' => [
        'key_prefix' => 'instagram',
        'duration' => 60 * 60 * 24, // 1 day
    ],

    'accounts' => [
        [
            'handle' => 'default',
            'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
            // 'instagram' (Instagram login) or 'unified' (Unified login / system user token)
            'login' => env('INSTAGRAM_LOGIN', 'instagram'),
            // Optional. Skips the user id lookup. Needed if a unified token has several Instagram accounts assigned.
            'user_id' => env('INSTAGRAM_USER_ID'),
        ],
    ],
];
