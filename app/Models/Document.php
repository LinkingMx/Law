<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'document_category_id',
        'name',
        'description',
        'expire_date',
        'notification_days',
        'file_path',
        'file_name',
        'file_extension',
        'file_size',
        'mime_type',
        'file_metadata',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'file_metadata' => 'array',
    ];

    protected $appends = [
        'is_expired',
        'is_expiring_soon',
        'has_file',
        'file_url',
        'formatted_file_size',
    ];

    public function documentCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_document')
            ->withTimestamps();
    }
    
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpired(): bool
    {
        return $this->expire_date && $this->expire_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if (!$this->expire_date) {
            return false;
        }
        
        $notificationDays = $this->notification_days ?? 30;
        return $this->expire_date->isBetween(now(), now()->addDays($notificationDays));
    }

    // Accessor methods for frontend
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->isExpiringSoon();
    }

    public function getHasFileAttribute(): bool
    {
        return $this->hasFile();
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFileUrl();
    }

    public function getFormattedFileSizeAttribute(): ?string
    {
        return $this->getFormattedFileSize();
    }
    
    /**
     * Verificar si el documento tiene archivo adjunto
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }
    
    /**
     * Obtener la URL del archivo
     */
    public function getFileUrl(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }
        
        return Storage::url($this->file_path);
    }
    
    /**
     * Obtener el tamaño del archivo formateado
     */
    public function getFormattedFileSize(): ?string
    {
        if (!$this->file_size) {
            return null;
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Obtener icono según el tipo de archivo
     */
    public function getFileIcon(): string
    {
        if (!$this->file_extension) {
            return 'heroicon-o-document';
        }
        
        return match (strtolower($this->file_extension)) {
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx' => 'heroicon-o-document-text',
            'xls', 'xlsx' => 'heroicon-o-table-cells',
            'ppt', 'pptx' => 'heroicon-o-presentation-chart-bar',
            'jpg', 'jpeg', 'png', 'gif' => 'heroicon-o-photo',
            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }
    
    /**
     * Obtener color del badge según el tipo de archivo
     */
    public function getFileTypeColor(): string
    {
        if (!$this->file_extension) {
            return 'gray';
        }
        
        return match (strtolower($this->file_extension)) {
            'pdf' => 'danger',
            'doc', 'docx' => 'info', 
            'xls', 'xlsx' => 'success',
            'ppt', 'pptx' => 'warning',
            'jpg', 'jpeg', 'png', 'gif' => 'purple',
            'zip', 'rar', '7z' => 'orange',
            default => 'gray',
        };
    }
    
    /**
     * Eliminar archivo del storage
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        
        return true;
    }
    
    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'expire_date', 'file_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Eliminar archivo al borrar el documento
        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }
}
