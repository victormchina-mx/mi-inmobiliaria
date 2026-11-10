<?php

namespace App\Libraries;

/**
 * Datos de la ficha técnica de una propiedad (las 3 plantillas los usan
 * igual: app/Views/agent/ficha/*.php).
 *
 * - Título con tipo, operación y zona: "Casa en Venta en Azcapotzalco".
 * - Precio destacado (en renta, "/mes").
 * - Datos clave: m² de construcción y terreno, recámaras, baños,
 *   estacionamientos y antigüedad (solo los que tengan valor).
 * - Solo la zona (colonia, alcaldía o municipio), nunca la dirección exacta.
 * - Asesor: nombre, foto, teléfono y WhatsApp con un mensaje que menciona
 *   la propiedad. Enlace corto /p/{id} para el código QR.
 */
class FichaTecnica
{
    /** Plantillas disponibles: clave => [nombre, formato, ancho, alto]. */
    public const TEMPLATES = [
        'elegante'    => ['name' => 'Elegante',    'format' => 'Vertical · carta (imprimir / PDF)',     'w' => 850,  'h' => 1100],
        'moderna'     => ['name' => 'Moderna',     'format' => 'Cuadrada · 1080 × 1080 (redes sociales)', 'w' => 1080, 'h' => 1080],
        'residencial' => ['name' => 'Residencial', 'format' => 'Horizontal · 1500 × 1000 (WhatsApp)',    'w' => 1500, 'h' => 1000],
    ];

    /** Código único del inmueble: VMCH-0025. */
    public static function code(int $pid): string
    {
        return 'VMCH-' . str_pad((string) $pid, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array $property propiedad con withOwner()
     * @param array $agent    asesor que aparece en la ficha (uname, uphone, uimage, uemail, utype)
     */
    public static function data(array $property, array $agent): array
    {
        $pid       = (int) $property['pid'];
        $isRent    = ($property['stype'] ?? '') === 'rent';
        $operation = $isRent ? 'Renta' : 'Venta';
        $type      = Labels::propertyType($property['type'] ?? '');

        $colonia = self::tidy($property['location'] ?? '');
        $city    = self::tidy($property['city'] ?? '');
        $state   = self::tidy($property['state'] ?? '');
        $zone    = $colonia !== '' ? $colonia : $city;            // lo más concreto que se puede mostrar
        $area    = implode(', ', array_values(array_unique(array_filter([$city !== $zone ? $city : '', $state]))));

        $photos = [];
        foreach (['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4'] as $i => $field) {
            if (! empty($property[$field])) {
                $photos[] = thumb_url('property', $property[$field], $i === 0 ? 1600 : 800);
            }
        }

        $url     = site_url('p/' . $pid);
        $message = 'Hola, me interesa la propiedad ' . self::code($pid) . ' (' . $type . ' en ' . $operation . ($zone !== '' ? ' en ' . $zone : '') . '): ' . $url;
        $wa      = ContactLinks::whatsappNumber($agent['uphone'] ?? '');

        return [
            'pid'       => $pid,
            'code'      => self::code($pid),
            'type'      => $type,
            'operation' => $operation,
            'isRent'    => $isRent,
            'title'     => $type . ' en ' . $operation . ($zone !== '' ? ' en ' . $zone : ''),
            'zone'      => $zone,
            'area'      => $area,                                   // "Miguel Hidalgo, CDMX"
            'place'     => implode(' · ', array_filter([$zone, $area])),
            'price'     => '$' . number_format((float) ($property['price'] ?? 0), 0),
            'priceNote' => $isRent ? '/mes' : 'MXN',
            'facts'     => self::facts($property),
            'photos'    => $photos,
            'url'       => $url,
            'shortUrl'  => preg_replace('#^https?://#', '', $url),
            'agent'     => [
                'name'  => trim((string) ($agent['uname'] ?? '')),
                'role'  => ($agent['utype'] ?? 'agent') === 'builder' ? 'Constructor' : 'Asesor inmobiliario',
                'phone' => ContactLinks::pretty($agent['uphone'] ?? ''),
                'photo' => ! empty($agent['uimage']) ? thumb_url('user', $agent['uimage'], 200) : '',
                'wa'    => $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($message) : '',
            ],
        ];
    }

    /**
     * Datos clave con valor, en el orden de la ficha.
     *
     * @return list<array{icon: string, value: string, label: string, long: string}>
     */
    public static function facts(array $p): array
    {
        $n     = static fn ($v): ?int => is_numeric($v) && (int) $v > 0 ? (int) $v : null;
        $facts = [];

        if ($v = $n($p['size'] ?? null)) {
            $facts[] = ['icon' => 'square_foot', 'value' => number_format($v) . ' m²', 'label' => 'Construcción', 'long' => number_format($v) . ' m² de construcción'];
        }
        if ($v = $n($p['land_size'] ?? null)) {
            $facts[] = ['icon' => 'landscape', 'value' => number_format($v) . ' m²', 'label' => 'Terreno', 'long' => number_format($v) . ' m² de terreno'];
        }
        if ($v = $n($p['bedroom'] ?? null)) {
            $facts[] = ['icon' => 'bed', 'value' => (string) $v, 'label' => $v === 1 ? 'Recámara' : 'Recámaras', 'long' => $v . ($v === 1 ? ' recámara' : ' recámaras')];
        }
        if ($v = $n($p['bathroom'] ?? null)) {
            $facts[] = ['icon' => 'bathtub', 'value' => (string) $v, 'label' => $v === 1 ? 'Baño' : 'Baños', 'long' => $v . ($v === 1 ? ' baño' : ' baños')];
        }
        if ($v = $n($p['parking'] ?? null)) {
            $facts[] = ['icon' => 'directions_car', 'value' => (string) $v, 'label' => $v === 1 ? 'Estacionamiento' : 'Estacionamientos', 'long' => $v . ($v === 1 ? ' estacionamiento' : ' estacionamientos')];
        }
        if (isset($p['age_years']) && is_numeric($p['age_years'])) {
            $age     = (int) $p['age_years'];
            $facts[] = $age === 0
                ? ['icon' => 'calendar_month', 'value' => 'Nueva', 'label' => 'A estrenar', 'long' => 'A estrenar']
                : ['icon' => 'calendar_month', 'value' => $age . ($age === 1 ? ' año' : ' años'), 'label' => 'Antigüedad', 'long' => $age . ($age === 1 ? ' año' : ' años') . ' de antigüedad'];
        }

        return $facts;
    }

    private static function tidy(mixed $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

        return $text === '' ? '' : mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}
