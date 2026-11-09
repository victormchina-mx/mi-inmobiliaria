<?php

namespace App\Libraries;

/**
 * Limpia los nombres de lugares de OpenStreetMap para "Lo que hay cerca".
 *
 * Los nombres los escribe cualquier persona en OpenStreetMap y llegan con:
 *  - claves oficiales de escuela (CCT): "Secundaria Jose Vasconcelos 15dtv0277k",
 *    "… Cct 15epr4213a", "… Cct: 15ppr3315", y el turno ("Matutino");
 *  - comillas: "Ambigu", Jardín de Niños "Narciso Mendoza";
 *  - todo en MAYÚSCULAS o empezando en minúscula;
 *  - palabras comunes sin acento: Clinica, Medico, Educacion, Cafeteria…
 *
 * Además detecta nombres que no dicen nada ("Cafetería", "Taquería",
 * "Consultorio Médico") y lugares mal clasificados (clases de taekwondo o
 * natación contadas como escuelas, un salón de fiestas como parque).
 *
 * Lo usa NearbyPlaces::tidy() al mostrar los lugares, así que también
 * corrige lo que ya estaba guardado en caché.
 */
class PlaceName
{
    /** Siglas que se dejan en mayúsculas aunque el resto se convierta. */
    private const ACRONYMS = [
        'IPN', 'UNAM', 'UAM', 'UVM', 'UNRC', 'ENCB', 'ISSSTE', 'IMSS', 'SEDENA', 'DIF', 'CDMX',
        'CETIS', 'CBTIS', 'CONALEP', 'SEP', 'UAEM', 'UAEMEX', 'ITESM', 'TEC', 'A.C.', 'S.C.', 'CUZEM',
    ];

    /** Palabras de enlace que van en minúscula dentro de un nombre. */
    private const SMALL = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'en', 'a', 'al', 'para', 'con', 'por'];

    /** Palabras comunes que en OpenStreetMap suelen ir sin acento. */
    private const ACCENTS = [
        'clinica' => 'clínica', 'clinicas' => 'clínicas', 'medico' => 'médico', 'medica' => 'médica',
        'medicos' => 'médicos', 'medicas' => 'médicas', 'educacion' => 'educación', 'nutricion' => 'nutrición',
        'psicologia' => 'psicología', 'psicologico' => 'psicológico', 'psicologica' => 'psicológica',
        'rehabilitacion' => 'rehabilitación', 'fisica' => 'física', 'dermatologia' => 'dermatología',
        'estetica' => 'estética', 'traumatologia' => 'traumatología', 'homeopatico' => 'homeopático',
        'odontologico' => 'odontológico', 'odontologia' => 'odontología', 'guarderia' => 'guardería',
        'natacion' => 'natación', 'pedagogico' => 'pedagógico', 'tecnologico' => 'tecnológico',
        'tecnologica' => 'tecnológica', 'historico' => 'histórico', 'historica' => 'histórica',
        'cafeteria' => 'cafetería', 'libreria' => 'librería', 'optica' => 'óptica', 'mexico' => 'México',
        'autonoma' => 'autónoma', 'numero' => 'número', 'oftalmica' => 'oftálmica', 'pizzeria' => 'pizzería',
        'taqueria' => 'taquería', 'jardin' => 'jardín', 'bibloteca' => 'biblioteca', 'electrica' => 'eléctrica',
        'publica' => 'pública', 'politecnico' => 'politécnico', 'academia' => 'academia', 'cientifico' => 'científico',
        'cientificos' => 'científicos', 'tecnicas' => 'técnicas', 'tecnico' => 'técnico', 'musica' => 'música',
        'quirurgica' => 'quirúrgica', 'farmaceutica' => 'farmacéutica', 'salon' => 'salón',
    ];

    /** Nombres que solo repiten el tipo de lugar: no aportan nada en la lista. */
    private const GENERIC = [
        'cafeteria', 'cafe', 'restaurante', 'restaurant', 'taqueria', 'tacos', 'hamburguesas', 'pizzeria',
        'consultorio', 'consultorio medico', 'consultorio dental', 'farmacia', 'farmacias', 'escuela', 'kinder',
        'preescolar', 'primaria', 'secundaria', 'parque', 'jardin', 'clinica', 'hospital', 'mercado',
        'supermercado', 'tienda', 'biblioteca', 'museo', 'estacion', 'centro cultural', 'cocina economica',
    ];

    public static function clean(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        // Claves de centro de trabajo (CCT) de la SEP y el turno de la escuela
        $name = preg_replace('/\s*\bC\.?\s?C\.?\s?T\.?\s*:?\s*[0-9A-Za-z]{6,12}\b/iu', '', $name);
        $name = preg_replace('/\s*\b\d{2}[A-Za-z]{3}\d{4}[A-Za-z]?\b/u', '', $name);
        $name = preg_replace('/\s+\bturno\b.*$/iu', '', $name);                          // "… Turno De Domingo"
        $name = preg_replace('/\s+\b(?:matutino|vespertino|nocturno)$/iu', '', $name);

        // Comillas y signos sueltos
        $name = str_replace(['"', '“', '”', '«', '»'], '', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name), " \t-–—,:;");

        // Todo en mayúsculas (salvo siglas cortas como "KALI" o "IPN"): a "Tipo Título"
        $letters = preg_replace('/[^\p{L}]/u', '', $name);
        if ($letters !== '' && mb_strtoupper($letters) === $letters && mb_strlen($letters) > 5) {
            $name = self::titleCase($name);
        }

        $name = self::fixAccents($name);

        // Primera letra en mayúscula ("colegio Preston" → "Colegio Preston")
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /** ¿El nombre solo repite el tipo ("Cafetería", "Consultorio Médico")? */
    public static function isGeneric(string $name, string $type = ''): bool
    {
        $key = self::key($name);

        return $key === '' || in_array($key, self::GENERIC, true) || ($type !== '' && $key === self::key($type));
    }

    /**
     * Corrige la categoría de lugares mal clasificados en OpenStreetMap.
     *
     * @return array{0: string, 1: string}|null [categoría, tipo]; null = no mostrarlo
     */
    public static function reclassify(string $name, string $cat, string $type): ?array
    {
        $key = self::key($name);

        // Clases deportivas o artísticas registradas como escuela
        if ($cat === 'school' && preg_match('/\b(tae ?kwon ?do|taekwondo|karate|judo|yoga|natacion|aqua|acuatic|gymboree|gimnasio|gym|baile|danza|ballet|box(eo)?|crossfit|futbol|musica|idiomas?|italiano|ingles)\b/u', $key)) {
            return null;
        }
        // Salones de fiestas o eventos registrados como parque o jardín
        if ($cat === 'park' && preg_match('/\b(salon|fiestas?|eventos?|banquetes?)\b/u', $key)) {
            return null;
        }
        // Farmacias registradas como hospital o clínica
        if ($cat === 'health' && $type !== 'Farmacia' && preg_match('/\bfarmacias?\b/u', $key) && ! preg_match('/\bconsultorio\b/u', $key)) {
            return ['health', 'Farmacia'];
        }
        // Ópticas y tiendas de lentes: no son servicios de salud
        if ($cat === 'health' && preg_match('/\b(optica|lentes)\b/u', $key)) {
            return null;
        }

        return [$cat, $type];
    }

    /** Texto en minúsculas, sin acentos ni signos, para comparar. */
    public static function key(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $text));
    }

    private static function titleCase(string $name): string
    {
        $words = explode(' ', mb_strtolower($name));

        foreach ($words as $i => $word) {
            $upper = mb_strtoupper($word);
            if (in_array($upper, self::ACRONYMS, true)) {
                $words[$i] = $upper;
            } elseif ($i > 0 && in_array($word, self::SMALL, true)) {
                $words[$i] = $word;
            } else {
                $words[$i] = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
            }
        }

        return implode(' ', $words);
    }

    private static function fixAccents(string $name): string
    {
        return preg_replace_callback('/\p{L}+/u', static function (array $m): string {
            $word  = $m[0];
            $lower = mb_strtolower($word);

            if (! isset(self::ACCENTS[$lower])) {
                return $word;
            }

            $fixed = self::ACCENTS[$lower];
            // Respetar si la palabra venía con mayúscula inicial
            if (mb_substr($word, 0, 1) !== mb_substr($lower, 0, 1)) {
                $fixed = mb_strtoupper(mb_substr($fixed, 0, 1)) . mb_substr($fixed, 1);
            }

            return $fixed;
        }, $name);
    }
}
