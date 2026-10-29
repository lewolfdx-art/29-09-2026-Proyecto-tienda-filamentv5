<?php

namespace Database\Seeders;

use App\Models\Category;
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

        // Las categorías no vienen en el JSON: un producto cuya categoría no existe
        // queda sin categoría en lugar de romper la clave foránea.
        $categorias = Category::query()->pluck('id')->flip();

        Product::withoutEvents(function () use ($productos, $categorias) {
            foreach ($productos as $p) {
                if (! is_array($p) || ! isset($p['id'])) {
                    continue;
                }

                $oculto = $p['deleted_at'] ?? null;
                unset($p['deleted_at']);

                if (isset($p['category_id']) && ! $categorias->has($p['category_id'])) {
                    $p['category_id'] = null;
                }

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
