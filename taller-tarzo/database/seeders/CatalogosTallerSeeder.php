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
        // [clave, nombre, color, días máx., avisa al cliente, final, requiere cotización aprobada, checklist para entrar, evidencia para salir]
        $etapas = [
            ['recepcion', 'Recepción', 'gray', 1, false, false, false, null, false],
            ['diagnostico', 'Diagnóstico', 'info', 2, false, false, false, null, false],
            ['espera_refacciones', 'Espera de refacciones', 'warning', 5, true, false, false, null, false],
            ['mecanica', 'Mecánica', 'primary', 3, false, false, true, null, false],
            ['hojalateria', 'Hojalatería', 'primary', 4, false, false, true, null, true],
            ['pintura', 'Preparación y pintura', 'primary', 3, true, false, true, null, true],
            ['armado', 'Armado y pulido', 'primary', 2, false, false, true, null, false],
            ['calidad', 'Control de calidad', 'info', 1, false, false, true, null, false],
            ['listo_entrega', 'Listo para entrega', 'success', 2, true, false, false, 'calidad', false],
            ['entregado', 'Entregado', 'gray', null, true, true, false, 'entrega', false],
        ];

        // firstOrCreate: crea las que falten sin pisar lo que el dueño ya configuró.
        foreach ($etapas as $orden => [$clave, $nombre, $color, $diasMax, $notifica, $final, $cotizacion, $checklist, $evidencia]) {
            Etapa::firstOrCreate(['clave' => $clave], [
                'nombre' => $nombre,
                'orden' => $orden + 1,
                'color' => $color,
                'dias_max' => $diasMax,
                'notifica_cliente' => $notifica,
                'es_final' => $final,
                'requiere_cotizacion_aprobada' => $cotizacion,
                'requiere_checklist' => $checklist,
                'requiere_evidencia' => $evidencia,
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
