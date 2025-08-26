<div class="space-y-8 max-h-[80vh] overflow-y-auto">
    <div class="text-center mb-8">
        <div class="flex items-center justify-center mb-3">
            <x-heroicon-o-clipboard-document-list class="h-8 w-8 text-primary-600 mr-2.5" />
            <h2 class="text-2xl font-bold">Guía de Variables para Templates</h2>
        </div>
        <p class="mt-3 text-sm opacity-75">
            Documentación completa sobre las variables disponibles y cómo usarlas en tus templates de email
        </p>
    </div>

    <!-- Resumen Rápido -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <x-heroicon-o-sparkles class="h-5 w-5 mr-2 text-blue-600" />
            Resumen Rápido
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="bg-blue-100 dark:bg-blue-800 rounded-full p-3 w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                    <x-heroicon-o-globe-alt class="h-6 w-6 text-blue-600 dark:text-blue-300" />
                </div>
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">Variables Globales</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">Siempre disponibles en todos los templates</p>
            </div>
            <div class="text-center">
                <div class="bg-green-100 dark:bg-green-800 rounded-full p-3 w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                    <x-heroicon-o-bolt class="h-6 w-6 text-green-600 dark:text-green-300" />
                </div>
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">Variables Comunes</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">Se generan automáticamente (fecha, usuario)</p>
            </div>
            <div class="text-center">
                <div class="bg-amber-100 dark:bg-amber-800 rounded-full p-3 w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                    <x-heroicon-o-tag class="h-6 w-6 text-amber-600 dark:text-amber-300" />
                </div>
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">Variables de Categoría</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">Específicas del tipo de template</p>
            </div>
        </div>
        <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-blue-500">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Consejo:</strong> Para la mayoría de casos, usa la categoría <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">model</code> que se adapta automáticamente a cualquier modelo de tu aplicación.
            </p>
        </div>
    </div>

    <!-- Ejemplo Práctico: Bank Model -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4 flex items-center">
            <x-heroicon-o-academic-cap class="h-5 w-5 mr-1.5" />
            Ejemplo Práctico: Modelo Bank
            <span class="ml-2 text-xs bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded-full">
                Caso de uso real
            </span>
        </h3>
        <p class="text-sm text-emerald-800 dark:text-emerald-200 mb-4">
            Supongamos que tienes un modelo Bank con campos <code class="bg-emerald-200 dark:bg-emerald-700 px-1 rounded">code</code> y <code class="bg-emerald-200 dark:bg-emerald-700 px-1 rounded">name</code>, y quieres notificar cuando se crea un nuevo banco.
        </p>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Código PHP -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <x-heroicon-o-code-bracket class="h-4 w-4 mr-1.5" />
                    Código PHP (en tu Controller/Observer)
                </h4>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-xs font-mono overflow-x-auto"><code class="text-gray-800 dark:text-gray-200">// Cuando se crea un banco
$bank = Bank::create([
    'code' => 'BCP001', 
    'name' => 'Banco de Crédito del Perú'
]);

// Preparar datos para el template
$emailService = app(EmailTemplateService::class);
$modelData = [
    'model_name' => 'Banco',
    'model_id' => $bank->id,
    'model_title' => $bank->name,
    'action_type' => 'crear',
    'action_user' => auth()->user()->name,
    'record_url' => route('admin.banks.show', $bank),
];

// Procesar template
$variables = $emailService->getModelVariables($modelData);
$template = $emailService->processTemplate('bank-created', $variables);</code></pre>
            </div>
            
            <!-- Template Example -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <x-heroicon-o-document-text class="h-4 w-4 mr-1.5" />
                    Template de Email
                </h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Asunto:</label>
                        <code class="block bg-gray-100 dark:bg-gray-900 p-2 rounded text-xs mt-1">
                            Nuevo @php echo '{{model_name}}'; @endphp creado: @php echo '{{model_title}}'; @endphp
                        </code>
                        <p class="text-xs text-gray-500 mt-1">Resultado: "Nuevo Banco creado: Banco de Crédito del Perú"</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Contenido:</label>
                        <code class="block bg-gray-100 dark:bg-gray-900 p-2 rounded text-xs mt-1">
                            &lt;h2&gt;@php echo '{{action_type}}'; @endphp realizada&lt;/h2&gt;<br>
                            &lt;p&gt;El @php echo '{{model_name}}'; @endphp &lt;strong&gt;@php echo '{{model_title}}'; @endphp&lt;/strong&gt; ha sido @php echo '{{action_type}}'; @endphp por @php echo '{{action_user}}'; @endphp.&lt;/p&gt;<br>
                            &lt;p&gt;Fecha: @php echo '{{action_date}}'; @endphp&lt;/p&gt;<br>
                            &lt;a href="@php echo '{{record_url}}'; @endphp"&gt;Ver registro&lt;/a&gt;
                        </code>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-emerald-100 dark:bg-emerald-800/30 rounded-lg">
            <div class="flex items-start space-x-2">
                <x-heroicon-o-light-bulb class="h-5 w-5 text-emerald-600 mt-0.5 mr-1.5" />
                <div>
                    <p class="text-sm text-emerald-900 dark:text-emerald-100">
                        <strong>¿Por qué funciona esto?</strong> El sistema usa la categoría <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">model</code> que es genérica y se adapta a cualquier modelo. Las variables como <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">model_name</code>, <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">model_title</code> se llenan automáticamente con los datos que pases.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Variables Globales -->
    <div class="rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <x-heroicon-o-globe-alt class="h-5 w-5 mr-1.5" />
            Variables Globales
            <span class="ml-2 text-xs px-2 py-1 rounded-full border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                Siempre disponibles
            </span>
        </h3>
        <p class="text-sm mb-4">
            Estas variables están disponibles en todos los templates, independientemente de la categoría. Se obtienen automáticamente de la configuración del sistema.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($globalVariables as $key => $description)
            <div class="flex items-start space-x-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex-shrink-0">
                    <code class="px-2 py-1 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        @php echo '{{' . $key . '}}'; @endphp
                    </code>
                </div>
                <div>
                    <p class="text-sm">{{ $description }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Variables Comunes -->
    <div class="rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <x-heroicon-o-bolt class="h-5 w-5 mr-1.5" />
            Variables Comunes
            <span class="ml-2 text-xs px-2 py-1 rounded-full border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                Generadas automáticamente
            </span>
        </h3>
        <p class="text-sm mb-4">
            Variables que se generan automáticamente cuando se procesa un template. Incluyen información de fecha/hora actual y del usuario autenticado.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($commonVariables as $key => $description)
            <div class="flex items-start space-x-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex-shrink-0">
                    <code class="px-2 py-1 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        @php echo '{{' . $key . '}}'; @endphp
                    </code>
                </div>
                <div>
                    <p class="text-sm">{{ $description }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Variables por Categoría -->
    @foreach($categoryVariables as $category => $variables)
    @if(!empty($variables))
    <div class="rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            @switch($category)
                @case('backup')
                    <x-heroicon-o-server class="h-5 w-5 mr-1.5" />
                    Variables de Respaldo
                    @break
                @case('user')
                    <x-heroicon-o-user class="h-5 w-5 mr-1.5" />
                    Variables de Usuario
                    @break
                @case('system')
                    <x-heroicon-o-cog-6-tooth class="h-5 w-5 mr-1.5" />
                    Variables de Sistema
                    @break
                @case('model')
                    <x-heroicon-o-table-cells class="h-5 w-5 mr-1.5" />
                    Variables de Modelos/CRUD
                    @break
                @case('ticket')
                    <x-heroicon-o-ticket class="h-5 w-5 mr-1.5" />
                    Variables de Tickets
                    @break
                @case('order')
                    <x-heroicon-o-shopping-cart class="h-5 w-5 mr-1.5" />
                    Variables de Pedidos
                    @break
                @case('invoice')
                    <x-heroicon-o-document-text class="h-5 w-5 mr-1.5" />
                    Variables de Facturas
                    @break
                @case('notification')
                    <x-heroicon-o-bell class="h-5 w-5 mr-1.5" />
                    Variables de Notificación
                    @break
                @default
                    <x-heroicon-o-folder class="h-5 w-5 mr-1.5" />
                    Variables de {{ ucfirst($category) }}
            @endswitch
            <span class="ml-2 text-xs px-2 py-1 rounded-full border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                Categoría: {{ ucfirst($category) }}
            </span>
        </h3>
        @switch($category)
            @case('backup')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan automáticamente usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getBackupVariables($backupData)</code>. 
                        Incluye información del proceso de respaldo, tamaño, estado y errores.
                    </p>
                </div>
                @break
            @case('user')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getUserVariables($userData)</code>. 
                        Perfectas para emails de bienvenida, verificación, reset de contraseña y notificaciones de usuario.
                    </p>
                </div>
                @break
            @case('system')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getSystemVariables($systemData)</code>. 
                        Para notificaciones de mantenimiento, errores del sistema y actualizaciones de estado.
                    </p>
                </div>
                @break
            @case('model')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getModelVariables($modelData)</code>. 
                        Perfectas para cualquier modelo: Bank, Product, Client, etc. Se adaptan automáticamente al nombre y datos del modelo.
                    </p>
                    <div class="mt-2 text-xs">
                        <strong>Ejemplo:</strong> Para el modelo Bank → <code>model_name</code> = "Banco", <code>model_title</code> = "Banco de Crédito del Perú"
                    </div>
                </div>
                @break
            @case('ticket')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getTicketVariables($ticketData)</code>. 
                        Para sistema de tickets/soporte - incluye número, prioridad, asignación y URLs de gestión.
                    </p>
                </div>
                @break
            @case('order')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getOrderVariables($orderData)</code>. 
                        Para e-commerce - incluye totales, cliente, fechas de entrega y métodos de pago.
                    </p>
                </div>
                @break
            @case('invoice')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getInvoiceVariables($invoiceData)</code>. 
                        Para facturación - incluye montos, fechas de vencimiento y enlaces de pago.
                    </p>
                </div>
                @break
            @case('notification')
                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/20">
                    <p class="text-sm">
                        <strong>Uso:</strong> Se pasan usando <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">getNotificationVariables($notificationData)</code>. 
                        Para notificaciones generales del sistema con título, mensaje y acciones.
                    </p>
                </div>
                @break
        @endswitch
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($variables as $key => $description)
            <div class="flex items-start space-x-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex-shrink-0">
                    <code class="px-2 py-1 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        @php echo '{{' . $key . '}}'; @endphp
                    </code>
                </div>
                <div>
                    <p class="text-sm">{{ $description }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

    <!-- Variables del Template Actual -->
    @if($currentTemplate && $currentTemplate->variables && count($currentTemplate->variables) > 0)
    <div class="rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <x-heroicon-o-star class="h-5 w-5 mr-1.5" />
            Variables Personalizadas del Template
            <span class="ml-2 text-xs px-2 py-1 rounded-full border">
                Específicas de este template
            </span>
        </h3>
        <p class="text-sm mb-4">
            Variables definidas específicamente para este template. Se pasan manualmente al procesar el template usando el array <code class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">$customVariables</code>.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($currentTemplate->variables as $key => $description)
            <div class="flex items-start space-x-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex-shrink-0">
                    <code class="px-2 py-1 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        @php echo '{{' . $key . '}}'; @endphp
                    </code>
                </div>
                <div>
                    <p class="text-sm">{{ $description }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Información de Uso -->
    <div class="rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <x-heroicon-o-light-bulb class="h-5 w-5 mr-1.5" />
            Cómo Usar las Variables en tus Templates
        </h3>
        <div class="space-y-6">
            <!-- Sintaxis básica -->
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium border">1</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium mb-2">Sintaxis Básica</h4>
                    <p class="text-sm mb-2">
                        Envuelve el nombre de la variable entre llaves dobles:
                    </p>
                    <code class="block px-3 py-2 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">@php echo '{{nombre_variable}}'; @endphp</code>
                </div>
            </div>

            <!-- En asuntos -->
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium border">2</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium mb-2">En el Asunto del Email</h4>
                    <p class="text-sm mb-2">Ejemplo de asunto personalizado:</p>
                    <code class="block px-3 py-2 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        Bienvenido a @php echo '{{app_name}}'; @endphp, @php echo '{{user_name}}'; @endphp
                    </code>
                    <p class="text-xs mt-1 opacity-70">
                        Resultado: "Bienvenido a SaaS Helpdesk, Juan Pérez"
                    </p>
                </div>
            </div>

            <!-- En contenido -->
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium border">3</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium mb-2">En el Contenido HTML</h4>
                    <p class="text-sm mb-2">Ejemplo de contenido de respaldo:</p>
                    <code class="block px-3 py-2 rounded text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
                        &lt;p&gt;El respaldo &lt;strong&gt;@php echo '{{backup_name}}'; @endphp&lt;/strong&gt; de @php echo '{{backup_size}}'; @endphp se completó exitosamente.&lt;/p&gt;
                        &lt;p&gt;Fecha: @php echo '{{backup_date}}'; @endphp&lt;/p&gt;
                    </code>
                </div>
            </div>

            <!-- Consejos importantes -->
            <div class="border-t pt-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-information-circle class="h-5 w-5 mt-0.5 mr-1.5" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium mb-2">Consejos Importantes</h4>
                        <ul class="text-sm space-y-1">
                            <li>• Las variables se reemplazan automáticamente al procesar el template</li>
                            <li>• Si una variable no existe o está vacía, se mostrará como texto vacío</li>
                            <li>• Las variables globales y comunes están siempre disponibles</li>
                            <li>• Las variables de categoría solo están disponibles cuando se pasan específicamente</li>
                            <li>• Puedes combinar múltiples variables en una sola línea</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guía de Implementación para Desarrolladores -->
    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 mt-2">
        <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center">
            <x-heroicon-o-code-bracket class="h-5 w-5 mr-1.5" />
            Cómo Implementar Variables para Nuevos Modelos
        </h3>
        
        <div class="space-y-6">
            <!-- Paso 1: Determinar categoría -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-indigo-500">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-medium mr-2">1</span>
                    Determinar la Categoría Apropiada
                </h4>
                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p><strong>Para modelos CRUD genéricos:</strong> usa la categoría <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">model</code></p>
                    <p><strong>Para modelos específicos:</strong> usa categorías como <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">ticket</code>, <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">invoice</code>, <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">order</code></p>
                    <p class="text-xs text-gray-500">Las variables de <code>model</code> se adaptan automáticamente a cualquier modelo usando métodos helper.</p>
                </div>
            </div>

            <!-- Paso 2: Ejemplo con Bank Model -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-indigo-500">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-medium mr-2">2</span>
                    Ejemplo Completo: Bank Model
                </h4>
                <div class="space-y-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300">Ejemplo paso a paso con el modelo Bank:</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-xs font-mono overflow-x-auto"><Code>// 1. Crear el banco
$bank = Bank::create([
    'code' => 'BCP001',
    'name' => 'Banco de Crédito del Perú'
]);

// 2. Preparar datos para el template
$emailService = app(EmailTemplateService::class);
$modelData = [
    'model_name' => 'Banco',
    'model_id' => $bank->id,
    'model_title' => $bank->name,  // "Banco de Crédito del Perú"
    'action_type' => 'crear',
    'action_user' => auth()->user()->name,
    'record_url' => route('admin.banks.show', $bank),
];

// 3. Procesar template
$variables = $emailService->getModelVariables($modelData);
$template = $emailService->processTemplate('bank-created', $variables);

// 4. Enviar email
Mail::send([], [], function($message) use ($template) {
    $message->to('admin@example.com')
            ->subject($template['subject'])
            ->html($template['content']);
});</Code></pre>
                </div>
            </div>

            <!-- Paso 3: Para modelos específicos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-indigo-500">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-medium mr-2">3</span>
                    Para Modelos con Variables Específicas
                </h4>
                <div class="space-y-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300">Si tu modelo necesita variables específicas, usa el método correspondiente:</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-xs font-mono overflow-x-auto"><Code>// Para tickets
$ticketData = [
    'ticket_number' => $ticket->number,
    'ticket_title' => $ticket->title,
    'ticket_priority' => $ticket->priority,
    'assigned_user' => $ticket->assignedUser?->name,
    // ... más campos específicos
];
$variables = $emailService->getTicketVariables($ticketData);

// Para facturas
$invoiceData = [
    'invoice_number' => $invoice->number,
    'invoice_amount' => $invoice->total,
    'client_name' => $invoice->client->name,
    'due_date' => $invoice->due_date->format('d/m/Y'),
    // ... más campos específicos
];
$variables = $emailService->getInvoiceVariables($invoiceData);</Code></pre>
                </div>
            </div>

            <!-- Paso 4: Crear nuevas categorías -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-green-500">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 rounded-full text-xs font-medium mr-2">4</span>
                    Crear Nuevas Categorías (Avanzado)
                </h4>
                <div class="space-y-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300">Para crear una nueva categoría completamente personalizada:</p>
                    <ol class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <li>1. Añadir la categoría en <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">EmailTemplateSettings::getAvailableCategories()</code></li>
                        <li>2. Definir variables en <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">EmailTemplateSettings::getCategoryVariables()</code></li>
                        <li>3. Crear método helper en <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">EmailTemplateService</code> (ej: <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">getProjectVariables()</code>)</li>
                        <li>4. Usar desde tu código con el nuevo método</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Consejo final -->
        <div class="mt-6 p-4 bg-indigo-100 dark:bg-indigo-800/30 rounded-lg">
            <div class="flex items-start space-x-2">
                <x-heroicon-o-light-bulb class="h-5 w-5 text-indigo-600 mt-0.5" />
                <div>
                    <p class="text-sm text-indigo-900 dark:text-indigo-100">
                        <strong>Consejo Pro:</strong> El sistema está diseñado para ser flexible. Para la mayoría de casos, usar la categoría <code class="bg-indigo-200 dark:bg-indigo-700 px-1 rounded">model</code> con <code class="bg-indigo-200 dark:bg-indigo-700 px-1 rounded">getModelVariables()</code> es suficiente. Solo crea nuevas categorías cuando necesites variables muy específicas del dominio.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="flex flex-wrap justify-center gap-3 pt-6 border-t">
        <button 
            type="button" 
            onclick="copyAllVariablesToClipboard()" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
        >
            <x-heroicon-o-clipboard class="h-4 w-4 mr-1.5" />
            Copiar Variables
        </button>
        <button 
            type="button" 
            onclick="insertExampleTemplate()" 
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
        >
            <x-heroicon-o-document-plus class="h-4 w-4 mr-1.5" />
            Insertar Ejemplo
        </button>
        <button 
            type="button" 
            onclick="showCodeExamples()" 
            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
        >
            <x-heroicon-o-code-bracket class="h-4 w-4 mr-1.5" />
            Ver Código
        </button>
    </div>
</div>

<script>
function copyAllVariablesToClipboard() {
    const variables = [
        @foreach($globalVariables as $key => $description)
        '{{ $key }}',
        @endforeach
        @foreach($commonVariables as $key => $description)
        '{{ $key }}',
        @endforeach
        @foreach($categoryVariables as $category => $variables)
            @foreach($variables as $key => $description)
            '{{ $key }}',
            @endforeach
        @endforeach
        @if($currentTemplate && $currentTemplate->variables)
            @foreach($currentTemplate->variables as $key => $description)
            '{{ $key }}',
            @endforeach
        @endif
    ];
    
    const text = variables.map(v => '{{' + v + '}}').join('\n');
    navigator.clipboard.writeText(text).then(() => {
        if (window.$wireui) {
            window.$wireui.notify({
                title: 'Variables Copiadas',
                description: 'Todas las variables han sido copiadas al portapapeles',
                icon: 'success'
            });
        }
    });
}

function insertExampleTemplate() {
    const openBrace = '{';
    const closeBrace = '}';
    const example = '<div style="font-family: Arial, sans-serif; padding: 20px;">' +
        '<h1>Hola ' + openBrace + openBrace + 'user_name' + closeBrace + closeBrace + ',</h1>' +
        '<p>Te escribimos desde <strong>' + openBrace + openBrace + 'app_name' + closeBrace + closeBrace + '</strong> para informarte sobre una actualización importante.</p>' +
        '<div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">' +
        '<h3>Detalles:</h3>' +
        '<ul>' +
        '<li><strong>Fecha:</strong> ' + openBrace + openBrace + 'current_date' + closeBrace + closeBrace + '</li>' +
        '<li><strong>Hora:</strong> ' + openBrace + openBrace + 'current_time' + closeBrace + closeBrace + '</li>' +
        '<li><strong>Aplicación:</strong> ' + openBrace + openBrace + 'app_name' + closeBrace + closeBrace + '</li>' +
        '</ul>' +
        '</div>' +
        '<p>Si tienes alguna pregunta, no dudes en contactarnos en <a href="mailto:' + openBrace + openBrace + 'contact_email' + closeBrace + closeBrace + '">' + openBrace + openBrace + 'contact_email' + closeBrace + closeBrace + '</a>.</p>' +
        '<p>Saludos,<br>El equipo de ' + openBrace + openBrace + 'app_name' + closeBrace + closeBrace + '</p>' +
        '</div>';
    
    const finalTemplate = example;
    
    try {
        const contentField = document.querySelector('[x-data] textarea, [x-data] .trix-content, [data-trix-input]');
        if (contentField && contentField.tagName === 'TEXTAREA') {
            contentField.value = finalTemplate;
            contentField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        if (window.$wireui) {
            window.$wireui.notify({
                title: 'Ejemplo Insertado',
                description: 'El template de ejemplo ha sido insertado en el editor',
                icon: 'success'
            });
        }
    } catch (error) {
        navigator.clipboard.writeText(finalTemplate).then(() => {
            if (window.$wireui) {
                window.$wireui.notify({
                    title: 'Ejemplo Copiado',
                    description: 'El template de ejemplo ha sido copiado al portapapeles',
                    icon: 'info'
                });
            }
        });
    }
}

function showCodeExamples() {
    // Create a new modal or navigate to code examples
    if (window.Livewire) {
        window.Livewire.emit('openModal', 'code-examples');
    } else {
        // Fallback: open in new window or show alert
        const codeExampleUrl = '/admin/email-templates/code-examples';
        window.open(codeExampleUrl, '_blank');
    }
}
</script>