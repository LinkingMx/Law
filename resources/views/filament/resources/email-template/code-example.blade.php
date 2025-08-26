<div class="space-y-6">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">💻 Ejemplo de Código</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Cómo implementar notificaciones de modelos en tu aplicación
        </p>
    </div>

    <!-- Ejemplo de Trait -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            1️⃣ Usar el Trait NotifiesModelChanges
        </h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-300"><code>&lt;?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NotifiesModelChanges;

class Product extends Model
{
    use NotifiesModelChanges;
    
    // Activar notificaciones para este modelo
    protected $notifyChanges = true;
    
    // Destinatarios de las notificaciones
    protected $notificationRecipients = [
        'admin@example.com',
        'manager@example.com'
    ];
    
    // Personalizar el nombre mostrado del modelo
    protected function getModelDisplayName(): string
    {
        return 'Producto';
    }
    
    // Personalizar el título del registro
    protected function getModelTitle(): string
    {
        return $this->name . ' (SKU: ' . $this->sku . ')';
    }
}</code></pre>
        </div>
    </div>

    <!-- Ejemplo Manual -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            2️⃣ Envío Manual de Notificaciones
        </h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-300"><code>&lt;?php

use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Mail;

// En tu controlador o servicio
public function createTicket(Request $request)
{
    $ticket = Ticket::create($request->validated());
    
    // Preparar datos del ticket
    $ticketData = [
        'ticket_number' => $ticket->number,
        'ticket_title' => $ticket->subject,
        'ticket_priority' => $ticket->priority,
        'ticket_status' => 'Abierto',
        'created_by' => auth()->user()->name,
        'ticket_url' => route('tickets.show', $ticket),
        'ticket_description' => $ticket->description,
    ];
    
    // Procesar template
    $emailService = app(EmailTemplateService::class);
    $variables = $emailService->getTicketVariables($ticketData);
    $template = $emailService->processTemplate('ticket-created', $variables);
    
    // Enviar email
    Mail::send([], [], function ($message) use ($template) {
        $message->to('support@example.com')
            ->subject($template['subject'])
            ->html($template['content']);
    });
    
    return response()->json(['ticket' => $ticket]);
}</code></pre>
        </div>
    </div>

    <!-- Ejemplo en Filament -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            3️⃣ Integración con Filament Resources
        </h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-300"><code>&lt;?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Services\EmailTemplateService;
use Filament\Notifications\Notification;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    
    protected function afterCreate(): void
    {
        $order = $this->record;
        
        // Preparar datos del pedido
        $orderData = [
            'order_number' => $order->number,
            'customer_name' => $order->customer->name,
            'customer_email' => $order->customer->email,
            'order_total' => number_format($order->total, 2),
            'payment_method' => $order->payment_method,
            'order_status' => 'Pendiente',
            'order_date' => $order->created_at->format('d/m/Y'),
            'order_url' => route('orders.show', $order),
        ];
        
        try {
            $emailService = app(EmailTemplateService::class);
            $variables = $emailService->getOrderVariables($orderData);
            $template = $emailService->processTemplate('order-created', $variables);
            
            // Enviar al cliente
            Mail::send([], [], function ($message) use ($template, $order) {
                $message->to($order->customer->email)
                    ->subject($template['subject'])
                    ->html($template['content']);
            });
            
            Notification::make()
                ->title('Email enviado')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al enviar email')
                ->danger()
                ->send();
        }
    }
}</code></pre>
        </div>
    </div>

    <!-- Ejemplo de Observer -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            4️⃣ Usar Observers para Notificaciones
        </h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-300"><code>&lt;?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\EmailTemplateService;

class InvoiceObserver
{
    protected EmailTemplateService $emailService;
    
    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }
    
    public function created(Invoice $invoice): void
    {
        $invoiceData = [
            'invoice_number' => $invoice->number,
            'client_name' => $invoice->client->name,
            'client_email' => $invoice->client->email,
            'invoice_amount' => number_format($invoice->total, 2),
            'due_date' => $invoice->due_date->format('d/m/Y'),
            'invoice_url' => route('invoices.show', $invoice),
            'payment_url' => route('invoices.pay', $invoice),
        ];
        
        $variables = $this->emailService->getInvoiceVariables($invoiceData);
        $template = $this->emailService->processTemplate('invoice-created', $variables);
        
        // Enviar notificación
        Mail::to($invoice->client->email)->send(
            new DynamicTemplateMail($template)
        );
    }
}</code></pre>
        </div>
    </div>

    <!-- Tips y Mejores Prácticas -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">
            💡 Tips y Mejores Prácticas
        </h3>
        <div class="space-y-3">
            <div class="flex items-start space-x-3">
                <span class="text-blue-500">✓</span>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Siempre verifica que el template existe antes de usarlo con <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">templateExists()</code>
                </p>
            </div>
            <div class="flex items-start space-x-3">
                <span class="text-blue-500">✓</span>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Usa try-catch para manejar errores en el envío de emails
                </p>
            </div>
            <div class="flex items-start space-x-3">
                <span class="text-blue-500">✓</span>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Considera usar colas (queues) para el envío de emails en producción
                </p>
            </div>
            <div class="flex items-start space-x-3">
                <span class="text-blue-500">✓</span>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Personaliza los métodos del trait según las necesidades de tu modelo
                </p>
            </div>
        </div>
    </div>
</div>