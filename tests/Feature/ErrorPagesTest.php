<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_a_missing_page_shows_the_spanish_error_page(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('Página no encontrada')
            ->assertSee('Volver a la tienda');
    }

    public function test_every_error_page_renders(): void
    {
        foreach ([401, 403, 404, 419, 429, 500, 503] as $code) {
            $html = view("errors.{$code}")->render();

            $this->assertStringContainsString((string) $code, $html);
            $this->assertStringContainsString('lang="es"', $html);
        }
    }
}
