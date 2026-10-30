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

    // Ollama (OpenAI-compatible) — used by the AI product-suggestion helper on the
    // Add/Edit product form. The key lives ONLY on the server (never in the
    // React bundle) so it can't be scraped from the browser.
    //
    // `models` is an ordered fallback chain: the first entry is the primary
    // model, and if it errors the next one is tried in turn.
    'ollama' => [
        'key' => env('OLLAMA_API_KEY'),
        'base_url' => env('OLLAMA_BASE_URL', 'https://ollama.com/v1'),
        'models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env(
                'OLLAMA_MODELS',
                'qwen2.5:7b-instruct,gemma2:9b-instruct,llama-3.1:8b-instruct'
            ))
        ))),
    ],

];
