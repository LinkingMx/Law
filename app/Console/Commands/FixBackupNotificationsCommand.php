<?php

namespace App\Console\Commands;

use App\Settings\BackupSettings;
use App\Models\EmailConfiguration;
use Illuminate\Console\Command;

class FixBackupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:fix-notifications 
                          {--enable-success : Enable success notifications}
                          {--enable-failure : Enable failure notifications}
                          {--enable-all : Enable all notifications}
                          {--email= : Set notification email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix common backup notification configuration issues';

    /**
     * Execute the console command.
     */
    public function handle(BackupSettings $settings): int
    {
        $this->info('🔧 Fixing Backup Notification Configuration');
        $this->line('');

        $changes = false;

        // Check if notifications are enabled
        if (!$settings->notifications_enabled) {
            $this->warn('Notifications are currently disabled.');
            if ($this->confirm('Would you like to enable notifications?')) {
                $settings->notifications_enabled = true;
                $changes = true;
                $this->info('✅ Notifications enabled');
            }
        }

        // Handle enable-all option
        if ($this->option('enable-all')) {
            $settings->notifications_enabled = true;
            $settings->notify_on_success = true;
            $settings->notify_on_failure = true;
            $changes = true;
            $this->info('✅ All notifications enabled');
        }

        // Handle specific options
        if ($this->option('enable-success')) {
            $settings->notify_on_success = true;
            $changes = true;
            $this->info('✅ Success notifications enabled');
        }

        if ($this->option('enable-failure')) {
            $settings->notify_on_failure = true;
            $changes = true;
            $this->info('✅ Failure notifications enabled');
        }

        // Handle email option
        if ($email = $this->option('email')) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $settings->notification_email = $email;
                $changes = true;
                $this->info("✅ Notification email set to: {$email}");
            } else {
                $this->error("❌ Invalid email address: {$email}");
                return self::FAILURE;
            }
        }

        // Interactive fixes if no options provided
        if (!$this->hasOptions()) {
            $changes = $this->runInteractiveFixes($settings) || $changes;
        }

        // Save changes
        if ($changes) {
            $settings->save();
            $this->info('');
            $this->info('💾 Settings saved successfully!');
        } else {
            $this->info('');
            $this->info('📋 No changes were made.');
        }

        // Show current status
        $this->showCurrentStatus($settings);

        return self::SUCCESS;
    }

    /**
     * Check if any options were provided
     */
    protected function hasOptions(): bool
    {
        return $this->option('enable-success') || 
               $this->option('enable-failure') || 
               $this->option('enable-all') || 
               $this->option('email');
    }

    /**
     * Run interactive fixes
     */
    protected function runInteractiveFixes(BackupSettings $settings): bool
    {
        $changes = false;

        // Check notification settings
        if (!$settings->notify_on_success) {
            $this->warn('Success notifications are disabled. You won\'t receive notifications when backups complete successfully.');
            if ($this->confirm('Enable success notifications?')) {
                $settings->notify_on_success = true;
                $changes = true;
            }
        }

        if (!$settings->notify_on_failure) {
            $this->warn('Failure notifications are disabled. You won\'t be notified if backups fail.');
            if ($this->confirm('Enable failure notifications?')) {
                $settings->notify_on_failure = true;
                $changes = true;
            }
        }

        // Check email configuration
        $emails = $settings->getNotificationEmails();
        if (empty($emails)) {
            $this->warn('No notification email addresses are configured.');
            $email = $this->ask('Enter notification email address');
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $settings->notification_email = $email;
                $changes = true;
                $this->info("✅ Email set to: {$email}");
            } elseif ($email) {
                $this->error("❌ Invalid email address: {$email}");
            }
        }

        // Check email configuration system
        $activeConfig = EmailConfiguration::getActive();
        if (!$activeConfig) {
            $this->warn('No active email configuration found.');
            $this->info('Please configure email settings in the admin panel under "Email Settings".');
        }

        return $changes;
    }

    /**
     * Show current notification status
     */
    protected function showCurrentStatus(BackupSettings $settings): void
    {
        $this->line('');
        $this->info('📊 Current Notification Status:');
        $this->table(
            ['Setting', 'Status'],
            [
                ['Notifications Enabled', $settings->notifications_enabled ? '✅ Yes' : '❌ No'],
                ['Notify on Success', $settings->notify_on_success ? '✅ Yes' : '❌ No'],
                ['Notify on Failure', $settings->notify_on_failure ? '✅ Yes' : '❌ No'],
                ['Email Recipients', empty($settings->getNotificationEmails()) ? '❌ None' : '✅ ' . implode(', ', $settings->getNotificationEmails())],
                ['Slack Webhook', empty($settings->slack_webhook_url) ? '❌ Not configured' : '✅ Configured'],
            ]
        );

        // Check email configuration
        try {
            $activeConfig = EmailConfiguration::getActive();
            if ($activeConfig) {
                $this->info("📧 Active Email Configuration: {$activeConfig->name} ({$activeConfig->driver})");
            } else {
                $this->warn('📧 No active email configuration found');
            }
        } catch (\Exception $e) {
            $this->error('📧 Error checking email configuration: ' . $e->getMessage());
        }

        // Suggest next steps
        $this->line('');
        $this->info('💡 Suggestions:');
        if (!$settings->notifications_enabled) {
            $this->line('  • Enable notifications to receive backup status updates');
        }
        if (!$settings->notify_on_success && !$settings->notify_on_failure) {
            $this->line('  • Enable at least one notification type (success or failure)');
        }
        if (empty($settings->getNotificationEmails())) {
            $this->line('  • Configure notification email addresses');
        }
        if (!EmailConfiguration::getActive()) {
            $this->line('  • Set up email configuration in the admin panel');
        }
        $this->line('  • Test notifications with: php artisan backup:debug-notifications --test');
    }
}