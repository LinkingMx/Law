<div class="space-y-4">
    @if($record->hasFile())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Información básica -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Información del Archivo
                </h3>
                
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-document class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Nombre:</span>
                        <span class="text-sm font-medium">{{ $record->file_name }}</span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-tag class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Tipo:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $record->getFileTypeColor() }}-100 text-{{ $record->getFileTypeColor() }}-800">
                            {{ strtoupper($record->file_extension) }}
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-scale class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Tamaño:</span>
                        <span class="text-sm font-medium">{{ $record->getFormattedFileSize() }}</span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-code-bracket class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">MIME:</span>
                        <span class="text-sm text-gray-500">{{ $record->mime_type }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Información de carga -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Información de Carga
                </h3>
                
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-user class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Subido por:</span>
                        <span class="text-sm font-medium">
                            {{ $record->uploader?->name ?? 'Sistema' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Fecha de carga:</span>
                        <span class="text-sm font-medium">
                            {{ $record->uploaded_at ? $record->uploaded_at->format('d/m/Y H:i') : 'No disponible' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-folder class="w-4 h-4 text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ruta:</span>
                        <span class="text-xs text-gray-500 font-mono break-all">
                            {{ $record->file_path }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metadatos adicionales -->
        @if($record->file_metadata && count($record->file_metadata) > 0)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    Metadatos Adicionales
                </h3>
                
                <div class="grid grid-cols-1 gap-2">
                    @foreach($record->file_metadata as $key => $value)
                        <div class="flex items-start space-x-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Vista previa para imágenes -->
        @if(in_array($record->file_extension, ['jpg', 'jpeg', 'png', 'gif']))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    Vista Previa
                </h3>
                
                <div class="flex justify-center">
                    <img 
                        src="{{ $record->getFileUrl() }}" 
                        alt="{{ $record->file_name }}"
                        class="max-w-full max-h-64 object-contain rounded-lg shadow-sm"
                    >
                </div>
            </div>
        @endif
        
        <!-- Acciones rápidas -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-2">
                @if($record->hasFile())
                    <a 
                        href="{{ route('filament.admin.resources.documents.download', $record) }}" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1" />
                        Descargar
                    </a>
                    
                    @if(in_array($record->file_extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif']))
                        <a 
                            href="{{ $record->getFileUrl() }}" 
                            target="_blank"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                            Ver
                        </a>
                    @endif
                @endif
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-document-minus class="w-12 h-12 mx-auto text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">
                Este documento no tiene archivo adjunto.
            </p>
        </div>
    @endif
</div>