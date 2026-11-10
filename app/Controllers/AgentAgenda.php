<?php

namespace App\Controllers;

use App\Filters\AgentOnlyFilter;
use App\Libraries\Agenda;
use App\Libraries\FichaTecnica;
use App\Libraries\PropertyExtras;
use App\Models\AppointmentModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Agenda de citas compartida entre agentes (filtro "agent-only").
 *
 * Vista: app/Views/agent/agenda.php · Reglas: App\Libraries\Agenda
 * SQL: app/Database/sql/2026-11-appointments.sql
 */
class AgentAgenda extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (! Agenda::ready()) {
            return view('agent/agenda', ['pageTitle' => 'Agenda de citas', 'noindex' => true, 'ready' => false]);
        }

        $uid    = $this->uid();
        $monday = Agenda::weekStart($this->request->getGet('semana'));
        $agents = $this->agents();
        $filter = (string) $this->request->getGet('agente');
        if ($filter !== 'mias' && ! isset($agents[(int) $filter])) {
            $filter = '';
        }

        // Citas de la semana
        $model = (new AppointmentModel())->withDetails()
            ->where('appointment.starts_at >=', $monday->format('Y-m-d 00:00:00'))
            ->where('appointment.starts_at <', $monday->modify('+7 days')->format('Y-m-d 00:00:00'));
        if ($filter !== '') {
            $who = $filter === 'mias' ? $uid : (int) $filter;
            $model->groupStart()
                ->where('appointment.shower_uid', $who)
                ->orWhere('property.uid', $who)
                ->orWhere('appointment.created_by', $who)
                ->groupEnd();
        }
        $week = $model->orderBy('appointment.starts_at')->findAll();

        // Cita abierta en el panel (editar o solo ver) o nueva
        $current = null;
        $citaId  = (int) $this->request->getGet('cita');
        if ($citaId) {
            $current = (new AppointmentModel())->withDetails()->find($citaId);
            if (! $current) {
                return redirect()->to('agente/agenda')->with('error', 'La cita ya no existe.');
            }
        }

        $properties = $this->properties($current['pid'] ?? (int) $this->request->getGet('pid'));
        $prefillPid = (int) $this->request->getGet('pid');
        $today      = new DateTimeImmutable('today');
        $fecha      = Agenda::date($this->request->getGet('fecha'));
        $defaultDay = $fecha ?? ($monday <= $today && $today < $monday->modify('+7 days') ? $today : $monday);

        return view('agent/agenda', [
            'pageTitle'  => 'Agenda de citas',
            'noindex'    => true,
            'ready'      => true,
            'uid'        => $uid,
            'monday'     => $monday,
            'week'       => $week,
            'agents'     => $agents,
            'filter'     => $filter,
            'current'    => $current,
            'editable'   => ! $current || Agenda::canEdit($current, $uid),
            'properties' => $properties,
            'defaults'   => [
                'pid'        => $current['pid'] ?? (isset($properties[$prefillPid]) ? $prefillPid : ''),
                'date'       => $current ? substr($current['starts_at'], 0, 10) : $defaultDay->format('Y-m-d'),
                'time'       => $current ? substr($current['starts_at'], 11, 5) : ($this->request->getGet('hora') && preg_match('/^\d{2}:\d{2}$/', (string) $this->request->getGet('hora')) ? (string) $this->request->getGet('hora') : '10:00'),
                'duration'   => $current ? (int) round((strtotime($current['ends_at']) - strtotime($current['starts_at'])) / 60) : 60,
                'shower_uid' => $current['shower_uid'] ?? $uid,
            ],
            'conflicts'  => session()->getFlashdata('agenda_conflict') ?? [],
        ]);
    }

    public function save(): RedirectResponse
    {
        if (! Agenda::ready()) {
            return redirect()->to('agente/agenda');
        }

        $uid  = $this->uid();
        $apid = (int) $this->request->getPost('apid');
        $old  = null;
        if ($apid) {
            $old = (new AppointmentModel())->withDetails()->find($apid);
            if (! $old || ! Agenda::canEdit($old, $uid)) {
                return redirect()->to('agente/agenda')->with('error', 'Solo quien agendó, quien muestra o el responsable del inmueble pueden cambiar esta cita.');
            }
        }

        [$data, $errors] = Agenda::fromInput($this->request->getPost(), array_keys($this->agents()));
        $property = $data['pid'] ? (new PropertyModel())->withOwner()->find($data['pid']) : null;
        if (! $errors && ! $property) {
            $errors[] = 'El inmueble no existe.';
        }
        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if (! $this->request->getPost('force')) {
            $conflicts = $this->conflictsFor($data, $apid ?: null);
            if ($conflicts) {
                return redirect()->back()->withInput()->with('agenda_conflict', $conflicts);
            }
        }

        $model = new AppointmentModel();
        if ($old) {
            $model->update($apid, $data);
            $message = 'Cita actualizada.';
        } else {
            $data['created_by'] = $uid;
            $apid    = (int) $model->insert($data);
            $message = 'Cita agendada.';
            $this->notify($apid, $uid);
        }

        return redirect()->to('agente/agenda?semana=' . substr($data['starts_at'], 0, 10) . '&cita=' . $apid)->with('success', $message);
    }

    public function delete(int $apid): RedirectResponse
    {
        $appt = Agenda::ready() ? (new AppointmentModel())->withDetails()->find($apid) : null;
        if (! $appt || ! Agenda::canEdit($appt, $this->uid())) {
            return redirect()->to('agente/agenda')->with('error', 'No puedes eliminar esta cita.');
        }

        (new AppointmentModel())->delete($apid);

        return redirect()->to('agente/agenda?semana=' . substr($appt['starts_at'], 0, 10))->with('success', 'Cita eliminada.');
    }

    /** Aviso en vivo del formulario: ¿se empalma con otra cita? (JSON) */
    public function conflicts(): ResponseInterface
    {
        if (! Agenda::ready()) {
            return $this->response->setJSON(['ok' => false, 'messages' => []]);
        }

        $in = $this->request->getGet();
        $in['client_name'] = 'x';   // solo se revisan inmueble, horario y agente
        [$data, $errors] = Agenda::fromInput($in, array_keys($this->agents()));
        $messages = $data['starts_at'] && ! $errors ? $this->conflictsFor($data, (int) ($in['apid'] ?? 0) ?: null) : [];

        return $this->response->setJSON(['ok' => true, 'messages' => $messages]);
    }

    // ---------------------------------------------------------------

    private function uid(): int
    {
        return (int) session()->get('uid');
    }

    /** Agentes y constructores (uid => fila), los que pueden mostrar. */
    private function agents(): array
    {
        $rows = (new UserModel())->select('uid, uname, uimage')->whereIn('utype', AgentOnlyFilter::TYPES)->orderBy('uname')->findAll();

        return array_column($rows, null, 'uid');
    }

    /**
     * Inmuebles para elegir (pid => datos de la sección 1): los disponibles
     * y, si se está editando o se pidió otro, también ese.
     */
    private function properties(int $include = 0): array
    {
        $model = (new PropertyModel())->withOwner()->groupStart()->where('property.status', 'available');
        if ($include) {
            $model->orWhere('property.pid', $include);
        }
        $rows = $model->groupEnd()->orderBy('property.pid', 'DESC')->findAll();

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
    private function conflictsFor(array $data, ?int $except): array
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
    private function notify(int $apid, int $uid): void
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
}
