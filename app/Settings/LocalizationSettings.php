<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LocalizationSettings extends Settings
{
    public ?string $default_language;
    public ?string $timezone;
    public ?string $date_format;
    public ?string $time_format;
    public ?string $currency;

    public static function group(): string
    {
        return 'localization';
    }

    public static function defaults(): array
    {
        return [
            'default_language' => 'es',
            'timezone' => 'America/Mexico_City',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency' => 'MXN',
        ];
    }

    /**
     * Validation rules for the settings
     */
    public static function rules(): array
    {
        return [
            'default_language' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'timezone'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}