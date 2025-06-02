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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'aws' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'credentials' => [
            'key' => env('APP_ENV') === 'local'
                ? env('AWS_ACCESS_KEY_ID_LOCAL', 'local')
                : env('AWS_ACCESS_KEY_ID'),
            'secret' => env('APP_ENV') === 'local'
                ? env('AWS_SECRET_ACCESS_KEY_LOCAL', 'local')
                : env('AWS_SECRET_ACCESS_KEY'),
        ],
    ],

    'dynamodb' => [
        'endpoint' => env('APP_ENV') === 'local'
            ? env('DYNAMODB_ENDPOINT', 'http://dynamodb:8000')
            : null,
        'tables' => [
            'metadata' => env('APP_ENV') === 'local'
                ? 'local-metadata'
                : env('DYNAMODB_METADATA_TABLE', 'img-stack-dev-metadata'),
            'jobs' => env('APP_ENV') === 'local'
                ? 'local-jobs'
                : env('DYNAMODB_JOBS_TABLE', 'img-stack-dev-jobs'),
        ],
    ],

];
