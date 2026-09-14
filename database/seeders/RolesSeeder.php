<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure permissions exist
        $employeePermissions = [
            'view_any_order', 'view_order', 'create_order', 
            'view_any_product', 'view_product', 
            'view_any_customer', 'view_customer', 'create_customer',
            'view_any_invoice', 'view_invoice',
        ];

        $managerPermissions = array_merge($employeePermissions, [
            'update_product',
        ]);

        foreach ($managerPermissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Employee Role
        $employeeRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $employeeRole->syncPermissions($employeePermissions);

        // Manager Roles (Manager and manager)
        foreach (['Manager', 'manager'] as $roleName) {
            $managerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $managerRole->syncPermissions($managerPermissions);
        }

        // Admin Role
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }
}
