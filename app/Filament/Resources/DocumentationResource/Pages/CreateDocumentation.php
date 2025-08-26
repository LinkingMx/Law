<?php

namespace App\Filament\Resources\DocumentationResource\Pages;

use App\Filament\Resources\DocumentationResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentation extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    
    protected static string $resource = DocumentationResource::class;
    
    protected function getSteps(): array
    {
        return [
            Wizard\Step::make('Información Básica')
                ->description('Información principal del documento')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Ingrese un título descriptivo para el documento'),
                        
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('Proporcione una descripción detallada del documento'),
                ]),
                
            Wizard\Step::make('Asignación de Sucursales')
                ->description('Seleccione las sucursales a las que aplica')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\Select::make('branches')
                        ->label('Sucursales')
                        ->relationship('branches', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Seleccione una o más sucursales a las que aplica este documento')
                        ->columnSpanFull()
                        ->placeholder('Buscar y seleccionar sucursales...')
                        ->optionsLimit(50)
                        ->loadingMessage('Cargando sucursales...')
                        ->noSearchResultsMessage('No se encontraron sucursales')
                        ->searchPrompt('Escriba para buscar sucursales'),
                ]),
                
            Wizard\Step::make('Confirmación')
                ->description('Revisar información antes de crear')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Forms\Components\Placeholder::make('confirmation')
                        ->label('Resumen del Documento')
                        ->content(function (callable $get): string {
                            $title = $get('title') ?? 'Sin título';
                            $description = $get('description') ?? 'Sin descripción';
                            $branches = $get('branches') ?? [];
                            
                            $branchNames = '';
                            if (!empty($branches)) {
                                $branchModels = \App\Models\Branch::whereIn('id', $branches)->pluck('name');
                                $branchNames = $branchModels->implode(', ');
                            }
                            
                            return "
                                **Título:** {$title}
                                
                                **Descripción:** {$description}
                                
                                **Sucursales:** " . ($branchNames ?: 'Ninguna seleccionada') . "
                                
                                El documento se creará en estado **Borrador** y podrá ser enviado para aprobación posteriormente.
                            ";
                        })
                        ->columnSpanFull(),
                        
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('preview')
                            ->label('Vista Previa')
                            ->icon('heroicon-o-eye')
                            ->color('gray')
                            ->action(function (callable $get) {
                                // Aquí podrías implementar una vista previa si es necesario
                                \Filament\Notifications\Notification::make()
                                    ->title('Vista previa no disponible')
                                    ->body('La funcionalidad de vista previa se implementará próximamente.')
                                    ->info()
                                    ->send();
                            }),
                    ]),
                ]),
        ];
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que el documento se crea en draft y con el usuario actual
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Documento creado exitosamente';
    }
    
    protected function getCreatedNotificationBody(): ?string
    {
        return 'El documento ha sido creado en estado borrador y está listo para ser enviado para aprobación.';
    }
}
