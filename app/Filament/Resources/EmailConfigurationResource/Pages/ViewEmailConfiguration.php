<?php

namespace App\Filament\Resources\EmailConfigurationResource\Pages;

use App\Filament\Resources\EmailConfigurationResource;
use App\Mail\TestEmail;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewEmailConfiguration extends ViewRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detalles de la Configuración')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nombre'),
                                    
                                TextEntry::make('driver')
                                    ->label('Proveedor')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'smtp' => 'gray',
                                        'mailgun' => 'warning',
                                        'postmark' => 'success',
                                        'ses' => 'info',
                                        'sendmail' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),
                    
                Section::make('Configuración de Conexión')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('settings.host')
                                    ->label('Servidor SMTP')
                                    ->visible(fn ($record): bool => $record->driver === 'smtp'),
                                    
                                TextEntry::make('settings.port')
                                    ->label('Puerto')
                                    ->visible(fn ($record): bool => $record->driver === 'smtp'),
                                    
                                TextEntry::make('settings.encryption')
                                    ->label('Encriptación')
                                    ->visible(fn ($record): bool => $record->driver === 'smtp'),
                                    
                                TextEntry::make('settings.username')
                                    ->label('Usuario')
                                    ->visible(fn ($record): bool => $record->driver === 'smtp'),
                                    
                                TextEntry::make('settings.domain')
                                    ->label('Dominio')
                                    ->visible(fn ($record): bool => $record->driver === 'mailgun'),
                                    
                                TextEntry::make('settings.endpoint')
                                    ->label('Endpoint')
                                    ->visible(fn ($record): bool => $record->driver === 'mailgun'),
                                    
                                TextEntry::make('settings.key')
                                    ->label('Access Key ID')
                                    ->visible(fn ($record): bool => $record->driver === 'ses'),
                                    
                                TextEntry::make('settings.region')
                                    ->label('Región')
                                    ->visible(fn ($record): bool => $record->driver === 'ses'),
                                    
                                TextEntry::make('settings.path')
                                    ->label('Ruta de Sendmail')
                                    ->visible(fn ($record): bool => $record->driver === 'sendmail'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('settings.password')
                                    ->label('Contraseña')
                                    ->formatStateUsing(fn (?string $state): string => $state ? '••••••••' : 'No configurada')
                                    ->visible(fn ($record): bool => in_array($record->driver, ['smtp'])),
                                    
                                TextEntry::make('settings.secret')
                                    ->label(fn ($record): string => match ($record->driver) {
                                        'mailgun' => 'API Key',
                                        'ses' => 'Secret Access Key',
                                        default => 'Secret'
                                    })
                                    ->formatStateUsing(fn (?string $state): string => $state ? '••••••••' : 'No configurado')
                                    ->visible(fn ($record): bool => in_array($record->driver, ['mailgun', 'ses'])),
                                    
                                TextEntry::make('settings.token')
                                    ->label('Server Token')
                                    ->formatStateUsing(fn (?string $state): string => $state ? '••••••••' : 'No configurado')
                                    ->visible(fn ($record): bool => $record->driver === 'postmark'),
                            ]),
                    ]),
                    
                Section::make('Configuración del Remitente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('settings.from_address')
                                    ->label('Email del remitente'),
                                    
                                TextEntry::make('settings.from_name')
                                    ->label('Nombre del remitente'),
                            ]),
                    ]),
                    
                Section::make('Información de Estado')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Estado')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                    
                                TextEntry::make('last_tested_at')
                                    ->label('Última prueba')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Nunca'),
                                    
                                TextEntry::make('created_at')
                                    ->label('Creada')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test')
                ->label('Probar Email')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('test_email')
                        ->label('Email de prueba')
                        ->email()
                        ->required()
                        ->placeholder('test@example.com'),
                ])
                ->action(function (array $data): void {
                    try {
                        // Apply configuration temporarily
                        $this->record->applyConfiguration();
                        
                        // Send test email
                        Mail::to($data['test_email'])->send(new TestEmail(
                            'Este es un email de prueba enviado desde la configuración "' . $this->record->name . '". Si recibes este mensaje, la configuración está funcionando correctamente.'
                        ));
                        
                        // Mark as tested
                        $this->record->markAsTested();
                        
                        Notification::make()
                            ->success()
                            ->title('Email enviado correctamente')
                            ->body("El email de prueba ha sido enviado a {$data['test_email']}")
                            ->send();
                            
                        // Refresh the page to show updated last_tested_at
                        $this->redirect(static::getUrl(['record' => $this->record]));
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body('No se pudo enviar el email de prueba: ' . $e->getMessage())
                            ->send();
                    }
                }),
                
            Actions\Action::make('toggle_active')
                ->label(fn (): string => $this->record->is_active ? 'Desactivar' : 'Activar')
                ->icon(fn (): string => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->record->is_active ? 'danger' : 'success')
                ->action(function (): void {
                    if ($this->record->is_active) {
                        $this->record->update(['is_active' => false]);
                        
                        Notification::make()
                            ->success()
                            ->title('Configuración desactivada')
                            ->body('La configuración ha sido desactivada correctamente.')
                            ->send();
                    } else {
                        $this->record->activate();
                        
                        Notification::make()
                            ->success()
                            ->title('Configuración activada')
                            ->body('La configuración ha sido activada correctamente.')
                            ->send();
                    }
                    
                    // Refresh the page to show updated status
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),
                
            Actions\EditAction::make()
                ->label('Editar'),
        ];
    }
}
