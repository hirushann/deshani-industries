<?php

namespace Tests\Feature;

use App\Filament\Pages\PriceList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PriceListPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_list_page_renders_stock_quantity(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::create(['name' => 'Tools']);

        $inStockProduct = Product::create([
            'name' => 'Hammer',
            'sku' => 'HAM-001',
            'cost_price' => 200.00,
            'price' => 350.00,
            'stock_quantity' => 45,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        $outOfStockProduct = Product::create([
            'name' => 'Drill',
            'sku' => 'DRL-002',
            'cost_price' => 1200.00,
            'price' => 1800.00,
            'stock_quantity' => 0,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(PriceList::class)
            ->assertSuccessful()
            ->assertTableColumnExists('stock_quantity')
            ->assertCanSeeTableRecords([$inStockProduct, $outOfStockProduct])
            ->assertSee('45')
            ->assertSee('0')
            ->assertDontSee('In Stock')
            ->assertDontSee('Out of Stock');
    }
}
