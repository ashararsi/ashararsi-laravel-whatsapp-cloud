<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('phone', 50);
            $table->string('name')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'phone']);
            $table->index(['account_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
