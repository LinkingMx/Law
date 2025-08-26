<?php

namespace App\Helpers;

use App\Settings\BackupSettings;
use Illuminate\Support\Facades\Log;

class BackupConfigHelper
{
    /**
     * Get dynamic backup configuration
     */
    public static function getConfig(): array
    {
        try {
            // Check if we're in config loading phase or if settings are available
            if (app()->bound(BackupSettings::class)) {
                $settings = app(BackupSettings::class);
                return self::getDynamicConfig($settings);
            }
        } catch (\Exception $e) {
            // Fall back to default configuration if settings can't be loaded
            Log::debug('Backup settings not available, using default config: ' . $e->getMessage());
        }
        
        return self::getDefaultConfig();
    }

    /**
     * Get configuration based on current settings
     */
    public static function getDynamicConfig(BackupSettings $settings): array
    {
        $config = self::getDefaultConfig();
        
        // Update backup name
        if (!is_null($settings->backup_name) && !empty($settings->backup_name)) {
            $config['backup']['name'] = $settings->backup_name;
        }
        
        // Configure what to include in backup
        if (!$settings->include_files) {
            $config['backup']['source']['files'] = [];
        } elseif (!empty($settings->directories_to_backup)) {
            $config['backup']['source']['files']['include'] = array_map(
                fn($dir) => base_path($dir),
                $settings->directories_to_backup
            );
        }
        
        // Configure excluded directories
        if (!empty($settings->exclude_directories)) {
            $config['backup']['source']['files']['exclude'] = array_merge(
                $config['backup']['source']['files']['exclude'] ?? [],
                array_map(fn($dir) => base_path($dir), $settings->exclude_directories)
            );
        }
        
        // Configure databases
        if (!$settings->include_databases) {
            $config['backup']['source']['databases'] = [];
        } elseif (!empty($settings->databases_to_backup)) {
            $config['backup']['source']['databases'] = $settings->databases_to_backup;
        }
        
        // Configure destination disks
        $disks = ['local'];
        if ($settings->google_drive_enabled && $settings->isGoogleDriveConfigured()) {
            $disks[] = 'google';
        }
        $config['backup']['destination']['disks'] = $disks;
        
        // Configure cleanup/retention settings
        if ($settings->delete_old_backups_enabled) {
            $config['cleanup']['default_strategy'] = [
                'keep_all_backups_for_days' => $settings->keep_all_backups_for_days ?? 7,
                'keep_daily_backups_for_days' => $settings->keep_daily_backups_for_days ?? 16,
                'keep_weekly_backups_for_weeks' => $settings->keep_weekly_backups_for_weeks ?? 8,
                'keep_monthly_backups_for_months' => $settings->keep_monthly_backups_for_months ?? 4,
                'keep_yearly_backups_for_years' => $settings->keep_yearly_backups_for_years ?? 2,
                'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
            ];
        }
        
        // Disable built-in Spatie notifications since we handle notifications in BackupService
        $config['notifications']['notifications'] = [];
        
        // Configure monitoring
        $config['monitor_backups'] = [[
            'name' => $settings->backup_name ?? env('APP_NAME', 'laravel-backup'),
            'disks' => $disks,
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ]];
        
        return $config;
    }

    /**
     * Get default backup configuration
     */
    private static function getDefaultConfig(): array
    {
        return [
            'backup' => [
                'name' => env('APP_NAME', 'laravel-backup'),
                'source' => [
                    'files' => [
                        'include' => [base_path()],
                        'exclude' => [
                            base_path('vendor'),
                            base_path('node_modules'),
                        ],
                        'follow_links' => false,
                        'ignore_unreadable_directories' => false,
                        'relative_path' => null,
                    ],
                    'databases' => [env('DB_CONNECTION', 'mysql')],
                ],
                'database_dump_compressor' => null,
                'database_dump_file_timestamp_format' => null,
                'database_dump_filename_base' => 'database',
                'database_dump_file_extension' => '',
                'destination' => [
                    'compression_method' => \ZipArchive::CM_DEFAULT,
                    'compression_level' => 9,
                    'filename_prefix' => '',
                    'disks' => ['local'],
                ],
                'temporary_directory' => storage_path('app/backup-temp'),
                'password' => env('BACKUP_ARCHIVE_PASSWORD'),
                'encryption' => 'default',
                'tries' => 1,
                'retry_delay' => 0,
            ],
            'notifications' => [
                'notifications' => [
                    \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
                    \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
                    \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
                ],
                'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
                'mail' => [
                    'to' => 'your@example.com',
                    'from' => [
                        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                        'name' => env('MAIL_FROM_NAME', 'Example'),
                    ],
                ],
                'slack' => [
                    'webhook_url' => '',
                    'channel' => null,
                    'username' => null,
                    'icon' => null,
                ],
                'discord' => [
                    'webhook_url' => '',
                    'username' => '',
                    'avatar_url' => '',
                ],
            ],
            'monitor_backups' => [
                [
                    'name' => env('APP_NAME', 'laravel-backup'),
                    'disks' => ['local'],
                    'health_checks' => [
                        \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                        \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
                    ],
                ],
            ],
            'cleanup' => [
                'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
                'default_strategy' => [
                    'keep_all_backups_for_days' => 7,
                    'keep_daily_backups_for_days' => 16,
                    'keep_weekly_backups_for_weeks' => 8,
                    'keep_monthly_backups_for_months' => 4,
                    'keep_yearly_backups_for_years' => 2,
                    'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
                ],
                'tries' => 1,
                'retry_delay' => 0,
            ],
        ];
    }
}