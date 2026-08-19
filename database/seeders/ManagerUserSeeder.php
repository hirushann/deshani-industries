<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ManagerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the Manager role exists
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        // Create the manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@deshaniindustries.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('manager1234'),
            ]
        );

        // Assign the role to the user
        $manager->assignRole($managerRole);
    }
}
