<?php

namespace App\Commands;

use App\Controllers\Thumb;
use App\Libraries\PropertyImages;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Fotos de propiedades que ya no usa ninguna propiedad (de propiedades
 * borradas o fotos reemplazadas antes de que el sitio las borrara solo).
 *
 *   php spark fotos:limpiar           → solo muestra la lista, no borra nada
 *   php spark fotos:limpiar --borrar  → borra esos archivos y sus copias reducidas
 */
class LimpiarFotos extends BaseCommand
{
    protected $group       = 'Inmobiliaria';
    protected $name        = 'fotos:limpiar';
    protected $description = 'Lista (o borra con --borrar) las fotos de propiedades que ya no usa ninguna propiedad.';
    protected $usage       = 'fotos:limpiar [--borrar]';
    protected $options     = ['--borrar' => 'Borra los archivos en lugar de solo listarlos.'];

    public function run(array $params)
    {
        $delete = CLI::getOption('borrar') !== null || array_key_exists('borrar', $params);

        try {
            $used = PropertyImages::referenced();
        } catch (\Throwable $e) {
            CLI::error('No se pudo leer la tabla property: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        // 1. Originales en uploads/property sin propiedad
        $orphans = [];
        $bytes   = 0;
        foreach (glob(PropertyImages::dir() . '*') ?: [] as $path) {
            $name = basename($path);
            if (! is_file($path) || ! PropertyImages::safeName($name) || isset($used[$name])) {
                continue;
            }
            $orphans[$name] = filesize($path) ?: 0;
            $bytes += $orphans[$name];
        }

        // 2. Copias reducidas cuyo original ya no se usa
        $thumbs = [];
        foreach (Thumb::WIDTHS as $w) {
            foreach (glob(FCPATH . 'img/property/' . $w . '/*') ?: [] as $path) {
                $original = preg_replace('/\.(webp|jpg)$/i', '', basename($path));
                if (is_file($path) && ! isset($used[$original])) {
                    $thumbs[] = $path;
                    $bytes += filesize($path) ?: 0;
                }
            }
        }

        CLI::write('Fotos en uso por alguna propiedad: ' . count($used));
        CLI::write('Fotos sin propiedad: ' . count($orphans) . ' · copias reducidas sin uso: ' . count($thumbs)
            . ' · espacio: ' . number_format($bytes / 1048576, 1) . ' MB');

        if (! $orphans && ! $thumbs) {
            CLI::write('Todo limpio.', 'green');

            return EXIT_SUCCESS;
        }

        foreach ($orphans as $name => $size) {
            CLI::write('  ' . $name . '  (' . number_format($size / 1024, 0) . ' KB)');
        }

        if (! $delete) {
            CLI::newLine();
            CLI::write('No se borró nada. Para borrar estos archivos: php spark fotos:limpiar --borrar', 'yellow');

            return EXIT_SUCCESS;
        }

        $n = 0;
        foreach (array_keys($orphans) as $name) {
            $n += PropertyImages::deleteFile($name) ? 1 : 0;
        }
        foreach ($thumbs as $path) {
            @unlink($path);
        }
        CLI::write("Listo: se borraron {$n} fotos y " . count($thumbs) . ' copias reducidas.', 'green');

        return EXIT_SUCCESS;
    }
}
