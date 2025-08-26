<?php

use App\Helpers\SettingsHelper;

if (!function_exists('settings')) {
    /**
     * Get settings helper instance or specific setting value
     */
    function settings(?string $key = null)
    {
        if ($key === null) {
            return new SettingsHelper();
        }

        // Handle dot notation like 'general.app_name'
        $keys = explode('.', $key);
        $settings = SettingsHelper::all();

        $value = $settings;
        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app_name')) {
    /**
     * Get the application name from settings
     */
    function app_name(): ?string
    {
        return SettingsHelper::appName();
    }
}

if (!function_exists('app_logo')) {
    /**
     * Get the application logo URL from settings
     */
    function app_logo(): ?string
    {
        return SettingsHelper::appLogo();
    }
}

if (!function_exists('dark_mode_logo')) {
    /**
     * Get the dark mode logo URL from settings
     */
    function dark_mode_logo(): ?string
    {
        return SettingsHelper::darkModeLogo();
    }
}

if (!function_exists('contact_email')) {
    /**
     * Get the contact email from settings
     */
    function contact_email(): ?string
    {
        return SettingsHelper::contactEmail();
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date using the configured format
     */
    function format_date($date): string
    {
        return SettingsHelper::formatDate($date);
    }
}

if (!function_exists('format_time')) {
    /**
     * Format a time using the configured format
     */
    function format_time($time): string
    {
        return SettingsHelper::formatTime($time);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a datetime using the configured format
     */
    function format_datetime($datetime): string
    {
        return SettingsHelper::formatDateTime($datetime);
    }
}