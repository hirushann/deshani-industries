<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_resource_does_not_have_edit_page(): void
    {
        $this->assertFalse(ProductResource::hasPage('edit'));
    }

    public function test_product_edit_action_opens_modal_and_updates_product(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view_any_product', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'update_product', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo(['view_any_product', 'update_product']);
        $category = ProductCategory::create(['name' => 'Hardware']);
        $product = Product::create([
            'name' => 'Original Name',
            'sku' => 'SKU-001',
            'cost_price' => 100.00,
            'price' => 150.00,
            'stock_quantity' => 10,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(ListProducts::class)
            ->assertTableActionExists('edit')
            ->mountTableAction('edit', $product)
            ->assertTableActionMounted('edit')
            ->setTableActionData([
                'name' => 'Updated Product Name',
                'sku' => 'SKU-001-UPDATED',
                'cost_price' => 120.00,
                'price' => 180.00,
                'stock_quantity' => 15,
                'min_stock_alert' => 3,
                'product_category_id' => $category->id,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $product->refresh();

        $this->assertSame('Updated Product Name', $product->name);
        $this->assertSame('SKU-001-UPDATED', $product->sku);
        $this->assertEquals(120.00, $product->cost_price);
        $this->assertEquals(180.00, $product->price);
        $this->assertSame(15, $product->stock_quantity);
        $this->assertSame(3, $product->min_stock_alert);
    }

    public function test_manager_role_can_edit_product(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $managerRole = \Spatie\Permission\Models\Role::findByName('Manager', 'web');
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        $category = ProductCategory::create(['name' => 'Tools']);
        $product = Product::create([
            'name' => 'Original Tool',
            'sku' => 'TOOL-001',
            'cost_price' => 50.00,
            'price' => 80.00,
            'stock_quantity' => 20,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->actingAs($manager);

        Livewire::test(ListProducts::class)
            ->assertTableActionExists('edit')
            ->mountTableAction('edit', $product)
            ->assertTableActionMounted('edit')
            ->setTableActionData([
                'name' => 'Updated Tool',
                'sku' => 'TOOL-001-NEW',
                'cost_price' => 60.00,
                'price' => 95.00,
                'stock_quantity' => 25,
                'min_stock_alert' => 4,
                'product_category_id' => $category->id,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $product->refresh();
        $this->assertSame('Updated Tool', $product->name);
        $this->assertSame(25, $product->stock_quantity);
    }

    public function test_manager_role_cannot_delete_product(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $managerRole = \Spatie\Permission\Models\Role::findByName('Manager', 'web');
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        $category = ProductCategory::create(['name' => 'Tools']);
        $product = Product::create([
            'name' => 'Original Tool',
            'sku' => 'TOOL-002',
            'cost_price' => 50.00,
            'price' => 80.00,
            'stock_quantity' => 20,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->actingAs($manager);

        $this->assertFalse($manager->can('delete', $product));
        $this->assertFalse($manager->can('deleteAny', Product::class));

        Livewire::test(ListProducts::class)
            ->assertTableActionHidden('delete', $product);
    }
}

