<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Apply the stock movement to the associated product.
     */
    public function applyToProduct(): void
    {
        $product = $this->product;
        if (! $product) {
            return;
        }

        $quantity = abs((int) $this->quantity);

        if ($this->type === 'in') {
            $product->increment('stock_quantity', $quantity);
        } elseif ($this->type === 'out') {
            $product->decrement('stock_quantity', $quantity);
        } elseif ($this->type === 'adjustment') {
            $product->update(['stock_quantity' => (int) $this->quantity]);
        }
    }

    /**
     * Revert the stock movement effect from the associated product.
     */
    public function revertFromProduct(): void
    {
        $product = $this->product;
        if (! $product) {
            return;
        }

        $quantity = abs((int) $this->quantity);

        if ($this->type === 'in') {
            $product->decrement('stock_quantity', $quantity);
        } elseif ($this->type === 'out') {
            $product->increment('stock_quantity', $quantity);
        }
    }
}

