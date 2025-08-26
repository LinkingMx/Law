<?php

namespace App\Filament\Resources\EmailConfigurationResource\Pages;

use App\Filament\Resources\EmailConfigurationResource;
use App\Models\EmailConfiguration;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailConfiguration extends EditRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this configuration should be active, deactivate others
        if ($data['is_active'] ?? false) {
            EmailConfiguration::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver'),
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }
}
