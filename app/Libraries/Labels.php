<?php

namespace App\Libraries;

/**
 * Textos en español para los valores internos que se guardan en inglés.
 *
 * Un solo lugar para la lista de tipos de propiedad: antes cada formulario
 * y buscador tenía su propia lista y no coincidían (el usuario podía
 * publicar "Bodega" o "Terreno", pero el buscador y el admin no los tenían).
 * La app móvil usa la misma lista en lib/constants.dart (propertyTypeLabels).
 */
class Labels
{
    public const PROPERTY_TYPES = [
        'apartment' => 'Departamento',
        'flat'      => 'Flat',
        'building'  => 'Edificio',
        'house'     => 'Casa',
        'villa'     => 'Villa',
        'office'    => 'Oficina',
        'local'     => 'Local',
        'bodega'    => 'Bodega',
        'terreno'   => 'Terreno',
    ];

    public const USER_TYPES = [
        'user'    => 'Usuario',
        'agent'   => 'Agente',
        'builder' => 'Constructor',
    ];

    public static function propertyType(?string $value): string
    {
        return self::PROPERTY_TYPES[$value] ?? ucfirst((string) $value);
    }

    public static function userType(?string $value): string
    {
        return self::USER_TYPES[$value] ?? ucfirst((string) $value);
    }
}
