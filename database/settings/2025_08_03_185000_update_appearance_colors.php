<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Remove secondary_color if it exists
        if ($this->migrator->exists('appearance.secondary_color')) {
            $this->migrator->delete('appearance.secondary_color');
        }
        
        // Add new Filament color fields with default values (only if they don't exist)
        if (!$this->migrator->exists('appearance.danger_color')) {
            $this->migrator->add('appearance.danger_color', '#ef4444');
        }
        if (!$this->migrator->exists('appearance.gray_color')) {
            $this->migrator->add('appearance.gray_color', '#71717a');
        }
        if (!$this->migrator->exists('appearance.info_color')) {
            $this->migrator->add('appearance.info_color', '#3b82f6');
        }
        if (!$this->migrator->exists('appearance.success_color')) {
            $this->migrator->add('appearance.success_color', '#10b981');
        }
        if (!$this->migrator->exists('appearance.warning_color')) {
            $this->migrator->add('appearance.warning_color', '#f59e0b');
        }
        
        // Update primary_color default to match new defaults
        $this->migrator->update('appearance.primary_color', fn($value) => '#f59e0b');
    }
    
    public function down(): void
    {
        // Add back secondary_color
        $this->migrator->add('appearance.secondary_color', '#6b7280');
        
        // Remove new color fields
        $this->migrator->delete('appearance.danger_color');
        $this->migrator->delete('appearance.gray_color');
        $this->migrator->delete('appearance.info_color');
        $this->migrator->delete('appearance.success_color');
        $this->migrator->delete('appearance.warning_color');
        
        // Revert primary_color to original default
        $this->migrator->update('appearance.primary_color', fn($value) => '#3b82f6');
    }
};