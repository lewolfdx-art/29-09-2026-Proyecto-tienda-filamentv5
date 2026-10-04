<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const KEY = 'cart';

    /**
     * @return array<int, int> id del producto => cantidad
     */
    public static function items(): array
    {
        return session(self::KEY, []);
    }

    public static function quantityOf(int $productId): int
    {
        return (int) (self::items()[$productId] ?? 0);
    }

    public static function set(int $productId, int $quantity): void
    {
        $items = self::items();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        session([self::KEY => $items]);
    }

    public static function remove(int $productId): void
    {
        self::set($productId, 0);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    /**
     * Líneas del carrito con los datos reales de la base de datos.
     * Los productos que ya no se pueden vender se sacan del carrito.
     */
    public static function lines(): Collection
    {
        $items = self::items();

        if (empty($items)) {
            return collect();
        }

        $products = Product::visibleInStore()
            ->withReserved()
            ->whereIn('products.id', array_keys($items))
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $id => $quantity) {
            $product = $products->get($id);

            if (! $product) {
                self::remove((int) $id);

                continue;
            }

            $lines->push([
                'product' => $product,
                'quantity' => (int) $quantity,
                'subtotal' => round((float) $product->price * (int) $quantity, 2),
                'in_stock' => $product->available_stock >= (int) $quantity,
            ]);
        }

        return $lines;
    }

    public static function total(): float
    {
        return round((float) self::lines()->sum('subtotal'), 2);
    }
}