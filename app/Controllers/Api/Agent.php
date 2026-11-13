<?php

namespace App\Controllers\Api;

use App\Filters\AgentOnlyFilter;
use App\Libraries\Agency;
use App\Libraries\Agenda;
use App\Libraries\AgendaService;
use App\Libraries\AgentListing;
use App\Libraries\FichaTecnica;
use App\Libraries\PropertyExtras;
use App\Models\AppointmentModel;
use App\Models\PropertyModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Área de agentes en la app móvil: "Todas las propiedades" y la agenda de
 * citas compartida. Mismas reglas que la web (AgentArea, AgentAgenda):
 * solo agentes y constructores; las notas del cliente y la edición solo
 * para quien agendó, quien muestra y el responsable del inmueble.
 */
class Agent extends BaseApiController
{
    /** GET /agent/properties?q=&stype=&status= */
    public function properties(): ResponseInterface
    {
        if ($denied = $this->denyNonAgents()) {
            return $denied;
        }

        $get    = fn (string $k): string => is_string($v = $this->request->getGet($k)) ? mb_substr(trim($v), 0, 100) : '';
        $status = $get('status');
        $rows   = AgentListing::search([
            'q'      => $get('q'),
            'stype'  => in_array($get('stype'), ['sale', 'rent'], true) ? $get('stype') : '',
            'status' => in_array($status, ['available', 'sold', 'rented', 'cancelled', 'all'], true) ? $status : 'available',
        ], (int) $this->currentUid());

        $keysReady = PropertyExtras::ready();
        $items     = array_map(static fn (array $p) => [
            'pid'         => (int) $p['pid'],
            'code'        => FichaTecnica::code((int) $p['pid']),
            'title'       => mb_strtoupper(mb_substr((string) $p['title'], 0, 1)) . mb_substr((string) $p['title'], 1),
            'stype'       => $p['stype'],
            'price'       => (float) $p['price'],
            'status'      => $p['status'],
            'zone'        => implode(', ', array_filter([trim((string) $p['location']), trim((string) $p['city'])])),
            'thumbnail'   => ! empty($p['pimage']) ? thumb_url('property', $p['pimage'], 800) : null,
            'owner_uid'   => (int) $p['uid'],
            'owner_name'  => (string) $p['uname'],
            'owner_photo' => ! empty($p['uimage']) ? thumb_url('user', $p['uimage'], 200) : null,
            'keys_status' => $keysReady ? ($p['keys_status'] ?? null) : null,
            'keys_label'  => $keysReady ? PropertyExtras::keysLabel($p['keys_status'] ?? null) : null,
            'keys_note'   => $keysReady ? ($p['keys_note'] ?? null) : null,
            'ficha_url'   => site_url('agente/ficha/' . $p['pid']),
        ], $rows);

        return $this->jsonResponse(['properties' => $items]);
    }

    /** GET /agenda?semana=YYYY-MM-DD&agente=mias|uid */
    public function agenda(): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $uid    = $this->currentUid();
        $monday = Agenda::weekStart($this->request->getGet('semana'));
        $filter = (string) $this->request->getGet('agente');

        $model = (new AppointmentModel())->withDetails()
            ->where('appointment.starts_at >=', $monday->format('Y-m-d 00:00:00'))
            ->where('appointment.starts_at <', $monday->modify('+7 days')->format('Y-m-d 00:00:00'));
        Agency::scopeAppointments($model, $uid);   // solo las de su inmobiliaria
        if ($filter === 'mias' || ctype_digit($filter)) {
            $who = $filter === 'mias' ? $uid : (int) $filter;
            $model->groupStart()
                ->where('appointment.shower_uid', $who)
                ->orWhere('property.uid', $who)
                ->orWhere('appointment.created_by', $who)
                ->groupEnd();
        }

        return $this->jsonResponse([
            'week_start'   => $monday->format('Y-m-d'),
            'title'        => Agenda::weekTitle($monday),
            'appointments' => array_map(static fn ($a) => AgendaService::toJson($a, $uid), $model->orderBy('appointment.starts_at')->findAll()),
        ]);
    }

    /** GET /agenda/options?pid= : inmuebles, agentes y opciones del formulario. */
    public function options(): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $uid        = (int) $this->currentUid();
        $properties = array_values(AgendaService::properties((int) $this->request->getGet('pid'), $uid));
        $agents     = array_values(array_map(static fn ($a) => ['uid' => (int) $a['uid'], 'uname' => $a['uname']], AgendaService::agents($uid)));

        return $this->jsonResponse([
            'properties' => $properties,
            'agents'     => $agents,
            'durations'  => array_map(static fn ($m, $l) => ['minutes' => $m, 'label' => $l], array_keys(Agenda::DURATIONS), Agenda::DURATIONS),
            'results'    => array_map(static fn ($k, $v) => ['value' => $k, 'label' => $v[0]], array_keys(Agenda::RESULTS), Agenda::RESULTS),
        ]);
    }

    /** GET /agenda/{apid} */
    public function show(int $apid): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $appt = (new AppointmentModel())->withDetails()->find($apid);
        if (! $appt || ! Agency::canSeeAppointment($appt, (int) $this->currentUid())) {
            return $this->jsonResponse(['error' => 'La cita ya no existe.'], 404);
        }

        return $this->jsonResponse(['appointment' => AgendaService::toJson($appt, (int) $this->currentUid())]);
    }

    /** GET /agenda/conflicts?pid=&date=&time=&duration=&shower_uid=&apid= */
    public function conflicts(): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $in                = $this->request->getGet();
        $in['client_name'] = 'x';   // solo se revisan inmueble, horario y agente
        [$data, $errors]   = Agenda::fromInput($in, array_keys(AgendaService::agents((int) $this->currentUid())));
        $messages          = $data['starts_at'] && ! $errors ? AgendaService::conflictsFor($data, (int) ($in['apid'] ?? 0) ?: null) : [];

        return $this->jsonResponse(['messages' => $messages]);
    }

    /** POST /agenda (nueva) o /agenda/{apid} (editar). Campos como el formulario web, más force=1. */
    public function save(?int $apid = null): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $uid = (int) $this->currentUid();
        $old = null;
        if ($apid) {
            $old = (new AppointmentModel())->withDetails()->find($apid);
            if (! $old) {
                return $this->jsonResponse(['error' => 'La cita ya no existe.'], 404);
            }
            if (! Agenda::canEdit($old, $uid)) {
                return $this->jsonResponse(['error' => 'Solo quien agendó, quien muestra o el responsable del inmueble pueden cambiar esta cita.'], 403);
            }
        }

        $in              = $this->body();
        [$data, $errors] = Agenda::fromInput($in, array_keys(AgendaService::agents($uid)));
        $property        = $data['pid'] ? (new PropertyModel())->find($data['pid']) : null;
        if (! $errors && (! $property || ! Agency::canSee($property, $uid))) {
            $errors[] = 'El inmueble no existe.';
        }
        if ($errors) {
            return $this->jsonResponse(['error' => implode(' ', $errors)], 422);
        }

        if (empty($in['force'])) {
            $conflicts = AgendaService::conflictsFor($data, $apid ?: null);
            if ($conflicts) {
                return $this->jsonResponse(['error' => 'El horario choca con otra cita.', 'conflicts' => $conflicts], 409);
            }
        }

        $model = new AppointmentModel();
        if ($old) {
            $model->update($apid, $data);
        } else {
            $data['created_by'] = $uid;
            $apid               = (int) $model->insert($data);
            AgendaService::notify($apid, $uid);
        }

        $saved = (new AppointmentModel())->withDetails()->find($apid);

        return $this->jsonResponse(['appointment' => AgendaService::toJson($saved, $uid)], $old ? 200 : 201);
    }

    /** POST /agenda/{apid}/delete */
    public function delete(int $apid): ResponseInterface
    {
        if ($denied = $this->denyAgenda()) {
            return $denied;
        }

        $appt = (new AppointmentModel())->withDetails()->find($apid);
        if (! $appt) {
            return $this->jsonResponse(['error' => 'La cita ya no existe.'], 404);
        }
        if (! Agenda::canEdit($appt, (int) $this->currentUid())) {
            return $this->jsonResponse(['error' => 'No puedes eliminar esta cita.'], 403);
        }

        (new AppointmentModel())->delete($apid);

        return $this->jsonResponse(['ok' => true]);
    }

    // ---------------------------------------------------------------

    /** Solo agentes y constructores (tipo leído de la BD con el token). */
    private function denyNonAgents(): ?ResponseInterface
    {
        $auth = $this->currentAuth();
        if (! $auth || ! in_array($auth['user']['utype'] ?? '', AgentOnlyFilter::TYPES, true)) {
            return $this->jsonResponse(['error' => 'Esta sección es solo para agentes inmobiliarios.'], 403);
        }

        return null;
    }

    private function denyAgenda(): ?ResponseInterface
    {
        if ($denied = $this->denyNonAgents()) {
            return $denied;
        }
        if (! Agenda::ready()) {
            return $this->jsonResponse(['error' => 'La agenda aún no está lista en el servidor.'], 503);
        }

        return null;
    }
}
