<?php

namespace App\Listeners;

use App\Services\BackupNotificationService;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use Illuminate\Support\Facades\Log;

class BackupEventListener
{
    protected BackupNotificationService $notificationService;

    public function __construct(BackupNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle backup successful events
     */
    public function handleBackupSuccessful(BackupWasSuccessful $event): void
    {
        try {
            Log::info('Backup successful event received, sending notification');
            
            $backupDestination = $event->backupDestination;
            $backupInfo = [
                'size' => $backupDestination->newestBackup()?->size() ?? 0,
                'duration' => null, // Not available from this event
                'files_count' => null, // Not available from this event
                'databases_count' => null, // Not available from this event
            ];
            
            $this->notificationService->sendBackupSuccessful($backupInfo);
            
        } catch (\Exception $e) {
            Log::error('Failed to handle backup successful event: ' . $e->getMessage(), [
                'exception' => $e,
                'event' => get_class($event)
            ]);
        }
    }

    /**
     * Handle backup failed events
     */
    public function handleBackupFailed(BackupHasFailed $event): void
    {
        try {
            Log::info('Backup failed event received, sending notification');
            
            $this->notificationService->sendBackupFailed(
                $event->exception->getMessage(),
                [
                    'exception_class' => get_class($event->exception),
                    'file' => $event->exception->getFile(),
                    'line' => $event->exception->getLine(),
                ]
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to handle backup failed event: ' . $e->getMessage(), [
                'exception' => $e,
                'event' => get_class($event)
            ]);
        }
    }
}