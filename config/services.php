<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot' => env('TELEGRAM_BOT_USERNAME'),
        'client_id' => null,
        'client_secret' => env('TELEGRAM_BOT_TOKEN'),
        'redirect' => env('TELEGRAM_REDIRECT_URI'),
    ],

    'subscriptions' => [
        'grace_days' => env('SUBSCRIPTION_GRACE_DAYS', 7),
    ],

    'sentry' => [
        'dsn' => env('SENTRY_DSN'),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.0),
        'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),
    ],

    'bugsnag' => [
        'api_key' => env('BUGSNAG_API_KEY'),
    ],

    'webhooks' => [
        'outbound_messages_secret' => env('OUTBOUND_WEBHOOK_SECRET'),
    ],

];
