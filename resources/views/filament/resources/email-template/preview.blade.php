<div class="space-y-4">
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Información del Template</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Clave:</span> {{ $template->key }}
            </div>
            <div>
                <span class="font-medium">Modelo:</span> {{ $template->model_type ? class_basename($template->model_type) : 'Sin modelo específico' }}
            </div>
            <div>
                <span class="font-medium">Idioma:</span> {{ $template->language }}
            </div>
            <div>
                <span class="font-medium">Estado:</span> 
                <span class="px-2 py-1 rounded-full text-xs {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $template->is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Asunto</h3>
        <p class="text-sm">{{ $template->subject }}</p>
    </div>

    <div class="p-4 bg-white dark:bg-gray-900 border rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Vista Previa del Contenido</h3>
        <div class="prose prose-sm max-w-none dark:prose-invert">
            {!! $template->content !!}
        </div>
    </div>

    @if($template->variables && count($template->variables) > 0)
    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Variables Personalizadas</h3>
        <div class="space-y-1 text-sm">
            @foreach($template->variables as $key => $description)
                <div>
                    <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{ '{' }}{{{ $key }}}{{ '}' }}</code>
                    - {{ $description }}
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Variables Comunes Disponibles</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{app_name}{{ '}' }}</code> - Nombre de la aplicación</div>
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{app_url}{{ '}' }}</code> - URL de la aplicación</div>
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{user_name}{{ '}' }}</code> - Nombre del usuario</div>
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{user_email}{{ '}' }}</code> - Email del usuario</div>
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{current_date}{{ '}' }}</code> - Fecha actual</div>
            <div><code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ '{' }}{contact_email}{{ '}' }}</code> - Email de contacto</div>
        </div>
    </div>
</div>