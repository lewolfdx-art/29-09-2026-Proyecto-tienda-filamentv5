<?php

namespace App\Models;

use App\Mail\OrderStatusUpdated;
use App\Models\Concerns\Auditable;
use App\Support\StoreNotifier;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use Auditable, HasFactory;

    public const COMMITTED_STATUSES = ['paid', 'shipped', 'delivered'];

    public const STATUS_LABELS = [
        'pending' => 'Pendiente de pago',
        'paid' => 'Pagado',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'expired' => 'Expirado',
    ];

    protected $fillable = [
        'customer_id', 'status', 'total', 'notes',
        'phone', 'address', 'city', 'expires_at',
        'payment_method', 'payment_reference', 'payment_proof_path',
        'payment_submitted_at', 'payment_verified_at', 'payment_verified_by',
        'payment_rejection_reason',
    ];

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

            $previousStatus = (string) $order->getOriginal('status');
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

            $order->notifyStatusChange($previousStatus);
        });

        // Al aprobar un pago dejamos registro de cuándo y quién lo validó.
        static::updating(function (Order $order) {
            if (! $order->isDirty('status')) {
                return;
            }

            if ($order->status === 'paid' && $order->getOriginal('status') !== 'paid') {
                $order->payment_verified_at = $order->payment_verified_at ?: now();
                $order->payment_verified_by = $order->payment_verified_by ?: auth('web')->id();
                $order->payment_rejection_reason = null;
            }
        });

        // Si se borra un pedido confirmado, devolvemos su stock antes de que la
        // cascada de la base de datos elimine los artículos sin disparar eventos.
        static::deleting(function (Order $order) {
            if ($order->isCommitted()) {
                $order->adjustStock(1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'expires_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'payment_verified_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditExcludedAttributes(): array
    {
        return ['phone', 'address'];
    }

    public function statusLabel(): string
    {
        return $this->statusLabelFor($this->status);
    }

    public function statusLabelFor(?string $status): string
    {
        return self::STATUS_LABELS[$status ?? ''] ?? (string) $status;
    }

    public function paymentMethodLabel(): string
    {
        $label = config("payments.methods.{$this->payment_method}.label");

        return $label ?: ($this->payment_method ? ucfirst($this->payment_method) : 'Sin definir');
    }

    public function paymentProofUrl(): ?string
    {
        if (blank($this->payment_proof_path)) {
            return null;
        }

        return Storage::disk(config('payments.proof_disk'))->url($this->payment_proof_path);
    }

    public function paymentProofIsImage(): bool
    {
        if (blank($this->payment_proof_path)) {
            return false;
        }

        return in_array(strtolower(pathinfo($this->payment_proof_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function paymentIsUnderReview(): bool
    {
        return $this->status === 'pending'
            && filled($this->payment_submitted_at)
            && blank($this->payment_rejection_reason);
    }

    public function paymentWasRejected(): bool
    {
        return $this->status === 'pending' && filled($this->payment_rejection_reason);
    }

    /**
     * Avisa al cliente por correo cuando el pedido cambia de estado.
     */
    private function notifyStatusChange(string $previousStatus): void
    {
        $email = $this->customer?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->queue(new OrderStatusUpdated($this, $previousStatus));
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

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
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
