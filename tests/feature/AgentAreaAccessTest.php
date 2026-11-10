<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * "Todas las propiedades", las fichas técnicas y la agenda son solo para agentes y
 * constructores (filtro agent-only). El filtro responde antes de llegar al
 * controlador, así que no hace falta base de datos.
 *
 * @internal
 */
final class AgentAreaAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public static function routes(): array
    {
        return [['agente/propiedades'], ['agente/ficha/25'], ['agente/agenda'], ['agente/agenda/conflictos']];
    }

    /** @dataProvider routes */
    public function testWithoutSessionGoesToLogin(string $route): void
    {
        $result = $this->get($route);

        $result->assertRedirectTo(site_url('login'));
    }

    /** @dataProvider routes */
    public function testBuyersCannotEnter(string $route): void
    {
        $result = $this->withSession(['uid' => 5, 'uemail' => 'comprador@example.com', 'utype' => 'user'])->get($route);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error', 'Esta sección es solo para agentes inmobiliarios.');
    }

    public function testShortLinkRedirectsToDetail(): void
    {
        $this->get('p/25')->assertRedirectTo(site_url('property/25'));
    }
}
