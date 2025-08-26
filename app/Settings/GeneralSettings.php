<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $app_name;
    public ?string $app_description;
    public ?string $app_logo;
    public ?string $contact_email;
    public ?string $site_url;

    public static function group(): string
    {
        return 'general';
    }

    public static function defaults(): array
    {
        return [
            'app_name' => 'SaaS Helpdesk',
            'app_description' => 'Sistema de gestión de helpdesk y soporte técnico',
            'app_logo' => null,
            'contact_email' => 'support@example.com',
            'site_url' => config('app.url'),
        ];
    }

    /**
     * Get the app logo URL with fallback
     */
    public function getAppLogoUrlAttribute(): ?string
    {
        if (!$this->app_logo) {
            return asset('logo.svg'); // Fallback to default logo
        }

        return asset('storage/' . $this->app_logo);
    }

    /**
     * Validation rules for the settings
     */
    public static function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'app_description' => ['nullable', 'string', 'max:500'],
            'app_logo' => ['nullable', 'string'],
            'contact_email' => ['required', 'email', 'max:255'],
            'site_url' => ['required', 'url', 'max:255'],
        ];
    }
}