<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use Auditable, HasFactory;

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

        // El stock se consume mientras el pedido esté confirmado. Un artículo
        // añadido a un pedido ya pagado descuenta stock; en un pedido pendiente
        // solo lo reserva (eso lo lleva Product::available_stock).
        static::created(function (OrderItem $item) {
            $order = $item->order;

            if ($order?->isCommitted()) {
                $order->adjustStockBy($item, -((int) $item->quantity));
            }
        });

        static::updated(function (OrderItem $item) {
            if ($item->wasChanged('order_id')) {
                $oldOrder = Order::find($item->getOriginal('order_id'));
                $newOrder = $item->order;

                if ($oldOrder?->isCommitted()) {
                    $oldOrder->adjustStockBy($item, (int) $item->getOriginal('quantity'));
                }

                if ($newOrder?->isCommitted()) {
                    $newOrder->adjustStockBy($item, -((int) $item->quantity));
                }

                return;
            }

            if ($item->wasChanged('quantity')) {
                $order = $item->order;
                $delta = (int) $item->quantity - (int) $item->getOriginal('quantity');

                if ($delta !== 0 && $order?->isCommitted()) {
                    $order->adjustStockBy($item, -$delta);
                }
            }
        });

        static::deleted(function (OrderItem $item) {
            $order = $item->order;

            if ($order?->isCommitted()) {
                $order->adjustStockBy($item, (int) $item->quantity);
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
