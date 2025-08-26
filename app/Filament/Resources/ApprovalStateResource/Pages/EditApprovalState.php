<?php

namespace App\Filament\Resources\ApprovalStateResource\Pages;

use App\Filament\Resources\ApprovalStateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalState extends EditRecord
{
    protected static string $resource = ApprovalStateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
