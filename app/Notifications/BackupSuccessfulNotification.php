<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BackupSuccessfulNotification extends Notification
{
    use Queueable;

    protected array $backupInfo;

    public function __construct(array $backupInfo)
    {
        $this->backupInfo = $backupInfo;
    }

    public function via($notifiable): array
    {
        $channels = ['mail'];
        
        // Add Slack if webhook URL is configured
        if (!empty($this->backupInfo['slack_webhook_url'])) {
            $channels[] = 'slack';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'SaaS Helpdesk');
        $backupName = $this->backupInfo['backup_name'] ?? 'Backup';
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        
        return (new MailMessage)
            ->subject("✅ Backup Exitoso - {$appName}")
            ->greeting('¡Backup Completado!')
            ->line("El backup '{$backupName}' se ha completado exitosamente.")
            ->line("📅 **Fecha:** {$timestamp}")
            ->line("📦 **Nombre:** {$backupName}")
            ->when(isset($this->backupInfo['size']), function ($message) {
                return $message->line("📊 **Tamaño:** " . $this->formatBytes($this->backupInfo['size']));
            })
            ->when(isset($this->backupInfo['duration']), function ($message) {
                return $message->line("⏱️ **Duración:** {$this->backupInfo['duration']} segundos");
            })
            ->when(isset($this->backupInfo['files_count']), function ($message) {
                return $message->line("📁 **Archivos:** {$this->backupInfo['files_count']}");
            })
            ->when(isset($this->backupInfo['databases_count']), function ($message) {
                return $message->line("🗄️ **Bases de datos:** {$this->backupInfo['databases_count']}");
            })
            ->line('Todos los datos han sido respaldados correctamente.')
            ->action('Ver Historial de Backups', url('/admin/backup-history'))
            ->line('Este es un mensaje automático del sistema de backup.')
            ->salutation("Saludos,\n{$appName}");
    }

    public function toSlack($notifiable): SlackMessage
    {
        $appName = config('app.name', 'SaaS Helpdesk');
        $backupName = $this->backupInfo['backup_name'] ?? 'Backup';
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        
        $message = (new SlackMessage)
            ->success()
            ->content("✅ Backup completado exitosamente")
            ->attachment(function ($attachment) use ($appName, $backupName, $timestamp) {
                $attachment
                    ->title("Backup: {$backupName}")
                    ->fields([
                        'Aplicación' => $appName,
                        'Fecha' => $timestamp,
                        'Estado' => '✅ Exitoso',
                    ])
                    ->color('good');
                    
                if (isset($this->backupInfo['size'])) {
                    $attachment->field('Tamaño', $this->formatBytes($this->backupInfo['size']));
                }
                
                if (isset($this->backupInfo['duration'])) {
                    $attachment->field('Duración', $this->backupInfo['duration'] . ' segundos');
                }
            });
            
        return $message;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}