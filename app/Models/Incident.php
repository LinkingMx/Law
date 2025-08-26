<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Incident extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_extension',
        'file_size',
        'mime_type',
        'status',
        'priority',
        'user_id',
        'branch_id',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }

    public function getFileUrl(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }
        
        return Storage::disk('public')->url($this->file_path);
    }

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

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'open' => 'Abierta',
            'in_progress' => 'En Progreso',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
            default => $this->status
        };
    }

    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => $this->priority
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'open' => 'warning',
            'in_progress' => 'info',
            'resolved' => 'success',
            'closed' => 'gray',
            default => 'gray'
        };
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'urgent' => 'danger',
            default => 'gray'
        };
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }
        
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'priority', 'assigned_to'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($incident) {
            $incident->deleteFile();
        });
    }
}
