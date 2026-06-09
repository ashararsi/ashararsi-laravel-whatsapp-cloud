<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('whatsapp_message_id')->nullable();
            $table->string('phone', 50);
            $table->text('message')->nullable();
            $table->string('type', 50);
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('whatsapp_message_id');
            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversation_messages');
    }
};
