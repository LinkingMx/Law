<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_name', 'SaaS Helpdesk');
        $this->migrator->add('general.app_description', 'Sistema de gestión de helpdesk y soporte técnico');
        $this->migrator->add('general.app_logo', null);
        $this->migrator->add('general.contact_email', 'support@example.com');
        $this->migrator->add('general.site_url', config('app.url'));
    }
};
