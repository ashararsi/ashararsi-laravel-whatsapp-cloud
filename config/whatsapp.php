<?php

use Vendor\LaravelWhatsAppCloud\Http\Middleware\AuthorizeWhatsAppAdmin;
use Vendor\LaravelWhatsAppCloud\Providers\MetaProvider;
use Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Runtime settings (database)
    |--------------------------------------------------------------------------
    |
    | Operational settings are stored in whatsapp_settings and applied at boot
    | via WhatsAppSettingsService. Manage them at /admin/whatsapp/settings.
    |
    */

    'default_account' => null,

    'default_provider' => 'meta',

    'providers' => [
        'meta' => MetaProvider::class,
        'twilio' => TwilioProvider::class,
    ],

    'api_version' => 'v21.0',

    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com'),

    'graph_api' => [
        'timeout' => 30,
        'max_retries' => 3,
        'retry_base_delay_ms' => 1000,
        'retry_max_delay_ms' => 60000,
    ],

    'cost' => [
        'utility' => 0.005,
        'marketing' => 0.015,
        'authentication' => 0.004,
        'service' => 0.0,
    ],

    'queue_enabled' => true,

    'queue' => [
        'tries' => 3,
        'backoff' => [10, 30, 60],
    ],

    'queue_connection' => env('WHATSAPP_QUEUE_CONNECTION', null),

    'queue_name' => env('WHATSAPP_QUEUE_NAME', 'default'),

    'log_messages' => true,

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
        'middleware' => ['web', AuthorizeWhatsAppAdmin::class],
        'authorization_enabled' => true,
        'gate' => env('WHATSAPP_ADMIN_GATE', 'manage-whatsapp'),
    ],

    'webhook' => [
        'prefix' => env('WHATSAPP_WEBHOOK_PREFIX', 'whatsapp'),
        'middleware' => [],
        'app_secret' => null,
        'require_signature' => false,
    ],

    'twilio' => [
        'require_signature' => true,
    ],

    'media' => [
        'enabled' => true,
        'disk' => env('WHATSAPP_MEDIA_DISK', 'local'),
    ],

    'openai' => [
        'api_key' => env('WHATSAPP_OPENAI_API_KEY'),
        'base_url' => env('WHATSAPP_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'chat_model' => env('WHATSAPP_OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'whisper_model' => env('WHATSAPP_OPENAI_WHISPER_MODEL', 'whisper-1'),
        'temperature' => env('WHATSAPP_OPENAI_TEMPERATURE', 0.7),
        'timeout' => env('WHATSAPP_OPENAI_TIMEOUT', 30),
    ],

    'ai' => [
        'enabled' => false,
        'fallback' => env('WHATSAPP_AI_FALLBACK', false),
        'transcription_enabled' => false,
        'use_queue' => env('WHATSAPP_AI_USE_QUEUE', false),
        'system_prompt' => env('WHATSAPP_AI_SYSTEM_PROMPT', 'You are a helpful WhatsApp assistant. Reply concisely.'),
    ],

    'campaigns' => [
        'use_queue' => false,
    ],

    'auto_reply' => [
        'enabled' => true,
    ],

    'tenant' => [
        'enabled' => env('WHATSAPP_TENANT_ENABLED', false),
        'column' => env('WHATSAPP_TENANT_COLUMN', 'tenant_id'),
        'resolver' => env('WHATSAPP_TENANT_RESOLVER'),
        'admin_middleware' => env('WHATSAPP_TENANT_ADMIN_MIDDLEWARE', true),
    ],

    'filament' => [
        'enabled' => env('WHATSAPP_FILAMENT_ENABLED', true),
    ],

    'events' => [
        'log_incoming' => env('WHATSAPP_LOG_INCOMING', true),
        'process_incoming' => true,
    ],

];
