<div class="space-y-6">
    {{-- Información General --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3">Información General</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">ID de Ejecución:</span>
                <span class="ml-2">#{{ $execution->id }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Estado:</span>
                <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium
                    @switch($execution->status)
                        @case('pending') bg-yellow-100 text-yellow-800 @break
                        @case('in_progress') bg-blue-100 text-blue-800 @break
                        @case('completed') bg-green-100 text-green-800 @break
                        @case('failed') bg-red-100 text-red-800 @break
                        @case('cancelled') bg-gray-100 text-gray-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch
                ">
                    {{ $execution->getStatusDescription() }}
                </span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Modelo:</span>
                <span class="ml-2">{{ class_basename($execution->target_model) }} #{{ $execution->target_id }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Progreso:</span>
                <span class="ml-2">{{ $execution->getProgress() }}%</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Iniciado por:</span>
                <span class="ml-2">{{ $execution->initiator?->name ?? 'Sistema' }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Tiempo transcurrido:</span>
                <span class="ml-2">
                    @php
                        $elapsed = $execution->getElapsedTime();
                        if ($elapsed) {
                            $hours = intval($elapsed / 60);
                            $minutes = $elapsed % 60;
                            echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                        } else {
                            echo '-';
                        }
                    @endphp
                </span>
            </div>
        </div>
    </div>

    {{-- Progreso Visual --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-3">Progreso del Workflow</h3>
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $execution->getProgress() }}%"></div>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Paso {{ $execution->current_step_order ?? 0 }} de {{ $execution->workflow->stepDefinitions()->active()->count() }}
        </div>
    </div>

    {{-- Pasos del Workflow --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Pasos del Workflow</h3>
        
        @if($stepExecutions->isEmpty())
            <p class="text-gray-500 dark:text-gray-400">No hay pasos ejecutados aún.</p>
        @else
            <div class="space-y-4">
                @foreach($stepExecutions as $stepExecution)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium
                                    @switch($stepExecution->status)
                                        @case('pending') bg-yellow-100 text-yellow-800 @break
                                        @case('in_progress') bg-blue-100 text-blue-800 @break
                                        @case('completed') bg-green-100 text-green-800 @break
                                        @case('failed') bg-red-100 text-red-800 @break
                                        @case('skipped') bg-gray-100 text-gray-800 @break
                                        @case('cancelled') bg-gray-100 text-gray-800 @break
                                        @default bg-gray-100 text-gray-800
                                    @endswitch
                                ">
                                    {{ $stepExecution->stepDefinition->step_order }}
                                </div>
                                <div>
                                    <h4 class="font-medium">{{ $stepExecution->stepDefinition->step_name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $stepExecution->stepDefinition->getTypeDescription() }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    @switch($stepExecution->status)
                                        @case('pending') bg-yellow-100 text-yellow-800 @break
                                        @case('in_progress') bg-blue-100 text-blue-800 @break
                                        @case('completed') bg-green-100 text-green-800 @break
                                        @case('failed') bg-red-100 text-red-800 @break
                                        @case('skipped') bg-gray-100 text-gray-800 @break
                                        @case('cancelled') bg-gray-100 text-gray-800 @break
                                        @default bg-gray-100 text-gray-800
                                    @endswitch
                                ">
                                    {{ $stepExecution->getStatusDescription() }}
                                </span>
                            </div>
                        </div>

                        @if($stepExecution->stepDefinition->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $stepExecution->stepDefinition->description }}
                            </p>
                        @endif

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if($stepExecution->started_at)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Iniciado:</span>
                                    <span class="ml-1">{{ $stepExecution->started_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif

                            @if($stepExecution->completed_at)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Completado:</span>
                                    <span class="ml-1">{{ $stepExecution->completed_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif

                            @if($stepExecution->assignedUser)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Asignado a:</span>
                                    <span class="ml-1">{{ $stepExecution->assignedUser->name }}</span>
                                </div>
                            @endif

                            @if($stepExecution->completedByUser)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Completado por:</span>
                                    <span class="ml-1">{{ $stepExecution->completedByUser->name }}</span>
                                </div>
                            @endif

                            @if($stepExecution->due_at)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Vence:</span>
                                    <span class="ml-1 @if($stepExecution->isOverdue()) text-red-600 @endif">
                                        {{ $stepExecution->due_at->format('d/m/Y H:i') }}
                                        @if($stepExecution->isOverdue())
                                            <span class="text-xs">(Vencido)</span>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($stepExecution->comments)
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <span class="font-medium text-gray-600 dark:text-gray-400">Comentarios:</span>
                                <p class="text-sm mt-1">{{ $stepExecution->comments }}</p>
                            </div>
                        @endif

                        @if($stepExecution->notifications_sent && count($stepExecution->notifications_sent) > 0)
                            <div class="mt-3">
                                <span class="font-medium text-gray-600 dark:text-gray-400 text-sm">
                                    Notificaciones enviadas: {{ count($stepExecution->notifications_sent) }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Contexto de Ejecución --}}
    @if($execution->context_data && count($execution->context_data) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-3">Contexto de Ejecución</h3>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                <pre class="text-sm overflow-x-auto">{{ json_encode($execution->context_data, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    {{-- Resultados de Pasos --}}
    @if($execution->step_results && count($execution->step_results) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-3">Resultados de Pasos</h3>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                <pre class="text-sm overflow-x-auto">{{ json_encode($execution->step_results, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif
</div>