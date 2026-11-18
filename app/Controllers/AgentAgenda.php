<?php

namespace App\Controllers;

use App\Libraries\Agency;
use App\Libraries\Agenda;
use App\Libraries\AgendaService;
use App\Models\AppointmentModel;
use App\Models\PropertyModel;
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
        $isMonth = $this->request->getGet('vista') === 'mes';
        $monday = Agenda::weekStart($this->request->getGet('semana'));
        $month  = Agenda::monthStart($this->request->getGet('mes'));
        [$from, $to] = $isMonth ? Agenda::monthGrid($month) : [$monday, $monday->modify('+7 days')];
        $agents = AgendaService::agents($uid);
        $filter = (string) $this->request->getGet('agente');
        if ($filter !== 'mias' && ! isset($agents[(int) $filter])) {
            $filter = '';
        }

        // Citas de la semana (o de las semanas que cubren el mes)
        $model = (new AppointmentModel())->withDetails()
            ->where('appointment.starts_at >=', $from->format('Y-m-d 00:00:00'))
            ->where('appointment.starts_at <', $to->format('Y-m-d 00:00:00'));
        Agency::scopeAppointments($model, $uid);   // solo las de su inmobiliaria
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
            if (! $current || ! Agency::canSeeAppointment($current, $uid)) {
                return redirect()->to('agente/agenda')->with('error', 'La cita ya no existe.');
            }
        }

        $properties = AgendaService::properties($current['pid'] ?? (int) $this->request->getGet('pid'), $uid);
        $prefillPid = (int) $this->request->getGet('pid');
        $today      = new DateTimeImmutable('today');
        $fecha      = Agenda::date($this->request->getGet('fecha'));
        $anchor     = $isMonth ? $month : $monday;
        $anchorEnd  = $isMonth ? $month->modify('+1 month') : $monday->modify('+7 days');
        $defaultDay = $fecha ?? ($anchor <= $today && $today < $anchorEnd ? $today : $anchor);

        return view('agent/agenda', [
            'pageTitle'  => 'Agenda de citas',
            'noindex'    => true,
            'ready'      => true,
            'uid'        => $uid,
            'isMonth'    => $isMonth,
            'monday'     => $monday,
            'month'      => $month,
            'gridStart'  => $from,
            'gridEnd'    => $to,
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

        [$data, $errors] = Agenda::fromInput($this->request->getPost(), array_keys(AgendaService::agents($uid)));
        $property = $data['pid'] ? (new PropertyModel())->withOwner()->find($data['pid']) : null;
        if (! $errors && (! $property || ! Agency::canSee($property, $uid))) {
            $errors[] = 'El inmueble no existe.';
        }
        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if (! $this->request->getPost('force')) {
            $conflicts = AgendaService::conflictsFor($data, $apid ?: null);
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
            AgendaService::notify($apid, $uid);
        }

        return redirect()->to($this->backTo($data['starts_at'], ['cita' => $apid]))->with('success', $message);
    }

    public function delete(int $apid): RedirectResponse
    {
        $appt = Agenda::ready() ? (new AppointmentModel())->withDetails()->find($apid) : null;
        if (! $appt || ! Agenda::canEdit($appt, $this->uid())) {
            return redirect()->to('agente/agenda')->with('error', 'No puedes eliminar esta cita.');
        }

        (new AppointmentModel())->delete($apid);

        return redirect()->to($this->backTo($appt['starts_at']))->with('success', 'Cita eliminada.');
    }

    /** Aviso en vivo del formulario: ¿se empalma con otra cita? (JSON) */
    public function conflicts(): ResponseInterface
    {
        if (! Agenda::ready()) {
            return $this->response->setJSON(['ok' => false, 'messages' => []]);
        }

        $in = $this->request->getGet();
        $in['client_name'] = 'x';   // solo se revisan inmueble, horario y agente
        [$data, $errors] = Agenda::fromInput($in, array_keys(AgendaService::agents($this->uid())));
        $messages = $data['starts_at'] && ! $errors ? AgendaService::conflictsFor($data, (int) ($in['apid'] ?? 0) ?: null) : [];

        return $this->response->setJSON(['ok' => true, 'messages' => $messages]);
    }

    // ---------------------------------------------------------------

    /**
     * Regresa a la agenda en la misma vista (semana o mes) y con el mismo
     * filtro de agente con que se guardó, en la fecha de la cita.
     */
    private function backTo(string $startsAt, array $extra = []): string
    {
        $q = $this->request->getPost('vista') === 'mes'
            ? ['vista' => 'mes', 'mes' => substr($startsAt, 0, 7)]
            : ['semana' => substr($startsAt, 0, 10)];
        $agente = (string) $this->request->getPost('agente');
        if ($agente === 'mias' || ctype_digit($agente)) {
            $q['agente'] = $agente;
        }

        return 'agente/agenda?' . http_build_query($q + $extra);
    }

    private function uid(): int
    {
        return (int) session()->get('uid');
    }
}
