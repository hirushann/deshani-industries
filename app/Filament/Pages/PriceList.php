<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PriceList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.price-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->with('productCategory'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Availability')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'In Stock' : 'Out of Stock')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->searchPlaceholder('Search by product name or SKU...')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
