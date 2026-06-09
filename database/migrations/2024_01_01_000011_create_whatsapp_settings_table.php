<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group', 50);
            $table->string('type', 20)->default('string');
            $table->text('value');
            $table->timestamps();

            $table->index('group');
        });

        $now = now();

        $defaults = [
            ['key' => 'graph_api.timeout', 'group' => 'graph_api', 'type' => 'integer', 'value' => '30'],
            ['key' => 'graph_api.max_retries', 'group' => 'graph_api', 'type' => 'integer', 'value' => '3'],
            ['key' => 'graph_api.retry_base_delay_ms', 'group' => 'graph_api', 'type' => 'integer', 'value' => '1000'],
            ['key' => 'graph_api.retry_max_delay_ms', 'group' => 'graph_api', 'type' => 'integer', 'value' => '60000'],
            ['key' => 'cost.utility', 'group' => 'cost', 'type' => 'float', 'value' => '0.005'],
            ['key' => 'cost.marketing', 'group' => 'cost', 'type' => 'float', 'value' => '0.015'],
            ['key' => 'cost.authentication', 'group' => 'cost', 'type' => 'float', 'value' => '0.004'],
            ['key' => 'cost.service', 'group' => 'cost', 'type' => 'float', 'value' => '0'],
            ['key' => 'queue.tries', 'group' => 'queue', 'type' => 'integer', 'value' => '3'],
        ];

        foreach ($defaults as $row) {
            DB::table('whatsapp_settings')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
