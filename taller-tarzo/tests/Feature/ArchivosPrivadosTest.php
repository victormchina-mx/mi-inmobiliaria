<?php

namespace Tests\Feature;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchivosPrivadosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
        Media::disco()->put('checkins/OS-2027-000001/foto.jpg', 'contenido-jpg');
    }

    public function test_el_enlace_temporal_no_termina_en_extension_y_entrega_el_archivo(): void
    {
        $url = Media::urlTemporal('checkins/OS-2027-000001/foto.jpg');

        // LiteSpeed (Hostinger) trata como estático lo que termina en .jpg y pierde la firma.
        $this->assertDoesNotMatchRegularExpression('#\.(jpe?g|png|webp|pdf|mp4)(\?|$)#i', parse_url($url, PHP_URL_PATH));
        $this->assertStringContainsString('/archivo/', $url);

        $respuesta = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('contenido-jpg', $respuesta->streamedContent());
    }

    public function test_sin_firma_o_con_la_ruta_alterada_no_entrega_nada(): void
    {
        $url = Media::urlTemporal('checkins/OS-2027-000001/foto.jpg');

        $this->get(strtok($url, '?'))->assertForbidden();

        $otra = str_replace(Media::codificar('checkins/OS-2027-000001/foto.jpg'), Media::codificar('firmas/otra.png'), $url);
        $this->get($otra)->assertForbidden();
    }

    public function test_no_permite_salir_de_la_carpeta_privada(): void
    {
        $this->assertNull(Media::decodificar(Media::codificar('../../.env')));
        $this->assertNull(Media::decodificar(Media::codificar('/etc/passwd')));
        $this->assertNull(Media::decodificar('no-es-base64!'));

        $url = Media::urlFirmada('no/existe.jpg', now()->addMinutes(5));
        $this->get($url)->assertNotFound();
    }
}
