<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Filters\AgentOnlyFilter;
use App\Libraries\Agency as Agencies;
use App\Libraries\AgencyForm;
use App\Models\AgencyModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Inmobiliarias: alta y edición (logo, colores, contacto,
 * encargado), y cuántos agentes y propiedades tiene cada una.
 * Reglas y marca en App\Libraries\Agency.
 */
class Agency extends BaseController
{
    public function index(): string
    {
        $ready    = Agencies::ready();
        $agencies = $ready ? Agencies::all() : [];
        $stats    = [];

        if ($ready) {
            foreach ((new UserModel())->select('agency_id, COUNT(*) AS n')->whereIn('utype', AgentOnlyFilter::TYPES)->groupBy('agency_id')->findAll() as $r) {
                $stats[(int) $r['agency_id']]['agents'] = (int) $r['n'];
            }
            foreach ((new PropertyModel())->select('agency_id, COUNT(*) AS n')->groupBy('agency_id')->findAll() as $r) {
                $stats[(int) $r['agency_id']]['properties'] = (int) $r['n'];
            }
        }

        $managerIds = array_filter(array_map(static fn ($a) => (int) $a['manager_uid'], $agencies));
        $managers   = $managerIds ? array_column((new UserModel())->select('uid, uname')->whereIn('uid', $managerIds)->findAll(), 'uname', 'uid') : [];

        return view('admin/agency/index', [
            'pageTitle' => 'Inmobiliarias',
            'ready'     => $ready,
            'agencies'  => $agencies,
            'stats'     => $stats,
            'managers'  => $managers,
        ]);
    }

    public function create(): string|RedirectResponse
    {
        if (! Agencies::ready()) {
            return redirect()->to('admin/agencies');
        }

        return $this->form(null);
    }

    public function edit(int $id): string|RedirectResponse
    {
        $agency = Agencies::find($id);
        if (! $agency) {
            return redirect()->to('admin/agencies')->with('error', 'Inmobiliaria no encontrada.');
        }

        return $this->form($agency);
    }

    public function store(): RedirectResponse
    {
        return $this->save(null);
    }

    public function update(int $id): RedirectResponse
    {
        $agency = Agencies::find($id);
        if (! $agency) {
            return redirect()->to('admin/agencies')->with('error', 'Inmobiliaria no encontrada.');
        }

        return $this->save($agency);
    }

    /** Solo se borra si ya no tiene agentes ni propiedades (si no, se pausa). */
    public function delete(int $id): RedirectResponse
    {
        $agency = Agencies::find($id);
        if (! $agency) {
            return redirect()->to('admin/agencies')->with('error', 'Inmobiliaria no encontrada.');
        }

        $agents     = (new UserModel())->where('agency_id', $id)->countAllResults();
        $properties = (new PropertyModel())->where('agency_id', $id)->countAllResults();
        if ($agents || $properties) {
            return redirect()->to('admin/agencies')->with('error', "No se puede eliminar: todavía tiene {$agents} agentes y {$properties} propiedades. Muévelos o pausa la inmobiliaria.");
        }

        (new AgencyModel())->delete($id);
        AgencyForm::deleteLogo($agency['logo'] ?? null);

        return redirect()->to('admin/agencies')->with('success', 'Inmobiliaria eliminada.');
    }

    // ---------------------------------------------------------------

    private function form(?array $agency): string
    {
        // Encargado: un agente o constructor de esta misma inmobiliaria
        $candidates = $agency
            ? (new UserModel())->select('uid, uname, uemail')->where('agency_id', $agency['agency_id'])->whereIn('utype', AgentOnlyFilter::TYPES)->orderBy('uname')->findAll()
            : [];

        return view('admin/agency/form', [
            'pageTitle'  => $agency ? 'Editar inmobiliaria' : 'Nueva inmobiliaria',
            'agency'     => $agency,
            'candidates' => $candidates,
        ]);
    }

    private function save(?array $agency): RedirectResponse
    {
        if (! Agencies::ready()) {
            return redirect()->to('admin/agencies');
        }

        [$data, $errors] = AgencyForm::read($this->request);
        $status = (string) $this->request->getPost('status');

        // Encargado: solo alguien de la misma inmobiliaria
        $manager = (int) $this->request->getPost('manager_uid');
        if ($manager && (! $agency || ! (new UserModel())->where('uid', $manager)->where('agency_id', $agency['agency_id'])->first())) {
            $errors[] = 'El encargado debe ser un agente de esta inmobiliaria.';
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $data['logo']        = AgencyForm::applyLogo($this->request, $agency['logo'] ?? null);
        $data['manager_uid'] = $manager ?: null;
        $data['status']      = isset(Agencies::STATUSES[$status]) ? $status : 'active';
        $name                = $data['name'];

        $model = new AgencyModel();
        if ($agency) {
            $model->update($agency['agency_id'], $data);
            $id = (int) $agency['agency_id'];
        } else {
            $data['slug'] = $this->uniqueSlug(Agencies::slugify($name));
            $id           = (int) $model->insert($data);
        }

        return redirect()->to('admin/agencies/edit/' . $id)->with('success', $agency ? 'Inmobiliaria actualizada.' : 'Inmobiliaria creada. Ahora asígnale agentes desde Usuarios → Agentes.');
    }

    private function uniqueSlug(string $slug): string
    {
        $model = new AgencyModel();
        $try   = $slug;
        for ($i = 2; $model->where('slug', $try)->countAllResults() > 0; $i++) {
            $try = mb_substr($slug, 0, 55) . '-' . $i;
        }

        return $try;
    }
}
