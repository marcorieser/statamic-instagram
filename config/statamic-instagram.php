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
            'handle' => '',
            'access_token' => '',
        ],
    ]
];
