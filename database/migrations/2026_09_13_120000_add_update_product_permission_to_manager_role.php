<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'update_product', 'guard_name' => 'web']);

        $roles = Role::whereIn('name', ['Manager', 'manager'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = Role::whereIn('name', ['Manager', 'manager'])->get();
        foreach ($roles as $role) {
            $role->revokePermissionTo('update_product');
        }

        $permission = Permission::where('name', 'update_product')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
