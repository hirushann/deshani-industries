<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Actions\Action::make('create_and_print')
                ->label('Create & Print')
                ->action(function () {
                    $this->create();
                    $order = $this->record;
                    if ($order && $order->invoice) {
                         return redirect()->route('invoices.print', $order->invoice);
                    }
                }),
            $this->getCancelFormAction(),
        ];
    }
}
