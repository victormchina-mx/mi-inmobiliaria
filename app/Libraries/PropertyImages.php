<?php

namespace App\Libraries;

use App\Controllers\Thumb;
use App\Models\PropertyModel;

/**
 * Archivos de las fotos y planos de las propiedades.
 *
 * La tabla property guarda solo el nombre del archivo; el archivo está en
 * public/uploads/property/ y sus copias reducidas (App\Controllers\Thumb)
 * en public/img/property/{ancho}/{archivo}.webp|.jpg.
 *
 * Al borrar una propiedad o reemplazar una foto se llama a removeUnused()
 * con los nombres que dejaron de usarse: un archivo solo se borra si
 * ninguna otra propiedad lo usa (por si un registro se copió).
 * La limpieza de lo ya acumulado está en `php spark fotos:limpiar`.
 */
class PropertyImages
{
    public const FIELDS = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4', 'mapimage', 'topmapimage', 'groundmapimage'];

    /** Nombres de archivo de una propiedad (sin vacíos ni repetidos). */
    public static function filesOf(?array $property): array
    {
        if (! $property) {
            return [];
        }

        $files = [];
        foreach (self::FIELDS as $field) {
            $name = trim((string) ($property[$field] ?? ''));
            if ($name !== '') {
                $files[$name] = true;
            }
        }

        return array_keys($files);
    }

    /** Archivos que tenía $before y ya no tiene $after (fotos reemplazadas). */
    public static function replaced(?array $before, ?array $after): array
    {
        return array_values(array_diff(self::filesOf($before), self::filesOf($after)));
    }

    /** Todos los nombres de archivo que usa alguna propiedad. */
    public static function referenced(): array
    {
        $rows = (new PropertyModel())->select(implode(', ', self::FIELDS))->findAll();
        $used = [];
        foreach ($rows as $row) {
            foreach (self::filesOf($row) as $name) {
                $used[$name] = true;
            }
        }

        return $used;
    }

    /**
     * Borra los archivos (y sus copias reducidas) que ya no usa ninguna
     * propiedad. Llamar después de borrar o actualizar el registro.
     *
     * @return int archivos originales borrados
     */
    public static function removeUnused(array $files): int
    {
        $files = array_filter(array_map(static fn ($f) => trim((string) $f), $files), [self::class, 'safeName']);
        if (! $files) {
            return 0;
        }

        try {
            $used = self::referenced();
        } catch (\Throwable $e) {
            log_message('error', 'No se pudieron revisar las fotos en uso: ' . $e->getMessage());

            return 0;   // ante la duda, no se borra nada
        }

        $deleted = 0;
        foreach (array_unique($files) as $name) {
            if (isset($used[$name])) {
                continue;
            }
            if (self::deleteFile($name)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /** Borra el original y sus copias reducidas. true si existía el original. */
    public static function deleteFile(string $name): bool
    {
        if (! self::safeName($name)) {
            return false;
        }

        foreach (self::thumbPaths($name) as $thumb) {
            @unlink($thumb);
        }

        $original = self::dir() . $name;

        return is_file($original) && @unlink($original);
    }

    /** Copias reducidas existentes de un archivo. */
    public static function thumbPaths(string $name): array
    {
        $paths = [];
        foreach (Thumb::WIDTHS as $w) {
            foreach (['webp', 'jpg'] as $ext) {
                $p = FCPATH . 'img/property/' . $w . '/' . $name . '.' . $ext;
                if (is_file($p)) {
                    $paths[] = $p;
                }
            }
        }

        return $paths;
    }

    public static function dir(): string
    {
        return FCPATH . 'uploads/property/';
    }

    /** Solo nombres simples (nada de rutas ni archivos ocultos como .htaccess). */
    public static function safeName(string $name): bool
    {
        return $name !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name) === 1 && ! str_contains($name, '..');
    }
}
