<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook/Instagram) API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Meta app credentials and API settings here.
    | App ID is public; App Secret must be kept private.
    |
    */

    'app_id' => env('META_APP_ID', '1127758050142770'),
    'app_secret' => env('META_APP_SECRET'),
    'graph_version' => env('META_GRAPH_VERSION', 'v22.0'),
    'redirect_uri' => env('META_REDIRECT_URI', config('app.url').'/auth/facebook/callback'),
    'data_deletion_url' => env('META_DATA_DELETION_URL', config('app.url').'/api/meta/data-deletion'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | Permissions requested during Facebook Login. These scopes are required
    | for the HyperBit Social MVP functionality.
    |
    */

    'scopes' => [
        'public_profile',
        'email',
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'pages_read_user_content',
        'instagram_basic',
        'instagram_content_publish',
        'business_management',
    ],

    /*
    |--------------------------------------------------------------------------
    | Graph API Endpoints
    |--------------------------------------------------------------------------
    */

    'graph_url' => 'https://graph.facebook.com',
    'oauth_url' => 'https://www.facebook.com',

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    */

    'token_refresh_days' => 50,
];
