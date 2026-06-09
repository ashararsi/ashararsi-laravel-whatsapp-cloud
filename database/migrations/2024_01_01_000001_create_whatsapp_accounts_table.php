<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('provider', 20)->default('meta');
            $table->string('phone_number', 50);
            $table->string('phone_number_id')->nullable()->unique();
            $table->text('access_token')->nullable();
            $table->text('app_secret')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->string('twilio_sid')->nullable();
            $table->text('twilio_token')->nullable();
            $table->string('twilio_whatsapp_number', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
