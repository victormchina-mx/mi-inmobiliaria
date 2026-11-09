<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Texto propio del agente para "¿Por qué vivir aquí?" (columna why_text de
 * la tabla property; ver app/Database/sql/2026-11-property-why-text.sql).
 *
 * Si el agente lo escribe, reemplaza el párrafo automático del recuadro
 * (NearbyPlaces::summary); el título y las ventajas siguen saliendo de los
 * datos reales. Si lo deja vacío, se usa el texto automático.
 *
 * Es texto plano: se quitan etiquetas HTML y se guardan como mucho MAX_LENGTH
 * caracteres; los saltos de línea se respetan al mostrarlo.
 */
class WhyText
{
    public const MAX_LENGTH = 600;

    private static ?bool $ready = null;

    /** ¿Ya existe la columna why_text? (para no fallar antes de correr el SQL) */
    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = db_connect()->fieldExists('why_text', 'property');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /**
     * Dato a guardar desde el formulario o la API.
     *
     * @param bool $onlyIfSent API: si la app no manda "why_text", no se toca lo guardado
     *
     * @return array{why_text?: string|null}
     */
    public static function fromRequest(IncomingRequest $request, bool $onlyIfSent = false): array
    {
        $value = $request->getPost('why_text');

        if (! self::ready() || ($onlyIfSent && $value === null)) {
            return [];
        }

        $text = self::clean($value);

        return ['why_text' => $text === '' ? null : $text];
    }

    /** Texto plano, sin espacios de más, con máximo MAX_LENGTH caracteres. */
    public static function clean(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\S\n]+/u', ' ', $text);   // espacios y tabuladores seguidos
        $text = preg_replace('/ *\n */u', "\n", $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);   // máximo una línea en blanco

        return trim(mb_substr(trim($text), 0, self::MAX_LENGTH));
    }

    /** El texto guardado de una propiedad ('' si no tiene). */
    public static function of(array $property): string
    {
        return self::clean($property['why_text'] ?? '');
    }
}
