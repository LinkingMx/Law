<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_state_id')->constrained('approval_states')->onDelete('cascade');
            $table->foreignId('to_state_id')->constrained('approval_states')->onDelete('cascade');
            $table->string('name'); // Nombre de la transición (ej: submit_for_approval, approve, reject)
            $table->string('label'); // Etiqueta visible (ej: Enviar para Aprobación, Aprobar, Rechazar)
            $table->text('description')->nullable();
            
            // Control de acceso
            $table->boolean('requires_permission')->default(false);
            $table->string('permission_name')->nullable(); // Permiso específico requerido
            $table->boolean('requires_role')->default(false);
            $table->json('role_names')->nullable(); // Roles que pueden ejecutar la transición
            
            // Configuración de aprobación
            $table->boolean('requires_approval')->default(false);
            $table->json('approver_roles')->nullable(); // Roles que pueden aprobar
            
            // Condiciones y reglas
            $table->json('condition_rules')->nullable(); // Reglas que deben cumplirse
            
            // Configuración de notificaciones
            $table->string('notification_template')->nullable(); // Template de email a usar
            
            // Mensajes
            $table->string('success_message')->nullable();
            $table->string('failure_message')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Índices
            $table->index(['from_state_id', 'is_active']);
            $table->index(['to_state_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_transitions');
    }
};