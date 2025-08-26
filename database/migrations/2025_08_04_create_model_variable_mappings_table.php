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
        Schema::create('model_variable_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('model_class'); // App\Models\Documentation
            $table->string('variable_key'); // creator_department_name
            $table->string('variable_name'); // Nombre del departamento del creador
            $table->text('description')->nullable(); // Descripción detallada
            $table->string('data_type')->default('string'); // string, integer, boolean, date, etc.
            $table->string('category')->default('custom'); // custom, computed, relation, etc.
            $table->json('mapping_config'); // Configuración de cómo obtener el valor
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('example_value')->nullable();
            $table->timestamps();

            $table->unique(['model_class', 'variable_key']);
            $table->index(['model_class', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_variable_mappings');
    }
};