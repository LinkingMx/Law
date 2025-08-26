<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Relación con documentos
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    
    /**
     * Obtener documentos vencidos de esta categoría
     */
    public function expiredDocuments(): HasMany
    {
        return $this->documents()
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', now());
    }
    
    /**
     * Obtener documentos próximos a vencer de esta categoría
     */
    public function expiringSoonDocuments(): HasMany
    {
        return $this->documents()
            ->whereNotNull('expire_date')
            ->whereBetween('expire_date', [now(), now()->addDays(30)]);
    }
    
    /**
     * Obtener documentos sin archivo de esta categoría
     */
    public function documentsWithoutFiles(): HasMany
    {
        return $this->documents()->whereNull('file_path');
    }
    
    /**
     * Obtener el conteo total de documentos de esta categoría
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }
}
