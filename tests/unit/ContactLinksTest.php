<?php

use App\Libraries\ContactLinks;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ContactLinksTest extends CIUnitTestCase
{
    public function testWhatsappNumberUsesMexicanInternationalFormat(): void
    {
        $this->assertSame('527777444455', ContactLinks::whatsappNumber('7777444455'));
        $this->assertSame('525512345678', ContactLinks::whatsappNumber('55 1234-5678'));
        $this->assertSame('525512345678', ContactLinks::whatsappNumber('+52 1 55 1234 5678'));
        $this->assertSame('525512345678', ContactLinks::whatsappNumber('+52 55 1234 5678'));
        $this->assertSame('525512345678', ContactLinks::whatsappNumber('044 55 1234 5678'));
        $this->assertSame('14155552671', ContactLinks::whatsappNumber('+1 415 555 2671'));
        $this->assertSame('', ContactLinks::whatsappNumber('555'));
        $this->assertSame('', ContactLinks::whatsappNumber(null));
    }

    public function testTelAndPretty(): void
    {
        $this->assertSame('tel:+527777444455', ContactLinks::tel('7777444455'));
        $this->assertSame('', ContactLinks::tel(''));
        $this->assertSame('777 744 4455', ContactLinks::pretty('7777444455'));
        $this->assertSame('+1 415 555 2671', ContactLinks::pretty(' +1 415 555 2671 '));
    }

    public function testWhatsappAndMailCarryTheProperty(): void
    {
        $url = 'https://inmobiliaria.vmch.net/property/25';

        $wa = ContactLinks::whatsapp('7777444455', 'Zills Home', $url);
        $this->assertStringStartsWith('https://wa.me/527777444455?text=', $wa);
        $this->assertStringContainsString(rawurlencode('"Zills Home"'), $wa);
        $this->assertStringContainsString(rawurlencode($url), $wa);

        $mail = ContactLinks::mail('christine@mail.com', 'Zills Home', $url);
        $this->assertStringStartsWith('mailto:christine@mail.com?subject=', $mail);
        $this->assertStringContainsString('&body=', $mail);
        $this->assertSame('', ContactLinks::mail('no-es-correo', 'x', $url));
        $this->assertSame('', ContactLinks::whatsapp('', 'x', $url));
    }
}
