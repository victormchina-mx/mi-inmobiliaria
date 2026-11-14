<?php

namespace App\Controllers;

use App\Filters\AgentOnlyFilter;
use App\Libraries\Agency;
use App\Libraries\AgencyForm;
use App\Libraries\Agenda;
use App\Models\AgencyModel;
use App\Models\AppointmentModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * "Mi inmobiliaria" (Mi cuenta, solo la encargada de una inmobiliaria):
 * identidad (logo con su recorte, colores, lema, contacto) y sus agentes
 * (agregar por correo, quitar). No ve a las demás inmobiliarias; crear
 * inmobiliarias, nombrar encargados y mover agentes entre inmobiliarias es
 * del administrador (Admin\Agency, Admin\User).
 *
 * Vista: app/Views/agent/agency.php · Reglas: App\Libraries\Agency
 */
class AgencyPanel extends BaseController
{
    public function index(): string|RedirectResponse
    {
        return $this->page('identidad');
    }

    public function agents(): string|RedirectResponse
    {
        return $this->page('agentes');
    }

    public function save(): RedirectResponse
    {
        $agency = $this->managed();
        if (! $agency) {
            return $this->denied();
        }

        [$data, $errors] = AgencyForm::read($this->request);
        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $data['logo'] = AgencyForm::applyLogo($this->request, $agency['logo'] ?? null);
        (new AgencyModel())->update($agency['agency_id'], $data);

        return redirect()->to('agente/inmobiliaria')->with('success', 'Datos de la inmobiliaria guardados.');
    }

    /** Agregar un agente independiente por el correo de su cuenta. */
    public function addAgent(): RedirectResponse
    {
        $agency = $this->managed();
        if (! $agency) {
            return $this->denied();
        }

        $email = mb_substr(trim((string) $this->request->getPost('email')), 0, 150);
        $user  = $email !== '' ? (new UserModel())->where('uemail', $email)->first() : null;
        $back  = redirect()->to('agente/inmobiliaria/agentes');

        if (! $user) {
            return $back->withInput()->with('error', 'No hay ninguna cuenta con ese correo. Pídele que se registre en el sitio como agente.');
        }
        if (! in_array($user['utype'], AgentOnlyFilter::TYPES, true)) {
            return $back->withInput()->with('error', 'Esa cuenta no es de agente. Pide al administrador que la cambie a Agente.');
        }
        if ((int) ($user['agency_id'] ?? 0) === (int) $agency['agency_id']) {
            return $back->with('error', $user['uname'] . ' ya es agente de ' . $agency['name'] . '.');
        }
        if (! empty($user['agency_id'])) {
            // No se dice cuál: la encargada no ve a las demás inmobiliarias
            return $back->withInput()->with('error', 'Ese agente pertenece a otra inmobiliaria. Solo el administrador puede cambiarlo.');
        }

        (new UserModel())->skipValidation(true)->update((int) $user['uid'], ['agency_id' => (int) $agency['agency_id']]);
        // Sus propiedades pasan con él a la inmobiliaria
        (new PropertyModel())->builder()->where('uid', (int) $user['uid'])->update(['agency_id' => (int) $agency['agency_id']]);

        return redirect()->to('agente/inmobiliaria/agentes')->with('success', $user['uname'] . ' ahora es agente de ' . $agency['name'] . '.');
    }

    /** Quitar a un agente: queda como independiente, con sus propiedades. */
    public function removeAgent(int $uid): RedirectResponse
    {
        $agency = $this->managed();
        if (! $agency) {
            return $this->denied();
        }

        $user = (new UserModel())->find($uid);
        $back = redirect()->to('agente/inmobiliaria/agentes');
        if (! $user || (int) ($user['agency_id'] ?? 0) !== (int) $agency['agency_id']) {
            return $back->with('error', 'Ese agente no está en tu inmobiliaria.');
        }
        if ((int) $agency['manager_uid'] === $uid) {
            return $back->with('error', 'No puedes quitar al encargado. Pide al administrador que nombre a otro.');
        }

        (new UserModel())->skipValidation(true)->update($uid, ['agency_id' => null]);
        (new PropertyModel())->builder()->where('uid', $uid)->update(['agency_id' => null]);

        return $back->with('success', $user['uname'] . ' ya no es agente de ' . $agency['name'] . '; quedó como independiente con sus propiedades.');
    }

    // ---------------------------------------------------------------

    private function page(string $tab): string|RedirectResponse
    {
        $agency = $this->managed();
        if (! $agency) {
            return $this->denied();
        }

        $id     = (int) $agency['agency_id'];
        $agents = (new UserModel())->select('uid, uname, uemail, uimage, utype')
            ->where('agency_id', $id)->whereIn('utype', AgentOnlyFilter::TYPES)->orderBy('uname')->findAll();

        // Propiedades y citas del mes de cada agente
        $props = array_column((new PropertyModel())->select('uid, COUNT(*) AS n')->where('agency_id', $id)->groupBy('uid')->findAll(), 'n', 'uid');
        $citas = [];
        if (Agenda::ready()) {
            $month = date('Y-m-01 00:00:00');
            $citas = array_column((new AppointmentModel())->select('shower_uid, COUNT(*) AS n')
                ->where('starts_at >=', $month)->where('starts_at <', date('Y-m-01 00:00:00', strtotime('+1 month', strtotime($month))))
                ->groupBy('shower_uid')->findAll(), 'n', 'shower_uid');
        }

        return view('agent/agency', [
            'pageTitle' => 'Mi inmobiliaria',
            'noindex'   => true,
            'tab'       => $tab,
            'agency'    => $agency,
            'agents'    => $agents,
            'props'     => $props,
            'citas'     => $citas,
        ]);
    }

    /** Inmobiliaria que encarga el usuario (null si no es encargado). */
    private function managed(): ?array
    {
        return Agency::managedBy((int) session()->get('uid'));
    }

    private function denied(): RedirectResponse
    {
        return redirect()->to('agente/propiedades')->with('error', 'Esta sección es solo para el encargado de la inmobiliaria.');
    }
}
