<?php

namespace App\Controllers;

use App\Libraries\Geocoder;
use App\Libraries\NearbyPlaces;
use App\Models\PropertyModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Property extends BaseController
{
    private PropertyModel $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new PropertyModel();
    }

    public function index(): string
    {
        return view('property/index', [
            'pageTitle'       => 'Propiedades en venta y renta',
            'metaDescription' => 'Casas, departamentos, oficinas, locales, bodegas y terrenos en venta y renta. Consulta precio, fotos, recámaras, baños y ubicación de cada propiedad.',
            'properties' => $this->propertyModel->withOwner()->available()->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function show(int $pid): string|RedirectResponse
    {
        $property = $this->propertyModel->withOwner()->available()->find($pid);

        if (! $property) {
            return redirect()->to('property')->with('error', 'Propiedad no encontrada.');
        }

        return view('property/detail', [
            'pageTitle'       => $property['title'], // el layout lo escapa
            'metaDescription' => $this->describe($property),
            'ogImage'         => ! empty($property['pimage']) ? base_url('uploads/property/' . $property['pimage']) : null,
            'property'        => $property,
            'featured'  => $this->propertyModel->withOwner()->featured()->findAll(),
        ]);
    }

    /**
     * Página "Ubicación": mapa con la zona aproximada de la propiedad
     * (las propiedades no guardan coordenadas; ver App\Libraries\Geocoder).
     */
    public function location(int $pid): string|RedirectResponse
    {
        $property = $this->propertyModel->withOwner()->available()->find($pid);

        if (! $property) {
            return redirect()->to('property')->with('error', 'Propiedad no encontrada.');
        }

        $address = Geocoder::address($property);

        return view('property/location', [
            'pageTitle'       => 'Ubicación de ' . $property['title'], // el layout lo escapa
            'metaDescription' => 'Ubicación de ' . $property['title'] . ($address !== '' ? ' en ' . $address : '') . ': mapa de la zona, cómo llegar y contacto.',
            // Complemento del detalle: que Google indexe el detalle, no esta página
            'noindex'         => true,
            'ogImage'         => ! empty($property['pimage']) ? base_url('uploads/property/' . $property['pimage']) : null,
            'property'        => $property,
            'address'         => $address,
            'place'           => $address !== '' ? (new Geocoder())->locate($property) : null,
        ]);
    }

    /**
     * "Lo que hay cerca" (JSON): lo pide public/js/nearby.js desde el detalle
     * y public/js/location.js desde la página de ubicación, después de cargar
     * la página, para no hacerla más lenta.
     *
     * Respuesta: {ok, html (partials/nearby), places [{name, cat, type, dist, min, lat, lng}], categories}
     * ok=false si no se pudo ubicar la zona (o solo el estado) o no hay lugares.
     */
    public function nearby(int $pid): ResponseInterface
    {
        $property = $this->propertyModel->available()->find($pid);
        $none     = $this->response->setJSON(['ok' => false])->setHeader('Cache-Control', 'public, max-age=600');

        if (! $property) {
            return $none->setStatusCode(404);
        }

        $place = Geocoder::address($property) !== '' ? (new Geocoder())->locate($property) : null;

        // Con solo el estado, las distancias desde su centro no dirían nada
        if (! $place || $place['level'] === 'state') {
            return $none;
        }

        $places = (new NearbyPlaces())->around($place['lat'], $place['lng']);
        if (! $places) {
            return $none;
        }

        $zone = trim((string) ($place['level'] === 'location' ? $property['location'] : $property['city']));

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setJSON([
                'ok'         => true,
                'html'       => view('partials/nearby', [
                    'places'  => $places,
                    'counts'  => NearbyPlaces::counts($places),
                    'summary' => NearbyPlaces::summary($places, $zone),
                    'mapUrl'  => site_url('property/' . $pid . '/ubicacion'),
                    'level'   => $place['level'],
                    'zone'    => $zone,
                ]),
                'places'     => $places,
                'categories' => NearbyPlaces::CATEGORIES,
            ]);
    }

    public function grid(): string
    {
        // Solo textos: un parámetro enviado como lista (city[]=x) se ignora
        $param = function (string $name): string {
            $value = $this->request->getPost($name) ?? $this->request->getGet($name);

            return is_string($value) ? trim($value) : '';
        };

        $filters = [
            'type'  => $param('type'),
            'stype' => $param('stype'),
            'city'  => mb_substr($param('city'), 0, 100),
        ];

        $properties = $this->propertyModel->withOwner()->applyFilters($filters)->findAll();

        return view('property/grid', [
            'pageTitle'  => 'Buscar Propiedades',
            // Resultados de búsqueda: Google no debe indexar cada combinación de filtros
            'noindex'    => true,
            'properties' => $properties,
            'filters'    => $filters,
        ]);
    }

    public function byState(int $id): string
    {
        $property = $this->propertyModel->find($id);
        $state    = $property['state'] ?? '';

        return view('property/by_state', [
            'pageTitle'  => 'Propiedades en ' . $state, // el layout lo escapa
            'metaDescription' => 'Propiedades en venta y renta en ' . $state . ': casas, departamentos, oficinas y más, con fotos, precios y ubicación.',
            'state'      => $state,
            'properties' => $this->propertyModel->withOwner()->available()->byState($state)->findAll(),
        ]);
    }

    /**
     * Descripción para buscadores y redes sociales (máx. ~160 caracteres):
     * "Casa en venta en Zinacantepec, Metepec: 3 recámaras, 2 baños, 200 m². $2,000,000."
     * seguida del inicio de la descripción de la propiedad, si cabe.
     */
    private function describe(array $p): string
    {
        $operation = ($p['stype'] ?? '') === 'rent' ? 'renta' : 'venta';
        $where     = implode(', ', array_filter([trim((string) ($p['location'] ?? '')), trim((string) ($p['city'] ?? ''))]));

        $parts = [];
        if ((int) ($p['bedroom'] ?? 0) > 0) {
            $parts[] = (int) $p['bedroom'] . ((int) $p['bedroom'] === 1 ? ' recámara' : ' recámaras');
        }
        if ((int) ($p['bathroom'] ?? 0) > 0) {
            $parts[] = (int) $p['bathroom'] . ((int) $p['bathroom'] === 1 ? ' baño' : ' baños');
        }
        if ((int) ($p['size'] ?? 0) > 0) {
            $parts[] = (int) $p['size'] . ' m²';
        }

        $text = \App\Libraries\Labels::propertyType($p['type'] ?? '') . ' en ' . $operation
            . ($where !== '' ? ' en ' . $where : '')
            . ($parts ? ': ' . implode(', ', $parts) : '')
            . '. Precio: $' . number_format((float) ($p['price'] ?? 0), 0) . '.';

        $content = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($p['pcontent'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($content !== '') {
            $text .= ' ' . $content;
        }

        return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157)) . '...' : $text;
    }
}
