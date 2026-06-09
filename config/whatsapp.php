<?php

use Vendor\LaravelWhatsAppCloud\Http\Middleware\AuthorizeWhatsAppAdmin;
use Vendor\LaravelWhatsAppCloud\Providers\MetaProvider;
use Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider;

return [

    'default_account' => env('WHATSAPP_DEFAULT_ACCOUNT', null),

    'default_provider' => env('WHATSAPP_DEFAULT_PROVIDER', 'meta'),

    'providers' => [
        'meta' => MetaProvider::class,
        'twilio' => TwilioProvider::class,
    ],

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com'),

    // Defaults only — overridden at runtime by whatsapp_settings table when migrated.
    'graph_api' => [
        'timeout' => env('WHATSAPP_GRAPH_API_TIMEOUT', 30),
        'max_retries' => env('WHATSAPP_GRAPH_API_MAX_RETRIES', 3),
        'retry_base_delay_ms' => env('WHATSAPP_GRAPH_API_RETRY_BASE_MS', 1000),
        'retry_max_delay_ms' => env('WHATSAPP_GRAPH_API_RETRY_MAX_MS', 60000),
    ],

    'cost' => [
        'utility' => env('WHATSAPP_COST_UTILITY', 0.005),
        'marketing' => env('WHATSAPP_COST_MARKETING', 0.015),
        'authentication' => env('WHATSAPP_COST_AUTHENTICATION', 0.004),
        'service' => env('WHATSAPP_COST_SERVICE', 0.0),
    ],

    'queue_enabled' => env('WHATSAPP_QUEUE_ENABLED', true),

    'queue' => [
        'tries' => env('WHATSAPP_QUEUE_TRIES', 3),
        'backoff' => [10, 30, 60],
    ],

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
        'middleware' => ['web', AuthorizeWhatsAppAdmin::class],
        'authorization_enabled' => env('WHATSAPP_ADMIN_AUTHORIZATION_ENABLED', true),
        'gate' => env('WHATSAPP_ADMIN_GATE', 'manage-whatsapp'),
    ],

    'webhook' => [
        'prefix' => env('WHATSAPP_WEBHOOK_PREFIX', 'whatsapp'),
        'middleware' => [],
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'require_signature' => env('WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE', false),
    ],

    'twilio' => [
        'require_signature' => env('WHATSAPP_TWILIO_REQUIRE_SIGNATURE', true),
    ],

    'media' => [
        'enabled' => env('WHATSAPP_MEDIA_DOWNLOAD_ENABLED', true),
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
        'enabled' => env('WHATSAPP_AI_ENABLED', false),
        'fallback' => env('WHATSAPP_AI_FALLBACK', false),
        'transcription_enabled' => env('WHATSAPP_AI_TRANSCRIPTION_ENABLED', false),
        'use_queue' => env('WHATSAPP_AI_USE_QUEUE', false),
        'system_prompt' => env('WHATSAPP_AI_SYSTEM_PROMPT', 'You are a helpful WhatsApp assistant. Reply concisely.'),
    ],

    'campaigns' => [
        'use_queue' => env('WHATSAPP_CAMPAIGNS_USE_QUEUE', false),
    ],

    'auto_reply' => [
        'enabled' => env('WHATSAPP_AUTO_REPLY_ENABLED', true),
    ],

    'tenant' => [
        'enabled' => env('WHATSAPP_TENANT_ENABLED', false),
        'column' => env('WHATSAPP_TENANT_COLUMN', 'tenant_id'),
    ],

    'filament' => [
        'enabled' => env('WHATSAPP_FILAMENT_ENABLED', true),
    ],

    'events' => [
        'log_incoming' => env('WHATSAPP_LOG_INCOMING', true),
        'process_incoming' => env('WHATSAPP_PROCESS_INCOMING', true),
    ],

];
