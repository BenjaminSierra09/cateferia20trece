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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

    'evolution' => [
        'api_url' => env('EVOLUTION_API_URL', 'https://evolution.benjaminsierra.com/message/sendText/San Miguel Live'),
        'api_key' => env('EVOLUTION_API_KEY'),
        'instance_id' => env('EVOLUTION_INSTANCE_ID', 'CAFETERIA20TRECE'),
        'webhook_token' => env('EVOLUTION_WEBHOOK_TOKEN'),
    ],

    'privacy' => [
        'email' => env('PRIVACY_CONTACT_EMAIL', 'privacidad@cafe20trece.com'),
    ],

    'invoicing' => [
        'email' => env('INVOICE_CONTACT_EMAIL', 'facturacion@cafe20trece.com'),
        'whatsapp' => env('WHATSAPP_CONTABILIDAD', '+524181878244'),
    ],

];
