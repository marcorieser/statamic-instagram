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
            'login_method' => env('INSTAGRAM_LOGIN_METHOD', 'instagram'), // 'instagram' (Instagram login) or 'unified' (Unified login / system user token)
            'access_token' => env('INSTAGRAM_ACCESS_TOKEN'), // Instagram login token, or a system user token when login_method is "unified"
            'user_id' => env('INSTAGRAM_USER_ID'), // Optional. Skips the user id lookup. Needed if a unified token has several Instagram accounts assigned.
        ],
    ],
];
