<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('direction', 20)->default('outgoing')->after('account_id');
            $table->string('from', 50)->nullable()->after('to');
            $table->index(['account_id', 'direction', 'created_at']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unique('whatsapp_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_message_id']);
            $table->dropIndex(['account_id', 'direction', 'created_at']);
            $table->dropColumn(['direction', 'from']);
        });
    }
};
