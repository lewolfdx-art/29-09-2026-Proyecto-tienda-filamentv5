<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;
use App\Support\StoreNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasAutoSlug;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'description',
        'price', 'stock', 'min_stock', 'image', 'is_active',
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
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}