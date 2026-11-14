<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Datos de identidad de una inmobiliaria desde un formulario: nombre, logo
 * (con su recorte del círculo), colores, lema y contacto. Lo usan el admin
 * (Admin\Agency) y la encargada en "Mi inmobiliaria" (AgencyPanel).
 */
class AgencyForm
{
    /** PNG, JPG o WebP (SVG no: podría llevar código). */
    public const LOGO_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    /**
     * Valida el formulario. No toca archivos: el logo se guarda con
     * applyLogo() solo si no hubo errores.
     *
     * @return array{0: array<string, mixed>, 1: list<string>} [datos, errores]
     */
    public static function read(IncomingRequest $request): array
    {
        $post = static fn (string $k, int $max = 200): string => mb_substr(trim((string) $request->getPost($k)), 0, $max);

        $name    = $post('name', 120);
        $primary = Agency::color($post('color_primary', 7));
        $accent  = Agency::color($post('color_accent', 7));
        $email   = $post('email', 150);
        $website = $post('website', 200);
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        $errors = [];
        if ($name === '') {
            $errors[] = 'Escribe el nombre de la inmobiliaria.';
        }
        if (! $primary || ! $accent) {
            $errors[] = 'Elige el color principal y el de acento.';
        }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo no es válido.';
        }
        if ($website !== '' && ! filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'El sitio web no es válido.';
        }

        $file = $request->getFile('logo');
        if ($file && $file->isValid() && ! $file->hasMoved() && ! in_array($file->getMimeType(), self::LOGO_TYPES, true)) {
            $errors[] = 'El logo debe ser PNG, JPG o WebP.';
        }

        return [[
            'name'          => $name,
            'color_primary' => $primary,
            'color_accent'  => $accent,
            'tagline'       => $post('tagline', 160) ?: null,
            'phone'         => $post('phone', 30) ?: null,
            'whatsapp'      => $post('whatsapp', 30) ?: null,
            'email'         => $email ?: null,
            'website'       => $website ?: null,
        ], $errors];
    }

    /**
     * Guarda el logo nuevo (o lo quita) y su recorte del círculo.
     *
     * @return string|null nombre del archivo del logo
     */
    public static function applyLogo(IncomingRequest $request, ?string $current): ?string
    {
        $logo = $current;
        $file = $request->getFile('logo');
        if ($file && $file->isValid() && ! $file->hasMoved() && in_array($file->getMimeType(), self::LOGO_TYPES, true)) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/agency', $newName);
            self::deleteLogo($logo);
            $logo = $newName;
        } elseif ($request->getPost('remove_logo')) {
            self::deleteLogo($logo);
            $logo = null;
        }

        // Parte del logo que se ve en el círculo (la eligió el usuario)
        $icon = (string) $request->getPost('logo_icon');
        if ($logo && $icon !== '') {
            Agency::saveIcon($logo, $icon);
        }

        return $logo;
    }

    /** Borra el logo y su recorte. */
    public static function deleteLogo(?string $file): void
    {
        if ($file && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file)) {
            foreach ([$file, Agency::iconFile($file)] as $f) {
                $path = FCPATH . 'uploads/agency/' . $f;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }
}
