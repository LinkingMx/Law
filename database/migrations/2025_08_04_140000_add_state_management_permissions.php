<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Crear permisos para ApprovalState
        $approvalStatePermissions = [
            'view_any_approval_state',
            'view_approval_state', 
            'create_approval_state',
            'update_approval_state',
            'delete_approval_state',
            'delete_any_approval_state',
        ];
        
        // Crear permisos para StateTransition
        $stateTransitionPermissions = [
            'view_any_state_transition',
            'view_state_transition',
            'create_state_transition', 
            'update_state_transition',
            'delete_state_transition',
            'delete_any_state_transition',
        ];
        
        $allPermissions = array_merge($approvalStatePermissions, $stateTransitionPermissions);
        
        // Crear todos los permisos
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
        
        // Asignar permisos al rol super_admin si existe
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($allPermissions);
        }
        
        // También asignar a cualquier usuario admin existente directamente
        $adminUsers = \App\Models\User::whereHas('roles', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        
        foreach ($adminUsers as $user) {
            $user->givePermissionTo($allPermissions);
        }
    }

    public function down(): void
    {
        $permissions = [
            'view_any_approval_state',
            'view_approval_state',
            'create_approval_state', 
            'update_approval_state',
            'delete_approval_state',
            'delete_any_approval_state',
            'view_any_state_transition',
            'view_state_transition',
            'create_state_transition',
            'update_state_transition', 
            'delete_state_transition',
            'delete_any_state_transition',
        ];
        
        Permission::whereIn('name', $permissions)->delete();
    }
};