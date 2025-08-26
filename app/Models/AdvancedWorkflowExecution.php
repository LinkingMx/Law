<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvancedWorkflowExecution extends Model
{
    // Estados de ejecución
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'advanced_workflow_id',
        'target_model',
        'target_id',
        'status',
        'current_step_id',
        'current_step_order',
        'context_data',
        'step_results',
        'initiated_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'step_results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación con el workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AdvancedWorkflow::class, 'advanced_workflow_id');
    }

    /**
     * Relación con el paso actual
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepDefinition::class, 'current_step_id');
    }

    /**
     * Relación con el usuario que inició el workflow
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Relación con las ejecuciones de pasos
     */
    public function stepExecutions(): HasMany
    {
        return $this->hasMany(WorkflowStepExecutionAdvanced::class, 'workflow_execution_id');
    }

    /**
     * Obtener el modelo objetivo
     */
    public function getTargetModel(): ?Model
    {
        if (!class_exists($this->target_model)) {
            return null;
        }

        try {
            return $this->target_model::find($this->target_id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtener título del modelo objetivo
     */
    public function getTargetTitle(): string
    {
        $targetModel = $this->getTargetModel();
        
        if (!$targetModel) {
            return "Registro #{$this->target_id}";
        }

        $titleFields = ['title', 'name', 'subject', 'description'];
        
        foreach ($titleFields as $field) {
            if (isset($targetModel->$field) && $targetModel->$field) {
                return $targetModel->$field;
            }
        }

        return class_basename($this->target_model) . " #{$this->target_id}";
    }

    /**
     * Inicializar la ejecución del workflow
     */
    public function initialize(): bool
    {
        $firstStep = $this->workflow->stepDefinitions()
            ->active()
            ->ordered()
            ->first();

        if (!$firstStep) {
            $this->markAsFailed('No se encontraron pasos activos');
            return false;
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'current_step_id' => $firstStep->id,
            'current_step_order' => $firstStep->step_order,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Avanzar al siguiente paso aplicable
     */
    public function advanceToNextStep(): ?WorkflowStepDefinition
    {
        $currentStep = $this->currentStep;
        $targetModel = $this->getTargetModel();
        $context = $this->getContext();
        
        // Buscar el siguiente paso que debería ejecutarse
        $nextSteps = $this->workflow->stepDefinitions()
            ->where('step_order', '>', $this->current_step_order)
            ->active()
            ->ordered()
            ->get();
            
        foreach ($nextSteps as $nextStep) {
            if ($nextStep->shouldExecute($targetModel, $context)) {
                $this->update([
                    'current_step_id' => $nextStep->id,
                    'current_step_order' => $nextStep->step_order,
                ]);
                return $nextStep;
            }
        }
        
        // No hay más pasos aplicables, completar workflow
        $this->markAsCompleted();
        return null;
    }

    /**
     * Obtener la ejecución del paso actual
     */
    public function getCurrentStepExecution(): ?WorkflowStepExecutionAdvanced
    {
        if (!$this->current_step_id) {
            return null;
        }

        return $this->stepExecutions()
            ->where('step_definition_id', $this->current_step_id)
            ->where('status', '!=', 'completed')
            ->first();
    }

    /**
     * Crear ejecución para el paso actual
     */
    public function createCurrentStepExecution(array $data = []): WorkflowStepExecutionAdvanced
    {
        return $this->stepExecutions()->create(array_merge([
            'step_definition_id' => $this->current_step_id,
            'status' => 'pending',
            'input_data' => $this->context_data,
            'started_at' => now(),
        ], $data));
    }

    /**
     * Marcar paso actual como completado
     */
    public function markCurrentStepCompleted(?string $comments = null, ?int $completedBy = null): void
    {
        $currentStepExecution = $this->getCurrentStepExecution();
        
        if ($currentStepExecution) {
            $currentStepExecution->markAsCompleted($comments, $completedBy);
        }

        // Actualizar resultados del paso
        $this->addStepResult($this->current_step_order, [
            'status' => 'completed',
            'completed_at' => now()->toISOString(),
            'completed_by' => $completedBy,
            'comments' => $comments,
        ]);
    }

    /**
     * Agregar resultado de un paso
     */
    public function addStepResult(int $stepOrder, array $result): void
    {
        $stepResults = $this->step_results ?? [];
        $stepResults[$stepOrder] = $result;
        
        $this->update(['step_results' => $stepResults]);
    }

    /**
     * Obtener progreso del workflow en porcentaje
     */
    public function getProgress(): int
    {
        $totalSteps = $this->workflow->getTotalSteps();
        
        if ($totalSteps === 0) {
            return 100;
        }

        $completedSteps = $this->stepExecutions()
            ->where('status', 'completed')
            ->count();

        return round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Obtener número de pasos completados
     */
    public function getCompletedStepsCount(): int
    {
        return $this->stepExecutions()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Marcar como completado
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'current_step_id' => null,
        ]);
    }

    /**
     * Marcar como fallado
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
        ]);

        // Agregar razón al contexto
        $contextData = $this->context_data ?? [];
        $contextData['failure_reason'] = $reason;
        $contextData['failed_at'] = now()->toISOString();
        
        $this->update(['context_data' => $contextData]);
    }

    /**
     * Marcar como cancelado
     */
    public function markAsCancelled(string $reason, ?int $cancelledBy = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        // Cancelar paso actual si existe
        $currentStepExecution = $this->getCurrentStepExecution();
        if ($currentStepExecution) {
            $currentStepExecution->markAsCancelled($reason, $cancelledBy);
        }

        // Agregar información de cancelación
        $contextData = $this->context_data ?? [];
        $contextData['cancelled_reason'] = $reason;
        $contextData['cancelled_by'] = $cancelledBy;
        $contextData['cancelled_at'] = now()->toISOString();
        
        $this->update(['context_data' => $contextData]);
    }

    /**
     * Verificar si está en progreso
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Verificar si está completado
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verificar si falló
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Verificar si fue cancelado
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Obtener descripción del estado
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_FAILED => 'Fallado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Estado desconocido',
        };
    }

    /**
     * Obtener contexto específico para este workflow
     */
    public function getContext(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->context_data ?? [];
        }

        return data_get($this->context_data, $key, $default);
    }

    /**
     * Establecer contexto
     */
    public function setContext(string $key, $value): void
    {
        $contextData = $this->context_data ?? [];
        data_set($contextData, $key, $value);
        
        $this->update(['context_data' => $contextData]);
    }

    /**
     * Obtener resultado de un paso específico
     */
    public function getStepResult(int $stepOrder): ?array
    {
        return $this->step_results[$stepOrder] ?? null;
    }

    /**
     * Scope para ejecuciones en progreso
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope para ejecuciones completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope para ejecuciones fallidas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope para un modelo específico
     */
    public function scopeForModel($query, string $modelClass, int $modelId)
    {
        return $query->where('target_model', $modelClass)
                    ->where('target_id', $modelId);
    }

    /**
     * Obtener tiempo transcurrido desde el inicio
     */
    public function getElapsedTime(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    /**
     * Verificar si puede ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Verificar si puede ser reiniciado
     */
    public function canBeRestarted(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }
}