<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_product_outside_local_does_not_touch_the_seeder_file(): void
    {
        $path = database_path('seeders/products.json');
        $before = File::get($path);

        $this->app['env'] = 'production';

        Product::factory()->create();

        $this->assertSame($before, File::get($path));
    }
}
