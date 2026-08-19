<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action, \App\Models\Product $record) {
                    if ($record->orderItems()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Cannot delete')
                            ->body('This product cannot be deleted because it is already used in an order.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
