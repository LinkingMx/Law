<?php

use App\Settings\BackupSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dynamic backup scheduling based on settings
Schedule::command('backup:scheduled')
    ->when(function () {
        try {
            $settings = app(BackupSettings::class);
            return $settings->schedule_enabled;
        } catch (\Exception $e) {
            \Log::error('Error checking backup schedule settings: ' . $e->getMessage());
            return false;
        }
    })
    ->hourly()
    ->then(function () {
        // Check if this execution should actually run based on frequency and time
        try {
            $settings = app(BackupSettings::class);
            
            if (!$settings->schedule_enabled) {
                return;
            }
            
            $now = now();
            $scheduleTime = $settings->schedule_time ? 
                \Carbon\Carbon::createFromFormat('H:i', $settings->schedule_time) : 
                \Carbon\Carbon::createFromTime(2, 0); // Default to 2 AM
            
            // Only run if current hour matches scheduled hour
            if ($now->hour !== $scheduleTime->hour) {
                return;
            }
            
            // Check frequency requirements
            $shouldRun = match($settings->schedule_frequency) {
                'daily' => true,
                'weekly' => $now->dayOfWeek === 1, // Monday
                'monthly' => $now->day === 1, // First day of month
                default => false,
            };
            
            if (!$shouldRun) {
                return;
            }
            
            \Log::info('Executing scheduled backup: ' . $settings->schedule_frequency);
            
        } catch (\Exception $e) {
            \Log::error('Error in backup schedule execution: ' . $e->getMessage());
        }
    });

// Backup monitoring - check backup health daily
Schedule::command('backup:monitor')
    ->dailyAt('06:00')
    ->when(function () {
        try {
            $settings = app(BackupSettings::class);
            return $settings->notifications_enabled;
        } catch (\Exception $e) {
            return false;
        }
    });

// Cleanup old backups weekly
Schedule::command('backup:clean')
    ->weeklyOn(1, '03:00') // Every Monday at 3 AM
    ->when(function () {
        try {
            $settings = app(BackupSettings::class);
            return $settings->delete_old_backups_enabled;
        } catch (\Exception $e) {
            return false;
        }
    });
