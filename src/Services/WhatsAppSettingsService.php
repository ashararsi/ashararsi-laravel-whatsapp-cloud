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
            'graph_api.timeout' => [
                'group' => 'graph_api',
                'label' => 'Graph API Timeout (seconds)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => (int) config('whatsapp.graph_api.timeout', 30),
                'min' => 5,
                'max' => 300,
            ],
            'graph_api.max_retries' => [
                'group' => 'graph_api',
                'label' => 'Graph API Max Retries',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => (int) config('whatsapp.graph_api.max_retries', 3),
                'min' => 0,
                'max' => 10,
            ],
            'graph_api.retry_base_delay_ms' => [
                'group' => 'graph_api',
                'label' => 'Retry Base Delay (ms)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => (int) config('whatsapp.graph_api.retry_base_delay_ms', 1000),
                'min' => 100,
                'max' => 60000,
            ],
            'graph_api.retry_max_delay_ms' => [
                'group' => 'graph_api',
                'label' => 'Retry Max Delay (ms)',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => (int) config('whatsapp.graph_api.retry_max_delay_ms', 60000),
                'min' => 1000,
                'max' => 300000,
            ],
            'cost.utility' => [
                'group' => 'cost',
                'label' => 'Utility Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => (float) config('whatsapp.cost.utility', 0.005),
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.marketing' => [
                'group' => 'cost',
                'label' => 'Marketing Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => (float) config('whatsapp.cost.marketing', 0.015),
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.authentication' => [
                'group' => 'cost',
                'label' => 'Authentication Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => (float) config('whatsapp.cost.authentication', 0.004),
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'cost.service' => [
                'group' => 'cost',
                'label' => 'Service Message Cost (USD)',
                'type' => WhatsAppSetting::TYPE_FLOAT,
                'default' => (float) config('whatsapp.cost.service', 0.0),
                'min' => 0,
                'max' => 1,
                'step' => 0.0001,
            ],
            'queue.tries' => [
                'group' => 'queue',
                'label' => 'Queue Max Tries',
                'type' => WhatsAppSetting::TYPE_INTEGER,
                'default' => (int) config('whatsapp.queue.tries', 3),
                'min' => 1,
                'max' => 20,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->tableExists()) {
            return $this->defaultsFromConfig();
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
            $castValue = $this->castValue((string) $value, $definition['type']);

            WhatsAppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $definition['group'],
                    'type' => $definition['type'],
                    'value' => (string) $castValue,
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

        config([
            'whatsapp.graph_api.timeout' => (int) ($settings['graph_api.timeout'] ?? config('whatsapp.graph_api.timeout')),
            'whatsapp.graph_api.max_retries' => (int) ($settings['graph_api.max_retries'] ?? config('whatsapp.graph_api.max_retries')),
            'whatsapp.graph_api.retry_base_delay_ms' => (int) ($settings['graph_api.retry_base_delay_ms'] ?? config('whatsapp.graph_api.retry_base_delay_ms')),
            'whatsapp.graph_api.retry_max_delay_ms' => (int) ($settings['graph_api.retry_max_delay_ms'] ?? config('whatsapp.graph_api.retry_max_delay_ms')),
            'whatsapp.cost.utility' => (float) ($settings['cost.utility'] ?? config('whatsapp.cost.utility')),
            'whatsapp.cost.marketing' => (float) ($settings['cost.marketing'] ?? config('whatsapp.cost.marketing')),
            'whatsapp.cost.authentication' => (float) ($settings['cost.authentication'] ?? config('whatsapp.cost.authentication')),
            'whatsapp.cost.service' => (float) ($settings['cost.service'] ?? config('whatsapp.cost.service')),
            'whatsapp.queue.tries' => (int) ($settings['queue.tries'] ?? config('whatsapp.queue.tries')),
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
            $resolved[$setting->key] = $this->castValue(
                $setting->value,
                $definitions[$setting->key]['type'] ?? $setting->type,
            );
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultsFromConfig(): array
    {
        $resolved = [];

        foreach ($this->definitions() as $key => $definition) {
            $resolved[$key] = $definition['default'];
        }

        return $resolved;
    }

    protected function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            WhatsAppSetting::TYPE_INTEGER => (int) $value,
            WhatsAppSetting::TYPE_FLOAT => (float) $value,
            default => $value,
        };
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
