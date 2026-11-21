<?php

namespace App\Support;

use App\Enums\Rol;

/**
 * Matriz de permisos del Plan Maestro TARZO 2.0.
 *
 * Convención: "{modulo}.ver" (consulta) y "{modulo}.gestionar" (crear/editar/borrar).
 * El rol Dueño recibe todos los permisos vía Gate::before (ver AppServiceProvider).
 */
final class MatrizPermisos
{
    /** @var list<string> */
    public const MODULOS = [
        'recepcion',
        'cotizacion',
        'piso',
        'almacen',
        'crm',
        'facturacion',
        'reportes',
        'catalogos',
        'usuarios',
    ];

    /**
     * Permisos puntuales fuera del esquema ver/gestionar.
     *
     * @var list<string>
     */
    public const ESPECIALES = [
        'piso.tareas_propias',   // técnico: iniciar/pausar/terminar sus tareas y subir evidencias
        'piso.forzar_etapa',     // mover una orden saltándose reglas (queda registrado)
    ];

    /**
     * @return list<string>
     */
    public static function todos(): array
    {
        $permisos = [];

        foreach (self::MODULOS as $modulo) {
            $permisos[] = "{$modulo}.ver";
            $permisos[] = "{$modulo}.gestionar";
        }

        return [...$permisos, ...self::ESPECIALES];
    }

    /**
     * @return array<string, list<string>> rol => permisos
     */
    public static function porRol(): array
    {
        return [
            Rol::Dueno->value => self::todos(),
            Rol::Asesor->value => [
                'recepcion.ver', 'recepcion.gestionar',
                'cotizacion.ver', 'cotizacion.gestionar',
                'piso.ver', 'almacen.ver',
                'crm.ver', 'crm.gestionar',
                'facturacion.ver', 'reportes.ver',
            ],
            Rol::Valuador->value => [
                'recepcion.ver',
                'cotizacion.ver', 'cotizacion.gestionar',
                'piso.ver', 'almacen.ver', 'reportes.ver',
            ],
            Rol::JefeTaller->value => [
                'recepcion.ver', 'cotizacion.ver',
                'piso.ver', 'piso.gestionar', 'piso.tareas_propias', 'piso.forzar_etapa',
                'almacen.ver', 'reportes.ver',
            ],
            Rol::Tecnico->value => [
                'piso.ver', 'piso.tareas_propias',
            ],
            Rol::Almacen->value => [
                'cotizacion.ver', 'piso.ver',
                'almacen.ver', 'almacen.gestionar',
                'reportes.ver',
            ],
            Rol::Contabilidad->value => [
                'recepcion.ver', 'cotizacion.ver', 'almacen.ver',
                'facturacion.ver', 'facturacion.gestionar',
                'reportes.ver', 'reportes.gestionar',
            ],
        ];
    }
}
