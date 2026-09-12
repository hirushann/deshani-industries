<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_number_format(): void
    {
        $this->assertSame('DI-INV-001', Invoice::generateInvoiceNumber(1));
        $this->assertSame('DI-INV-002', Invoice::generateInvoiceNumber(2));
        $this->assertSame('DI-INV-025', Invoice::generateInvoiceNumber(25));
        $this->assertSame('DI-INV-100', Invoice::generateInvoiceNumber(100));
        $this->assertSame('DI-INV-1000', Invoice::generateInvoiceNumber(1000));
    }

    public function test_order_creation_automatically_generates_invoice_with_di_inv_format(): void
    {
        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0771234567',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'status' => 'pending',
            'total_amount' => 2500.00,
        ]);

        $order->refresh();
        $this->assertNotNull($order->invoice);
        $expectedNumber = 'DI-INV-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
        $this->assertSame($expectedNumber, $order->invoice->invoice_number);

        // For the first order in fresh DB, it should be DI-INV-001
        $this->assertSame('DI-INV-001', $order->invoice->invoice_number);

        // Create second order
        $order2 = Order::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'status' => 'pending',
            'total_amount' => 1500.00,
        ]);

        $order2->refresh();
        $this->assertSame('DI-INV-002', $order2->invoice->invoice_number);
    }

    public function test_invoice_model_hook_fills_missing_invoice_number(): void
    {
        $customer = Customer::create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'status' => 'pending',
            'total_amount' => 500.00,
        ]);

        // Delete the auto-created invoice to test manual creation
        $order->invoice()->delete();

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'total_amount' => 500.00,
            'balance_due' => 500.00,
            'status' => 'unpaid',
            'issued_date' => now(),
        ]);

        $this->assertSame('DI-INV-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT), $invoice->invoice_number);
    }
}
