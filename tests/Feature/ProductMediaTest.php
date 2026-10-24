<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_gallery_media_as_cover_and_gallery_urls(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['image' => null]);
        $product->addMedia(UploadedFile::fake()->image('cover.jpg', 640, 480))
            ->toMediaCollection('gallery');

        $this->assertNotNull($product->cover_url);
        $this->assertStringContainsString('cover.jpg', $product->cover_url);
        $this->assertNotNull($product->cover_thumb_url);
        $this->assertStringContainsString('cover-thumb.jpg', $product->cover_thumb_url);
        $this->assertCount(1, $product->gallery_urls);
        $this->assertSame($product->cover_url, $product->gallery_urls[0]);

        $response = $this->get(route('store.show', $product->slug));

        $response->assertOk();
        $response->assertSee($product->cover_url, false);
    }

    public function test_it_falls_back_to_the_legacy_image_column(): void
    {
        $product = Product::factory()->create(['image' => 'products/legacy.jpg']);

        $expected = asset('storage/products/legacy.jpg');

        $this->assertSame($expected, $product->cover_url);
        $this->assertSame($expected, $product->cover_thumb_url);
        $this->assertSame([$expected], $product->gallery_urls);
    }
}
