<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/products.json');

        if (! file_exists($path)) {
            return;
        }

        $productos = json_decode(file_get_contents($path), true);

        if (! is_array($productos)) {
            return;
        }

        Product::withoutEvents(function () use ($productos) {
            foreach ($productos as $p) {
                if (! is_array($p) || ! isset($p['id'])) {
                    continue;
                }

                $oculto = $p['deleted_at'] ?? null;
                unset($p['deleted_at']);

                $producto = Product::withTrashed()->updateOrCreate(['id' => $p['id']], $p);

                if ($oculto) {
                    $producto->delete();
                } else {
                    $producto->restore();
                }
            }
        });
    }
}
