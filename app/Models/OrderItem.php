<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price'];

    protected static function booted(): void
    {
        // El precio siempre sale del producto al crear el artículo
        // o al cambiarle el producto. Si solo cambia la cantidad, se conserva.
        static::saving(function (OrderItem $item) {
            if ($item->isDirty('product_id')) {
                $price = Product::find($item->product_id)?->price;

                if ($price !== null) {
                    $item->unit_price = $price;
                }
            }
        });

        static::saved(fn (OrderItem $item) => $item->order?->recalculateTotal());
        static::deleted(fn (OrderItem $item) => $item->order?->recalculateTotal());
    }

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}