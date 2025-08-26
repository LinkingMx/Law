<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalState extends Model
{
    protected $fillable = [
        'model_type',
        'name',
        'label',
        'description',
        'color',
        'icon',
        'is_initial',
        'is_final',
        'requires_approval',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con las transiciones que salen de este estado
     */
    public function fromTransitions(): HasMany
    {
        return $this->hasMany(StateTransition::class, 'from_state_id');
    }

    /**
     * Relación con las transiciones que llegan a este estado
     */
    public function toTransitions(): HasMany
    {
        return $this->hasMany(StateTransition::class, 'to_state_id');
    }

    /**
     * Obtener estados por modelo
     */
    public static function forModel(string $modelType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('model_type', $modelType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Obtener estado inicial para un modelo
     */
    public static function getInitialForModel(string $modelType): ?self
    {
        return static::where('model_type', $modelType)
            ->where('is_initial', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Obtener estados finales para un modelo
     */
    public static function getFinalStatesForModel(string $modelType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('model_type', $modelType)
            ->where('is_final', true)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Verificar si este estado puede transicionar al estado dado
     */
    public function canTransitionTo(ApprovalState $toState): bool
    {
        return $this->fromTransitions()
            ->where('to_state_id', $toState->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtener transiciones válidas desde este estado
     */
    public function getValidTransitions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->fromTransitions()
            ->with('toState')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Scope para estados activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para un modelo específico
     */
    public function scopeForModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope para estados iniciales
     */
    public function scopeInitial($query)
    {
        return $query->where('is_initial', true);
    }

    /**
     * Scope para estados finales
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Obtener color CSS para badges
     */
    public function getColorAttribute($value): string
    {
        return $value ?: match ($this->name) {
            'draft' => 'warning',
            'pending_approval' => 'info',
            'approved', 'published' => 'success',
            'rejected' => 'danger',
            'archived' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Obtener icono por defecto si no se especifica
     */
    public function getIconAttribute($value): string
    {
        return $value ?: match ($this->name) {
            'draft' => 'heroicon-o-document-text',
            'pending_approval' => 'heroicon-o-clock',
            'approved' => 'heroicon-o-check-circle',
            'published' => 'heroicon-o-eye',
            'rejected' => 'heroicon-o-x-circle',
            'archived' => 'heroicon-o-archive-box',
            default => 'heroicon-o-circle-stack',
        };
    }
}