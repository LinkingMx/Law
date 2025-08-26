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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Identificador único del template
            $table->string('name'); // Nombre descriptivo
            $table->string('subject'); // Asunto del email
            $table->text('content'); // Contenido HTML/texto del email
            $table->json('variables')->nullable(); // Variables disponibles
            $table->string('language', 5)->default('es'); // Idioma del template
            $table->string('category')->default('general'); // Categoría (backup, user, etc.)
            $table->boolean('is_active')->default(true); // Si está activo
            $table->text('description')->nullable(); // Descripción del template
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['key', 'language']);
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
