<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Aseguradora;
use App\Models\Etapa;
use Illuminate\Database\Seeder;

/**
 * Catálogos iniciales del taller. Las tarifas quedan en 0: las define el dueño.
 */
class CatalogosTallerSeeder extends Seeder
{
    public function run(): void
    {
        // Etapas del tablero Kanban (Plan Maestro, módulo 3).
        $etapas = [
            ['recepcion', 'Recepción', 'gray', 1, false, false],
            ['diagnostico', 'Diagnóstico', 'info', 2, false, false],
            ['espera_refacciones', 'Espera de refacciones', 'warning', 5, true, false],
            ['mecanica', 'Mecánica', 'primary', 3, false, false],
            ['hojalateria', 'Hojalatería', 'primary', 4, false, false],
            ['pintura', 'Preparación y pintura', 'primary', 3, true, false],
            ['armado', 'Armado y pulido', 'primary', 2, false, false],
            ['calidad', 'Control de calidad', 'info', 1, false, false],
            ['listo_entrega', 'Listo para entrega', 'success', 2, true, false],
            ['entregado', 'Entregado', 'gray', null, true, true],
        ];

        foreach ($etapas as $orden => [$clave, $nombre, $color, $diasMax, $notifica, $final]) {
            Etapa::updateOrCreate(['clave' => $clave], [
                'nombre' => $nombre,
                'orden' => $orden + 1,
                'color' => $color,
                'dias_max' => $diasMax,
                'notifica_cliente' => $notifica,
                'es_final' => $final,
            ]);
        }

        foreach (['Mecánica', 'Eléctrico', 'Hojalatería', 'Pintura', 'Preparación', 'Armado y pulido'] as $nombre) {
            Area::firstOrCreate(['nombre' => $nombre]);
        }

        foreach (['Qualitas', 'Banorte', 'Afirme', 'GNP', 'AXA', 'HDI', 'Chubb', 'Mapfre'] as $nombre) {
            Aseguradora::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
