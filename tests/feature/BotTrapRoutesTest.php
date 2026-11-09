<?php

use App\Libraries\BotTrap;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Los envíos de robots se rechazan en el filtro "bottrap", antes de llegar
 * al controlador (y a la base de datos).
 *
 * @internal
 */
final class BotTrapRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('JWT_SECRET=clave-de-prueba-0123456789abcdef0123456789abcdef');
        $_ENV['JWT_SECRET'] = $_SERVER['JWT_SECRET'] = 'clave-de-prueba-0123456789abcdef0123456789abcdef';

        // Sin CSRF en estas pruebas: aquí solo se prueba el filtro contra robots
        $filters = config('Filters');
        $filters->globals['before'] = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filters);
    }

    public static function routes(): array
    {
        return [['contact'], ['register'], ['forgot-password']];
    }

    /** @dataProvider routes */
    public function testHoneypotIsDiscardedSilently(string $route): void
    {
        $result = $this->post($route, ['website' => 'http://spam.example', 'form_token' => BotTrap::token(time() - 30), 'email' => 'bot@example.com']);

        $result->assertRedirect();
        $result->assertSessionHas('success', 'Gracias, recibimos tu solicitud.');
    }

    /** @dataProvider routes */
    public function testTooFastGoesBackWithAMessage(string $route): void
    {
        $result = $this->post($route, ['website' => '', 'form_token' => BotTrap::token(), 'email' => 'bot@example.com']);

        $result->assertRedirect();
        $result->assertSessionHas('error', 'Por seguridad, espera unos segundos y vuelve a enviar el formulario.');
    }

    /** @dataProvider routes */
    public function testWithoutTokenIsRejected(string $route): void
    {
        $this->post($route, ['email' => 'bot@example.com'])->assertSessionHas('error');
    }
}
