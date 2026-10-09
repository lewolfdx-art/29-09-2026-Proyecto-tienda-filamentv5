<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;
use App\Support\StoreNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasAutoSlug;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'description',
        'price', 'stock', 'min_stock', 'image', 'is_active', 'is_featured',
    ];

    protected static function booted(): void
    {
        static::updated(function (Product $product) {
            if (! $product->wasChanged('stock')) {
                return;
            }

            if (StoreNotifier::crossedLowStock(
                (int) $product->getOriginal('stock'),
                (int) $product->stock,
                (int) $product->min_stock,
            )) {
                StoreNotifier::lowStock($product);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Stock físico menos lo reservado por pedidos pendientes que no han vencido.
     */
    protected function availableStock(): Attribute
    {
        return Attribute::get(function () {
            $reserved = array_key_exists('reserved', $this->attributes)
                ? (int) $this->attributes['reserved']
                : $this->reservedQuantity();

            return max(0, (int) $this->stock - $reserved);
        });
    }

    public function reservedQuantity(): int
    {
        return (int) self::pendingReservationsQuery()
            ->where('order_items.product_id', $this->id)
            ->sum('order_items.quantity');
    }

    /**
     * Artículos de pedidos pendientes que siguen reservando stock:
     * los que no tienen vencimiento (hechos en el panel) o aún no vencen.
     */
    public static function pendingReservationsQuery(): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'pending')
            ->where(function ($query) {
                $query->whereNull('orders.expires_at')
                    ->orWhere('orders.expires_at', '>', now());
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('products.is_featured', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Productos que un cliente puede ver: activos y, si tienen categoría,
     * que esa categoría también esté activa.
     */
    public function scopeVisibleInStore(Builder $query): Builder
    {
        return $query->where('is_active', true)->where(function (Builder $q) {
            $q->whereNull('category_id')
                ->orWhereHas('category', fn (Builder $c) => $c->where('is_active', true));
        });
    }

    /**
     * Agrega la columna "reserved" en la misma consulta (evita una consulta por producto).
     * Solo para leer: estos modelos no se deben guardar.
     */
    public function scopeWithReserved(Builder $query): Builder
    {
        return $query->select('products.*')->addSelect([
            'reserved' => self::pendingReservationsQuery()
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                ->whereColumn('order_items.product_id', 'products.id'),
        ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}