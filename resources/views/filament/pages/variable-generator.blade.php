<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-primary-900 dark:text-primary-100">
                        Generador de Variables
                    </h2>
                    <p class="text-sm text-primary-700 dark:text-primary-300">
                        Crea variables personalizadas de forma fácil e intuitiva
                    </p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-gray-700 dark:text-gray-300">Selector inteligente de variables</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span class="text-gray-700 dark:text-gray-300">Preview en tiempo real</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    <span class="text-gray-700 dark:text-gray-300">Configuración paso a paso</span>
                </div>
            </div>
        </div>
        
        <form wire:submit="create">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page>