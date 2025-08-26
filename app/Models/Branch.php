<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the users that belong to the branch.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch');
    }
    
    /**
     * Relación con documentos (many-to-many)
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'branch_document')
            ->withTimestamps();
    }
    
    /**
     * Relación muchos a muchos con documentación (Documentation)
     */
    public function documentations(): BelongsToMany
    {
        return $this->belongsToMany(Documentation::class, 'documentation_branches')
            ->withTimestamps();
    }
    
    /**
     * Obtener documentos vencidos de esta sucursal
     */
    public function expiredDocuments(): BelongsToMany
    {
        return $this->documents()
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', now());
    }
    
    /**
     * Obtener documentos próximos a vencer de esta sucursal
     */
    public function expiringSoonDocuments(): BelongsToMany
    {
        return $this->documents()
            ->whereNotNull('expire_date')
            ->whereBetween('expire_date', [now(), now()->addDays(30)]);
    }
    
    /**
     * Obtener documentos sin archivo de esta sucursal
     */
    public function documentsWithoutFiles(): BelongsToMany
    {
        return $this->documents()->whereNull('file_path');
    }
    
    /**
     * Obtener el conteo total de documentos de esta sucursal
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }
}
