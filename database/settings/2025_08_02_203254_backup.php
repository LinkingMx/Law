<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Google Drive Configuration
        $this->migrator->add('backup.google_drive_enabled', false);
        $this->migrator->add('backup.google_drive_service_account_path', '');
        $this->migrator->add('backup.google_drive_service_account_original_name', '');
        $this->migrator->add('backup.google_drive_folder_id', '');
        $this->migrator->add('backup.google_drive_folder_name', 'Laravel Backups');
        
        // Backup Configuration
        $this->migrator->add('backup.backup_name', 'SaaS Helpdesk');
        $this->migrator->add('backup.include_files', true);
        $this->migrator->add('backup.include_databases', true);
        $this->migrator->add('backup.directories_to_backup', [
            'app',
            'config',
            'database',
            'resources',
            'storage/app',
        ]);
        $this->migrator->add('backup.exclude_directories', [
            'vendor',
            'node_modules',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
        ]);
        $this->migrator->add('backup.databases_to_backup', ['sqlite']);
        
        // Retention Configuration
        $this->migrator->add('backup.delete_old_backups_enabled', true);
        $this->migrator->add('backup.keep_all_backups_for_days', 7);
        $this->migrator->add('backup.keep_daily_backups_for_days', 16);
        $this->migrator->add('backup.keep_weekly_backups_for_weeks', 8);
        $this->migrator->add('backup.keep_monthly_backups_for_months', 4);
        $this->migrator->add('backup.keep_yearly_backups_for_years', 2);
        
        // Notification Configuration
        $this->migrator->add('backup.notifications_enabled', true);
        $this->migrator->add('backup.notification_email', '');
        $this->migrator->add('backup.slack_webhook_url', '');
        $this->migrator->add('backup.notify_on_success', false);
        $this->migrator->add('backup.notify_on_failure', true);
        
        // Schedule Configuration
        $this->migrator->add('backup.schedule_enabled', false);
        $this->migrator->add('backup.schedule_frequency', 'daily');
        $this->migrator->add('backup.schedule_time', '02:00');
    }
};
