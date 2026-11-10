<?php

namespace App\Controllers\Api;

use App\Libraries\PropertyLocation;
use App\Libraries\WhyText;
use App\Libraries\PropertyExtras;
use App\Libraries\HtmlSanitizer;
use App\Models\PropertyModel;

class Properties extends BaseApiController
{
    private PropertyModel $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new PropertyModel();
    }

    public function mine()
    {
        $uid = $this->currentUid();

        $properties = $this->propertyModel->byUser($uid)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $cards = array_map(static function (array $p) {
            return [
                'pid'       => (int) $p['pid'],
                'title'     => $p['title'],
                'city'      => $p['city'],
                'state'     => $p['state'],
                'price'     => (float) $p['price'],
                'status'    => $p['status'],
                'thumbnail' => $p['pimage'] ? base_url('uploads/property/' . $p['pimage']) : null,
            ];
        }, $properties);

        return $this->jsonResponse(['properties' => $cards]);
    }

    public function show(int $pid)
    {
        $uid      = $this->currentUid();
        $property = $this->propertyModel->find($pid);

        if (! $property || (int) $property['uid'] !== $uid) {
            return $this->jsonResponse(['error' => 'Propiedad no encontrada.'], 404);
        }

        return $this->jsonResponse(['property' => $this->serialize($property)]);
    }

    public function store()
    {
        return $this->saveProperty(null);
    }

    public function update(int $pid)
    {
        return $this->saveProperty($pid);
    }

    /** Crea o actualiza; mismo mapeo de campos que App\Controllers\User\MyProperty. */
    private function saveProperty(?int $pid)
    {
        $uid = $this->currentUid();

        $existing = null;
        if ($pid !== null) {
            $existing = $this->propertyModel->find($pid);

            if (! $existing || (int) $existing['uid'] !== $uid) {
                return $this->jsonResponse(['error' => 'Acción no permitida.'], 403);
            }
        }

        $rules = [
            'title' => 'required|max_length[200]',
            'ptype' => 'required',
            'stype' => 'required|in_list[sale,rent]',
            'price' => 'required|decimal',
            'status' => 'permit_empty|in_list[available,sold,rented,cancelled]',
        ];

        if (! $this->validateData($this->body(), $rules)) {
            return $this->jsonResponse(['error' => implode(' ', $this->validator->getErrors())], 422);
        }

        $status       = $this->request->getPost('status') ?? 'available';
        $cancelReason = trim((string) $this->request->getPost('cancel_reason'));

        if ($status === 'cancelled' && $cancelReason === '') {
            return $this->jsonResponse(['error' => 'Debes indicar el motivo de cancelación.'], 422);
        }

        $data = array_merge([
            'title'         => $this->request->getPost('title'),
            'pcontent'      => HtmlSanitizer::clean($this->request->getPost('content')),
            'type'          => $this->request->getPost('ptype'),
            'bhk'           => $this->request->getPost('bhk') ?? '',
            'stype'         => $this->request->getPost('stype'),
            'bedroom'       => (int) $this->request->getPost('bed'),
            'bathroom'      => (int) $this->request->getPost('bath'),
            'balcony'       => (int) $this->request->getPost('balc'),
            'kitchen'       => (int) $this->request->getPost('kitc'),
            'hall'          => (int) $this->request->getPost('hall'),
            'floor'         => $this->request->getPost('floor') ?? '',
            'size'          => (int) $this->request->getPost('asize'),
            'price'         => (float) $this->request->getPost('price'),
            'location'      => $this->request->getPost('loc') ?? '',
            'city'          => $this->request->getPost('city') ?? '',
            'state'         => $this->request->getPost('state') ?? '',
            'feature'       => HtmlSanitizer::clean($this->request->getPost('feature')),
            'totalfloor'    => $this->request->getPost('totalfl') ?? '',
            'status'        => $status,
            'cancel_reason' => $status === 'cancelled' ? $cancelReason : null,
        ], $this->uploadImages($existing ?? []), PropertyLocation::fromRequest($this->request, true), WhyText::fromRequest($this->request, true), PropertyExtras::fromRequest($this->request, true));

        if ($pid === null) {
            $data['uid']        = $uid;
            $data['isFeatured'] = 0; // solo el admin destaca propiedades
            $this->propertyModel->insert($data);

            return $this->jsonResponse([
                'pid'     => $this->propertyModel->getInsertID(),
                'message' => 'Propiedad publicada correctamente.',
            ], 201);
        }

        $this->propertyModel->update($pid, $data);

        return $this->jsonResponse(['pid' => $pid, 'message' => 'Propiedad actualizada correctamente.']);
    }

    /** Sube las 8 imágenes de la propiedad; mantiene las existentes si no se sube ninguna. */
    private function uploadImages(array $existing): array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fields  = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4', 'mapimage', 'topmapimage', 'groundmapimage'];
        $input   = [
            'pimage' => 'aimage', 'pimage1' => 'aimage1', 'pimage2' => 'aimage2',
            'pimage3' => 'aimage3', 'pimage4' => 'aimage4',
            'mapimage' => 'fimage', 'topmapimage' => 'fimage1', 'groundmapimage' => 'fimage2',
        ];

        $result = [];
        foreach ($fields as $field) {
            $file = $this->request->getFile($input[$field]);

            if ($file && $file->isValid() && ! $file->hasMoved() && in_array($file->getMimeType(), $allowed, true)) {
                $name = $file->getRandomName();
                $file->move(FCPATH . 'uploads/property', $name);
                $result[$field] = $name;
            } else {
                $result[$field] = $existing[$field] ?? '';
            }
        }

        return $result;
    }

    private function serialize(array $p): array
    {
        $imageFields = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4', 'mapimage', 'topmapimage', 'groundmapimage'];
        $images      = [];

        foreach ($imageFields as $f) {
            $images[$f] = $p[$f] ? base_url('uploads/property/' . $p[$f]) : null;
        }

        return [
            'pid'           => (int) $p['pid'],
            'title'         => $p['title'],
            'pcontent'      => $p['pcontent'],
            'type'          => $p['type'],
            'stype'         => $p['stype'],
            'bedroom'       => (int) $p['bedroom'],
            'bathroom'      => (int) $p['bathroom'],
            'balcony'       => (int) $p['balcony'],
            'size'          => (int) $p['size'],
            'price'         => (float) $p['price'],
            'location'      => $p['location'],
            'city'          => $p['city'],
            'state'         => $p['state'],
            'feature'       => $p['feature'],
            'status'        => $p['status'],
            'cancel_reason' => $p['cancel_reason'],
            'isFeatured'    => (int) $p['isFeatured'],
            'lat'           => isset($p['lat']) ? (float) $p['lat'] : null,
            'lng'           => isset($p['lng']) ? (float) $p['lng'] : null,
            'exact_location' => (int) ($p['exact_location'] ?? 0),
            'why_text'      => $p['why_text'] ?? '',
            'land_size'     => isset($p['land_size']) ? (int) $p['land_size'] : null,
            'parking'       => isset($p['parking']) ? (int) $p['parking'] : null,
            'age_years'     => isset($p['age_years']) ? (int) $p['age_years'] : null,
            'keys_status'   => $p['keys_status'] ?? null,
            'keys_note'     => $p['keys_note'] ?? null,
            'images'        => $images,
        ];
    }
}
