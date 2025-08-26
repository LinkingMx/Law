<?php

namespace App\Providers;

use App\Models\EmailConfiguration;
use App\Settings\LocalizationSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentExceptions\Models\Exception as FilamentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply localization settings
        $this->applyLocalizationSettings();

        // Apply active email configuration if available
        if (Schema::hasTable('email_configurations')) {
            try {
                $activeConfig = EmailConfiguration::getActive();
                if ($activeConfig) {
                    $activeConfig->applyConfiguration();
                }
            } catch (\Exception $e) {
                // Silently fail if there's an issue with email configuration
                // This prevents the app from breaking during development
            }
        }

        // Register backup event listeners
        $this->registerBackupEventListeners();

        // Register exception reporting for Filament Exceptions
        $this->registerExceptionReporting();
    }

    /**
     * Apply localization settings to the application
     */
    protected function applyLocalizationSettings(): void
    {
        // Check if settings table exists to prevent errors during migration
        if (!Schema::hasTable('settings')) {
            return;
        }

        try {
            $localizationSettings = app(LocalizationSettings::class);

            // Apply timezone
            if ($localizationSettings->timezone) {
                config(['app.timezone' => $localizationSettings->timezone]);
                date_default_timezone_set($localizationSettings->timezone);
            }

            // Apply locale
            if ($localizationSettings->default_language) {
                config(['app.locale' => $localizationSettings->default_language]);
                app()->setLocale($localizationSettings->default_language);
            }

        } catch (\Exception $e) {
            // Silently fail if there's an issue with localization settings
            // This prevents the app from breaking during development or if settings are not yet configured
        }
    }

    /**
     * Register backup event listeners
     */
    protected function registerBackupEventListeners(): void
    {
        // Listen to backup events and trigger our notification system
        \Illuminate\Support\Facades\Event::listen(
            \Spatie\Backup\Events\BackupWasSuccessful::class,
            [\App\Listeners\BackupEventListener::class, 'handleBackupSuccessful']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Spatie\Backup\Events\BackupHasFailed::class,
            [\App\Listeners\BackupEventListener::class, 'handleBackupFailed']
        );
    }

    /**
     * Register exception reporting for Filament Exceptions
     */
    protected function registerExceptionReporting(): void
    {
        if (!Schema::hasTable('filament_exceptions')) {
            return;
        }

        // Register the global exception handler
        $this->app->singleton('filament-exceptions', function () {
            return new class {
                public function report(\Throwable $exception): void
                {
                    try {
                        FilamentException::createFromThrowable($exception);
                    } catch (\Exception $e) {
                        // Silently fail to prevent recursive exceptions
                    }
                }
            };
        });
    }
}
