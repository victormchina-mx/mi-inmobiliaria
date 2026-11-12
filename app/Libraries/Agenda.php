<?php

namespace App\Libraries;

use DateTimeImmutable;
use Throwable;

/**
 * Reglas de la agenda de citas compartida (AgentAgenda, tabla appointment).
 *
 * - Todos los agentes ven todas las citas: inmueble, horario, quién muestra
 *   y el resultado.
 * - Las notas del cliente y la edición solo son para quien agendó, quien
 *   muestra y el responsable del inmueble. Los demás ven el nombre
 *   abreviado ("Ana R.").
 */
class Agenda
{
    /** Resultado de la visita: valor => [texto, clase de color]. */
    public const RESULTS = [
        'pending'    => ['Programada', 'is-pending'],
        'interested' => ['Le interesó', 'is-interested'],
        'offer'      => ['Oferta', 'is-offer'],
        'discarded'  => ['Descartado', 'is-discarded'],
    ];

    /** Duraciones que se pueden elegir (minutos => texto). */
    public const DURATIONS = [30 => '30 min', 45 => '45 min', 60 => '1 hora', 90 => '1 h 30 min', 120 => '2 horas'];

    /** Horario visible del calendario (horas completas). */
    public const FIRST_HOUR = 8;
    public const LAST_HOUR  = 21;

    public const DAYS   = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM'];
    public const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    private static ?bool $ready = null;

    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = db_connect()->tableExists('appointment');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /** Lunes de la semana de la fecha dada (hoy si no es válida). */
    public static function weekStart(?string $date): DateTimeImmutable
    {
        $day = self::date($date) ?? new DateTimeImmutable('today');

        return $day->modify('-' . ((int) $day->format('N') - 1) . ' days')->setTime(0, 0);
    }

    /** "9 – 15 de noviembre 2026" / "30 de noviembre – 6 de diciembre 2026". */
    public static function weekTitle(DateTimeImmutable $monday): string
    {
        $sunday = $monday->modify('+6 days');
        $m1     = self::MONTHS[(int) $monday->format('n') - 1];
        $m2     = self::MONTHS[(int) $sunday->format('n') - 1];

        if ($monday->format('Y') !== $sunday->format('Y')) {
            return $monday->format('j') . ' de ' . $m1 . ' ' . $monday->format('Y') . ' – ' . $sunday->format('j') . ' de ' . $m2 . ' ' . $sunday->format('Y');
        }
        if ($m1 !== $m2) {
            return $monday->format('j') . ' de ' . $m1 . ' – ' . $sunday->format('j') . ' de ' . $m2 . ' ' . $sunday->format('Y');
        }

        return $monday->format('j') . ' – ' . $sunday->format('j') . ' de ' . $m1 . ' ' . $sunday->format('Y');
    }

    /** "lunes 10 de noviembre, 10:00 a 11:00" */
    public static function when(string $start, string $end): string
    {
        $s    = new DateTimeImmutable($start);
        $e    = new DateTimeImmutable($end);
        $days = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

        return $days[(int) $s->format('N') - 1] . ' ' . $s->format('j') . ' de ' . self::MONTHS[(int) $s->format('n') - 1]
            . ', ' . $s->format('H:i') . ' a ' . $e->format('H:i');
    }

    /** Fecha Y-m-d válida, o null. */
    public static function date(?string $value): ?DateTimeImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $d && $d->format('Y-m-d') === $value ? $d : null;
    }

    /** ¿Puede ver los datos del cliente y editar la cita? */
    public static function canEdit(array $appt, int $uid): bool
    {
        return $uid > 0 && in_array($uid, [(int) ($appt['created_by'] ?? 0), (int) ($appt['shower_uid'] ?? 0), (int) ($appt['owner_uid'] ?? 0)], true);
    }

    /** "Ana Ruiz López" → "Ana R." (para quien no puede ver los datos). */
    public static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 2) {
            return $parts[0] ?? '';
        }

        return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
    }

    /** Posición de la cita en la columna del día (porcentaje de alto). */
    public static function position(string $start, string $end): array
    {
        $span = (self::LAST_HOUR - self::FIRST_HOUR) * 60;
        $from = static function (string $t): int {
            $d = new DateTimeImmutable($t);

            return ((int) $d->format('G') - self::FIRST_HOUR) * 60 + (int) $d->format('i');
        };
        $a = max(0, min($span, $from($start)));
        $b = max(0, min($span, $from($end)));
        if ($b - $a < 30) {
            $a = min($a, $span - 30);
            $b = $a + 30;
        }

        return ['top' => round($a / $span * 100, 3), 'height' => round(($b - $a) / $span * 100, 3)];
    }

    /**
     * Citas de un día con su carril, para que las que se empalman queden
     * lado a lado: agrega 'lane' (0…) y 'lanes' (carriles del grupo).
     *
     * @param list<array> $events ordenadas por starts_at
     */
    public static function lanes(array $events): array
    {
        $out = [];
        $group = [];
        $groupEnd = '';
        $flush = static function () use (&$group, &$out): void {
            $count = 0;
            foreach ($group as $e) {
                $count = max($count, $e['lane'] + 1);
            }
            foreach ($group as $e) {
                $e['lanes'] = $count;
                $out[] = $e;
            }
            $group = [];
        };

        foreach ($events as $e) {
            if ($group && $e['starts_at'] >= $groupEnd) {
                $flush();
            }
            // Primer carril libre del grupo
            $lane = 0;
            while (array_filter($group, static fn ($g) => $g['lane'] === $lane && $g['ends_at'] > $e['starts_at'])) {
                $lane++;
            }
            $e['lane'] = $lane;
            $group[]   = $e;
            $groupEnd  = max($groupEnd, $e['ends_at']);
        }
        if ($group) {
            $flush();
        }

        return $out;
    }

    /**
     * Datos de la cita desde el formulario.
     *
     * @param list<int> $agentIds agentes que pueden mostrar
     *
     * @return array{0: array<string, mixed>, 1: list<string>} [datos, errores]
     */
    public static function fromInput(array $in, array $agentIds): array
    {
        $text = static fn (string $k, int $max): string => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
        $errors = [];

        $pid  = (int) ($in['pid'] ?? 0);
        $day  = self::date((string) ($in['date'] ?? ''));
        $time = (string) ($in['time'] ?? '');
        $dur  = (int) ($in['duration'] ?? 0);

        if ($pid <= 0) {
            $errors[] = 'Elige el inmueble.';
        }
        if (! $day) {
            $errors[] = 'Elige la fecha de la cita.';
        }
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            $errors[] = 'Elige la hora de la cita.';
        }
        if (! isset(self::DURATIONS[$dur])) {
            $dur = 60;
        }

        $shower = (int) ($in['shower_uid'] ?? 0);
        if (! in_array($shower, $agentIds, true)) {
            $errors[] = 'Elige el agente que muestra el inmueble.';
        }

        $name  = $text('client_name', 120);
        if ($name === '') {
            $errors[] = 'Escribe el nombre del cliente.';
        }

        $result = (string) ($in['result'] ?? 'pending');
        if (! isset(self::RESULTS[$result])) {
            $result = 'pending';
        }
        $next = trim((string) ($in['next_date'] ?? ''));
        if ($next !== '' && ! self::date($next)) {
            $errors[] = 'La fecha de la próxima acción no es válida.';
        }

        $start = $day && ! $errors ? $day->format('Y-m-d') . ' ' . $time . ':00' : null;

        return [[
            'pid'          => $pid,
            'starts_at'    => $start,
            'ends_at'      => $start ? (new DateTimeImmutable($start))->modify('+' . $dur . ' minutes')->format('Y-m-d H:i:s') : null,
            'shower_uid'   => $shower,
            'client_name'  => $name,
            'client_notes' => $text('client_notes', 500) ?: null,
            'result'       => $result,
            'comments'     => $text('comments', 2000) ?: null,
            'next_action'  => $text('next_action', 160) ?: null,
            'next_date'    => $next !== '' && self::date($next) ? $next : null,
        ], $errors];
    }
}
