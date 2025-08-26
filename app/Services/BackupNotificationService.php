<?php

namespace App\Services;

use App\Notifications\BackupSuccessfulNotification;
use App\Notifications\BackupFailedNotification;
use App\Settings\BackupSettings;
use App\Models\EmailConfiguration;
use App\Services\BackupNotifiable;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupNotificationService
{
    protected BackupSettings $settings;
    protected EmailTemplateService $emailTemplateService;

    public function __construct(BackupSettings $settings, EmailTemplateService $emailTemplateService)
    {
        $this->settings = $settings;
        $this->emailTemplateService = $emailTemplateService;
    }

    /**
     * Send backup successful notification
     */
    public function sendBackupSuccessful(array $backupInfo = []): void
    {
        if (!$this->settings->notifications_enabled || !$this->settings->notify_on_success) {
            return;
        }

        try {
            // Check if email templates are enabled and template exists
            if ($this->emailTemplateService->isEnabled() && $this->emailTemplateService->templateExists('backup-success')) {
                $this->sendTemplateBasedNotification('backup-success', $backupInfo);
            } else {
                // Fallback to traditional notification
                $notificationData = array_merge($backupInfo, [
                    'backup_name' => $this->settings->backup_name,
                    'slack_webhook_url' => $this->settings->slack_webhook_url,
                ]);

                $this->sendNotification(new BackupSuccessfulNotification($notificationData));
            }
            
            Log::info('Backup success notification sent', $backupInfo);
        } catch (\Exception $e) {
            Log::error('Failed to send backup success notification: ' . $e->getMessage());
        }
    }

    /**
     * Send backup failed notification
     */
    public function sendBackupFailed(string $error, array $errorInfo = []): void
    {
        if (!$this->settings->notifications_enabled || !$this->settings->notify_on_failure) {
            return;
        }

        try {
            // Check if email templates are enabled and template exists
            if ($this->emailTemplateService->isEnabled() && $this->emailTemplateService->templateExists('backup-failed')) {
                $backupData = array_merge($errorInfo, ['error' => $error]);
                $this->sendTemplateBasedNotification('backup-failed', $backupData);
            } else {
                // Fallback to traditional notification
                $notificationData = array_merge($errorInfo, [
                    'backup_name' => $this->settings->backup_name,
                    'error' => $error,
                    'slack_webhook_url' => $this->settings->slack_webhook_url,
                ]);

                $this->sendNotification(new BackupFailedNotification($notificationData));
            }
            
            Log::info('Backup failure notification sent', array_merge($errorInfo, ['error' => $error]));
        } catch (\Exception $e) {
            Log::error('Failed to send backup failure notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to configured recipients
     */
    protected function sendNotification($notification): void
    {
        $emails = $this->settings->getNotificationEmails();
        
        if (empty($emails)) {
            Log::warning('No notification emails configured for backup notifications');
            return;
        }

        // Ensure email configuration is applied before sending notifications
        $this->ensureEmailConfigurationIsApplied();

        // Log the email configuration for debugging
        $this->logEmailConfiguration();

        // Create notifiable for email sending
        $notifiable = new BackupNotifiable($emails, $this->settings->slack_webhook_url);

        try {
            Notification::send($notifiable, $notification);
            Log::info('Backup notification sent successfully to: ' . implode(', ', $emails));
        } catch (\Exception $e) {
            Log::error('Failed to send backup notification', [
                'error' => $e->getMessage(),
                'emails' => $emails,
                'notification_type' => get_class($notification),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ensure the active email configuration is applied
     */
    protected function ensureEmailConfigurationIsApplied(): void
    {
        try {
            $activeConfig = EmailConfiguration::getActive();
            if ($activeConfig) {
                $activeConfig->applyConfiguration();
                Log::debug('Applied email configuration for backup notifications', [
                    'config_name' => $activeConfig->name,
                    'driver' => $activeConfig->driver
                ]);
            } else {
                Log::warning('No active email configuration found for backup notifications');
            }
        } catch (\Exception $e) {
            Log::error('Failed to apply email configuration for backup notifications: ' . $e->getMessage());
        }
    }

    /**
     * Log current email configuration for debugging
     */
    protected function logEmailConfiguration(): void
    {
        try {
            $mailer = config('mail.default');
            $from = config('mail.from');
            $config = config("mail.mailers.{$mailer}");
            
            Log::debug('Current email configuration for backup notifications', [
                'default_mailer' => $mailer,
                'from_address' => $from['address'] ?? 'not set',
                'from_name' => $from['name'] ?? 'not set',
                'mailer_config' => [
                    'transport' => $config['transport'] ?? 'not set',
                    'host' => $config['host'] ?? 'not set',
                    'port' => $config['port'] ?? 'not set',
                    'username' => !empty($config['username']) ? 'configured' : 'not set',
                    'password' => !empty($config['password']) ? 'configured' : 'not set',
                    'encryption' => $config['encryption'] ?? 'not set',
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not log email configuration: ' . $e->getMessage());
        }
    }

    /**
     * Test notification system
     */
    public function sendTestNotification(): array
    {
        try {
            Log::info('Starting backup notification test');

            // Check if notifications are enabled
            if (!$this->settings->notifications_enabled) {
                return [
                    'success' => false,
                    'message' => 'Las notificaciones están deshabilitadas.',
                    'details' => [
                        'notifications_enabled' => false
                    ]
                ];
            }

            // Check if emails are configured
            $emails = $this->settings->getNotificationEmails();
            if (empty($emails)) {
                return [
                    'success' => false,
                    'message' => 'No hay emails configurados para notificaciones.',
                    'details' => [
                        'notifications_enabled' => true,
                        'configured_emails' => []
                    ]
                ];
            }

            // Check email configuration
            $emailConfigStatus = $this->validateEmailConfiguration();
            if (!$emailConfigStatus['valid']) {
                return [
                    'success' => false,
                    'message' => 'Configuración de email inválida: ' . $emailConfigStatus['message'],
                    'details' => $emailConfigStatus
                ];
            }

            // Test mail connection
            $connectionTest = $this->testMailConnection();
            if (!$connectionTest['success']) {
                return [
                    'success' => false,
                    'message' => 'Error de conexión de email: ' . $connectionTest['message'],
                    'details' => array_merge($emailConfigStatus, $connectionTest)
                ];
            }

            // Send test success notification
            $this->sendBackupSuccessful([
                'size' => 1024 * 1024 * 10, // 10 MB
                'duration' => 45,
                'files_count' => 1500,
                'databases_count' => 1,
            ]);

            Log::info('Backup notification test completed successfully');

            return [
                'success' => true,
                'message' => 'Notificación de prueba enviada correctamente a: ' . implode(', ', $emails),
                'details' => [
                    'notifications_enabled' => true,
                    'configured_emails' => $emails,
                    'email_config' => $emailConfigStatus,
                    'connection_test' => $connectionTest
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Test notification failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al enviar notificación de prueba: ' . $e->getMessage(),
                'details' => [
                    'exception_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]
            ];
        }
    }

    /**
     * Validate email configuration
     */
    protected function validateEmailConfiguration(): array
    {
        try {
            $activeConfig = EmailConfiguration::getActive();
            
            if (!$activeConfig) {
                return [
                    'valid' => false,
                    'message' => 'No hay configuración de email activa',
                    'active_config' => null
                ];
            }

            // Apply configuration to ensure it's current
            $activeConfig->applyConfiguration();

            $mailer = config('mail.default');
            $from = config('mail.from');
            
            if (empty($from['address'])) {
                return [
                    'valid' => false,
                    'message' => 'Dirección de email de origen no configurada',
                    'active_config' => [
                        'name' => $activeConfig->name,
                        'driver' => $activeConfig->driver
                    ],
                    'from_address' => null
                ];
            }

            return [
                'valid' => true,
                'message' => 'Configuración de email válida',
                'active_config' => [
                    'name' => $activeConfig->name,
                    'driver' => $activeConfig->driver,
                    'last_tested' => $activeConfig->last_tested_at?->format('Y-m-d H:i:s')
                ],
                'mailer' => $mailer,
                'from_address' => $from['address'],
                'from_name' => $from['name']
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error al validar configuración: ' . $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Test mail connection
     */
    protected function testMailConnection(): array
    {
        try {
            // Use Laravel's Mail facade to test the connection
            $mailer = config('mail.default');
            
            // For SMTP, we can test the connection
            if ($mailer === 'smtp') {
                $config = config('mail.mailers.smtp');
                
                if (empty($config['host']) || empty($config['port'])) {
                    return [
                        'success' => false,
                        'message' => 'Configuración SMTP incompleta (host/puerto)',
                        'config_checked' => ['host' => $config['host'] ?? null, 'port' => $config['port'] ?? null]
                    ];
                }

                // Test SMTP connection with a basic socket test
                try {
                    $host = $config['host'];
                    $port = $config['port'];
                    $timeout = 10;
                    
                    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
                    if ($socket) {
                        fclose($socket);
                        return [
                            'success' => true,
                            'message' => 'Conexión SMTP exitosa',
                            'host' => $host,
                            'port' => $port
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => "Error de conexión SMTP: {$errstr} ({$errno})",
                            'host' => $host,
                            'port' => $port
                        ];
                    }
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'Error de conexión SMTP: ' . $e->getMessage(),
                        'host' => $config['host'],
                        'port' => $config['port']
                    ];
                }
            }

            // For other mailers, we assume they're configured correctly if settings are present
            return [
                'success' => true,
                'message' => 'Configuración de email parece válida',
                'mailer' => $mailer,
                'note' => 'No se puede probar la conexión para este tipo de configuración'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al probar conexión: ' . $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Send template-based notification
     */
    protected function sendTemplateBasedNotification(string $templateKey, array $backupData): void
    {
        $emails = $this->settings->getNotificationEmails();
        
        if (empty($emails)) {
            Log::warning('No notification emails configured for backup notifications');
            return;
        }

        // Ensure email configuration is applied
        $this->ensureEmailConfigurationIsApplied();

        try {
            // Prepare backup variables
            $backupVariables = $this->emailTemplateService->getBackupVariables($backupData);
            
            // Process template
            $processedTemplate = $this->emailTemplateService->processTemplate($templateKey, $backupVariables);
            
            // Get wrapped content if configured
            $content = $this->emailTemplateService->getWrappedContent($processedTemplate['content']);

            // Send email to each recipient
            foreach ($emails as $email) {
                try {
                    Mail::send([], [], function ($message) use ($email, $processedTemplate, $content) {
                        $message->to($email)
                            ->subject($processedTemplate['subject'])
                            ->html($content)
                            ->from($processedTemplate['from_email'], $processedTemplate['from_name']);
                    });

                    Log::info("Template-based backup notification sent successfully", [
                        'template_key' => $templateKey,
                        'recipient' => $email,
                        'subject' => $processedTemplate['subject']
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to send template-based notification to {$email}", [
                        'template_key' => $templateKey,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to process template-based notification', [
                'template_key' => $templateKey,
                'error' => $e->getMessage(),
                'backup_data' => $backupData
            ]);
            throw $e;
        }
    }
}