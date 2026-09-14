<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0771112233',
        ]);

        $category = ProductCategory::create(['name' => 'Standard']);
        $this->productA = Product::create([
            'name' => 'Product A',
            'sku' => 'PRD-A',
            'cost_price' => 50.00,
            'price' => 100.00,
            'stock_quantity' => 100,
            'min_stock_alert' => 10,
            'product_category_id' => $category->id,
        ]);

        $this->productB = Product::create([
            'name' => 'Product B',
            'sku' => 'PRD-B',
            'cost_price' => 80.00,
            'price' => 200.00,
            'stock_quantity' => 50,
            'min_stock_alert' => 5,
            'product_category_id' => $category->id,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
    }

    public function test_order_creation_calculates_quantity_and_totals_accurately(): void
    {
        $test = Livewire::test(CreateOrder::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'status' => 'pending',
                'date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 5,
                        'unit_price' => 100.00,
                        'subtotal' => 500.00,
                    ],
                ],
                'discount_type' => 'fixed',
                'discount' => 50.00,
                'total_amount' => 450.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(450.00, $order->total_amount);

        $orderItem = $order->items->first();
        $this->assertNotNull($orderItem);
        $this->assertSame(5, $orderItem->quantity);
        $this->assertEquals(100.00, $orderItem->unit_price);
        $this->assertEquals(500.00, $orderItem->subtotal);
    }

    public function test_order_creation_with_custom_quantity_persists_correctly(): void
    {
        Livewire::test(CreateOrder::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'status' => 'pending',
                'date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->productB->id,
                        'quantity' => 12,
                        'unit_price' => 200.00,
                        'subtotal' => 2400.00,
                    ],
                ],
                'discount_type' => 'percentage',
                'discount' => 10.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        // 12 * 200 = 2400, minus 10% (240) = 2160
        $this->assertEquals(2160.00, $order->total_amount);

        $item = $order->items->first();
        $this->assertSame(12, $item->quantity);
        $this->assertEquals(200.00, $item->unit_price);
        $this->assertEquals(2400.00, $item->subtotal);
    }

    public function test_quantity_input_updates_subtotal_and_grand_total(): void
    {
        Livewire::test(CreateOrder::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'status' => 'pending',
                'date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 7,
                        'unit_price' => 100.00,
                        'subtotal' => 700.00,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(700.00, $order->total_amount);
        $this->assertSame(7, $order->items->first()->quantity);
    }
}

