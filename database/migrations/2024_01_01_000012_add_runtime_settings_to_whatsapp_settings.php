<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_settings')) {
            return;
        }

        $now = now();

        $rows = [
            ['key' => 'general.default_account', 'group' => 'general', 'type' => 'string', 'value' => ''],
            ['key' => 'general.default_provider', 'group' => 'general', 'type' => 'string', 'value' => 'meta'],
            ['key' => 'general.api_version', 'group' => 'general', 'type' => 'string', 'value' => 'v21.0'],
            ['key' => 'webhook.app_secret', 'group' => 'webhook', 'type' => 'string', 'value' => ''],
            ['key' => 'webhook.require_signature', 'group' => 'webhook', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'twilio.require_signature', 'group' => 'twilio', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'queue.enabled', 'group' => 'queue', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'campaigns.use_queue', 'group' => 'campaigns', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'ai.enabled', 'group' => 'ai', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'ai.transcription_enabled', 'group' => 'ai', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'auto_reply.enabled', 'group' => 'auto_reply', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'media.enabled', 'group' => 'media', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'events.process_incoming', 'group' => 'events', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'log_messages', 'group' => 'logging', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'admin.authorization_enabled', 'group' => 'admin', 'type' => 'boolean', 'value' => '1'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('whatsapp_settings')->where('key', $row['key'])->exists();

            if (! $exists) {
                DB::table('whatsapp_settings')->insert([
                    ...$row,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_settings')) {
            return;
        }

        DB::table('whatsapp_settings')->whereIn('key', [
            'general.default_account',
            'general.default_provider',
            'general.api_version',
            'webhook.app_secret',
            'webhook.require_signature',
            'twilio.require_signature',
            'queue.enabled',
            'campaigns.use_queue',
            'ai.enabled',
            'ai.transcription_enabled',
            'auto_reply.enabled',
            'media.enabled',
            'events.process_incoming',
            'log_messages',
            'admin.authorization_enabled',
        ])->delete();
    }
};
