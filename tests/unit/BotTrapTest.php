<?php

use App\Libraries\BotTrap;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BotTrapTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('JWT_SECRET=clave-de-prueba-0123456789abcdef0123456789abcdef');
        $_ENV['JWT_SECRET'] = $_SERVER['JWT_SECRET'] = 'clave-de-prueba-0123456789abcdef0123456789abcdef';
    }

    public function testHumanAfterSomeSecondsPasses(): void
    {
        $now = 1_800_000_000;
        $this->assertSame('ok', BotTrap::check(['website' => '', 'form_token' => BotTrap::token($now - 10)], 3, $now));
    }

    public function testHoneypotFilled(): void
    {
        $now = 1_800_000_000;
        $this->assertSame('honeypot', BotTrap::check(['website' => 'http://spam.example', 'form_token' => BotTrap::token($now - 10)], 3, $now));
    }

    public function testTooFast(): void
    {
        $now = 1_800_000_000;
        $this->assertSame('fast', BotTrap::check(['form_token' => BotTrap::token($now - 1)], 3, $now));
    }

    public function testForgedMissingOrOldToken(): void
    {
        $now = 1_800_000_000;
        $this->assertSame('invalid', BotTrap::check(['form_token' => ($now - 60) . '.' . str_repeat('a', 32)], 3, $now));
        $this->assertSame('invalid', BotTrap::check([], 3, $now));
        $this->assertSame('invalid', BotTrap::check(['form_token' => BotTrap::token($now - 2 * DAY)], 3, $now));
    }
}
