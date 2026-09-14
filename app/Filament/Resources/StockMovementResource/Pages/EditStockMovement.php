<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStockMovement extends EditRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var StockMovement $record */
        $record->revertFromProduct();
        $record->update($data);
        $record->refresh();
        $record->applyToProduct();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn (StockMovement $record) => $record->revertFromProduct()),
        ];
    }
}
