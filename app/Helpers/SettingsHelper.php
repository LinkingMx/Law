<?php

namespace App\Helpers;

use App\Settings\AppearanceSettings;
use App\Settings\BackupSettings;
use App\Settings\GeneralSettings;
use App\Settings\LocalizationSettings;

class SettingsHelper
{
    /**
     * Get general settings instance
     */
    public static function general(): GeneralSettings
    {
        return app(GeneralSettings::class);
    }

    /**
     * Get appearance settings instance
     */
    public static function appearance(): AppearanceSettings
    {
        return app(AppearanceSettings::class);
    }

    /**
     * Get localization settings instance
     */
    public static function localization(): LocalizationSettings
    {
        return app(LocalizationSettings::class);
    }

    /**
     * Get backup settings instance
     */
    public static function backup(): BackupSettings
    {
        return app(BackupSettings::class);
    }

    /**
     * Get all settings as array
     */
    public static function all(): array
    {
        return [
            'general' => self::general()->toArray(),
            'appearance' => self::appearance()->toArray(),
            'localization' => self::localization()->toArray(),
            'backup' => self::backup()->toArray(),
        ];
    }

    /**
     * Get app name
     */
    public static function appName(): ?string
    {
        return self::general()->app_name;
    }

    /**
     * Get app description
     */
    public static function appDescription(): ?string
    {
        return self::general()->app_description;
    }

    /**
     * Get app logo URL
     */
    public static function appLogo(): ?string
    {
        return self::general()->getAppLogoUrlAttribute();
    }

    /**
     * Get dark mode logo URL
     */
    public static function darkModeLogo(): ?string
    {
        return self::appearance()->getDarkModeLogoUrlAttribute();
    }

    /**
     * Get contact email
     */
    public static function contactEmail(): ?string
    {
        return self::general()->contact_email;
    }

    /**
     * Get site URL
     */
    public static function siteUrl(): ?string
    {
        return self::general()->site_url;
    }

    /**
     * Get theme
     */
    public static function theme(): ?string
    {
        return self::appearance()->theme;
    }

    /**
     * Get primary color
     */
    public static function primaryColor(): ?string
    {
        return self::appearance()->primary_color;
    }

    /**
     * Get danger color
     */
    public static function dangerColor(): ?string
    {
        return self::appearance()->danger_color;
    }

    /**
     * Get gray color
     */
    public static function grayColor(): ?string
    {
        return self::appearance()->gray_color;
    }

    /**
     * Get info color
     */
    public static function infoColor(): ?string
    {
        return self::appearance()->info_color;
    }

    /**
     * Get success color
     */
    public static function successColor(): ?string
    {
        return self::appearance()->success_color;
    }

    /**
     * Get warning color
     */
    public static function warningColor(): ?string
    {
        return self::appearance()->warning_color;
    }

    /**
     * Get font family
     */
    public static function fontFamily(): ?string
    {
        return self::appearance()->font_family;
    }

    /**
     * Get default language
     */
    public static function defaultLanguage(): ?string
    {
        return self::localization()->default_language;
    }

    /**
     * Get timezone
     */
    public static function timezone(): ?string
    {
        return self::localization()->timezone;
    }

    /**
     * Get date format
     */
    public static function dateFormat(): ?string
    {
        return self::localization()->date_format;
    }

    /**
     * Get time format
     */
    public static function timeFormat(): ?string
    {
        return self::localization()->time_format;
    }

    /**
     * Get currency
     */
    public static function currency(): ?string
    {
        return self::localization()->currency;
    }

    /**
     * Format a date using the configured format
     */
    public static function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $timezone = self::timezone() ?: config('app.timezone', 'UTC');
        $format = self::dateFormat() ?: 'd/m/Y';

        return $date->setTimezone($timezone)->format($format);
    }

    /**
     * Format a time using the configured format
     */
    public static function formatTime($time): string
    {
        if (!$time) {
            return '';
        }

        if (is_string($time)) {
            $time = \Carbon\Carbon::parse($time);
        }

        $timezone = self::timezone() ?: config('app.timezone', 'UTC');
        $format = self::timeFormat() ?: 'H:i';

        return $time->setTimezone($timezone)->format($format);
    }

    /**
     * Format a datetime using the configured format
     */
    public static function formatDateTime($datetime): string
    {
        if (!$datetime) {
            return '';
        }

        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        $timezone = self::timezone() ?: config('app.timezone', 'UTC');
        $dateFormat = self::dateFormat() ?: 'd/m/Y';
        $timeFormat = self::timeFormat() ?: 'H:i';

        return $datetime->setTimezone($timezone)->format("{$dateFormat} {$timeFormat}");
    }

    /**
     * Get current date formatted with settings
     */
    public static function currentDate(): string
    {
        return self::formatDate(\Carbon\Carbon::now());
    }

    /**
     * Get current time formatted with settings
     */
    public static function currentTime(): string
    {
        return self::formatTime(\Carbon\Carbon::now());
    }

    /**
     * Get current datetime formatted with settings
     */
    public static function currentDateTime(): string
    {
        return self::formatDateTime(\Carbon\Carbon::now());
    }

    /**
     * Create a Carbon instance in the configured timezone
     */
    public static function now(): \Carbon\Carbon
    {
        $timezone = self::timezone() ?: config('app.timezone', 'UTC');
        return \Carbon\Carbon::now($timezone);
    }

    /**
     * Parse a date string in the configured timezone
     */
    public static function parseDate(string $date): \Carbon\Carbon
    {
        $timezone = self::timezone() ?: config('app.timezone', 'UTC');
        return \Carbon\Carbon::parse($date, $timezone);
    }

    /**
     * Format a monetary amount using the configured currency
     */
    public static function formatMoney(float $amount, bool $showSymbol = true): string
    {
        $currency = self::currency() ?: 'USD';
        
        $currencySymbols = [
            'MXN' => '$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'COP' => '$',
            'ARS' => '$',
            'BRL' => 'R$',
            'CLP' => '$',
            'PEN' => 'S/',
        ];

        $symbol = $currencySymbols[$currency] ?? $currency;
        $formattedAmount = number_format($amount, 2);

        return $showSymbol ? $symbol . ' ' . $formattedAmount : $formattedAmount;
    }

    /**
     * Get currency symbol for the configured currency
     */
    public static function getCurrencySymbol(): string
    {
        $currency = self::currency() ?: 'USD';
        
        $currencySymbols = [
            'MXN' => '$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'COP' => '$',
            'ARS' => '$',
            'BRL' => 'R$',
            'CLP' => '$',
            'PEN' => 'S/',
        ];

        return $currencySymbols[$currency] ?? $currency;
    }

    /**
     * Convert hex color to Filament color array
     */
    public static function hexToFilamentColor(string $hex): array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Generate color shades
        return [
            '50' => self::adjustBrightness($r, $g, $b, 0.95),
            '100' => self::adjustBrightness($r, $g, $b, 0.9),
            '200' => self::adjustBrightness($r, $g, $b, 0.8),
            '300' => self::adjustBrightness($r, $g, $b, 0.6),
            '400' => self::adjustBrightness($r, $g, $b, 0.4),
            '500' => sprintf('#%02x%02x%02x', $r, $g, $b), // Original color
            '600' => self::adjustBrightness($r, $g, $b, -0.1),
            '700' => self::adjustBrightness($r, $g, $b, -0.2),
            '800' => self::adjustBrightness($r, $g, $b, -0.3),
            '900' => self::adjustBrightness($r, $g, $b, -0.4),
            '950' => self::adjustBrightness($r, $g, $b, -0.5),
        ];
    }

    /**
     * Adjust brightness of RGB color
     */
    private static function adjustBrightness(int $r, int $g, int $b, float $factor): string
    {
        if ($factor > 0) {
            // Lighten
            $r = min(255, $r + (255 - $r) * $factor);
            $g = min(255, $g + (255 - $g) * $factor);
            $b = min(255, $b + (255 - $b) * $factor);
        } else {
            // Darken
            $factor = abs($factor);
            $r = max(0, $r * (1 - $factor));
            $g = max(0, $g * (1 - $factor));
            $b = max(0, $b * (1 - $factor));
        }
        
        return sprintf('#%02x%02x%02x', (int)$r, (int)$g, (int)$b);
    }

    /**
     * Get Filament colors based on appearance settings
     */
    public static function getFilamentColors(): array
    {
        $appearance = self::appearance();
        
        return [
            'primary' => self::hexToFilamentColor($appearance->primary_color ?? '#f59e0b'),
            'danger' => self::hexToFilamentColor($appearance->danger_color ?? '#ef4444'),
            'gray' => self::hexToFilamentColor($appearance->gray_color ?? '#71717a'),
            'info' => self::hexToFilamentColor($appearance->info_color ?? '#3b82f6'),
            'success' => self::hexToFilamentColor($appearance->success_color ?? '#10b981'),
            'warning' => self::hexToFilamentColor($appearance->warning_color ?? '#f59e0b'),
        ];
    }
}