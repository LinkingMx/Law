<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_configurable_workflow","view_any_configurable_workflow","create_configurable_workflow","update_configurable_workflow","restore_configurable_workflow","restore_any_configurable_workflow","replicate_configurable_workflow","reorder_configurable_workflow","delete_configurable_workflow","delete_any_configurable_workflow","force_delete_configurable_workflow","force_delete_any_configurable_workflow","view_documentation","view_any_documentation","create_documentation","update_documentation","restore_documentation","restore_any_documentation","replicate_documentation","reorder_documentation","delete_documentation","delete_any_documentation","force_delete_documentation","force_delete_any_documentation","view_email_configuration","view_any_email_configuration","create_email_configuration","update_email_configuration","restore_email_configuration","restore_any_email_configuration","replicate_email_configuration","reorder_email_configuration","delete_email_configuration","delete_any_email_configuration","force_delete_email_configuration","force_delete_any_email_configuration","view_email_template","view_any_email_template","create_email_template","update_email_template","restore_email_template","restore_any_email_template","replicate_email_template","reorder_email_template","delete_email_template","delete_any_email_template","force_delete_email_template","force_delete_any_email_template","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_workflow_execution","view_any_workflow_execution","create_workflow_execution","update_workflow_execution","restore_workflow_execution","restore_any_workflow_execution","replicate_workflow_execution","reorder_workflow_execution","delete_workflow_execution","delete_any_workflow_execution","force_delete_workflow_execution","force_delete_any_workflow_execution","view_activity","view_any_activity","create_activity","update_activity","restore_activity","restore_any_activity","replicate_activity","reorder_activity","delete_activity","delete_any_activity","force_delete_activity","force_delete_any_activity","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_menu","view_any_menu","create_menu","update_menu","restore_menu","restore_any_menu","replicate_menu","reorder_menu","delete_menu","delete_any_menu","force_delete_menu","force_delete_any_menu","view_exception","view_any_exception","create_exception","update_exception","restore_exception","restore_any_exception","replicate_exception","reorder_exception","delete_exception","delete_any_exception","force_delete_exception","force_delete_any_exception","page_BackupConfiguration","page_BackupHistory","page_BackupManager","page_GeneralSettings","page_AppearanceSettings","page_LocalizationSettings","widget_AccountWidget","widget_FilamentInfoWidget"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}