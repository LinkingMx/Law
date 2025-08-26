<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('localization.default_language', 'es');
        $this->migrator->add('localization.timezone', 'America/Mexico_City');
        $this->migrator->add('localization.date_format', 'd/m/Y');
        $this->migrator->add('localization.time_format', 'H:i');
        $this->migrator->add('localization.currency', 'MXN');
    }
};
