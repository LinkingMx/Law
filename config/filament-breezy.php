<?php

return [
    'enable_profile_page' => true,
    'show_avatar_form' => true,
    'use_app_name' => true,
    'enable_2fa' => true,
    'force_2fa' => false,
    'enable_sanctum_tokens' => true,
    'enable_session_management' => true,
    
    'profile_page_group' => null,
    'profile_page_icon' => 'heroicon-o-user-circle',
    'profile_page_navigation_label' => 'Mi Perfil',
    'profile_page_navigation_sort' => null,
    
    'password_rules' => [
        'required',
        'string',
        'min:8',
        'confirmed',
        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
    ],
    
    'sanctum_permissions' => [
        'create' => 'Crear',
        'read' => 'Leer', 
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
    ],
];