<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_by_name(): void
    {
        Customer::factory()->create(['name' => 'María Fernández']);
        Customer::factory()->create(['name' => 'Carlos Ramírez']);

        $found = Customer::search('María')->get();

        $this->assertCount(1, $found);
        $this->assertSame('María Fernández', $found->first()->name);
    }

    public function test_it_searches_by_email(): void
    {
        Customer::factory()->create(['email' => 'maria@ejemplo.com']);
        Customer::factory()->create(['email' => 'carlos@ejemplo.com']);

        $this->assertCount(1, Customer::search('carlos@ejemplo.com')->get());
    }

    public function test_it_searches_by_city(): void
    {
        Customer::factory()->create(['city' => 'Miraflores']);
        Customer::factory()->create(['city' => 'Arequipa']);

        $this->assertCount(1, Customer::search('Miraflores')->get());
    }

    /**
     * El teléfono se guarda cifrado, así que el LIKE sobre la columna nunca
     * encuentra nada. La búsqueda tiene que usar el índice ciego.
     */
    public function test_it_searches_by_phone(): void
    {
        Customer::factory()->create(['phone' => '+51 987 654 321']);
        Customer::factory()->create(['phone' => '+51 900 111 222']);

        $found = Customer::search('987654321')->get();

        $this->assertCount(1, $found);
        $this->assertSame('+51 987 654 321', $found->first()->phone);
    }

    public function test_the_phone_search_ignores_the_country_code_and_the_format(): void
    {
        Customer::factory()->create(['phone' => '+51 987 654 321']);

        foreach (['987654321', '987 654 321', '+51 987 654 321', '51987654321', '(51) 987-654-321'] as $term) {
            $this->assertCount(1, Customer::search($term)->get(), "No encontró con: {$term}");
        }
    }

    public function test_the_address_search_finds_a_customer_by_any_word(): void
    {
        Customer::factory()->create([
            'address' => 'Av. Siempre Viva 742, Miraflores',
        ]);
        Customer::factory()->create([
            'address' => 'Jr. Las Begonias 123, San Isidro',
        ]);

        $this->assertCount(1, Customer::search('Miraflores')->get());
        $this->assertCount(1, Customer::search('Begonias')->get());
    }

    public function test_the_address_search_ignores_accents_and_case(): void
    {
        Customer::factory()->create(['address' => 'Calle Cañón deideas']);
        Customer::factory()->create(['address' => 'Avenida Cañón de ideas']);

        $this->assertCount(2, Customer::search('canon')->get());
    }

    public function test_a_text_search_does_not_match_the_phone_index(): void
    {
        Customer::factory()->create(['name' => 'Sin teléfono', 'phone' => null]);

        // Un cliente sin teléfono no debe aparecer al buscar cualquier texto.
        $this->assertCount(0, Customer::search('María')->get());
    }

    public function test_the_index_is_updated_when_the_phone_changes(): void
    {
        $customer = Customer::factory()->create(['phone' => '+51 987 654 321']);

        $this->assertCount(1, Customer::search('987654321')->get());

        $customer->update(['phone' => '+51 900 111 222']);

        $this->assertCount(0, Customer::search('987654321')->get());
        $this->assertCount(1, Customer::search('900111222')->get());
    }

    public function test_the_index_is_updated_when_the_address_changes(): void
    {
        $customer = Customer::factory()->create(['address' => 'Av. Siempre Viva 742']);

        $this->assertCount(1, Customer::search('Siempre')->get());

        $customer->update(['address' => 'Jr. Las Begonias 123']);

        $this->assertCount(0, Customer::search('Siempre')->get());
        $this->assertCount(1, Customer::search('Begonias')->get());
    }

    public function test_the_index_is_not_stored_in_clear(): void
    {
        Customer::factory()->create([
            'phone' => '+51 987 654 321',
            'address' => 'Av. Siempre Viva 742',
        ]);

        $row = Customer::query()->first()->getAttributes();

        $this->assertArrayNotHasKey('+51 987 654 321', $row);
        $this->assertStringNotContainsString('987654321', (string) $row['phone_hash']);
        $this->assertStringNotContainsString('Siempre', (string) $row['address_tokens']);
    }

    public function test_the_hash_is_deterministic_for_the_same_value(): void
    {
        $this->assertSame(
            BlindIndex::hashPhone('+51 987 654 321'),
            BlindIndex::hashPhone('987654321')
        );
    }

    public function test_an_empty_search_returns_everything(): void
    {
        Customer::factory()->count(3)->create();

        $this->assertCount(3, Customer::search('')->get());
        $this->assertCount(3, Customer::search('   ')->get());
    }
}
