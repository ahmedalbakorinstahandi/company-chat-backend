<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | The path to your Firebase service account credentials JSON file.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | Your Firebase Realtime Database URL.
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Your Firebase Storage bucket name.
    |
    */
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Server Key
    |--------------------------------------------------------------------------
    |
    | Your Firebase Server Key for sending push notifications.
    |
    */
    'server_key' => env('FIREBASE_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM).
    |
    */
    'fcm' => [
        'default_topic' => 'all_users',
        'notification' => [
            'sound' => 'default',
            'badge' => '1',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ],
    ],
]; 