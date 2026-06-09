<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppSetting;

class WhatsAppSettingsService
{
    public const CACHE_KEY = 'whatsapp.settings.all';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'general.default_account' => [
                'group' => 'general',
                'label' => 'Default Account (ID or name)',
                'type' => WhatsAppSetting::TYPE_STRING,
                'default' => '',
                'nullable' => true,
            ],
            'general.default_provider' => [
                'group' => 'general',
                'label' => 'Default Provider',
                'type' => WhatsAppSetting::TYPE_STRING,
                'default' => 'meta',
                'options' => ['meta', 'twilio'],
            ],
            'general.api_version' => [
                'group' => 'general',
                'label' => 'Meta API Version',
                'type' => WhatsAppSetting::TYPE_STRING,
                'default' => 'v21.0',
            ],
            'webhook.app_secret' => [
                'group' => 'webhook',
                'label' => 'Global App Secret (fallback)',
                'type' => WhatsAppSetting::TYPE_STRING,
                'default' => '',
                'nullable' => true,
                'help' => 'Per-account app_secret on whatsapp_accounts is preferred.',
            ],
            'webhook.require_signature' => [
                'group' => 'webhook',
                'label' => 'Require Meta Webhook Signature',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => false,
            ],
            'twilio.require_signature' => [
                'group' => 'twilio',
                'label' => 'Require Twilio Webhook Signature',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'graph_api.timeout' => [
                'group' => 'graph_api',
                'label' => 'Graph API Timeout (seconds)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => 30,
                'min' => 5,
                'max' => 300,
            ],
            'graph_api.max_retries' => [
                'group' => 'graph_api',
                'label' => 'Graph API Max Retries',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => 3,
                'min' => 0,
                'max' => 10,
            ],
            'graph_api.retry_base_delay_ms' => [
                'group' => 'graph_api',
                'label' => 'Retry Base Delay (ms)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => 1000,
                'min' => 100,
                'max' => 60000,
            ],
            'graph_api.retry_max_delay_ms' => [
                'group' => 'graph_api',
                'label' => 'Retry Max Delay (ms)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => 60000,
                'min' => 1000,
                'max' => 300000,
            ],
            'queue.enabled' => [
                'group' => 'queue',
                'label' => 'Queue Outgoing Messages',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'queue.tries' => [
                'group' => 'queue',
                'label' => 'Queue Max Tries',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => 3,
                'min' => 1,
                'max' => 20,
            ],
            'campaigns.use_queue' => [
                'group' => 'campaigns',
                'label' => 'Queue Campaign Sends',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => false,
            ],
            'cost.utility' => [
                'group' => 'cost',
                'label' => 'Utility Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => 0.005,
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.marketing' => [
                'group' => 'cost',
                'label' => 'Marketing Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => 0.015,
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.authentication' => [
                'group' => 'cost',
                'label' => 'Authentication Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => 0.004,
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.service' => [
                'group' => 'cost',
                'label' => 'Service Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => 0.0,
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'ai.enabled' => [
                'group' => 'ai',
                'label' => 'AI Auto Reply',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => false,
            ],
            'ai.transcription_enabled' => [
                'group' => 'ai',
                'label' => 'Audio Transcription',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => false,
            ],
            'auto_reply.enabled' => [
                'group' => 'auto_reply',
                'label' => 'Keyword Auto Reply',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'media.enabled' => [
                'group' => 'media',
                'label' => 'Download Incoming Media',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'events.process_incoming' => [
                'group' => 'events',
                'label' => 'Process Incoming Messages',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'log_messages' => [
                'group' => 'logging',
                'label' => 'Log Outgoing Messages',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
            'admin.authorization_enabled' => [
                'group' => 'admin',
                'label' => 'Require Admin Authorization',
                'type' => WhatsAppSetting::TYPE_BOOLEAN,
                'default' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function groups(): array
    {
        return [
            'general' => 'General',
            'webhook' => 'Webhook Security',
            'twilio' => 'Twilio',
            'graph_api' => 'Graph API',
            'queue' => 'Queue',
            'campaigns' => 'Campaigns',
            'cost' => 'Cost Analytics',
            'ai' => 'AI Features',
            'auto_reply' => 'Auto Reply',
            'media' => 'Media',
            'events' => 'Events',
            'logging' => 'Logging',
            'admin' => 'Admin Panel',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->tableExists()) {
            return $this->defaultsFromDefinitions();
        }

        $definitions = $this->definitions();
        $stored = $this->allCached();
        $resolved = [];

        foreach ($definitions as $key => $definition) {
            $resolved[$key] = array_key_exists($key, $stored)
                ? $stored[$key]
                : $definition['default'];
        }

        return $resolved;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateMany(array $values): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $definitions = $this->definitions();

        foreach ($values as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];
            $castValue = $this->castIncomingValue($value, $definition['type']);

            WhatsAppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $definition['group'],
                    'type' => $definition['type'],
                    'value' => $this->serializeValue($castValue, $definition['type']),
                ],
            );
        }

        $this->clearCache();
        $this->applyToConfig();
    }

    public function applyToConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $settings = $this->all();

        $defaultAccount = trim((string) ($settings['general.default_account'] ?? ''));

        config([
            'whatsapp.default_account' => $defaultAccount !== '' ? $defaultAccount : null,
            'whatsapp.default_provider' => (string) ($settings['general.default_provider'] ?? 'meta'),
            'whatsapp.api_version' => (string) ($settings['general.api_version'] ?? 'v21.0'),
            'whatsapp.webhook.app_secret' => $this->nullableString($settings['webhook.app_secret'] ?? null),
            'whatsapp.webhook.require_signature' => (bool) ($settings['webhook.require_signature'] ?? false),
            'whatsapp.twilio.require_signature' => (bool) ($settings['twilio.require_signature'] ?? true),
            'whatsapp.graph_api.timeout' => (int) ($settings['graph_api.timeout'] ?? 30),
            'whatsapp.graph_api.max_retries' => (int) ($settings['graph_api.max_retries'] ?? 3),
            'whatsapp.graph_api.retry_base_delay_ms' => (int) ($settings['graph_api.retry_base_delay_ms'] ?? 1000),
            'whatsapp.graph_api.retry_max_delay_ms' => (int) ($settings['graph_api.retry_max_delay_ms'] ?? 60000),
            'whatsapp.queue_enabled' => (bool) ($settings['queue.enabled'] ?? true),
            'whatsapp.queue.tries' => (int) ($settings['queue.tries'] ?? 3),
            'whatsapp.campaigns.use_queue' => (bool) ($settings['campaigns.use_queue'] ?? false),
            'whatsapp.cost.utility' => (float) ($settings['cost.utility'] ?? 0.005),
            'whatsapp.cost.marketing' => (float) ($settings['cost.marketing'] ?? 0.015),
            'whatsapp.cost.authentication' => (float) ($settings['cost.authentication'] ?? 0.004),
            'whatsapp.cost.service' => (float) ($settings['cost.service'] ?? 0.0),
            'whatsapp.ai.enabled' => (bool) ($settings['ai.enabled'] ?? false),
            'whatsapp.ai.transcription_enabled' => (bool) ($settings['ai.transcription_enabled'] ?? false),
            'whatsapp.auto_reply.enabled' => (bool) ($settings['auto_reply.enabled'] ?? true),
            'whatsapp.media.enabled' => (bool) ($settings['media.enabled'] ?? true),
            'whatsapp.events.process_incoming' => (bool) ($settings['events.process_incoming'] ?? true),
            'whatsapp.log_messages' => (bool) ($settings['log_messages'] ?? true),
            'whatsapp.admin.authorization_enabled' => (bool) ($settings['admin.authorization_enabled'] ?? true),
        ]);
    }

    public function clearCache(): void
    {
        if (config('whatsapp.cache.enabled', true)) {
            Cache::forget(self::CACHE_KEY);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function allCached(): array
    {
        if (! config('whatsapp.cache.enabled', true)) {
            return $this->loadFromDatabase();
        }

        return Cache::remember(
            self::CACHE_KEY,
            (int) config('whatsapp.cache.ttl', 300),
            fn () => $this->loadFromDatabase(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadFromDatabase(): array
    {
        $definitions = $this->definitions();
        $resolved = [];

        foreach (WhatsAppSetting::query()->get(['key', 'value', 'type']) as $setting) {
            $resolved[$setting->key] = $this->castStoredValue(
                $setting->value,
                $definitions[$setting->key]['type'] ?? $setting->type,
            );
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultsFromDefinitions(): array
    {
        $resolved = [];

        foreach ($this->definitions() as $key => $definition) {
            $resolved[$key] = $definition['default'];
        }

        return $resolved;
    }

    protected function castIncomingValue(mixed $value, string $type): mixed
    {
        if ($type === WhatsAppSetting::TYPE_BOOLEAN) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $this->castStoredValue((string) $value, $type);
    }

    protected function castStoredValue(string $value, string $type): mixed
    {
        return match ($type) {
            WhatsAppSetting::TYPE_INTEGER => (int) $value,
            WhatsAppSetting::TYPE_FLOAT => (float) $value,
            WhatsAppSetting::TYPE_BOOLEAN => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            default => $value,
        };
    }

    protected function serializeValue(mixed $value, string $type): string
    {
        if ($type === WhatsAppSetting::TYPE_BOOLEAN) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }

    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable('whatsapp_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
