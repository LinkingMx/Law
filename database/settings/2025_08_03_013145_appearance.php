<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('appearance.theme', 'light');
        $this->migrator->add('appearance.primary_color', '#3b82f6');
        $this->migrator->add('appearance.secondary_color', '#6b7280');
        $this->migrator->add('appearance.font_family', 'Inter');
        $this->migrator->add('appearance.dark_mode_logo', null);
    }
};
