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
    | Facebook Login for Business Configuration
    |--------------------------------------------------------------------------
    |
    | Business-type Meta Apps use "Login Configurations" instead of scope lists.
    | When login_config_id is set, OAuth uses config_id parameter instead of scopes.
    | Create a Login Configuration in Meta Developer Console:
    | Facebook Login for Business > Configurations > Create
    |
    */

    'login_config_id' => env('META_LOGIN_CONFIG_ID'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes (fallback when login_config_id is not set)
    |--------------------------------------------------------------------------
    |
    | Permissions requested during Facebook Login when not using a Login Configuration.
    | Note: 'email' scope is not used - we identify users by Facebook ID.
    |
    */

    'scopes' => array_filter(array_map('trim', explode(',', env(
        'META_SCOPES',
        'pages_show_list,pages_manage_posts,pages_read_engagement,read_insights,instagram_basic,instagram_content_publish,instagram_manage_insights,business_management'
    )))),

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
