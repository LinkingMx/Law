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
            // Migrar html_content a content si content está vacío
            $templates = \DB::table('email_templates')
                ->whereNull('content')
                ->orWhere('content', '')
                ->get();
                
            foreach ($templates as $template) {
                if (!empty($template->html_content)) {
                    \DB::table('email_templates')
                        ->where('id', $template->id)
                        ->update(['content' => $template->html_content]);
                }
            }
            
            // Eliminar campos innecesarios
            if (Schema::hasColumn('email_templates', 'html_content')) {
                $table->dropColumn('html_content');
            }
            if (Schema::hasColumn('email_templates', 'use_html_editor')) {
                $table->dropColumn('use_html_editor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->text('html_content')->nullable()->after('content');
            $table->boolean('use_html_editor')->default(true)->after('is_active');
        });
    }
};