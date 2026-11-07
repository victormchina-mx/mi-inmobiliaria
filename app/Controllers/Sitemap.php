<?php

namespace App\Controllers;

use App\Models\PropertyModel;
use CodeIgniter\HTTP\ResponseInterface;

class Sitemap extends BaseController
{
    public function index(): ResponseInterface
    {
        $propertyModel = new PropertyModel();
        // Solo las disponibles: las vendidas, rentadas o canceladas redirigen al
        // listado y Google las marca como error del sitemap.
        $properties = $propertyModel->select('pid, updated_at')->available()->findAll();

        $staticPages = [
            ['url' => '',         'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => 'property', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => 'about',    'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => 'contact',  'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => 'agents',   'priority' => '0.6', 'changefreq' => 'weekly'],
            ['url' => 'calculator', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        $xml = view('sitemap/xml', [
            'staticPages' => $staticPages,
            'properties'  => $properties,
        ]);

        return $this->response
            ->setContentType('application/xml')
            ->setBody($xml);
    }
}
