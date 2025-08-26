<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;
    
    protected static ?string $title = 'Ver Sucursal';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil-square'),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->successRedirectUrl(BranchResource::getUrl('index'))
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->icon('heroicon-o-trash')
                        ->title('Sucursal eliminada')
                        ->body('La sucursal ha sido eliminada exitosamente.')
                        ->duration(5000)
                ),
        ];
    }
}