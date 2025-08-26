<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Info Banner -->
        <x-filament::section
            icon="heroicon-o-information-circle"
            icon-color="primary"
        >
            <x-slot name="description">
                Panel de control principal para la gestión de backups. Ejecuta backups, limpia archivos antiguos y consulta estadísticas en tiempo real.
            </x-slot>
        </x-filament::section>

        <!-- Statistics Information -->
        {{ $this->statisticsInfolist }}
    </div>
</x-filament-panels::page>