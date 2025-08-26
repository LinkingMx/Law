<?php

namespace App\Console\Commands;

use App\Services\BackupNotificationService;
use App\Settings\BackupSettings;
use App\Models\EmailConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugBackupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:debug-notifications {--test : Send a test notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug backup notification system configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(BackupNotificationService $notificationService, BackupSettings $settings): int
    {
        $this->info('🔍 Debugging Backup Notification System');
        $this->line('');

        // Check backup settings
        $this->checkBackupSettings($settings);
        $this->line('');

        // Check email configuration
        $this->checkEmailConfiguration();
        $this->line('');

        // Check mail settings
        $this->checkMailSettings();
        $this->line('');

        // Run test if requested
        if ($this->option('test')) {
            $this->runNotificationTest($notificationService);
        }

        return self::SUCCESS;
    }

    /**
     * Check backup notification settings
     */
    protected function checkBackupSettings(BackupSettings $settings): void
    {
        $this->info('📋 Backup Notification Settings:');
        
        $this->line("  Notifications enabled: " . ($settings->notifications_enabled ? '✅ Yes' : '❌ No'));
        $this->line("  Notify on success: " . ($settings->notify_on_success ? '✅ Yes' : '❌ No'));
        $this->line("  Notify on failure: " . ($settings->notify_on_failure ? '✅ Yes' : '❌ No'));
        
        $emails = $settings->getNotificationEmails();
        if (empty($emails)) {
            $this->line("  Email recipients: ❌ None configured");
        } else {
            $this->line("  Email recipients: ✅ " . implode(', ', $emails));
        }
        
        if ($settings->slack_webhook_url) {
            $this->line("  Slack webhook: ✅ Configured");
        } else {
            $this->line("  Slack webhook: ❌ Not configured");
        }
    }

    /**
     * Check email configuration
     */
    protected function checkEmailConfiguration(): void
    {
        $this->info('📧 Email Configuration:');
        
        try {
            $activeConfig = EmailConfiguration::getActive();
            
            if (!$activeConfig) {
                $this->line("  Active configuration: ❌ None found");
                return;
            }
            
            $this->line("  Active configuration: ✅ {$activeConfig->name}");
            $this->line("  Driver: {$activeConfig->driver}");
            $this->line("  Last tested: " . ($activeConfig->last_tested_at ? $activeConfig->last_tested_at->format('Y-m-d H:i:s') : 'Never'));
            
            // Apply configuration
            $activeConfig->applyConfiguration();
            $this->line("  Configuration applied: ✅ Yes");
            
        } catch (\Exception $e) {
            $this->line("  Configuration error: ❌ " . $e->getMessage());
        }
    }

    /**
     * Check current mail settings
     */
    protected function checkMailSettings(): void
    {
        $this->info('⚙️ Current Mail Settings:');
        
        $mailer = config('mail.default');
        $from = config('mail.from');
        
        $this->line("  Default mailer: {$mailer}");
        $this->line("  From address: " . ($from['address'] ?? 'Not set'));
        $this->line("  From name: " . ($from['name'] ?? 'Not set'));
        
        $config = config("mail.mailers.{$mailer}");
        if ($config) {
            $this->line("  Transport: " . ($config['transport'] ?? 'Not set'));
            
            if ($mailer === 'smtp') {
                $this->line("  SMTP Host: " . ($config['host'] ?? 'Not set'));
                $this->line("  SMTP Port: " . ($config['port'] ?? 'Not set'));
                $this->line("  SMTP Username: " . (!empty($config['username']) ? 'Configured' : 'Not set'));
                $this->line("  SMTP Password: " . (!empty($config['password']) ? 'Configured' : 'Not set'));
                $this->line("  SMTP Encryption: " . ($config['encryption'] ?? 'None'));
            }
        } else {
            $this->line("  Mailer config: ❌ Not found");
        }
    }

    /**
     * Run notification test
     */
    protected function runNotificationTest(BackupNotificationService $notificationService): void
    {
        $this->info('🧪 Running Notification Test:');
        $this->line('');
        
        try {
            $this->line('Sending test notification...');
            $result = $notificationService->sendTestNotification();
            
            if ($result['success']) {
                $this->info('✅ Test notification sent successfully!');
                $this->line("Message: {$result['message']}");
                
                if (isset($result['details'])) {
                    $this->line('');
                    $this->info('📊 Test Details:');
                    $this->displayTestDetails($result['details']);
                }
            } else {
                $this->error('❌ Test notification failed!');
                $this->line("Message: {$result['message']}");
                
                if (isset($result['details'])) {
                    $this->line('');
                    $this->info('🔍 Error Details:');
                    $this->displayTestDetails($result['details']);
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Test notification crashed: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Display test details in a formatted way
     */
    protected function displayTestDetails(array $details, int $indent = 0): void
    {
        $prefix = str_repeat('  ', $indent);
        
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $this->line("{$prefix}{$key}:");
                $this->displayTestDetails($value, $indent + 1);
            } elseif (is_bool($value)) {
                $this->line("{$prefix}{$key}: " . ($value ? 'Yes' : 'No'));
            } else {
                $this->line("{$prefix}{$key}: {$value}");
            }
        }
    }
}