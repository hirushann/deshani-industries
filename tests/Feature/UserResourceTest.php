<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_page_renders_successfully(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view_any_user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('view_any_user');

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$user]);
    }
}
