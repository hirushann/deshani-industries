<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Writer;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * Canonical CSV field => list of accepted (normalized) header aliases.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $csvColumnAliases = [
        'sku' => ['sku'],
        'name' => ['name'],
        'description' => ['description'],
        'category' => ['category', 'product_category', 'productcategory_name'],
        'cost_price' => ['cost_price', 'costprice'],
        'price' => ['price', 'selling_price', 'sellingprice'],
        'stock_quantity' => ['stock_quantity', 'stock', 'quantity'],
        'min_stock_alert' => ['min_stock_alert', 'min_stock', 'minimum_stock'],
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $query = $this->getTableQueryForExport()->with('productCategory');

                    return response()->streamDownload(function () use ($query) {
                        $csv = Writer::from('php://output', 'w');

                        $csv->insertOne([
                            'sku',
                            'name',
                            'description',
                            'category',
                            'cost_price',
                            'price',
                            'stock_quantity',
                            'min_stock_alert',
                        ]);

                        $query->orderBy('id')->chunk(200, function ($products) use ($csv) {
                            foreach ($products as $product) {
                                $csv->insertOne([
                                    $product->sku,
                                    $product->name,
                                    $product->description,
                                    $product->productCategory?->name,
                                    number_format((float) $product->cost_price, 2, '.', ''),
                                    number_format((float) $product->price, 2, '.', ''),
                                    $product->stock_quantity,
                                    $product->min_stock_alert,
                                ]);
                            }
                        });
                    }, 'products-' . now()->format('Y-m-d-His') . '.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Forms\Components\Placeholder::make('instructions')
                        ->label('')
                        ->content('CSV columns: sku, name, description, category, cost_price, price, stock_quantity, min_stock_alert. "sku" is required and is used to match existing products for updates — everything else is created new. Missing optional values default to 0 (cost_price/stock_quantity) or 5 (min_stock_alert). Unknown categories are created automatically.'),
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV file')
                        ->required()
                        ->storeFiles(false)
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel']),
                ])
                ->action(function (array $data) {
                    $this->importCsv($data['file']);
                }),
        ];
    }

    protected function importCsv(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): void
    {
        $csv = Reader::from($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $rawToCanonical = [];
        foreach ($csv->getHeader() as $rawHeader) {
            $normalized = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($rawHeader))), '_');

            foreach (static::$csvColumnAliases as $canonical => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $rawToCanonical[$rawHeader] = $canonical;
                    continue 2;
                }
            }
        }

        if (! in_array('sku', $rawToCanonical, true) || ! in_array('name', $rawToCanonical, true) || ! in_array('price', $rawToCanonical, true)) {
            Notification::make()
                ->title('Import failed')
                ->body('The CSV must contain at least "sku", "name" and "price" columns.')
                ->danger()
                ->send();

            return;
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $lineNumber = 1;

        $categoryCache = [];

        foreach ($csv->getRecords() as $record) {
            $lineNumber++;

            $row = [];
            foreach ($rawToCanonical as $rawHeader => $canonical) {
                $value = $record[$rawHeader] ?? null;
                $row[$canonical] = $value === null ? null : trim((string) $value);
            }

            $validator = Validator::make($row, [
                'sku' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'stock_quantity' => ['nullable', 'integer', 'min:0'],
                'min_stock_alert' => ['nullable', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$lineNumber}: " . implode(' ', $validator->errors()->all());

                continue;
            }

            $categoryId = null;
            $categoryName = $row['category'] ?? null;

            if (filled($categoryName)) {
                $cacheKey = strtolower($categoryName);

                if (! array_key_exists($cacheKey, $categoryCache)) {
                    $category = ProductCategory::query()
                        ->whereRaw('LOWER(name) = ?', [$cacheKey])
                        ->first();

                    if (! $category) {
                        $category = ProductCategory::create(['name' => $categoryName]);
                    }

                    $categoryCache[$cacheKey] = $category->id;
                }

                $categoryId = $categoryCache[$cacheKey];
            }

            $attributes = [
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'product_category_id' => $categoryId,
                'cost_price' => is_numeric($row['cost_price'] ?? null) ? (float) $row['cost_price'] : 0,
                'price' => (float) $row['price'],
                'stock_quantity' => is_numeric($row['stock_quantity'] ?? null) ? (int) $row['stock_quantity'] : 0,
                'min_stock_alert' => is_numeric($row['min_stock_alert'] ?? null) ? (int) $row['min_stock_alert'] : 5,
            ];

            try {
                $product = Product::query()->where('sku', $row['sku'])->first();

                if ($product) {
                    $product->update($attributes);
                    $updated++;
                } else {
                    Product::create($attributes + ['sku' => $row['sku']]);
                    $created++;
                }
            } catch (\Throwable $exception) {
                $errors[] = "Row {$lineNumber}: " . $exception->getMessage();
            }
        }

        $body = "{$created} created, {$updated} updated.";

        if ($errors) {
            $body .= ' ' . count($errors) . ' row(s) skipped: ' . implode(' | ', array_slice($errors, 0, 10));

            if (count($errors) > 10) {
                $body .= ' …and ' . (count($errors) - 10) . ' more.';
            }
        }

        Notification::make()
            ->title('Import complete')
            ->body($body)
            ->color($errors ? 'warning' : 'success')
            ->persistent()
            ->send();
    }
}
