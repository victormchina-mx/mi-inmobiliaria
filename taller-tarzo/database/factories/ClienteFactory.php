<?php

namespace Database\Factories;

use App\Enums\CanalPreferido;
use App\Enums\TipoPersona;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'tipo_persona' => TipoPersona::Fisica,
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'telefono' => '722'.fake()->numerify('#######'),
            'whatsapp' => '722'.fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'municipio' => 'Toluca',
            'estado' => 'Estado de México',
            'codigo_postal' => fake()->numerify('50###'),
            'canal_preferido' => CanalPreferido::WhatsApp,
        ];
    }
}
