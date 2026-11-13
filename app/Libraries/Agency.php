<?php

namespace App\Libraries;

use App\Filters\AgentOnlyFilter;
use App\Models\AgencyModel;
use App\Models\UserModel;
use Throwable;

/**
 * Varias inmobiliarias (tabla agency, app/Database/sql/2026-11-agencies.sql).
 *
 * Cada agente y cada propiedad pertenece a una inmobiliaria o es
 * independiente (agency_id vacío). Aquí vive toda la separación:
 *
 * - En el área de agentes (Todas las propiedades, Agenda, Ficha técnica;
 *   web y app) un agente solo ve lo de su inmobiliaria; un independiente,
 *   solo lo suyo. El administrador (panel /admin) ve todo.
 * - Marca de cada inmobiliaria: logo y colores para las fichas técnicas y
 *   el portal. Los independientes usan "Real Estate by Vick" con los
 *   colores neutros del sitio.
 *
 * Si la base de datos aún no tiene las tablas (ready() = false) todo
 * funciona como antes: una sola inmobiliaria y las fichas de Frices.
 */
class Agency
{
    /** Colores del portal (independientes): azul del sitio y dorado. */
    public const NEUTRAL = ['primary' => '#0F172A', 'accent' => '#C9A96E'];

    /** Colores de Frices (fichas antes de ejecutar el SQL). */
    public const FRICES = ['primary' => '#0A2342', 'accent' => '#D1A84B'];

    public const STATUSES = ['active' => 'Activa', 'paused' => 'Pausada'];

    private static ?bool $ready = null;
    private static array $byId  = [];
    private static array $ofUid = [];

    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                $db          = db_connect();
                self::$ready = $db->tableExists('agency')
                    && $db->fieldExists('agency_id', 'user')
                    && $db->fieldExists('agency_id', 'property');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /** Para las pruebas: olvidar lo guardado en memoria. */
    public static function reset(?bool $ready = null): void
    {
        self::$ready = $ready;
        self::$byId  = [];
        self::$ofUid = [];
    }

    public static function find(?int $id): ?array
    {
        if (! $id || ! self::ready()) {
            return null;
        }
        if (! array_key_exists($id, self::$byId)) {
            self::$byId[$id] = (new AgencyModel())->find($id);
        }

        return self::$byId[$id];
    }

    /** Todas las inmobiliarias (id => fila), por nombre. */
    public static function all(bool $onlyActive = false): array
    {
        if (! self::ready()) {
            return [];
        }
        $model = (new AgencyModel())->orderBy('name');
        if ($onlyActive) {
            $model->where('status', 'active');
        }

        return array_column($model->findAll(), null, 'agency_id');
    }

    /** Inmobiliaria del usuario (null = independiente o sin tablas). */
    public static function idOf(int $uid): ?int
    {
        if ($uid <= 0 || ! self::ready()) {
            return null;
        }
        if (! array_key_exists($uid, self::$ofUid)) {
            $row               = (new UserModel())->select('agency_id')->find($uid);
            self::$ofUid[$uid] = ! empty($row['agency_id']) ? (int) $row['agency_id'] : null;
        }

        return self::$ofUid[$uid];
    }

    /**
     * Campo a guardar en la propiedad: la inmobiliaria de su agente.
     * Vacío si aún no existen las columnas (no se intenta guardar).
     */
    public static function propertyFields(int $ownerUid): array
    {
        return self::ready() ? ['agency_id' => self::idOf($ownerUid)] : [];
    }

    public static function ofUser(int $uid): ?array
    {
        return self::find(self::idOf($uid));
    }

    /** Inmobiliaria que encarga el usuario (null si no es encargado). */
    public static function managedBy(int $uid): ?array
    {
        if ($uid <= 0 || ! self::ready()) {
            return null;
        }

        return (new AgencyModel())->where('manager_uid', $uid)->first();
    }

    // ---------------------------------------------------------------
    // Qué ve cada agente
    // ---------------------------------------------------------------

    /**
     * Limita una consulta de propiedades (tabla "property") a lo que ve el
     * usuario: las de su inmobiliaria, o solo las suyas si es independiente.
     *
     * @param \CodeIgniter\Model|\CodeIgniter\Database\BaseBuilder $query
     */
    public static function scopeProperties($query, int $uid)
    {
        if (! self::ready()) {
            return $query;
        }
        $agency = self::idOf($uid);

        return $agency
            ? $query->where('property.agency_id', $agency)
            : $query->where('property.uid', $uid);
    }

    /**
     * Limita las citas (con withDetails(), que une "property") a las de la
     * inmobiliaria; un independiente ve las de sus inmuebles y en las que
     * participa.
     */
    public static function scopeAppointments($query, int $uid)
    {
        if (! self::ready()) {
            return $query;
        }
        $agency = self::idOf($uid);
        if ($agency) {
            return $query->where('property.agency_id', $agency);
        }

        return $query->groupStart()
            ->where('property.uid', $uid)
            ->orWhere('appointment.shower_uid', $uid)
            ->orWhere('appointment.created_by', $uid)
            ->groupEnd();
    }

    /** ¿El usuario puede ver esta propiedad en el área de agentes? */
    public static function canSee(array $property, int $uid): bool
    {
        if (! self::ready()) {
            return true;
        }
        $agency = self::idOf($uid);

        return $agency
            ? (int) ($property['agency_id'] ?? 0) === $agency
            : (int) ($property['uid'] ?? 0) === $uid;
    }

    /** ¿El usuario puede ver esta cita? (withDetails: owner_uid, property_agency) */
    public static function canSeeAppointment(array $a, int $uid): bool
    {
        if (! self::ready()) {
            return true;
        }
        $agency = self::idOf($uid);
        if ($agency) {
            return (int) ($a['property_agency'] ?? 0) === $agency;
        }

        return in_array($uid, [(int) ($a['owner_uid'] ?? 0), (int) ($a['shower_uid'] ?? 0), (int) ($a['created_by'] ?? 0)], true);
    }

    /**
     * Agentes que puede elegir el usuario (agente que muestra, etc.):
     * los de su inmobiliaria, o solo él si es independiente.
     *
     * @return array<int, array> uid => fila (uid, uname, uimage)
     */
    public static function agentsFor(int $uid): array
    {
        $model = (new UserModel())->select('uid, uname, uimage')->whereIn('utype', AgentOnlyFilter::TYPES);
        if (self::ready()) {
            $agency = self::idOf($uid);
            $agency ? $model->where('agency_id', $agency) : $model->where('uid', $uid);
        }

        return array_column($model->orderBy('uname')->findAll(), null, 'uid');
    }

    // ---------------------------------------------------------------
    // Marca: logo y colores
    // ---------------------------------------------------------------

    /**
     * Marca para la ficha técnica y el portal.
     *
     * @return array{independent: bool, frices: bool, name: string, tagline: string, logo: ?string, website: string, colors: array<string, string>}
     */
    public static function brand(?array $agency): array
    {
        if (! self::ready()) {
            // Antes del SQL: las fichas siguen con la imagen de Frices
            return self::brandArray(false, true, 'Grupo Inmobiliario Frices', 'Fortaleza • Resiliencia • Integridad • Confianza', null, 'https://www.frices.com.mx', self::FRICES);
        }
        if (! $agency) {
            return self::brandArray(true, false, 'Real Estate by Vick', '', null, '', self::NEUTRAL);
        }

        return self::brandArray(
            false,
            $agency['slug'] === 'frices' && empty($agency['logo']),
            (string) $agency['name'],
            (string) ($agency['tagline'] ?? ''),
            ! empty($agency['logo']) ? self::logoUrl($agency['logo']) : null,
            (string) ($agency['website'] ?? ''),
            ['primary' => self::color($agency['color_primary'] ?? '') ?? self::FRICES['primary'], 'accent' => self::color($agency['color_accent'] ?? '') ?? self::FRICES['accent']],
        );
    }

    /** Variables CSS de la ficha (--fx-*) para un color principal y uno de acento. */
    public static function palette(string $primary, string $accent): array
    {
        $light = self::mix($accent, '#FFFFFF', .45);
        $dark  = self::mix($accent, '#000000', .22);

        return [
            '--fx-navy'       => $primary,
            '--fx-navy-2'     => self::mix($primary, '#FFFFFF', .12),
            '--fx-navy-deep'  => self::mix($primary, '#000000', .3),
            '--fx-gold'       => $accent,
            '--fx-gold-light' => self::mix($accent, '#FFFFFF', .25),
            '--fx-gold-dark'  => $dark,
            '--fx-ivory'      => self::mix($accent, '#FFFFFF', .9),
            '--fx-gold-grad'  => "linear-gradient(135deg, {$light} 0%, {$accent} 45%, {$dark} 100%)",
        ];
    }

    /** style="..." con las variables de la paleta. */
    public static function cssVars(array $colors): string
    {
        $out = '';
        foreach (self::palette($colors['primary'], $colors['accent']) as $k => $v) {
            $out .= $k . ': ' . $v . '; ';
        }

        return trim($out);
    }

    /** "#0a2342" → "#0A2342"; null si no es un color #RRGGBB. */
    public static function color(?string $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/^#?([0-9a-f]{6})$/i', $value, $m)) {
            return '#' . strtoupper($m[1]);
        }

        return null;
    }

    /** Mezcla dos colores (#RRGGBB); $t = cuánto del segundo (0–1). */
    public static function mix(string $a, string $b, float $t): string
    {
        $a = self::color($a) ?? '#000000';
        $b = self::color($b) ?? '#000000';
        $out = '#';
        for ($i = 1; $i < 7; $i += 2) {
            $x = hexdec(substr($a, $i, 2));
            $y = hexdec(substr($b, $i, 2));
            $out .= sprintf('%02X', (int) round($x + ($y - $x) * $t));
        }

        return $out;
    }

    public static function logoUrl(?string $file): ?string
    {
        return $file ? base_url('uploads/agency/' . rawurlencode($file)) : null;
    }

    /** Texto corto para el portal: "Grupo Inmobiliario Frices" → "Frices". */
    public static function shortName(string $name): string
    {
        $short = trim((string) preg_replace('/^(grupo\s+)?(inmobiliari[ao]\s+)?/iu', '', $name));

        return $short !== '' ? $short : $name;
    }

    /** "Grupo Inmobiliario Frices" → "grupo-inmobiliario-frices". */
    public static function slugify(string $name): string
    {
        $s = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name));
        $s = trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-');

        return $s !== '' ? mb_substr($s, 0, 60) : 'inmobiliaria';
    }

    /** Iniciales para cuando no hay logo: "Casa Norte" → "CN". */
    public static function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', self::shortName($name)) ?: []));
        $ini   = mb_substr($words[0] ?? '?', 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : '');

        return mb_strtoupper($ini);
    }

    private static function brandArray(bool $independent, bool $frices, string $name, string $tagline, ?string $logo, string $website, array $colors): array
    {
        return compact('independent', 'frices', 'name', 'tagline', 'logo', 'website', 'colors');
    }
}
