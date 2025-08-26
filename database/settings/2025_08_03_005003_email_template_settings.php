<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email_templates.enabled', true);
        $this->migrator->add('email_templates.default_language', 'es');
        $this->migrator->add('email_templates.available_languages', [
            'es' => 'Español',
            'en' => 'English'
        ]);
        $this->migrator->add('email_templates.default_from_name', 'SaaS Helpdesk');
        $this->migrator->add('email_templates.default_from_email', 'noreply@saashelpdesk.test');
        $this->migrator->add('email_templates.auto_wrap_content', true);
        $this->migrator->add('email_templates.template_wrapper', 'emails.wrapper');
        $this->migrator->add('email_templates.global_variables', [
            'app_name' => 'SaaS Helpdesk',
            'app_url' => 'http://saashelpdesk.test',
            'contact_email' => 'support@saashelpdesk.test',
            'support_url' => 'http://saashelpdesk.test/support',
        ]);
    }
};
