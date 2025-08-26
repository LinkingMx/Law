<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Settings\EmailTemplateSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EmailTemplateService
{
    public function __construct(
        private EmailTemplateSettings $settings,
        private GeneralSettings $generalSettings
    ) {}

    /**
     * Procesar template por clave
     */
    public function processTemplate(string $key, array $variables = [], string $language = null): array
    {
        $language = $language ?? $this->settings->default_language;
        
        $template = EmailTemplate::getByKey($key, $language);
        
        if (!$template) {
            throw new \Exception("Template con clave '{$key}' no encontrado para idioma '{$language}'");
        }

        $processedVariables = $this->mergeVariables($variables);

        return [
            'subject' => $template->processSubject($processedVariables),
            'content' => $template->processContent($processedVariables),
            'from_name' => $this->settings->default_from_name,
            'from_email' => $this->settings->default_from_email,
            'template' => $template,
        ];
    }

    /**
     * Combinar variables globales, comunes y específicas
     */
    public function mergeVariables(array $customVariables = []): array
    {
        $globalVariables = $this->getGlobalVariables();
        $commonVariables = $this->getCommonVariables();
        
        return array_merge($globalVariables, $commonVariables, $customVariables);
    }

    /**
     * Obtener variables globales del sistema
     */
    public function getGlobalVariables(): array
    {
        return [
            'app_name' => $this->generalSettings->app_name ?? config('app.name'),
            'app_url' => config('app.url'),
            'contact_email' => $this->generalSettings->contact_email ?? 'contact@example.com',
            'support_url' => config('app.url') . '/support',
        ];
    }

    /**
     * Obtener variables comunes disponibles
     */
    public function getCommonVariables(): array
    {
        $user = Auth::user();
        
        return [
            'current_date' => Carbon::now()->format('d/m/Y'),
            'current_time' => Carbon::now()->format('H:i:s'),
            'current_year' => Carbon::now()->year,
            'user_name' => $user?->name ?? 'Usuario',
            'user_email' => $user?->email ?? '',
        ];
    }

    /**
     * Obtener variables específicas para categoría de backup
     */
    public function getBackupVariables(array $backupData): array
    {
        return [
            'backup_name' => $backupData['name'] ?? 'Respaldo',
            'backup_size' => $this->formatBytes($backupData['size'] ?? 0),
            'backup_date' => $backupData['date'] ?? Carbon::now()->format('d/m/Y H:i:s'),
            'backup_status' => $backupData['status'] ?? 'Desconocido',
            'backup_error' => $backupData['error'] ?? '',
            'backup_duration' => $backupData['duration'] ?? '',
            'backup_destination' => $backupData['destination'] ?? 'Google Drive',
        ];
    }

    /**
     * Obtener variables específicas para usuarios
     */
    public function getUserVariables(array $userData): array
    {
        return [
            'user_name' => $userData['name'] ?? 'Usuario',
            'user_email' => $userData['email'] ?? '',
            'user_role' => $userData['role'] ?? 'Usuario',
            'verification_url' => $userData['verification_url'] ?? '',
            'reset_url' => $userData['reset_url'] ?? '',
        ];
    }

    /**
     * Obtener variables específicas para sistema
     */
    public function getSystemVariables(array $systemData): array
    {
        return [
            'system_status' => $systemData['status'] ?? 'Operacional',
            'error_message' => $systemData['error'] ?? '',
            'maintenance_start' => $systemData['maintenance_start'] ?? '',
            'maintenance_end' => $systemData['maintenance_end'] ?? '',
            'server_name' => $systemData['server_name'] ?? 'Servidor Principal',
            'version' => $systemData['version'] ?? '1.0.0',
        ];
    }

    /**
     * Obtener variables específicas para modelos/CRUD
     */
    public function getModelVariables(array $modelData): array
    {
        return [
            'model_name' => $modelData['model_name'] ?? 'Registro',
            'model_id' => $modelData['model_id'] ?? '',
            'model_title' => $modelData['model_title'] ?? $modelData['title'] ?? $modelData['name'] ?? 'Sin título',
            'model_status' => $modelData['model_status'] ?? $modelData['status'] ?? 'Activo',
            'action_type' => $modelData['action_type'] ?? 'actualizar',
            'action_user' => $modelData['action_user'] ?? 'Sistema',
            'action_date' => $modelData['action_date'] ?? Carbon::now()->format('d/m/Y H:i:s'),
            'changes_summary' => $modelData['changes_summary'] ?? 'Se realizaron cambios en el registro',
            'record_url' => $modelData['record_url'] ?? config('app.url'),
            'edit_url' => $modelData['edit_url'] ?? config('app.url'),
        ];
    }

    /**
     * Obtener variables específicas para tickets
     */
    public function getTicketVariables(array $ticketData): array
    {
        return [
            'ticket_id' => $ticketData['id'] ?? $ticketData['ticket_id'] ?? '',
            'ticket_number' => $ticketData['number'] ?? $ticketData['ticket_number'] ?? '',
            'ticket_title' => $ticketData['title'] ?? $ticketData['subject'] ?? 'Sin título',
            'ticket_description' => $ticketData['description'] ?? $ticketData['content'] ?? '',
            'ticket_status' => $ticketData['status'] ?? 'Abierto',
            'ticket_priority' => $ticketData['priority'] ?? 'Media',
            'ticket_category' => $ticketData['category'] ?? 'General',
            'assigned_user' => $ticketData['assigned_user'] ?? $ticketData['assigned_to'] ?? 'Sin asignar',
            'created_by' => $ticketData['created_by'] ?? $ticketData['user_name'] ?? 'Usuario',
            'due_date' => $ticketData['due_date'] ?? '',
            'ticket_url' => $ticketData['ticket_url'] ?? config('app.url'),
        ];
    }

    /**
     * Obtener variables específicas para pedidos
     */
    public function getOrderVariables(array $orderData): array
    {
        return [
            'order_id' => $orderData['id'] ?? $orderData['order_id'] ?? '',
            'order_number' => $orderData['number'] ?? $orderData['order_number'] ?? '',
            'order_total' => $orderData['total'] ?? $orderData['amount'] ?? '0.00',
            'order_status' => $orderData['status'] ?? 'Pendiente',
            'customer_name' => $orderData['customer_name'] ?? $orderData['client_name'] ?? 'Cliente',
            'customer_email' => $orderData['customer_email'] ?? $orderData['client_email'] ?? '',
            'order_date' => $orderData['order_date'] ?? $orderData['created_at'] ?? Carbon::now()->format('d/m/Y'),
            'delivery_date' => $orderData['delivery_date'] ?? '',
            'payment_method' => $orderData['payment_method'] ?? 'No especificado',
            'order_url' => $orderData['order_url'] ?? config('app.url'),
        ];
    }

    /**
     * Obtener variables específicas para facturas
     */
    public function getInvoiceVariables(array $invoiceData): array
    {
        return [
            'invoice_id' => $invoiceData['id'] ?? $invoiceData['invoice_id'] ?? '',
            'invoice_number' => $invoiceData['number'] ?? $invoiceData['invoice_number'] ?? '',
            'invoice_amount' => $invoiceData['amount'] ?? $invoiceData['total'] ?? '0.00',
            'invoice_status' => $invoiceData['status'] ?? 'Pendiente',
            'invoice_date' => $invoiceData['invoice_date'] ?? $invoiceData['created_at'] ?? Carbon::now()->format('d/m/Y'),
            'due_date' => $invoiceData['due_date'] ?? '',
            'client_name' => $invoiceData['client_name'] ?? $invoiceData['customer_name'] ?? 'Cliente',
            'client_email' => $invoiceData['client_email'] ?? $invoiceData['customer_email'] ?? '',
            'payment_terms' => $invoiceData['payment_terms'] ?? '30 días',
            'invoice_url' => $invoiceData['invoice_url'] ?? config('app.url'),
            'payment_url' => $invoiceData['payment_url'] ?? config('app.url'),
        ];
    }

    /**
     * Obtener variables específicas para notificaciones
     */
    public function getNotificationVariables(array $notificationData): array
    {
        return [
            'notification_title' => $notificationData['title'] ?? $notificationData['subject'] ?? 'Notificación',
            'notification_message' => $notificationData['message'] ?? $notificationData['content'] ?? '',
            'notification_type' => $notificationData['type'] ?? 'info',
            'notification_priority' => $notificationData['priority'] ?? 'normal',
            'notification_url' => $notificationData['url'] ?? $notificationData['action_url'] ?? config('app.url'),
            'expires_at' => $notificationData['expires_at'] ?? '',
        ];
    }

    /**
     * Verificar si los templates están habilitados
     */
    public function isEnabled(): bool
    {
        return $this->settings->enabled;
    }

    /**
     * Obtener template con contenido envuelto si está configurado
     */
    public function getWrappedContent(string $content): string
    {
        if (!$this->settings->auto_wrap_content) {
            return $content;
        }

        $wrapper = $this->settings->template_wrapper;
        
        if (view()->exists($wrapper)) {
            return view($wrapper, ['content' => $content])->render();
        }

        return $content;
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Validar que un template existe y está activo
     */
    public function templateExists(string $key, string $language = null): bool
    {
        $language = $language ?? $this->settings->default_language;
        
        return EmailTemplate::where('key', $key)
            ->where('language', $language)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtener lista de templates disponibles por categoría
     */
    public function getTemplatesByCategory(string $category, string $language = null): array
    {
        $language = $language ?? $this->settings->default_language;
        
        return EmailTemplate::getByCategory($category, $language)
            ->map(function ($template) {
                return [
                    'key' => $template->key,
                    'name' => $template->name,
                    'subject' => $template->subject,
                    'description' => $template->description,
                ];
            })
            ->toArray();
    }
}