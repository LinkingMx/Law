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
        Schema::create('advanced_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('target_model'); // App\Models\Documentation
            $table->json('trigger_conditions'); // Condiciones para disparar el workflow
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->json('global_variables')->nullable(); // Variables globales del workflow
            $table->timestamps();

            $table->index(['target_model', 'is_active']);
        });

        Schema::create('workflow_step_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advanced_workflow_id')->constrained()->onDelete('cascade');
            $table->string('step_name');
            $table->text('description')->nullable();
            $table->enum('step_type', ['notification', 'approval', 'action', 'condition', 'wait']);
            $table->integer('step_order');
            $table->json('step_config'); // Configuración específica del paso
            $table->json('conditions')->nullable(); // Condiciones para ejecutar este paso
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['advanced_workflow_id', 'step_order']);
        });

        Schema::create('workflow_step_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_step_definition_id')->constrained()->onDelete('cascade');
            $table->string('recipient_type'); // 'creator', 'approver', 'role', 'conditional'
            $table->json('recipient_config'); // Configuración del destinatario
            $table->string('email_template_key');
            $table->json('template_variables')->nullable(); // Variables específicas para este template
            $table->timestamps();
        });

        Schema::create('advanced_workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advanced_workflow_id')->constrained()->onDelete('cascade');
            $table->string('target_model');
            $table->unsignedBigInteger('target_id');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled']);
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_step_definitions');
            $table->integer('current_step_order')->default(1);
            $table->json('context_data')->nullable(); // Datos del contexto del workflow
            $table->json('step_results')->nullable(); // Resultados de cada paso
            $table->foreignId('initiated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['target_model', 'target_id']);
            $table->index(['status', 'current_step_order']);
        });

        Schema::create('workflow_step_executions_advanced', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_execution_id')->constrained('advanced_workflow_executions')->onDelete('cascade');
            $table->foreignId('step_definition_id')->constrained('workflow_step_definitions')->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'skipped', 'cancelled']);
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->json('notifications_sent')->nullable(); // Log de notificaciones enviadas
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comments')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index(['due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_step_executions_advanced');
        Schema::dropIfExists('advanced_workflow_executions');
        Schema::dropIfExists('workflow_step_templates');
        Schema::dropIfExists('workflow_step_definitions');
        Schema::dropIfExists('advanced_workflows');
    }
};