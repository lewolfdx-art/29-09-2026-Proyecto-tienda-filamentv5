<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\File;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->exportar();
    }

    public function deleted(Product $product): void
    {
        $this->exportar();
    }

    public function restored(Product $product): void
    {
        $this->exportar();
    }

    private function exportar(): void
    {
        // Solo en local: en el servidor el archivo es parte del código y modificarlo
        // haría fallar el git pull del siguiente despliegue.
        if (! app()->isLocal() || app()->runningUnitTests()) {
            return;
        }

        File::put(
            database_path('seeders/products.json'),
            Product::withTrashed()->get()->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
