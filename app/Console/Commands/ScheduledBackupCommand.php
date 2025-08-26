<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Settings\BackupSettings;
use App\Models\EmailConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a scheduled backup according to the configuration';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService, BackupSettings $settings): int
    {
        if (!$settings->schedule_enabled) {
            $this->info('Scheduled backups are disabled.');
            return self::SUCCESS;
        }

        // Ensure email configuration is applied for scheduled runs
        $this->ensureEmailConfigurationIsApplied();

        $this->info('Starting scheduled backup...');
        Log::info('Starting scheduled backup execution');

        $result = $backupService->executeBackup();

        if ($result['success']) {
            $this->info('Backup completed successfully.');
            Log::info('Scheduled backup completed successfully');
            
            // Clean old backups if enabled
            if ($settings->delete_old_backups_enabled) {
                $this->info('Cleaning old backups...');
                $cleanResult = $backupService->cleanOldBackups();
                
                if ($cleanResult['success']) {
                    $this->info('Old backups cleaned successfully.');
                    Log::info('Old backups cleaned successfully');
                } else {
                    $this->error('Failed to clean old backups: ' . $cleanResult['message']);
                    Log::error('Failed to clean old backups: ' . $cleanResult['message']);
                }
            }
            
            return self::SUCCESS;
        } else {
            $this->error('Backup failed: ' . $result['message']);
            Log::error('Scheduled backup failed: ' . $result['message']);
            return self::FAILURE;
        }
    }

    /**
     * Ensure email configuration is applied for scheduled commands
     */
    protected function ensureEmailConfigurationIsApplied(): void
    {
        try {
            $activeConfig = EmailConfiguration::getActive();
            if ($activeConfig) {
                $activeConfig->applyConfiguration();
                $this->info('Applied email configuration: ' . $activeConfig->name);
                Log::info('Applied email configuration for scheduled backup', [
                    'config_name' => $activeConfig->name,
                    'driver' => $activeConfig->driver
                ]);
            } else {
                $this->warn('No active email configuration found');
                Log::warning('No active email configuration found for scheduled backup');
            }
        } catch (\Exception $e) {
            $this->error('Failed to apply email configuration: ' . $e->getMessage());
            Log::error('Failed to apply email configuration for scheduled backup: ' . $e->getMessage());
        }
    }
}
