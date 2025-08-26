<?php

namespace App\Traits;

use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

trait NotifiesModelChanges
{
    /**
     * Boot the trait
     */
    public static function bootNotifiesModelChanges()
    {
        // Notify on model creation
        static::created(function ($model) {
            $model->notifyModelCreated();
            $model->triggerWorkflow('created');
        });

        // Notify on model update
        static::updated(function ($model) {
            $model->notifyModelUpdated();
            $model->triggerWorkflow('updated');
        });

        // Notify on model deletion
        static::deleting(function ($model) {
            $model->notifyModelDeleted();
            $model->triggerWorkflow('deleted');
        });
    }

    /**
     * Notify when model is created
     */
    protected function notifyModelCreated()
    {
        if (!$this->shouldNotifyModelChanges()) {
            return;
        }

        try {
            $emailService = app(EmailTemplateService::class);
            
            $modelData = $this->getModelNotificationData('created');
            $variables = $emailService->getModelVariables($modelData);
            
            $template = $emailService->processTemplate('model-created', $variables);
            
            $this->sendModelNotification($template);
            
        } catch (\Exception $e) {
            Log::error('Failed to send model created notification: ' . $e->getMessage());
        }
    }

    /**
     * Notify when model is updated
     */
    protected function notifyModelUpdated()
    {
        if (!$this->shouldNotifyModelChanges() || !$this->wasChanged()) {
            return;
        }

        try {
            $emailService = app(EmailTemplateService::class);
            
            $modelData = $this->getModelNotificationData('updated');
            $modelData['changes_summary'] = $this->getChangesSummary();
            
            $variables = $emailService->getModelVariables($modelData);
            
            $template = $emailService->processTemplate('model-updated', $variables);
            
            $this->sendModelNotification($template);
            
        } catch (\Exception $e) {
            Log::error('Failed to send model updated notification: ' . $e->getMessage());
        }
    }

    /**
     * Notify when model is deleted
     */
    protected function notifyModelDeleted()
    {
        if (!$this->shouldNotifyModelChanges()) {
            return;
        }

        try {
            $emailService = app(EmailTemplateService::class);
            
            $modelData = $this->getModelNotificationData('deleted');
            $variables = $emailService->getModelVariables($modelData);
            
            $template = $emailService->processTemplate('model-deleted', $variables);
            
            $this->sendModelNotification($template);
            
        } catch (\Exception $e) {
            Log::error('Failed to send model deleted notification: ' . $e->getMessage());
        }
    }

    /**
     * Get data for model notification
     */
    protected function getModelNotificationData(string $action): array
    {
        return [
            'model_name' => $this->getModelDisplayName(),
            'model_id' => $this->getKey(),
            'model_title' => $this->getModelTitle(),
            'model_status' => $this->getModelStatus(),
            'action_type' => $action,
            'action_user' => auth()->user()->name ?? 'Sistema',
            'action_date' => now()->format('d/m/Y H:i:s'),
            'record_url' => $this->getModelUrl(),
            'edit_url' => $this->getModelEditUrl(),
        ];
    }

    /**
     * Get changes summary for update notifications
     */
    protected function getChangesSummary(): string
    {
        $changes = $this->getChanges();
        $summary = [];
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at', 'created_at'])) {
                continue;
            }
            
            $oldValue = $this->getOriginal($field);
            $fieldLabel = $this->getFieldLabel($field);
            
            $summary[] = "{$fieldLabel}: {$oldValue} → {$newValue}";
        }
        
        return implode(', ', $summary);
    }

    /**
     * Send the model notification
     */
    protected function sendModelNotification(array $template)
    {
        $recipients = $this->getModelNotificationRecipients();
        
        if (empty($recipients)) {
            return;
        }

        $content = app(EmailTemplateService::class)->getWrappedContent($template['content']);
        
        foreach ($recipients as $email) {
            Mail::send([], [], function ($message) use ($email, $template, $content) {
                $message->to($email)
                    ->subject($template['subject'])
                    ->html($content)
                    ->from($template['from_email'], $template['from_name']);
            });
        }
    }

    /**
     * Determine if model changes should be notified
     * Override in your model to customize
     */
    protected function shouldNotifyModelChanges(): bool
    {
        return property_exists($this, 'notifyChanges') ? $this->notifyChanges : false;
    }

    /**
     * Get recipients for model notifications
     * Override in your model to customize
     */
    protected function getModelNotificationRecipients(): array
    {
        return property_exists($this, 'notificationRecipients') 
            ? $this->notificationRecipients 
            : [config('mail.from.address')];
    }

    /**
     * Get model display name
     * Override in your model to customize
     */
    protected function getModelDisplayName(): string
    {
        return class_basename($this);
    }

    /**
     * Get model title for notifications
     * Override in your model to customize
     */
    protected function getModelTitle(): string
    {
        if (method_exists($this, 'getTitle')) {
            return $this->getTitle();
        }
        
        return $this->name ?? $this->title ?? "ID: {$this->getKey()}";
    }

    /**
     * Get model status
     * Override in your model to customize
     */
    protected function getModelStatus(): string
    {
        if (property_exists($this, 'status')) {
            return $this->status;
        }
        
        return 'Activo';
    }

    /**
     * Get model URL
     * Override in your model to customize
     */
    protected function getModelUrl(): string
    {
        $modelName = strtolower(class_basename($this));
        return url("/admin/{$modelName}s/{$this->getKey()}");
    }

    /**
     * Get model edit URL
     * Override in your model to customize
     */
    protected function getModelEditUrl(): string
    {
        $modelName = strtolower(class_basename($this));
        return url("/admin/{$modelName}s/{$this->getKey()}/edit");
    }

    /**
     * Get field label for changes summary
     * Override in your model to customize field labels
     */
    protected function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Nombre',
            'email' => 'Email',
            'status' => 'Estado',
            'price' => 'Precio',
            'quantity' => 'Cantidad',
            'description' => 'Descripción',
            // Add more field labels as needed
        ];
        
        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Trigger workflow for this model
     */
    protected function triggerWorkflow(string $event): void
    {
        if (!$this->shouldTriggerWorkflows()) {
            return;
        }

        try {
            $workflowEngine = app(\App\Services\AdvancedWorkflowEngine::class);
            $workflowEngine->trigger($this, $event);
        } catch (\Exception $e) {
            Log::error('Failed to trigger workflow: ' . $e->getMessage(), [
                'model' => get_class($this),
                'id' => $this->getKey(),
                'event' => $event,
            ]);
        }
    }

    /**
     * Determine if workflows should be triggered for this model
     * Override in your model to customize
     */
    protected function shouldTriggerWorkflows(): bool
    {
        return property_exists($this, 'enableWorkflows') ? $this->enableWorkflows : true;
    }
}