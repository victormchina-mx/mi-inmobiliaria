<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_responde_con_cabeceras_de_seguridad(): void
    {
        $this->get('/panel/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');
    }

    public function test_hsts_solo_si_se_activa_y_hay_https(): void
    {
        config(['tarzo.seguridad.hsts' => true]);

        $this->get('/panel/login')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/panel/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_una_ruta_inexistente_no_expone_detalles(): void
    {
        config(['app.debug' => false]);

        $this->get('/robots-no-existe.txt')->assertNotFound()->assertDontSee('vendor/laravel');
    }
}
