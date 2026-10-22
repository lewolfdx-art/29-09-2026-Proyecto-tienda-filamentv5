<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_visible_products(): void
    {
        Product::factory()->create(['name' => 'Camiseta Visible']);
        Product::factory()->inactive()->create(['name' => 'Camiseta Oculta']);
        Product::factory()->create(['name' => 'Camiseta Sin categoria', 'category_id' => null]);

        $response = $this->get(route('store.index'));

        $response->assertOk();
        $response->assertSee('Camiseta Visible');
        $response->assertSee('Camiseta Sin categoria');
        $response->assertDontSee('Camiseta Oculta');
    }

    public function test_it_hides_products_of_inactive_categories(): void
    {
        $category = Category::factory()->inactive()->create();
        Product::factory()->create(['name' => 'Producto Oculto', 'category_id' => $category->id]);

        $response = $this->get(route('store.index'));

        $response->assertOk();
        $response->assertDontSee('Producto Oculto');
    }

    public function test_it_searches_by_name(): void
    {
        Product::factory()->create(['name' => 'Camiseta Algodon']);
        Product::factory()->create(['name' => 'Zapatos Deportivos']);

        $response = $this->get(route('store.index', ['q' => 'algodon']));

        $response->assertOk();
        $response->assertSee('Camiseta Algodon');
        $response->assertDontSee('Zapatos Deportivos');
    }

    public function test_it_searches_by_description(): void
    {
        Product::factory()->create([
            'name' => 'Teclado Mecanico',
            'description' => 'Teclado con switches Cherry MX',
        ]);
        Product::factory()->create([
            'name' => 'Mouse Inalambrico',
            'description' => 'Mouse con receptor USB',
        ]);

        $response = $this->get(route('store.index', ['q' => 'cherry']));

        $response->assertOk();
        $response->assertSee('Teclado Mecanico');
        $response->assertDontSee('Mouse Inalambrico');
    }

    /**
     * El "%" debe buscarse de forma literal: si actuara como comodín,
     * "100%" devolvería todo el catálogo.
     */
    public function test_the_percent_sign_is_searched_literally(): void
    {
        Product::factory()->create(['name' => 'Camiseta 100% Algodon']);
        Product::factory()->create(['name' => 'Camiseta 100 Algodon']);

        $response = $this->get(route('store.index', ['q' => '100%']));

        $response->assertOk();
        $response->assertSee('Camiseta 100% Algodon');
        $response->assertDontSee('Camiseta 100 Algodon');
    }

    /**
     * El "_" también es comodín en LIKE: "a_b" no debe encontrar "axb".
     */
    public function test_the_underscore_is_searched_literally(): void
    {
        Product::factory()->create(['name' => 'Teclado a_b especial']);
        Product::factory()->create(['name' => 'Teclado axb especial']);

        $response = $this->get(route('store.index', ['q' => 'a_b']));

        $response->assertOk();
        $response->assertSee('Teclado a_b especial');
        $response->assertDontSee('Teclado axb especial');
    }

    public function test_it_filters_by_category(): void
    {
        $category = Category::factory()->create(['name' => 'Ropa']);
        $other = Category::factory()->create(['name' => 'Electronicos']);

        Product::factory()->create(['name' => 'Camiseta', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Laptop', 'category_id' => $other->id]);

        $response = $this->get(route('store.index', ['categoria' => $category->slug]));

        $response->assertOk();
        $response->assertSee('Camiseta');
        $response->assertDontSee('Laptop');
    }

    public function test_it_sorts_by_price(): void
    {
        Product::factory()->create(['name' => 'Producto Barato', 'price' => 10]);
        Product::factory()->create(['name' => 'Producto Caro', 'price' => 500]);

        $response = $this->get(route('store.index', ['orden' => 'precio_asc']));

        $response->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), 'Producto Caro'),
            strpos($response->getContent(), 'Producto Barato')
        );
    }

    public function test_it_returns_404_for_an_unknown_product(): void
    {
        $this->get(route('store.show', 'producto-inexistente'))->assertNotFound();
    }
}
