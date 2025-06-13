<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pusher Channels
    |--------------------------------------------------------------------------
    |
    | Configuration for Pusher channels and events.
    |
    */
    'channels' => [
        'private' => [
            'messages' => 'private-messages',
            'stories' => 'private-stories',
            'companies' => 'private-companies',
        ],
        'presence' => [
            'users' => 'presence-users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pusher Events
    |--------------------------------------------------------------------------
    |
    | Configuration for Pusher events.
    |
    */
    'events' => [
        'messages' => [
            'new' => 'message.new',
            'read' => 'message.read',
            'typing' => 'message.typing',
        ],
        'stories' => [
            'new' => 'story.new',
            'view' => 'story.view',
        ],
        'companies' => [
            'employee_added' => 'company.employee.added',
            'employee_removed' => 'company.employee.removed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pusher Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Pusher client.
    |
    */
    'client' => [
        'app_id' => env('PUSHER_APP_ID'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'options' => [
            'cluster' => 'us3',
            'useTLS' => true
        ],
    ],
];
