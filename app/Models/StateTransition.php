<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateTransition extends Model
{
    protected $fillable = [
        'from_state_id',
        'to_state_id',
        'name',
        'label',
        'description',
        'requires_permission',
        'permission_name',
        'requires_role',
        'role_names',
        'requires_approval',
        'approver_roles',
        'condition_rules',
        'notification_template',
        'success_message',
        'failure_message',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_permission' => 'boolean',
        'requires_role' => 'boolean',
        'requires_approval' => 'boolean',
        'role_names' => 'array',
        'approver_roles' => 'array',
        'condition_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el estado origen
     */
    public function fromState(): BelongsTo
    {
        return $this->belongsTo(ApprovalState::class, 'from_state_id');
    }

    /**
     * Relación con el estado destino
     */
    public function toState(): BelongsTo
    {
        return $this->belongsTo(ApprovalState::class, 'to_state_id');
    }

    /**
     * Verificar si el usuario puede ejecutar esta transición
     */
    public function canBeExecutedBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Verificar permisos
        if ($this->requires_permission && $this->permission_name) {
            if (!$user->can($this->permission_name)) {
                return false;
            }
        }

        // Verificar roles
        if ($this->requires_role && !empty($this->role_names)) {
            $hasRole = false;
            foreach ($this->role_names as $roleName) {
                if ($user->hasRole($roleName)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar si se cumplen las condiciones para la transición
     */
    public function conditionsAreMet(Model $model): bool
    {
        if (empty($this->condition_rules)) {
            return true;
        }

        foreach ($this->condition_rules as $rule) {
            if (!$this->evaluateCondition($model, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluar una condición específica
     */
    protected function evaluateCondition(Model $model, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? '=';
        $value = $rule['value'] ?? null;

        if (!$field) {
            return true;
        }

        $modelValue = data_get($model, $field);

        return match ($operator) {
            '=' => $modelValue == $value,
            '!=' => $modelValue != $value,
            '>' => $modelValue > $value,
            '<' => $modelValue < $value,
            '>=' => $modelValue >= $value,
            '<=' => $modelValue <= $value,
            'in' => in_array($modelValue, (array) $value),
            'not_in' => !in_array($modelValue, (array) $value),
            'contains' => str_contains((string) $modelValue, (string) $value),
            'starts_with' => str_starts_with((string) $modelValue, (string) $value),
            'ends_with' => str_ends_with((string) $modelValue, (string) $value),
            'is_null' => is_null($modelValue),
            'is_not_null' => !is_null($modelValue),
            default => true,
        };
    }

    /**
     * Obtener transiciones disponibles para un estado
     */
    public static function getAvailableTransitions(ApprovalState $fromState, ?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $transitions = static::where('from_state_id', $fromState->id)
            ->where('is_active', true)
            ->with(['toState'])
            ->orderBy('sort_order')
            ->get();

        return $transitions->filter(function ($transition) use ($user) {
            return $transition->canBeExecutedBy($user);
        });
    }

    /**
     * Obtener mensaje de éxito para la transición
     */
    public function getSuccessMessage(): string
    {
        return $this->success_message ?: "Estado cambiado a {$this->toState->label} exitosamente.";
    }

    /**
     * Obtener mensaje de error para la transición
     */
    public function getFailureMessage(): string
    {
        return $this->failure_message ?: "No se pudo cambiar el estado a {$this->toState->label}.";
    }

    /**
     * Scope para transiciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para transiciones desde un estado específico
     */
    public function scopeFromState($query, int $stateId)
    {
        return $query->where('from_state_id', $stateId);
    }

    /**
     * Scope para transiciones hacia un estado específico
     */
    public function scopeToState($query, int $stateId)
    {
        return $query->where('to_state_id', $stateId);
    }
}