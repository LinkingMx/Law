<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailTemplateSettings extends Settings
{
    public bool $enabled;
    public string $default_language;
    public array $available_languages;
    public string $default_from_name;
    public string $default_from_email;
    public bool $auto_wrap_content;
    public string $template_wrapper;
    public array $global_variables;

    public static function group(): string
    {
        return 'email_templates';
    }

    public static function encrypted(): array
    {
        return [];
    }

    /**
     * Get default settings
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'default_language' => 'es',
            'available_languages' => ['es' => 'Español', 'en' => 'English'],
            'default_from_name' => 'SaaS Helpdesk',
            'default_from_email' => 'noreply@saashelpdesk.test',
            'auto_wrap_content' => true,
            'template_wrapper' => 'emails.wrapper',
            'global_variables' => [
                'app_name' => 'SaaS Helpdesk',
                'app_url' => 'http://saashelpdesk.test',
                'contact_email' => 'support@saashelpdesk.test',
                'support_url' => 'http://saashelpdesk.test/support',
            ],
        ];
    }

    /**
     * Get available template categories
     */
    public function getAvailableCategories(): array
    {
        return [
            'general' => 'General',
            'backup' => 'Respaldos',
            'user' => 'Usuarios',
            'system' => 'Sistema',
            'model' => 'Modelos/CRUD',
            'documentation' => 'Documentación',
            'ticket' => 'Tickets',
            'order' => 'Pedidos',
            'invoice' => 'Facturas',
            'notification' => 'Notificaciones',
        ];
    }

    /**
     * Get common variables for all templates
     */
    public function getCommonVariables(): array
    {
        return [
            'app_name' => 'Nombre de la aplicación',
            'app_url' => 'URL de la aplicación',
            'contact_email' => 'Email de contacto',
            'support_url' => 'URL de soporte',
            'current_date' => 'Fecha actual',
            'current_time' => 'Hora actual',
            'user_name' => 'Nombre del usuario',
            'user_email' => 'Email del usuario',
        ];
    }

    /**
     * Get category-specific variables
     */
    public function getCategoryVariables(string $category): array
    {
        return match ($category) {
            'backup' => [
                'backup_name' => 'Nombre del respaldo',
                'backup_size' => 'Tamaño del respaldo',
                'backup_date' => 'Fecha del respaldo',
                'backup_status' => 'Estado del respaldo',
                'backup_error' => 'Error del respaldo',
                'backup_duration' => 'Duración del respaldo',
                'backup_destination' => 'Destino del respaldo',
            ],
            'user' => [
                'user_name' => 'Nombre del usuario',
                'user_email' => 'Email del usuario',
                'user_role' => 'Rol del usuario',
                'user_id' => 'ID del usuario',
                'verification_url' => 'URL de verificación',
                'reset_url' => 'URL de reset de contraseña',
                'login_url' => 'URL de inicio de sesión',
                'profile_url' => 'URL del perfil',
            ],
            'system' => [
                'system_status' => 'Estado del sistema',
                'error_message' => 'Mensaje de error',
                'maintenance_start' => 'Inicio de mantenimiento',
                'maintenance_end' => 'Fin de mantenimiento',
                'server_name' => 'Nombre del servidor',
                'version' => 'Versión del sistema',
            ],
            'model' => [
                'model_name' => 'Nombre del modelo/recurso',
                'model_id' => 'ID del registro',
                'model_title' => 'Título/nombre del registro',
                'model_status' => 'Estado del registro',
                'action_type' => 'Tipo de acción (crear, actualizar, eliminar)',
                'action_user' => 'Usuario que realizó la acción',
                'action_date' => 'Fecha de la acción',
                'changes_summary' => 'Resumen de cambios realizados',
                'record_url' => 'URL para ver el registro',
                'edit_url' => 'URL para editar el registro',
            ],
            'ticket' => [
                'ticket_id' => 'ID del ticket',
                'ticket_number' => 'Número del ticket',
                'ticket_title' => 'Título del ticket',
                'ticket_description' => 'Descripción del ticket',
                'ticket_status' => 'Estado del ticket',
                'ticket_priority' => 'Prioridad del ticket',
                'ticket_category' => 'Categoría del ticket',
                'assigned_user' => 'Usuario asignado',
                'created_by' => 'Creado por',
                'due_date' => 'Fecha límite',
                'ticket_url' => 'URL del ticket',
            ],
            'order' => [
                'order_id' => 'ID del pedido',
                'order_number' => 'Número del pedido',
                'order_total' => 'Total del pedido',
                'order_status' => 'Estado del pedido',
                'customer_name' => 'Nombre del cliente',
                'customer_email' => 'Email del cliente',
                'order_date' => 'Fecha del pedido',
                'delivery_date' => 'Fecha de entrega',
                'payment_method' => 'Método de pago',
                'order_url' => 'URL del pedido',
            ],
            'invoice' => [
                'invoice_id' => 'ID de la factura',
                'invoice_number' => 'Número de factura',
                'invoice_amount' => 'Monto de la factura',
                'invoice_status' => 'Estado de la factura',
                'invoice_date' => 'Fecha de factura',
                'due_date' => 'Fecha de vencimiento',
                'client_name' => 'Nombre del cliente',
                'client_email' => 'Email del cliente',
                'payment_terms' => 'Términos de pago',
                'invoice_url' => 'URL de la factura',
                'payment_url' => 'URL de pago',
            ],
            'documentation' => [
                'document_title' => 'Título del documento',
                'document_id' => 'ID del documento',
                'document_status' => 'Estado del documento (Borrador, Publicado, etc.)',
                'document_url' => 'URL para ver/editar el documento',
                'creator_name' => 'Nombre del creador del documento',
                'created_date' => 'Fecha de creación del documento',
                'editor_name' => 'Nombre del último editor',
                'edited_date' => 'Fecha de la última edición',
                'last_editor' => 'Último usuario que editó el documento',
                'modification_date' => 'Fecha de la última modificación',
            ],
            'notification' => [
                'notification_title' => 'Título de la notificación',
                'notification_message' => 'Mensaje de la notificación',
                'notification_type' => 'Tipo de notificación',
                'notification_priority' => 'Prioridad de la notificación',
                'notification_url' => 'URL de la notificación',
                'expires_at' => 'Fecha de expiración',
            ],
            default => [],
        };
    }
}