<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const COMMITTED_STATUSES = ['paid', 'shipped', 'delivered'];

    protected $fillable = ['customer_id', 'status', 'total', 'notes'];

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $wasCommitted = in_array($order->getOriginal('status'), self::COMMITTED_STATUSES);
            $isCommitted = in_array($order->status, self::COMMITTED_STATUSES);

            if (! $wasCommitted && $isCommitted) {
                $order->adjustStock(-1);
            } elseif ($wasCommitted && ! $isCommitted) {
                $order->adjustStock(1);
            }
        });
    }

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total' => $this->items()->get()->sum(fn ($item) => $item->quantity * $item->unit_price),
        ]);
    }

    /**
     * -1 descuenta stock (venta confirmada), 1 lo devuelve (cancelación).
     */
    public function adjustStock(int $direction): void
    {
        foreach ($this->items()->get() as $item) {
            Product::where('id', $item->product_id)
                ->increment('stock', $direction * $item->quantity);
        }
    }

    /**
     * Devuelve los productos que no tienen stock suficiente para este pedido.
     */
    public function productsWithoutStock(): array
    {
        $missing = [];

        foreach ($this->items()->with('product')->get() as $item) {
            if ($item->product && $item->product->stock < $item->quantity) {
                $missing[] = $item->product->name . ' (stock: ' . $item->product->stock . ', pedido: ' . $item->quantity . ')';
            }
        }

        return $missing;
    }
}