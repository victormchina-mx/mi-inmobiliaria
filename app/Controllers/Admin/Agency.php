<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Filters\AgentOnlyFilter;
use App\Libraries\Agency as Agencies;
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
    private const LOGO_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

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
        $this->deleteLogo($agency['logo'] ?? null);

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

        $post = fn (string $k, int $max = 200): string => mb_substr(trim((string) $this->request->getPost($k)), 0, $max);

        $name    = $post('name', 120);
        $primary = Agencies::color($post('color_primary', 7));
        $accent  = Agencies::color($post('color_accent', 7));
        $email   = $post('email', 150);
        $website = $post('website', 200);
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        $errors = [];
        if ($name === '') {
            $errors[] = 'Escribe el nombre de la inmobiliaria.';
        }
        if (! $primary || ! $accent) {
            $errors[] = 'Elige el color principal y el de acento.';
        }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo no es válido.';
        }
        if ($website !== '' && ! filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'El sitio web no es válido.';
        }

        // Encargado: solo alguien de la misma inmobiliaria
        $manager = (int) $this->request->getPost('manager_uid');
        if ($manager && (! $agency || ! (new UserModel())->where('uid', $manager)->where('agency_id', $agency['agency_id'])->first())) {
            $errors[] = 'El encargado debe ser un agente de esta inmobiliaria.';
        }

        // Logo: PNG, JPG o WebP (SVG no: podría llevar código)
        $logo = $agency['logo'] ?? null;
        $file = $this->request->getFile('logo');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if (! in_array($file->getMimeType(), self::LOGO_TYPES, true)) {
                $errors[] = 'El logo debe ser PNG, JPG o WebP.';
            }
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/agency', $newName);
            $this->deleteLogo($logo);
            $logo = $newName;
        } elseif ($this->request->getPost('remove_logo')) {
            $this->deleteLogo($logo);
            $logo = null;
        }

        $data = [
            'name'          => $name,
            'logo'          => $logo,
            'color_primary' => $primary,
            'color_accent'  => $accent,
            'tagline'       => $post('tagline', 160) ?: null,
            'phone'         => $post('phone', 30) ?: null,
            'whatsapp'      => $post('whatsapp', 30) ?: null,
            'email'         => $email ?: null,
            'website'       => $website ?: null,
            'manager_uid'   => $manager ?: null,
            'status'        => isset(Agencies::STATUSES[$post('status', 20)]) ? $post('status', 20) : 'active',
        ];

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

    private function deleteLogo(?string $file): void
    {
        if ($file && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file)) {
            $path = FCPATH . 'uploads/agency/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
