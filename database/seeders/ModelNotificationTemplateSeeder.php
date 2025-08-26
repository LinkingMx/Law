<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class ModelNotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Templates para CRUD de modelos
            [
                'key' => 'model-created',
                'name' => 'Registro Creado',
                'subject' => '✅ Nuevo {{model_name}} creado - {{model_title}}',
                'content' => $this->getModelCreatedContent(),
                'category' => 'model',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se crea un nuevo registro',
                'variables' => [
                    'model_name' => 'Nombre del tipo de registro (Ej: Usuario, Producto)',
                    'model_title' => 'Título o nombre del registro específico',
                    'action_user' => 'Usuario que creó el registro',
                    'record_url' => 'URL para ver el registro creado'
                ]
            ],
            [
                'key' => 'model-updated',
                'name' => 'Registro Actualizado',
                'subject' => '🔄 {{model_name}} actualizado - {{model_title}}',
                'content' => $this->getModelUpdatedContent(),
                'category' => 'model',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se actualiza un registro existente',
                'variables' => [
                    'model_name' => 'Nombre del tipo de registro',
                    'model_title' => 'Título del registro actualizado',
                    'changes_summary' => 'Resumen de los cambios realizados',
                    'action_user' => 'Usuario que realizó la actualización'
                ]
            ],
            [
                'key' => 'model-deleted',
                'name' => 'Registro Eliminado',
                'subject' => '🗑️ {{model_name}} eliminado - {{model_title}}',
                'content' => $this->getModelDeletedContent(),
                'category' => 'model',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se elimina un registro',
                'variables' => [
                    'model_name' => 'Nombre del tipo de registro eliminado',
                    'model_title' => 'Título del registro eliminado',
                    'action_user' => 'Usuario que eliminó el registro',
                    'action_date' => 'Fecha y hora de eliminación'
                ]
            ],
            
            // Templates para Tickets
            [
                'key' => 'ticket-created',
                'name' => 'Nuevo Ticket',
                'subject' => '🎫 Nuevo ticket creado #{{ticket_number}} - {{ticket_title}}',
                'content' => $this->getTicketCreatedContent(),
                'category' => 'ticket',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se crea un nuevo ticket de soporte',
                'variables' => [
                    'ticket_number' => 'Número único del ticket',
                    'ticket_title' => 'Título del ticket',
                    'ticket_priority' => 'Prioridad del ticket',
                    'created_by' => 'Usuario que creó el ticket'
                ]
            ],
            [
                'key' => 'ticket-assigned',
                'name' => 'Ticket Asignado',
                'subject' => '👤 Ticket #{{ticket_number}} asignado - {{ticket_title}}',
                'content' => $this->getTicketAssignedContent(),
                'category' => 'ticket',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se asigna un ticket a un usuario',
                'variables' => [
                    'ticket_number' => 'Número del ticket',
                    'assigned_user' => 'Usuario asignado al ticket',
                    'ticket_priority' => 'Prioridad del ticket',
                    'due_date' => 'Fecha límite del ticket'
                ]
            ],
            [
                'key' => 'ticket-resolved',
                'name' => 'Ticket Resuelto',
                'subject' => '✅ Ticket #{{ticket_number}} resuelto - {{ticket_title}}',
                'content' => $this->getTicketResolvedContent(),
                'category' => 'ticket',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se resuelve un ticket',
                'variables' => [
                    'ticket_number' => 'Número del ticket resuelto',
                    'assigned_user' => 'Usuario que resolvió el ticket',
                    'ticket_description' => 'Descripción de la solución'
                ]
            ],

            // Templates para Pedidos
            [
                'key' => 'order-created',
                'name' => 'Nuevo Pedido',
                'subject' => '🛒 Nuevo pedido #{{order_number}} - {{customer_name}}',
                'content' => $this->getOrderCreatedContent(),
                'category' => 'order',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se crea un nuevo pedido',
                'variables' => [
                    'order_number' => 'Número del pedido',
                    'customer_name' => 'Nombre del cliente',
                    'order_total' => 'Total del pedido',
                    'payment_method' => 'Método de pago utilizado'
                ]
            ],
            [
                'key' => 'order-shipped',
                'name' => 'Pedido Enviado',
                'subject' => '📦 Pedido #{{order_number}} enviado - En camino',
                'content' => $this->getOrderShippedContent(),
                'category' => 'order',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se envía un pedido',
                'variables' => [
                    'order_number' => 'Número del pedido enviado',
                    'delivery_date' => 'Fecha estimada de entrega',
                    'customer_name' => 'Nombre del cliente'
                ]
            ],

            // Templates para Facturas
            [
                'key' => 'invoice-created',
                'name' => 'Nueva Factura',
                'subject' => '💳 Nueva factura #{{invoice_number}} - {{client_name}}',
                'content' => $this->getInvoiceCreatedContent(),
                'category' => 'invoice',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando se genera una nueva factura',
                'variables' => [
                    'invoice_number' => 'Número de la factura',
                    'invoice_amount' => 'Monto total de la factura',
                    'due_date' => 'Fecha de vencimiento',
                    'payment_url' => 'URL para realizar el pago'
                ]
            ],
            [
                'key' => 'invoice-overdue',
                'name' => 'Factura Vencida',
                'subject' => '⚠️ Factura #{{invoice_number}} vencida - Acción requerida',
                'content' => $this->getInvoiceOverdueContent(),
                'category' => 'invoice',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Notificación cuando una factura está vencida',
                'variables' => [
                    'invoice_number' => 'Número de la factura vencida',
                    'invoice_amount' => 'Monto pendiente',
                    'due_date' => 'Fecha de vencimiento original'
                ]
            ],

            // Template de ejemplo para Bank
            [
                'key' => 'bank-created',
                'name' => 'Nuevo Banco Creado',
                'subject' => '🏦 Nuevo {{model_name}} registrado - {{model_title}}',
                'content' => $this->getBankCreatedContent(),
                'category' => 'model',
                'language' => 'es',
                'is_active' => true,
                'description' => 'Ejemplo de template para cuando se crea un nuevo banco en el sistema',
                'variables' => [
                    'model_name' => 'Tipo de registro (Banco)',
                    'model_title' => 'Nombre del banco',
                    'model_id' => 'ID del banco',
                    'action_user' => 'Usuario que registró el banco',
                    'record_url' => 'URL para ver el banco'
                ]
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                [
                    'key' => $template['key'],
                    'language' => $template['language']
                ],
                $template
            );
        }
    }

    private function getModelCreatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">✅ Nuevo Registro Creado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">{{model_name}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha creado un nuevo <strong>{{model_name}}</strong> en {{app_name}}.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📋 Detalles del Registro</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Tipo:</strong> {{model_name}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Título:</strong> {{model_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>ID:</strong> {{model_id}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Estado:</strong> {{model_status}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Creado por:</strong> {{action_user}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha:</strong> {{action_date}}</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{record_url}}" style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Ver Registro
                    </a>
                </div>
                
                <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280; text-align: center;">
                    El registro está ahora disponible en el sistema y puede ser consultado o editado según los permisos correspondientes.
                </p>
            </div>
        </div>';
    }

    private function getModelUpdatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🔄 Registro Actualizado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">{{model_name}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha actualizado el registro <strong>{{model_title}}</strong> en {{app_name}}.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📋 Información del Registro</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Registro:</strong> {{model_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>ID:</strong> {{model_id}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Estado:</strong> {{model_status}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Actualizado por:</strong> {{action_user}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha:</strong> {{action_date}}</li>
                    </ul>
                </div>
                
                <div style="background: #eff6ff; padding: 20px; border-radius: 8px; border-left: 4px solid #60a5fa; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">📝 Resumen de Cambios</h3>
                    <p style="margin: 0; color: #374151;">{{changes_summary}}</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{record_url}}" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 10px;">
                        Ver Registro
                    </a>
                    <a href="{{edit_url}}" style="display: inline-block; background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Editar
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getModelDeletedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🗑️ Registro Eliminado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">{{model_name}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha eliminado el registro <strong>{{model_title}}</strong> de tipo {{model_name}} en {{app_name}}.
                </p>
                
                <div style="background: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #991b1b; font-size: 18px;">⚠️ Detalles de la Eliminación</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #fecaca;"><strong>Registro eliminado:</strong> {{model_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #fecaca;"><strong>ID:</strong> {{model_id}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #fecaca;"><strong>Eliminado por:</strong> {{action_user}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha de eliminación:</strong> {{action_date}}</li>
                    </ul>
                </div>
                
                <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">💡 Información Importante</h3>
                    <p style="margin: 0; color: #92400e;">
                        Esta acción puede ser irreversible. Si necesitas recuperar este registro, contacta al administrador del sistema inmediatamente.
                    </p>
                </div>
                
                <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280; text-align: center;">
                    Si tienes preguntas sobre esta eliminación, contacta al administrador del sistema.
                </p>
            </div>
        </div>';
    }

    private function getTicketCreatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🎫 Nuevo Ticket Creado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{ticket_number}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha creado un nuevo ticket de soporte en {{app_name}}.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #8b5cf6; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">🎫 Detalles del Ticket</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Número:</strong> #{{ticket_number}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Título:</strong> {{ticket_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Prioridad:</strong> {{ticket_priority}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Categoría:</strong> {{ticket_category}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Creado por:</strong> {{created_by}}</li>
                        <li style="padding: 8px 0;"><strong>Estado:</strong> {{ticket_status}}</li>
                    </ul>
                </div>
                
                <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 16px;">📄 Descripción</h3>
                    <p style="margin: 0; color: #374151;">{{ticket_description}}</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ticket_url}}" style="display: inline-block; background: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Ver Ticket
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getTicketAssignedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">👤 Ticket Asignado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{ticket_number}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    El ticket <strong>#{{ticket_number}}</strong> ha sido asignado a <strong>{{assigned_user}}</strong>.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📋 Información del Ticket</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Título:</strong> {{ticket_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Asignado a:</strong> {{assigned_user}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Prioridad:</strong> {{ticket_priority}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha límite:</strong> {{due_date}}</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ticket_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Ver Ticket
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getTicketResolvedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">✅ Ticket Resuelto</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{ticket_number}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    ¡Excelente noticia! El ticket <strong>#{{ticket_number}}</strong> ha sido resuelto exitosamente.
                </p>
                
                <div style="background: #dcfce7; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #047857; font-size: 18px;">✅ Detalles de la Resolución</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #bbf7d0;"><strong>Ticket:</strong> {{ticket_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #bbf7d0;"><strong>Resuelto por:</strong> {{assigned_user}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha de resolución:</strong> {{action_date}}</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 16px;">📝 Descripción de la Solución</h3>
                    <p style="margin: 0; color: #374151;">{{ticket_description}}</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ticket_url}}" style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Ver Ticket
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getOrderCreatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🛒 Nuevo Pedido</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{order_number}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha creado un nuevo pedido de <strong>{{customer_name}}</strong> en {{app_name}}.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #06b6d4; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">🛍️ Detalles del Pedido</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Número:</strong> #{{order_number}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Cliente:</strong> {{customer_name}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Email:</strong> {{customer_email}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Total:</strong> ${{order_total}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Método de pago:</strong> {{payment_method}}</li>
                        <li style="padding: 8px 0;"><strong>Estado:</strong> {{order_status}}</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{order_url}}" style="display: inline-block; background: #06b6d4; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Ver Pedido
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getOrderShippedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">📦 Pedido Enviado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{order_number}} - En camino</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    ¡Buenas noticias <strong>{{customer_name}}</strong>! Tu pedido ha sido enviado y está en camino.
                </p>
                
                <div style="background: #dcfce7; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #047857; font-size: 18px;">📦 Información de Envío</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #bbf7d0;"><strong>Pedido:</strong> #{{order_number}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #bbf7d0;"><strong>Estado:</strong> {{order_status}}</li>
                        <li style="padding: 8px 0;"><strong>Entrega estimada:</strong> {{delivery_date}}</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{order_url}}" style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Rastrear Pedido
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getInvoiceCreatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">💳 Nueva Factura</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{invoice_number}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha generado una nueva factura para <strong>{{client_name}}</strong>.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #6366f1; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">🧾 Detalles de la Factura</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Número:</strong> #{{invoice_number}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Cliente:</strong> {{client_name}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Monto:</strong> ${{invoice_amount}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Fecha de vencimiento:</strong> {{due_date}}</li>
                        <li style="padding: 8px 0;"><strong>Términos:</strong> {{payment_terms}}</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{invoice_url}}" style="display: inline-block; background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 10px;">
                        Ver Factura
                    </a>
                    <a href="{{payment_url}}" style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Pagar Ahora
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getInvoiceOverdueContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">⚠️ Factura Vencida</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">#{{invoice_number}} - Acción Requerida</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    La factura <strong>#{{invoice_number}}</strong> para <strong>{{client_name}}</strong> está vencida y requiere atención inmediata.
                </p>
                
                <div style="background: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #991b1b; font-size: 18px;">💸 Información de Pago Pendiente</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #fecaca;"><strong>Monto pendiente:</strong> ${{invoice_amount}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #fecaca;"><strong>Fecha de vencimiento:</strong> {{due_date}}</li>
                        <li style="padding: 8px 0;"><strong>Días de retraso:</strong> {{days_overdue}}</li>
                    </ul>
                </div>
                
                <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">🔔 Acción Requerida</h3>
                    <p style="margin: 0; color: #92400e;">
                        Por favor, realiza el pago lo antes posible para evitar cargos adicionales o suspensión del servicio.
                    </p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{payment_url}}" style="display: inline-block; background: #ef4444; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; margin-right: 10px;">
                        Pagar Ahora
                    </a>
                    <a href="{{invoice_url}}" style="display: inline-block; background: #6b7280; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                        Ver Factura
                    </a>
                </div>
            </div>
        </div>';
    }

    private function getBankCreatedContent(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #059669, #047857); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🏦 Nuevo Banco Registrado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">{{model_title}} - {{current_date}}</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
                    Se ha registrado un nuevo <strong>{{model_name}}</strong> en el sistema {{app_name}}.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #059669; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">🏦 Información del Banco</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Nombre:</strong> {{model_title}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>ID del registro:</strong> {{model_id}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Estado:</strong> {{model_status}}</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><strong>Registrado por:</strong> {{action_user}}</li>
                        <li style="padding: 8px 0;"><strong>Fecha de registro:</strong> {{action_date}}</li>
                    </ul>
                </div>
                
                <div style="background: #dcfce7; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #047857; font-size: 16px;">✅ Estado del Registro</h3>
                    <p style="margin: 0; color: #047857;">
                        El banco ha sido registrado exitosamente y está ahora disponible en el sistema para su uso en transacciones y operaciones bancarias.
                    </p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{record_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 10px;">
                        Ver Banco
                    </a>
                    <a href="{{edit_url}}" style="display: inline-block; background: #0891b2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Editar Información
                    </a>
                </div>
                
                <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280; text-align: center;">
                    Este registro puede ser consultado y modificado por usuarios con los permisos apropiados.
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 20px; padding: 20px; color: #6b7280; font-size: 12px;">
                <p style="margin: 0;">Este email fue enviado automáticamente por {{app_name}}</p>
                <p style="margin: 5px 0 0 0;">Si tienes preguntas, contacta a <a href="mailto:{{contact_email}}" style="color: #059669;">{{contact_email}}</a></p>
            </div>
        </div>';
    }
}