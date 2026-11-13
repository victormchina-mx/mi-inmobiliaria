<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * La API del área de agentes (todas las propiedades y agenda) exige token.
 *
 * @internal
 */
final class AgentApiAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public static function routes(): array
    {
        return [
            ['get', 'api/v1/agent/properties'],
            ['get', 'api/v1/agenda'],
            ['get', 'api/v1/agenda/options'],
            ['get', 'api/v1/agenda/conflicts'],
            ['get', 'api/v1/agenda/3'],
            ['post', 'api/v1/agenda'],
            ['post', 'api/v1/agenda/3'],
            ['post', 'api/v1/agenda/3/delete'],
            ['post', 'api/v1/me/photo'],
        ];
    }

    /** @dataProvider routes */
    public function testRequiresToken(string $method, string $route): void
    {
        $result = $this->call($method, $route);

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => 'Token no proporcionado.']);
    }
}
