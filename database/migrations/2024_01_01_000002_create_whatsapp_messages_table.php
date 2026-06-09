<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('whatsapp_message_id')->nullable();
            $table->string('to', 50);
            $table->string('type', 50);
            $table->text('message')->nullable();
            $table->string('status', 50)->default('pending');
            $table->json('meta_json')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['account_id', 'status']);
            $table->index('whatsapp_message_id');
            $table->index('to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
