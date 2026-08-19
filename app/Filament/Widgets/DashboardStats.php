<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Payments Received', 'LKR ' . number_format(Payment::sum('amount'), 2))
                ->color('success'),

            Stat::make('Total Sales', 'LKR ' . number_format(Order::where('status', '!=', 'cancelled')->sum('total_amount'), 2))
                ->description('Total value of non-cancelled orders')
                ->color('success'),

            Stat::make('Total Profit', 'LKR ' . number_format(
                \App\Models\OrderItem::whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
                    ->get()
                    ->sum(fn ($item) => ($item->unit_price - $item->cost_price) * $item->quantity)
            , 2))
                ->description('Net profit from non-cancelled orders')
                ->color('success'),

            Stat::make('Total Orders', Order::where('status', '!=', 'cancelled')->count())
                ->description('Total count of non-cancelled orders')
                ->color('primary'),

            Stat::make('Orders Today', Order::whereDate('date', today())->count())
                ->color('primary'),

            Stat::make('Low Stock Products', Product::whereColumn('stock_quantity', '<=', 'min_stock_alert')->count())
                ->description('Products below minimum stock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
