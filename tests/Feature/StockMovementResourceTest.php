<?php

namespace Tests\Feature;

use App\Filament\Resources\StockMovementResource;
use App\Filament\Resources\StockMovementResource\Pages\CreateStockMovement;
use App\Filament\Resources\StockMovementResource\Pages\EditStockMovement;
use App\Filament\Resources\StockMovementResource\Pages\ListStockMovements;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockMovementResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $category = ProductCategory::create(['name' => 'General']);
        $this->product = Product::create([
            'name' => 'Safety Gloves',
            'sku' => 'SFT-GLV-01',
            'cost_price' => 50.00,
            'price' => 100.00,
            'stock_quantity' => 50,
            'min_stock_alert' => 10,
            'product_category_id' => $category->id,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
    }

    public function test_stock_movement_form_has_searchable_product_select(): void
    {
        Livewire::test(CreateStockMovement::class)
            ->assertFormFieldExists('product_id');

        $component = StockMovementResource::form(new \Filament\Forms\Form(Livewire::test(CreateStockMovement::class)->instance()))
            ->getComponent('product_id');

        $this->assertTrue($component->isSearchable());
    }

    public function test_creating_in_stock_movement_increments_product_stock(): void
    {
        $this->assertSame(50, $this->product->stock_quantity);

        Livewire::test(CreateStockMovement::class)
            ->fillForm([
                'product_id' => $this->product->id,
                'type' => 'in',
                'quantity' => 20,
                'date' => now()->toDateString(),
                'reference' => 'PO-1001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->product->refresh();
        $this->assertSame(70, $this->product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 20,
        ]);
    }

    public function test_creating_out_stock_movement_decrements_product_stock(): void
    {
        $this->assertSame(50, $this->product->stock_quantity);

        Livewire::test(CreateStockMovement::class)
            ->fillForm([
                'product_id' => $this->product->id,
                'type' => 'out',
                'quantity' => 15,
                'date' => now()->toDateString(),
                'reference' => 'DMG-001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->product->refresh();
        $this->assertSame(35, $this->product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 15,
        ]);
    }

    public function test_creating_adjustment_stock_movement_sets_product_stock(): void
    {
        $this->assertSame(50, $this->product->stock_quantity);

        Livewire::test(CreateStockMovement::class)
            ->fillForm([
                'product_id' => $this->product->id,
                'type' => 'adjustment',
                'quantity' => 80,
                'date' => now()->toDateString(),
                'reference' => 'AUDIT-2026',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->product->refresh();
        $this->assertSame(80, $this->product->stock_quantity);
    }

    public function test_editing_stock_movement_adjusts_product_stock(): void
    {
        // Initial in movement of 20 -> stock becomes 70
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 20,
            'date' => now()->toDateString(),
        ]);
        $movement->applyToProduct();
        $this->product->refresh();
        $this->assertSame(70, $this->product->stock_quantity);

        // Edit movement quantity to 30 -> stock should become 80
        Livewire::test(EditStockMovement::class, ['record' => $movement->getRouteKey()])
            ->fillForm([
                'quantity' => 30,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->product->refresh();
        $this->assertSame(80, $this->product->stock_quantity);
    }

    public function test_deleting_stock_movement_reverts_product_stock(): void
    {
        // Initial in movement of 25 -> stock becomes 75
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 25,
            'date' => now()->toDateString(),
        ]);
        $movement->applyToProduct();
        $this->product->refresh();
        $this->assertSame(75, $this->product->stock_quantity);

        // Delete from edit page
        Livewire::test(EditStockMovement::class, ['record' => $movement->getRouteKey()])
            ->callAction('delete');

        $this->product->refresh();
        $this->assertSame(50, $this->product->stock_quantity);
    }

    public function test_table_delete_action_reverts_product_stock(): void
    {
        // Initial out movement of 10 -> stock becomes 40
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 10,
            'date' => now()->toDateString(),
        ]);
        $movement->applyToProduct();
        $this->product->refresh();
        $this->assertSame(40, $this->product->stock_quantity);

        // Delete from table action
        Livewire::test(ListStockMovements::class)
            ->callTableAction('delete', $movement);

        $this->product->refresh();
        $this->assertSame(50, $this->product->stock_quantity);
    }
}
