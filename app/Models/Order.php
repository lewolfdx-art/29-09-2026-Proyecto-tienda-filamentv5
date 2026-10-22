<?php

namespace App\Models;

use App\Support\StoreNotifier;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const COMMITTED_STATUSES = ['paid', 'shipped', 'delivered'];

    public const STATUS_LABELS = [
        'pending' => 'Pendiente de pago',
        'paid' => 'Pagado',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'expired' => 'Expirado',
    ];

    protected $fillable = ['customer_id', 'status', 'total', 'notes', 'expires_at'];

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            // Un pedido creado ya pagado avisa al equipo, pero todavía no tiene
            // artículos: el stock se descuenta cuando se añadan (ver OrderItem).
            if ($order->status === 'paid') {
                StoreNotifier::orderPaid($order);
            }
        });

        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $wasCommitted = in_array($order->getOriginal('status'), self::COMMITTED_STATUSES, true);
            $isCommitted = $order->isCommitted();

            if (! $wasCommitted && $isCommitted) {
                $order->adjustStock(-1);

                if ($order->status === 'paid') {
                    StoreNotifier::orderPaid($order);
                }
            } elseif ($wasCommitted && ! $isCommitted) {
                $order->adjustStock(1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Un pedido confirmado (pagado, enviado o entregado) ya consumió el stock
     * de sus artículos. Los pendientes solo lo reservan.
     */
    public function isCommitted(): bool
    {
        return in_array($this->status, self::COMMITTED_STATUSES, true);
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
            $this->adjustStockBy($item, $direction * (int) $item->quantity);
        }
    }

    /**
     * Aplica un cambio de stock de un artículo.
     *
     * El delta es la variación con signo del stock: negativo lo descuenta
     * (venta) y positivo lo devuelve (devolución). El stock nunca queda
     * negativo.
     */
    public function adjustStockBy(OrderItem $item, int $delta): void
    {
        $product = Product::find($item->product_id);

        if (! $product || $delta === 0) {
            return;
        }

        $before = (int) $product->stock;
        $after = max(0, $before + $delta);

        if ($after === $before) {
            return;
        }

        Product::where('id', $product->id)->update(['stock' => $after]);

        if ($delta < 0 && StoreNotifier::crossedLowStock($before, $after, (int) $product->min_stock)) {
            StoreNotifier::lowStock($product);
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
                $missing[] = $item->product->name.' (stock: '.$item->product->stock.', pedido: '.$item->quantity.')';
            }
        }

        return $missing;
    }
}
