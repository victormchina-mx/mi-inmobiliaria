<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Operacion;
use App\Models\Paquete;
use App\Models\Parametro;
use App\Models\PiezaCarroceria;
use Illuminate\Database\Seeder;

/**
 * Catálogos del Módulo 2. Los montos son VALORES DE EJEMPLO para que el cotizador funcione desde
 * el primer día; el dueño debe ajustarlos en Catálogos → Parámetros de cotización.
 * Solo crea lo que no existe: no sobrescribe cambios hechos en producción.
 */
class CatalogosCotizacionSeeder extends Seeder
{
    public function run(): void
    {
        $claves = [
            'Mecánica' => 'mecanica', 'Eléctrico' => 'electrico', 'Hojalatería' => 'hojalateria',
            'Pintura' => 'pintura', 'Preparación' => 'preparacion', 'Armado y pulido' => 'armado',
        ];
        foreach ($claves as $nombre => $clave) {
            $area = Area::firstOrCreate(['nombre' => $nombre]);
            if ($area->clave === null) {
                $area->update(['clave' => $clave]);
            }
        }

        $parametros = [
            ['costo_m2_solida', 380, 'Materiales de pintura sólida por m² (fondo, color, barniz, abrasivos)', 'MXN/m²'],
            ['costo_m2_metalica', 520, 'Materiales de pintura metálica por m²', 'MXN/m²'],
            ['costo_m2_perlada', 780, 'Materiales de pintura perlada / tricapa por m²', 'MXN/m²'],
            ['factor_dano_leve', 0.4, 'Parte de la pieza que se pinta al reparar un daño leve', 'factor'],
            ['factor_dano_medio', 0.7, 'Parte de la pieza que se pinta al reparar un daño medio', 'factor'],
            ['factor_dano_fuerte', 1.0, 'Parte de la pieza que se pinta al reparar un daño fuerte', 'factor'],
            ['tarifa_ciclo_cabina', 650, 'Costo por ciclo de horneado en cabina', 'MXN'],
            ['piezas_por_ciclo_cabina', 3, 'Piezas que caben en un ciclo de cabina', 'piezas'],
            ['margen_refacciones', 30, 'Margen por defecto sobre el costo de refacciones', '%'],
            ['vigencia_cotizacion', 15, 'Días de vigencia de un presupuesto', 'días'],
        ];
        foreach ($parametros as [$clave, $valor, $descripcion, $unidad]) {
            Parametro::firstOrCreate(['clave' => $clave], compact('valor', 'descripcion', 'unidad'));
        }

        // Superficie aproximada de un sedán compacto (m²) y horas de referencia para daño medio.
        $piezas = [
            ['Facia delantera', 1.1, 2.5, 2.5], ['Facia trasera', 1.0, 2.5, 2.5], ['Cofre', 1.6, 3.0, 3.0],
            ['Toldo', 2.0, 4.0, 3.5], ['Tapa de cajuela', 1.2, 2.5, 2.5],
            ['Salpicadera delantera izquierda', 0.7, 2.0, 2.0], ['Salpicadera delantera derecha', 0.7, 2.0, 2.0],
            ['Puerta delantera izquierda', 1.0, 2.5, 2.5], ['Puerta delantera derecha', 1.0, 2.5, 2.5],
            ['Puerta trasera izquierda', 0.9, 2.5, 2.3], ['Puerta trasera derecha', 0.9, 2.5, 2.3],
            ['Costado trasero izquierdo', 0.9, 3.0, 2.5], ['Costado trasero derecho', 0.9, 3.0, 2.5],
            ['Estribo izquierdo', 0.4, 1.5, 1.2], ['Estribo derecho', 0.4, 1.5, 1.2],
            ['Espejo lateral izquierdo', 0.1, 0.5, 0.6], ['Espejo lateral derecho', 0.1, 0.5, 0.6],
            ['Moldura / spoiler', 0.3, 0.5, 1.0],
        ];
        foreach ($piezas as [$nombre, $m2, $lam, $pin]) {
            PiezaCarroceria::firstOrCreate(['nombre' => $nombre], ['superficie_m2' => $m2, 'horas_laminado_ref' => $lam, 'horas_pintura_ref' => $pin]);
        }

        $mecanica = Area::firstWhere('clave', 'mecanica')->id;
        $electrico = Area::firstWhere('clave', 'electrico')->id;
        $operaciones = [
            [$mecanica, 'Cambio de aceite y filtro', 0.5], [$mecanica, 'Afinación motor 4 cilindros', 1.5],
            [$mecanica, 'Afinación motor 6 cilindros', 2.5], [$mecanica, 'Cambio de balatas delanteras', 1.2],
            [$mecanica, 'Cambio de balatas traseras', 1.2], [$mecanica, 'Rectificado de discos (par)', 1.0],
            [$mecanica, 'Cambio de amortiguadores delanteros (par)', 2.0], [$mecanica, 'Cambio de banda de distribución', 3.5],
            [$mecanica, 'Cambio de bomba de agua', 2.5], [$mecanica, 'Alineación y balanceo', 1.0],
            [$mecanica, 'Diagnóstico con escáner', 0.5],
            [$electrico, 'Revisión de sistema de carga', 0.8], [$electrico, 'Cambio de batería', 0.3],
        ];
        foreach ($operaciones as [$area, $nombre, $horas]) {
            Operacion::firstOrCreate(['nombre' => $nombre], ['area_id' => $area, 'horas_estandar' => $horas]);
        }

        Paquete::firstOrCreate(['nombre' => 'Afinación mayor 4 cilindros'], [
            'descripcion' => 'Ejemplo: ajusta refacciones y costos a tus proveedores.',
            'partidas' => [
                ['tipo' => 'mano_obra', 'descripcion' => 'Afinación motor 4 cilindros', 'area_id' => $mecanica, 'horas' => 1.5],
                ['tipo' => 'refaccion', 'descripcion' => 'Juego de bujías (4)', 'cantidad' => 1, 'costo_unitario' => 480],
                ['tipo' => 'refaccion', 'descripcion' => 'Filtro de aire', 'cantidad' => 1, 'costo_unitario' => 220],
                ['tipo' => 'refaccion', 'descripcion' => 'Filtro de gasolina', 'cantidad' => 1, 'costo_unitario' => 180],
                ['tipo' => 'refaccion', 'descripcion' => 'Aceite sintético 5W-30 (litro)', 'cantidad' => 4, 'costo_unitario' => 150],
                ['tipo' => 'refaccion', 'descripcion' => 'Filtro de aceite', 'cantidad' => 1, 'costo_unitario' => 120],
            ],
        ]);
    }
}
