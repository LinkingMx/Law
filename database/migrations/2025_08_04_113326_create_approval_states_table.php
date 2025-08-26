<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_states', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // Clase del modelo (ej: App\Models\Documentation)
            $table->string('name'); // Nombre único del estado (ej: draft, pending_approval)
            $table->string('label'); // Etiqueta visible (ej: Borrador, Pendiente de Aprobación)
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable(); // Color para badges (success, warning, etc.)
            $table->string('icon', 50)->nullable(); // Icono heroicon
            $table->boolean('is_initial')->default(false); // Es el estado inicial
            $table->boolean('is_final')->default(false); // Es un estado final
            $table->boolean('requires_approval')->default(false); // Requiere aprobación manual
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['model_type', 'is_active']);
            $table->index(['model_type', 'is_initial']);
            $table->index(['model_type', 'is_final']);
            $table->unique(['model_type', 'name'], 'unique_model_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_states');
    }
};