<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Datos extra de la propiedad para la ficha técnica y la agenda:
 * m² de terreno, estacionamientos, antigüedad y dónde están las llaves
 * (app/Database/sql/2026-11-property-extras.sql).
 *
 * Si la base de datos aún no tiene las columnas, ready() es false: los
 * formularios no muestran los campos y no se intenta guardarlos.
 */
class PropertyExtras
{
    /** Dónde están las llaves (valor guardado => texto). */
    public const KEYS = [
        'office'   => 'En oficina',
        'agent'    => 'Con el agente',
        'owner'    => 'Con el propietario',
        'lockbox'  => 'Caja de seguridad',
        'doorman'  => 'Con portero / vigilancia',
        'occupied' => 'Inmueble habitado (coordinar)',
        'other'    => 'Otro',
    ];

    private static ?bool $ready = null;

    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = db_connect()->fieldExists('land_size', 'property')
                    && db_connect()->fieldExists('keys_status', 'property');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /**
     * Datos a guardar desde el formulario o la API.
     *
     * @param bool $onlyIfSent API: lo que la app no mande no se toca
     */
    public static function fromRequest(IncomingRequest $request, bool $onlyIfSent = false): array
    {
        if (! self::ready()) {
            return [];
        }

        $data = [];
        foreach (['land_size' => 999_999, 'parking' => 99, 'age_years' => 300] as $field => $max) {
            $value = $request->getPost($field);
            if ($onlyIfSent && $value === null) {
                continue;
            }
            // Terreno con hasta 2 decimales; lo demás, entero
            $data[$field] = $field === 'land_size' ? self::decimal($value, $max) : self::number($value, $max);
        }

        $keys = $request->getPost('keys_status');
        if (! ($onlyIfSent && $keys === null)) {
            $data['keys_status'] = isset(self::KEYS[$keys]) ? $keys : null;
        }

        $note = $request->getPost('keys_note');
        if (! ($onlyIfSent && $note === null)) {
            $note              = trim(strip_tags((string) $note));
            $data['keys_note'] = $note === '' ? null : mb_substr($note, 0, 120);
        }

        return $data;
    }

    /** Número entero entre 0 y $max, o null si viene vacío o no es número. */
    public static function number(mixed $value, int $max): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, min($max, (int) round((float) $value)));
    }

    /** Número con hasta 2 decimales entre 0 y $max ("1,250.5" → 1250.5), o null. */
    public static function decimal(mixed $value, int $max): ?float
    {
        $value = is_string($value) ? str_replace([',', ' '], ['', ''], trim($value)) : $value;
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0.0, min((float) $max, round((float) $value, 2)));
    }

    /** m² para mostrar: 496 → "496", 496.5 → "496.5", 1250.25 → "1,250.25". */
    public static function area(mixed $value): string
    {
        $text = number_format((float) $value, 2);

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }

    public static function keysLabel(?string $status): string
    {
        return self::KEYS[$status] ?? 'Sin dato';
    }
}
