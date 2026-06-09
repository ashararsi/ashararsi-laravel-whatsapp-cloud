<?php

return [

    'default_account' => env('WHATSAPP_DEFAULT_ACCOUNT', null),

    'default_provider' => env('WHATSAPP_DEFAULT_PROVIDER', 'meta'),

    'providers' => [
        'meta' => \Vendor\LaravelWhatsAppCloud\Providers\MetaProvider::class,
        'twilio' => \Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider::class,
    ],

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com'),

    'queue_enabled' => env('WHATSAPP_QUEUE_ENABLED', true),

    'queue_connection' => env('WHATSAPP_QUEUE_CONNECTION', null),

    'queue_name' => env('WHATSAPP_QUEUE_NAME', 'default'),

    'log_messages' => env('WHATSAPP_LOG_MESSAGES', true),

    'conversations' => [
        'enabled' => env('WHATSAPP_CONVERSATIONS_ENABLED', true),
    ],

    'cache' => [
        'enabled' => env('WHATSAPP_CACHE_ENABLED', true),
        'ttl' => env('WHATSAPP_CACHE_TTL', 300),
    ],

    'admin' => [
        'enabled' => env('WHATSAPP_ADMIN_ENABLED', true),
        'prefix' => env('WHATSAPP_ADMIN_PREFIX', 'admin/whatsapp'),
        'middleware' => ['web', \Vendor\LaravelWhatsAppCloud\Http\Middleware\AuthorizeWhatsAppAdmin::class],
        'authorization_enabled' => env('WHATSAPP_ADMIN_AUTHORIZATION_ENABLED', true),
        'gate' => env('WHATSAPP_ADMIN_GATE', 'manage-whatsapp'),
    ],

    'webhook' => [
        'prefix' => env('WHATSAPP_WEBHOOK_PREFIX', 'whatsapp'),
        'middleware' => [],
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'require_signature' => env('WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE', false),
    ],

];
