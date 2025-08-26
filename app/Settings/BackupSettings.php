<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BackupSettings extends Settings
{
    // Google Drive Configuration
    public bool $google_drive_enabled = false;
    public ?string $google_drive_service_account_path = null;
    public ?string $google_drive_service_account_original_name = null;
    public ?string $google_drive_folder_id = null;
    public string $google_drive_folder_name = 'Laravel Backups';
    
    // Backup Configuration
    public string $backup_name = 'SaaS Helpdesk';
    public bool $include_files = true;
    public bool $include_databases = true;
    public array $directories_to_backup = ['app', 'config', 'database', 'resources', 'storage/app'];
    public array $exclude_directories = ['vendor', 'node_modules', 'storage/logs'];
    public array $databases_to_backup = ['sqlite'];
    
    // Retention Configuration
    public bool $delete_old_backups_enabled = false;
    public int $keep_all_backups_for_days = 7;
    public int $keep_daily_backups_for_days = 16;
    public int $keep_weekly_backups_for_weeks = 8;
    public int $keep_monthly_backups_for_months = 4;
    public int $keep_yearly_backups_for_years = 2;
    
    // Notification Configuration
    public bool $notifications_enabled = false;
    public ?string $notification_email = null;
    public ?string $slack_webhook_url = null;
    public bool $notify_on_success = true;
    public bool $notify_on_failure = true;
    
    // Schedule Configuration
    public bool $schedule_enabled = false;
    public string $schedule_frequency = 'daily';
    public string $schedule_time = '02:00';

    public static function group(): string
    {
        return 'backup';
    }


    /**
     * Get the list of available schedule frequencies
     */
    public static function getScheduleFrequencies(): array
    {
        return [
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
        ];
    }

    /**
     * Get the Google Drive credentials array
     */
    public function getGoogleDriveCredentials(): ?array
    {
        if (!$this->google_drive_enabled || is_null($this->google_drive_service_account_path) || empty($this->google_drive_service_account_path)) {
            return null;
        }

        $credentialsPath = storage_path('app/private/' . $this->google_drive_service_account_path);
        
        if (!file_exists($credentialsPath)) {
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            return $credentials;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if Google Drive is properly configured
     */
    public function isGoogleDriveConfigured(): bool
    {
        return $this->google_drive_enabled && 
               !is_null($this->google_drive_service_account_path) &&
               !empty($this->google_drive_service_account_path) &&
               !is_null($this->google_drive_folder_id) &&
               !empty($this->google_drive_folder_id) &&
               $this->getGoogleDriveCredentials() !== null;
    }

    /**
     * Get notification email recipients
     */
    public function getNotificationEmails(): array
    {
        if (is_null($this->notification_email) || empty($this->notification_email)) {
            return [];
        }

        return array_map('trim', explode(',', $this->notification_email));
    }
}