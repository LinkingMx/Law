<?php

namespace App\Models;

use App\States\DocumentationState;
use App\States\DraftState;
use App\Traits\NotifiesModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class Documentation extends Model
{
    use LogsActivity, NotifiesModelChanges, HasStates;

    protected $fillable = [
        'title',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'last_edited_by',
        'last_edited_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'approval_level',
        'approval_history',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'rejected_at' => 'datetime',
        'approval_history' => 'array',
        'state' => DocumentationState::class,
    ];

    // DEPRECATED: Las constantes se mantienen por compatibilidad
    // El nuevo sistema usa ApprovalState dinámicamente
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';
    
    // DEPRECATED: El nuevo sistema no usa niveles de aprobación fijos
    const APPROVAL_LEVEL_NONE = 0;
    const APPROVAL_LEVEL_FIRST = 1;
    const APPROVAL_LEVEL_SECOND = 2;
    const APPROVAL_LEVEL_FINAL = 3;

    /**
     * Configuración de workflows - habilitar workflows para este modelo
     */
    public $enableWorkflows = true;

    /**
     * Boot del modelo para eventos automáticos
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear, inicializar estado
        static::creating(function ($model) {
            if (!$model->state) {
                $stateService = app(\App\Services\StateTransitionService::class);
                $stateService->initializeModelState($model);
            }
        });

        // Al actualizar, registrar automáticamente el editor
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->last_edited_by = auth()->id();
                $model->last_edited_at = now();
            }
        });
    }

    /**
     * Relación con el usuario que creó el documento
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que aprobó el documento
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relación con el último usuario que editó el documento
     */
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * Relación con el usuario que rechazó el documento
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Relación muchos a muchos con sucursales (branches)
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'documentation_branches')
            ->withTimestamps();
    }

    /**
     * Verificación de estados usando el nuevo sistema
     */
    public function isDraft(): bool
    {
        return $this->state?->getStateName() === 'draft' || $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->state?->getStateName() === 'published' || $this->status === self::STATUS_PUBLISHED;
    }

    public function isPendingApproval(): bool
    {
        return $this->state?->getStateName() === 'pending_approval' || $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isRejected(): bool
    {
        return $this->state?->getStateName() === 'rejected' || $this->status === self::STATUS_REJECTED;
    }
    
    public function isArchived(): bool
    {
        return $this->state?->getStateName() === 'archived' || $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * @deprecated El nuevo sistema maneja aprobaciones dinámicamente
     */
    public function getCurrentApprovalLevel(): int
    {
        return $this->approval_level ?? self::APPROVAL_LEVEL_NONE;
    }

    /**
     * @deprecated El nuevo sistema maneja aprobaciones dinámicamente
     */
    public function needsApprovalLevel(int $level): bool
    {
        return $this->isPendingApproval() && $this->getCurrentApprovalLevel() < $level;
    }

    /**
     * @deprecated El nuevo sistema maneja aprobaciones dinámicamente
     */
    public function getNextApprovalLevel(): ?int
    {
        // El nuevo sistema no usa niveles fijos
        return null;
    }

    // DEPRECATED: Usar solo el nuevo sistema de estados via StateTransitionService
    // Los métodos legacy se mantienen por compatibilidad pero redirigen al nuevo sistema
    
    /**
     * @deprecated Usar publishDocument() con nuevo sistema de estados
     */
    public function publish(?int $approvedBy = null): void
    {
        $this->publishDocument(['approved_by' => $approvedBy]);
    }
    
    /**
     * @deprecated Usar archiveDocument() con nuevo sistema de estados
     */
    public function archive(): void
    {
        $this->archiveDocument();
    }
    
    /**
     * @deprecated Usar submitForApprovalViaStates() con nuevo sistema de estados
     */
    public function submitForApproval(?int $submittedBy = null): void
    {
        $this->submitForApprovalViaStates(['submitted_by' => $submittedBy]);
    }
    
    /**
     * @deprecated El nuevo sistema maneja aprobaciones dinámicamente
     */
    public function approveAtLevel(int $level, ?int $approvedBy = null, ?string $comments = null): void
    {
        $this->approveViaStates([
            'approved_by' => $approvedBy,
            'comments' => $comments,
            'approval_level' => $level
        ]);
    }
    
    /**
     * @deprecated Usar rejectViaStates() con nuevo sistema de estados
     */
    public function reject(?int $rejectedBy = null, ?string $reason = null): void
    {
        $this->rejectViaStates([
            'rejected_by' => $rejectedBy,
            'rejection_reason' => $reason
        ]);
    }
    
    /**
     * @deprecated El nuevo sistema maneja esto a través de transiciones
     */
    public function resetApprovalProcess(): void
    {
        $this->executeTransitionByName('back_to_draft', ['reset_by' => auth()->id()]);
    }

    /**
     * Agregar entrada al historial de aprobaciones
     * NOTA: Ahora Spatie Activity Log maneja esto automáticamente
     */
    protected function addToApprovalHistory(string $action, int $userId, ?string $comments = null): void
    {
        // El nuevo sistema usa Spatie Activity Log para el historial
        // Este método se mantiene por compatibilidad pero ya no es necesario
        if (method_exists($this, 'activity')) {
            activity()
                ->performedOn($this)
                ->causedBy($userId)
                ->withProperties([
                    'action' => $action,
                    'comments' => $comments,
                    'legacy_method' => true
                ])
                ->log("Acción: {$action}");
        }
    }

    /**
     * Scope para documentos en borrador
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope para documentos publicados
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Obtener descripción del estado (prioriza nuevo sistema)
     */
    public function getStatusDescription(): string
    {
        // Priorizar el nuevo sistema de estados
        if ($this->state) {
            return $this->state->getDescription();
        }

        // Fallback al sistema legacy
        return match($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_PENDING_APPROVAL => 'Pendiente de Aprobación',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_PUBLISHED => 'Publicado',
            self::STATUS_ARCHIVED => 'Archivado',
            default => 'Sin Estado',
        };
    }

    /**
     * Obtener color del badge según el estado (prioriza nuevo sistema)
     */
    public function getStatusColor(): string
    {
        // Priorizar el nuevo sistema de estados
        if ($this->state) {
            return $this->state->getColor();
        }

        // Fallback al sistema legacy
        return match($this->status) {
            self::STATUS_DRAFT => 'warning',
            self::STATUS_PENDING_APPROVAL => 'info',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_ARCHIVED => 'gray',
            default => 'gray',
        };
    }
    
    /**
     * Obtener estado actual como string (unificado)
     */
    public function getCurrentState(): string
    {
        return $this->state?->getStateName() ?? $this->status ?? 'draft';
    }
    
    /**
     * Verificar si puede hacer una transición específica
     */
    public function canTransitionTo(string $stateName, ?User $user = null): bool
    {
        $availableTransitions = $this->getAvailableStateTransitions($user);
        
        foreach ($availableTransitions as $transition) {
            if ($transition['to_state']->name === $stateName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'branches'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Override para el título en workflows y notificaciones
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Obtener transiciones de estado disponibles
     */
    public function getAvailableStateTransitions(?User $user = null): array
    {
        $stateService = app(\App\Services\StateTransitionService::class);
        return $stateService->getAvailableTransitions($this, $user);
    }

    /**
     * Ejecutar una transición de estado
     */
    public function executeStateTransition(int $transitionId, array $data = [], ?User $user = null): bool
    {
        $transition = \App\Models\StateTransition::find($transitionId);
        if (!$transition) {
            return false;
        }

        $stateService = app(\App\Services\StateTransitionService::class);
        return $stateService->executeTransition($this, $transition, $user, $data);
    }

    /**
     * Métodos de conveniencia para transiciones comunes (usando nuevo sistema)
     */
    public function submitForApprovalViaStates(array $data = []): bool
    {
        return $this->executeTransitionByName('submit_for_approval', $data);
    }

    public function approveViaStates(array $data = []): bool
    {
        return $this->executeTransitionByName('approve', $data);
    }

    public function rejectViaStates(array $data = []): bool
    {
        return $this->executeTransitionByName('reject', $data);
    }

    public function publishDocument(array $data = []): bool
    {
        return $this->executeTransitionByName('publish', $data);
    }

    public function archiveDocument(array $data = []): bool
    {
        return $this->executeTransitionByName('archive', $data);
    }

    /**
     * Ejecutar transición por nombre
     */
    private function executeTransitionByName(string $transitionName, array $data = []): bool
    {
        $availableTransitions = $this->getAvailableStateTransitions();
        
        foreach ($availableTransitions as $transitionData) {
            if ($transitionData['transition']->name === $transitionName) {
                return $this->executeStateTransition($transitionData['transition']->id, $data);
            }
        }

        return false;
    }
}