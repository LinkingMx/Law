<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('model_type')->nullable()->after('category');
            $table->json('model_variables')->nullable()->after('variables');
            $table->boolean('use_html_editor')->default(true)->after('is_active');
            $table->text('html_content')->nullable()->after('content');
            $table->index('model_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['model_type', 'model_variables', 'use_html_editor', 'html_content']);
        });
    }
};