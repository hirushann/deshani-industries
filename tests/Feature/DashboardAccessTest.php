<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_access_dashboard(): void
    {
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $this->actingAs($manager);
        $this->assertFalse(Dashboard::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(Dashboard::canAccess());
    }

    public function test_dashboard_is_hidden_from_manager_navigation(): void
    {
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($manager);

        $navigationItems = [];
        foreach (Filament::getNavigation() as $group) {
            foreach ($group->getItems() as $item) {
                $navigationItems[] = $item->getLabel();
            }
        }

        $this->assertNotContains('My Dashboard', $navigationItems);
        $this->assertNotContains('Dashboard', $navigationItems);
    }

    public function test_manager_visiting_dashboard_is_redirected_away(): void
    {
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($manager);

        $test = Livewire::test(Dashboard::class);
        $test->assertRedirect();
    }
}
