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
            // Verificar si la columna existe antes de eliminarla
            if (Schema::hasColumn('email_templates', 'category')) {
                // Eliminar índices asociados si existen
                try {
                    $table->dropIndex(['category']);
                } catch (\Exception $e) {
                    // Ignorar si el índice no existe
                }
                
                $table->dropColumn('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('category')->default('general')->after('language');
        });
    }
};