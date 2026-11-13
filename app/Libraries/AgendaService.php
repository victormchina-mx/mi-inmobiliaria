<?php

namespace App\Libraries;

use App\Models\AppointmentModel;
use App\Models\PropertyModel;
use App\Models\UserModel;

/**
 * Datos y acciones de la agenda que comparten la web (AgentAgenda) y la API
 * de la app móvil (Api\Agenda): agentes, inmuebles para elegir, choques de
 * horario y avisos por correo. Reglas puras en App\Libraries\Agenda.
 */
class AgendaService
{
    /**
     * Agentes que pueden mostrar (uid => fila): los de la inmobiliaria del
     * usuario, o solo él si es independiente (App\Libraries\Agency).
     */
    public static function agents(int $uid): array
    {
        return Agency::agentsFor($uid);
    }

    /**
     * Inmuebles para elegir (pid => datos de la sección 1): los disponibles
     * y, si se está editando o se pidió otro, también ese.
     */
    public static function properties(int $include, int $uid): array
    {
        $model = (new PropertyModel())->withOwner()->groupStart()->where('property.status', 'available');
        if ($include) {
            $model->orWhere('property.pid', $include);
        }
        $model->groupEnd();
        Agency::scopeProperties($model, $uid);   // solo los de su inmobiliaria
        $rows = $model->orderBy('property.pid', 'DESC')->findAll();

        $out = [];
        foreach ($rows as $p) {
            $keys = PropertyExtras::ready() ? ($p['keys_status'] ?? null) : null;
            $out[(int) $p['pid']] = [
                'pid'   => (int) $p['pid'],
                'code'  => FichaTecnica::code((int) $p['pid']),
                'title' => mb_strtoupper(mb_substr((string) $p['title'], 0, 1)) . mb_substr((string) $p['title'], 1),
                'zone'  => implode(', ', array_filter(array_map(static fn ($v) => trim((string) $v), [$p['location'], $p['city'], $p['state']]))),
                'owner' => (string) $p['uname'],
                'keys'  => PropertyExtras::ready() ? PropertyExtras::keysLabel($keys) . (! empty($p['keys_note']) ? ' — ' . $p['keys_note'] : '') : '',
                'warn'  => in_array($keys, ['owner', 'occupied', 'other'], true) || ! $keys,
            ];
        }

        return $out;
    }

    /** Mensajes de las citas que chocan con la nueva (mismo inmueble o mismo agente). */
    public static function conflictsFor(array $data, ?int $except): array
    {
        $rows = (new AppointmentModel())->withDetails()
            ->overlapping($data['starts_at'], $data['ends_at'], $except)
            ->groupStart()->where('appointment.pid', $data['pid'])->orWhere('appointment.shower_uid', $data['shower_uid'])->groupEnd()
            ->orderBy('appointment.starts_at')->findAll();

        $out = [];
        foreach ($rows as $r) {
            $hours = substr($r['starts_at'], 11, 5) . ' a ' . substr($r['ends_at'], 11, 5);
            if ((int) $r['pid'] === (int) $data['pid']) {
                $out[] = ($r['shower_name'] ?: 'Otro agente') . ' ya tiene una cita en este inmueble de ' . $hours . '.';
            } else {
                $out[] = ($r['shower_name'] ?: 'El agente') . ' ya tiene otra cita a esa hora (' . FichaTecnica::code((int) $r['pid']) . ', ' . $hours . ').';
            }
        }

        return array_values(array_unique($out));
    }

    /** Correo al responsable del inmueble y al agente que muestra (si no son quien agendó). */
    public static function notify(int $apid, int $uid): void
    {
        $appt = (new AppointmentModel())->withDetails()->find($apid);
        if (! $appt) {
            return;
        }
        $me = (new UserModel())->select('uname')->find($uid);

        $to = [];
        if ((int) $appt['owner_uid'] !== $uid && ! empty($appt['owner_email'])) {
            $to[$appt['owner_email']] = 'owner';
        }
        if ((int) $appt['shower_uid'] !== $uid && ! empty($appt['shower_email']) && ! isset($to[$appt['shower_email']])) {
            $to[$appt['shower_email']] = 'shower';
        }

        foreach ($to as $email => $role) {
            $mail = \Config\Services::email();
            $mail->setTo($email);
            $mail->setSubject('Nueva cita · ' . FichaTecnica::code((int) $appt['pid']) . ' · ' . Agenda::when($appt['starts_at'], $appt['ends_at']));
            $mail->setMessage(view('agent/emails/appointment', [
                'a'       => $appt,
                'role'    => $role,
                'creator' => $me['uname'] ?? 'Un agente',
                'link'    => site_url('agente/agenda?semana=' . substr($appt['starts_at'], 0, 10) . '&cita=' . $apid),
            ]));
            $mail->setMailType('html');
            if (! $mail->send(false)) {
                log_message('error', 'Error enviando aviso de cita: ' . $mail->printDebugger(['headers']));
            }
        }
    }

    /** Al borrar una propiedad se borran sus citas (si la agenda está lista). */
    public static function deleteForProperty(int $pid): void
    {
        if ($pid > 0 && Agenda::ready()) {
            (new AppointmentModel())->where('pid', $pid)->delete();
        }
    }

    /**
     * Cita para la app: los datos privados (notas, comentarios, próxima
     * acción) solo van si quien pregunta puede editarla.
     */
    public static function toJson(array $a, int $uid): array
    {
        $mine = Agenda::canEdit($a, $uid);
        $res  = Agenda::RESULTS[$a['result']] ?? Agenda::RESULTS['pending'];

        return [
            'apid'         => (int) $a['apid'],
            'pid'          => (int) $a['pid'],
            'code'         => FichaTecnica::code((int) $a['pid']),
            'title'        => (string) ($a['title'] ?? ''),
            'zone'         => implode(', ', array_filter([trim((string) ($a['location'] ?? '')), trim((string) ($a['city'] ?? ''))])),
            'starts_at'    => $a['starts_at'],
            'ends_at'      => $a['ends_at'],
            'shower_uid'   => (int) $a['shower_uid'],
            'shower_name'  => (string) ($a['shower_name'] ?? ''),
            'owner_uid'    => (int) ($a['owner_uid'] ?? 0),
            'owner_name'   => (string) ($a['owner_name'] ?? ''),
            'created_by'   => (int) $a['created_by'],
            'client_name'  => $mine ? $a['client_name'] : Agenda::shortName($a['client_name']),
            'client_notes' => $mine ? ($a['client_notes'] ?? '') : null,
            'result'       => $a['result'],
            'result_label' => $res[0],
            'comments'     => $mine ? ($a['comments'] ?? '') : null,
            'next_action'  => $mine ? ($a['next_action'] ?? '') : null,
            'next_date'    => $mine ? $a['next_date'] : null,
            'editable'     => $mine,
        ];
    }
}
