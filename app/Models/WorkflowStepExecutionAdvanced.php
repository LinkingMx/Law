<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepExecutionAdvanced extends Model
{
    protected $table = 'workflow_step_executions_advanced';

    // Estados de ejecución de paso
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'workflow_execution_id',
        'step_definition_id',
        'status',
        'input_data',
        'output_data',
        'notifications_sent',
        'assigned_to',
        'completed_by',
        'comments',
        'started_at',
        'completed_at',
        'due_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'notifications_sent' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    /**
     * Relación con la ejecución del workflow
     */
    public function workflowExecution(): BelongsTo
    {
        return $this->belongsTo(AdvancedWorkflowExecution::class, 'workflow_execution_id');
    }

    /**
     * Relación con la definición del paso
     */
    public function stepDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepDefinition::class, 'step_definition_id');
    }

    /**
     * Relación con el usuario asignado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relación con el usuario que completó el paso
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Marcar como iniciado
     */
    public function markAsStarted(?int $assignedTo = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'assigned_to' => $assignedTo,
        ]);
    }

    /**
     * Marcar como completado
     */
    public function markAsCompleted(?string $comments = null, ?int $completedBy = null): void
    {
        $outputData = $this->output_data ?? [];
        $outputData['completed_at'] = now()->toISOString();
        $outputData['completion_method'] = $completedBy ? 'manual' : 'automatic';

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $completedBy,
            'comments' => $comments,
            'output_data' => $outputData,
        ]);
    }

    /**
     * Marcar como fallado
     */
    public function markAsFailed(string $reason, ?int $failedBy = null): void
    {
        $outputData = $this->output_data ?? [];
        $outputData['failure_reason'] = $reason;
        $outputData['failed_at'] = now()->toISOString();
        $outputData['failed_by'] = $failedBy;

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'comments' => $reason,
            'output_data' => $outputData,
        ]);
    }

    /**
     * Marcar como saltado
     */
    public function markAsSkipped(string $reason): void
    {
        $outputData = $this->output_data ?? [];
        $outputData['skip_reason'] = $reason;
        $outputData['skipped_at'] = now()->toISOString();

        $this->update([
            'status' => self::STATUS_SKIPPED,
            'completed_at' => now(),
            'comments' => $reason,
            'output_data' => $outputData,
        ]);
    }

    /**
     * Marcar como cancelado
     */
    public function markAsCancelled(string $reason, ?int $cancelledBy = null): void
    {
        $outputData = $this->output_data ?? [];
        $outputData['cancelled_reason'] = $reason;
        $outputData['cancelled_at'] = now()->toISOString();
        $outputData['cancelled_by'] = $cancelledBy;

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
            'comments' => $reason,
            'output_data' => $outputData,
        ]);
    }

    /**
     * Establecer fecha límite
     */
    public function setDueDate(?\DateTime $dueDate = null): void
    {
        if ($dueDate === null) {
            // Calcular basado en la configuración del paso
            $timeout = $this->stepDefinition->getTimeout();
            if ($timeout) {
                $dueDate = now()->addHours($timeout);
            }
        }

        $this->update(['due_at' => $dueDate]);
    }

    /**
     * Registrar notificación enviada
     */
    public function addNotificationSent(string $recipient, string $templateKey, array $details = []): void
    {
        $notifications = $this->notifications_sent ?? [];
        
        $notifications[] = [
            'recipient' => $recipient,
            'template_key' => $templateKey,
            'sent_at' => now()->toISOString(),
            'details' => $details,
        ];

        $this->update(['notifications_sent' => $notifications]);
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
     * Verificar si fue saltado
     */
    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Verificar si fue cancelado
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Verificar si está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verificar si requiere acción del usuario
     */
    public function requiresUserAction(): bool
    {
        return $this->stepDefinition->requiresManualIntervention() && 
               $this->isInProgress();
    }

    /**
     * Verificar si está vencido
     */
    public function isOverdue(): bool
    {
        return $this->due_at && 
               now()->isAfter($this->due_at) && 
               !$this->isCompleted();
    }

    /**
     * Obtener tiempo restante hasta vencimiento
     */
    public function getTimeRemaining(): ?int
    {
        if (!$this->due_at || $this->isCompleted()) {
            return null;
        }

        $remaining = now()->diffInMinutes($this->due_at, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Obtener tiempo transcurrido
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
     * Obtener descripción del estado
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_FAILED => 'Fallado',
            self::STATUS_SKIPPED => 'Saltado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Estado desconocido',
        };
    }

    /**
     * Obtener color del estado para UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SKIPPED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Obtener datos de entrada específicos
     */
    public function getInputData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->input_data ?? [];
        }

        return data_get($this->input_data, $key, $default);
    }

    /**
     * Establecer datos de entrada
     */
    public function setInputData(string $key, $value): void
    {
        $inputData = $this->input_data ?? [];
        data_set($inputData, $key, $value);
        
        $this->update(['input_data' => $inputData]);
    }

    /**
     * Obtener datos de salida específicos
     */
    public function getOutputData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->output_data ?? [];
        }

        return data_get($this->output_data, $key, $default);
    }

    /**
     * Establecer datos de salida
     */
    public function setOutputData(string $key, $value): void
    {
        $outputData = $this->output_data ?? [];
        data_set($outputData, $key, $value);
        
        $this->update(['output_data' => $outputData]);
    }

    /**
     * Obtener número de notificaciones enviadas
     */
    public function getNotificationCount(): int
    {
        return count($this->notifications_sent ?? []);
    }

    /**
     * Verificar si ya se envió una notificación a un destinatario
     */
    public function wasNotificationSentTo(string $recipient): bool
    {
        $notifications = $this->notifications_sent ?? [];
        
        foreach ($notifications as $notification) {
            if ($notification['recipient'] === $recipient) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope para pasos en progreso
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope para pasos vencidos
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope para pasos asignados a un usuario
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope para pasos que requieren acción
     */
    public function scopeRequiringAction($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS)
                    ->whereHas('stepDefinition', function ($subQuery) {
                        $subQuery->where('step_type', WorkflowStepDefinition::TYPE_APPROVAL);
                    });
    }
}