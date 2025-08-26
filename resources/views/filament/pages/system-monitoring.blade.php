<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            📊 Monitoreo del Sistema en Tiempo Real
        </x-slot>
        
        <x-slot name="description">
            Supervise el rendimiento, cache, colas y excepciones de su aplicación en tiempo real.
        </x-slot>

        <div class="grid gap-6">
            @foreach($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>