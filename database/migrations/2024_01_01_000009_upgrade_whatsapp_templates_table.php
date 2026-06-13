<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the composite unique index to back the account_id FK.
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->index('account_id', 'whatsapp_templates_account_id_index');
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'name', 'language']);
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->renameColumn('name', 'template_name');
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('provider', 20)->default('meta')->after('account_id');
            $table->string('meta_template_id')->nullable()->after('status');
            $table->unique(['account_id', 'template_name', 'language']);
            $table->index(['account_id', 'category']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'category']);
            $table->dropIndex(['account_id', 'status']);
            $table->dropUnique(['account_id', 'template_name', 'language']);
            $table->dropColumn(['provider', 'meta_template_id']);
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->renameColumn('template_name', 'name');
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->unique(['account_id', 'name', 'language']);
            $table->dropIndex('whatsapp_templates_account_id_index');
        });
    }
};
