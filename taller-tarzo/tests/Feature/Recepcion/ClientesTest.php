<?php

namespace Tests\Feature\Recepcion;

use App\Enums\Rol;
use App\Filament\Resources\Clientes\Pages\CreateCliente;
use App\Filament\Resources\Clientes\Pages\ViewCliente;
use App\Models\Cliente;
use Livewire\Livewire;

class ClientesTest extends RecepcionTestCase
{
    public function test_el_asesor_registra_un_cliente_con_datos_fiscales(): void
    {
        Livewire::test(CreateCliente::class)
            ->fillForm([
                'tipo_persona' => 'fisica',
                'nombre' => 'Laura',
                'apellidos' => 'Hernández Ruiz',
                'telefono' => '+52 (722) 123-4567',
                'email' => 'laura@example.com',
                'canal_preferido' => 'whatsapp',
                'datosFiscales' => [
                    'rfc' => 'heRl850315ab1',
                    'nombre_fiscal' => 'Laura Hernández Ruiz',
                    'regimen_fiscal' => '612',
                    'codigo_postal' => '50000',
                    'uso_cfdi' => 'G03',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cliente = Cliente::with('datosFiscales')->firstWhere('email', 'laura@example.com');
        $this->assertSame('7221234567', $cliente->telefono);
        $this->assertSame('527221234567', $cliente->numeroWhatsapp());
        $this->assertSame('HERL850315AB1', $cliente->datosFiscales->rfc);
        $this->assertSame('LAURA HERNÁNDEZ RUIZ', $cliente->datosFiscales->nombre_fiscal);
    }

    public function test_sin_rfc_no_se_crean_datos_fiscales(): void
    {
        Livewire::test(CreateCliente::class)
            ->fillForm(['tipo_persona' => 'fisica', 'nombre' => 'Pedro', 'telefono' => '7221112233', 'canal_preferido' => 'llamada'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Cliente::firstWhere('nombre', 'Pedro')->datosFiscales);
    }

    public function test_rechaza_un_rfc_invalido(): void
    {
        Livewire::test(CreateCliente::class)
            ->fillForm([
                'tipo_persona' => 'moral', 'nombre' => 'Transportes del Valle', 'canal_preferido' => 'correo',
                'datosFiscales' => ['rfc' => 'TVA9913', 'nombre_fiscal' => 'TRANSPORTES DEL VALLE', 'regimen_fiscal' => '601', 'codigo_postal' => '50000', 'uso_cfdi' => 'G03'],
            ])
            ->call('create')
            ->assertHasFormErrors(['datosFiscales.rfc']);
    }

    public function test_detecta_posibles_duplicados_por_telefono(): void
    {
        $existente = Cliente::factory()->create(['telefono' => '7225550000']);

        $this->assertTrue(Cliente::posiblesDuplicados('(722) 555-0000', null)->contains($existente));
        $this->assertTrue(Cliente::posiblesDuplicados('722 555 0000', null, $existente->id)->isEmpty());
    }

    public function test_la_ficha_del_cliente_muestra_su_historial(): void
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(ViewCliente::class, ['record' => $cliente->getRouteKey()])->assertOk()->assertSee($cliente->nombre);
    }

    public function test_contabilidad_consulta_pero_no_registra_clientes(): void
    {
        $this->actingAs($this->usuario(Rol::Contabilidad));

        $this->get('/panel/clientes')->assertOk();
        $this->get('/panel/clientes/create')->assertForbidden();
    }

    public function test_el_tecnico_no_entra_a_clientes(): void
    {
        $this->actingAs($this->usuario(Rol::Tecnico));

        $this->get('/panel/clientes')->assertForbidden();
        $this->get('/panel/ordenes')->assertForbidden();
    }
}
