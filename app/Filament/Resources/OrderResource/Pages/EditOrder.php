<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $items = ! empty($data['items']) ? $data['items'] : ($this->data['items'] ?? []);
        if (! empty($items)) {
            $subtotal = collect($items)->sum(function ($item) {
                $qty = is_numeric($item['quantity'] ?? null) && (float) $item['quantity'] > 0 ? (float) $item['quantity'] : 0;
                $price = is_numeric($item['unit_price'] ?? null) ? (float) $item['unit_price'] : 0;
                return $qty * $price;
            });

            $discount = is_numeric($data['discount'] ?? null) ? (float) $data['discount'] : 0;
            $type = $data['discount_type'] ?? 'fixed';

            $discountAmount = $type === 'percentage' ? $subtotal * ($discount / 100) : $discount;
            $data['total_amount'] = max(0, $subtotal - $discountAmount);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('print_invoice')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->action(function (Order $record) {
                    if (! $record->invoice) {
                        // Generate invoice if missing
                        \App\Models\Invoice::create([
                            'order_id' => $record->id,
                            'invoice_number' => \App\Models\Invoice::generateInvoiceNumber($record->id),
                            'total_amount' => $record->total_amount,
                            'balance_due' => $record->total_amount,
                            'status' => 'unpaid',
                            'issued_date' => now(),
                        ]);
                        $record->refresh();
                    }
                    // Redirect to print
                    return redirect()->route('invoices.print', $record->invoice);
                }),
        ];
    }
}
