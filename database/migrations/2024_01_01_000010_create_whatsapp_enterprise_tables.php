<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('verification_status')->nullable();
            $table->string('quality_rating')->nullable();
            $table->string('messaging_tier')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('account_id');
        });

        Schema::create('whatsapp_synced_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('phone_number_id');
            $table->string('display_phone_number')->nullable();
            $table->string('verified_name')->nullable();
            $table->string('status')->nullable();
            $table->string('quality_rating')->nullable();
            $table->string('messaging_tier')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'phone_number_id']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('retry_count')->default(0)->after('status');
            $table->text('last_error')->nullable()->after('retry_count');
            $table->timestamp('dead_lettered_at')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_error', 'dead_lettered_at']);
        });

        Schema::dropIfExists('whatsapp_synced_phone_numbers');
        Schema::dropIfExists('whatsapp_business_profiles');
    }
};
