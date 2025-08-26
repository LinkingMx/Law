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
        // Eliminar tablas de workflows legacy si existen
        Schema::dropIfExists('workflow_step_executions');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('configurable_workflows');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir esta migración de limpieza
        // Las tablas eliminadas pertenecían al sistema legacy
    }
};