<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BackupFailedNotification extends Notification
{
    use Queueable;

    protected array $errorInfo;

    public function __construct(array $errorInfo)
    {
        $this->errorInfo = $errorInfo;
    }

    public function via($notifiable): array
    {
        $channels = ['mail'];
        
        // Add Slack if webhook URL is configured
        if (!empty($this->errorInfo['slack_webhook_url'])) {
            $channels[] = 'slack';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'SaaS Helpdesk');
        $backupName = $this->errorInfo['backup_name'] ?? 'Backup';
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        $error = $this->errorInfo['error'] ?? 'Error desconocido';
        
        return (new MailMessage)
            ->subject("❌ Error en Backup - {$appName}")
            ->greeting('Error en Backup')
            ->error()
            ->line("Ha ocurrido un error durante la ejecución del backup '{$backupName}'.")
            ->line("📅 **Fecha:** {$timestamp}")
            ->line("📦 **Backup:** {$backupName}")
            ->line("❌ **Error:** {$error}")
            ->when(isset($this->errorInfo['command']), function ($message) {
                return $message->line("🔧 **Comando:** {$this->errorInfo['command']}");
            })
            ->when(isset($this->errorInfo['exit_code']), function ($message) {
                return $message->line("🔢 **Código de salida:** {$this->errorInfo['exit_code']}");
            })
            ->line('**Acciones recomendadas:**')
            ->line('• Verificar la configuración de backup')
            ->line('• Revisar el espacio en disco disponible')
            ->line('• Comprobar las credenciales de Google Drive (si aplica)')
            ->line('• Verificar los permisos de archivos y directorios')
            ->action('Revisar Configuración', url('/admin/backup-configuration'))
            ->line('Por favor, revisa la configuración y vuelve a intentar.')
            ->salutation("Saludos,\n{$appName}");
    }

    public function toSlack($notifiable): SlackMessage
    {
        $appName = config('app.name', 'SaaS Helpdesk');
        $backupName = $this->errorInfo['backup_name'] ?? 'Backup';
        $timestamp = Carbon::now()->format('d/m/Y H:i:s');
        $error = $this->errorInfo['error'] ?? 'Error desconocido';
        
        return (new SlackMessage)
            ->error()
            ->content("❌ Error en backup")
            ->attachment(function ($attachment) use ($appName, $backupName, $timestamp, $error) {
                $attachment
                    ->title("Backup Fallido: {$backupName}")
                    ->fields([
                        'Aplicación' => $appName,
                        'Fecha' => $timestamp,
                        'Estado' => '❌ Error',
                        'Error' => $error,
                    ])
                    ->color('danger');
                    
                if (isset($this->errorInfo['exit_code'])) {
                    $attachment->field('Código de salida', $this->errorInfo['exit_code']);
                }
            });
    }
}