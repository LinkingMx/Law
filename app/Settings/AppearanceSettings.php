<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppearanceSettings extends Settings
{
    public ?string $theme;
    public ?string $primary_color;
    public ?string $danger_color;
    public ?string $gray_color;
    public ?string $info_color;
    public ?string $success_color;
    public ?string $warning_color;
    public ?string $font_family;
    public ?string $dark_mode_logo;

    public static function group(): string
    {
        return 'appearance';
    }

    public static function defaults(): array
    {
        return [
            'theme' => 'light',
            'primary_color' => '#f59e0b', // Amber
            'danger_color' => '#ef4444', // Red
            'gray_color' => '#71717a', // Zinc
            'info_color' => '#3b82f6', // Blue
            'success_color' => '#10b981', // Green
            'warning_color' => '#f59e0b', // Amber
            'font_family' => 'Inter',
            'dark_mode_logo' => null,
        ];
    }

    /**
     * Get the dark mode logo URL with fallback
     */
    public function getDarkModeLogoUrlAttribute(): ?string
    {
        if (!$this->dark_mode_logo) {
            // Fallback to general app logo
            $generalSettings = app(\App\Settings\GeneralSettings::class);
            return $generalSettings->getAppLogoUrlAttribute();
        }

        return asset('storage/' . $this->dark_mode_logo);
    }

    /**
     * Validation rules for the settings
     */
    public static function rules(): array
    {
        return [
            'theme' => ['required', 'in:light,dark,auto'],
            'primary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'danger_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'gray_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'info_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'success_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'warning_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'font_family' => ['required', 'string', 'max:100'],
            'dark_mode_logo' => ['nullable', 'string'],
        ];
    }
}