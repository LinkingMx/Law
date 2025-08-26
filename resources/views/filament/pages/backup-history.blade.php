<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Info Banner -->
        <x-filament::section
            icon="heroicon-o-information-circle"
            icon-color="primary"
        >
            <x-slot name="description">
                Aquí puedes ver todos los backups disponibles, descargarlos o eliminarlos. Los backups se muestran desde el más reciente al más antiguo.
            </x-slot>
        </x-filament::section>

        <!-- Backups Information -->
        {{ $this->backupsInfolist }}
    </div>
</x-filament-panels::page>