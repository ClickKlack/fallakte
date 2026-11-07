<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'wikipedia' => [
        'endpoint' => env('WIKIPEDIA_API_ENDPOINT', 'https://de.wikipedia.org/w/api.php'),
        // Wikipedia verlangt einen aussagekräftigen User-Agent mit Kontaktmöglichkeit.
        // Ohne WIKIPEDIA_USER_AGENT wird die E-Mail-Adresse des (einzigen) Benutzers als Kontakt angehängt.
        'user_agent' => env('WIKIPEDIA_USER_AGENT'),
        'user_agent_base' => 'fallakte/1.0 (+https://github.com/ClickKlack/fallakte)',
        // Automatischer Import als Cron-Ausdruck (leer = abgeschaltet). Greift nur, wenn ein Scheduler läuft:
        // Docker-Service „scheduler“ oder ein Cronjob mit `php artisan schedule:run`.
        'import_schedule' => env('IMPORT_SCHEDULE', '0 4 * * 1'),
        'import_timezone' => env('IMPORT_TIMEZONE', 'Europe/Berlin'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
