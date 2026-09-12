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
}
