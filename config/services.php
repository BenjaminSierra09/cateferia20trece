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

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'marketing_rate_per_minute' => env('WHATSAPP_MARKETING_RATE_PER_MINUTE', 60),
        'templates' => [
            'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_MX'),
            'customer_credential' => env('WHATSAPP_CUSTOMER_CREDENTIAL_TEMPLATE', 'customer_credential'),
            'invoice_request' => env('WHATSAPP_INVOICE_REQUEST_TEMPLATE', 'invoice_request'),
        ],
    ],

    'privacy' => [
        'email' => env('PRIVACY_CONTACT_EMAIL', 'privacidad@cafe20trece.com'),
    ],

    'invoicing' => [
        'email' => env('INVOICE_CONTACT_EMAIL', 'facturacion@cafe20trece.com'),
        'whatsapp' => env('WHATSAPP_CONTABILIDAD', '+524181878244'),
    ],

];
