<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->icon('heroicon-o-building-office')
            ->title('Sucursal creada exitosamente')
            ->body('La sucursal ha sido registrada en el sistema correctamente.')
            ->duration(5000)
            ->send();
    }
}
