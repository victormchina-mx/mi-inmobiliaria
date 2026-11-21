<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        $modelos = [['Nissan', 'Versa'], ['Volkswagen', 'Jetta'], ['Chevrolet', 'Aveo'], ['Mazda', 'Mazda 3'], ['Toyota', 'Hilux'], ['Honda', 'CR-V']];
        [$marca, $modelo] = fake()->randomElement($modelos);

        return [
            'cliente_id' => Cliente::factory(),
            'vin' => null,
            'placas' => strtoupper(fake()->bothify('???-###-?')),
            'marca' => $marca,
            'modelo' => $modelo,
            'anio' => fake()->numberBetween(2012, 2026),
            'color' => fake()->randomElement(['Blanco', 'Gris', 'Rojo', 'Negro', 'Azul']),
            'kilometraje' => fake()->numberBetween(5000, 180000),
        ];
    }
}
