<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Rutas públicas ──────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('sitemap.xml', 'Sitemap::index');
// Versiones reducidas de las fotos (se generan la primera vez y luego son archivos estáticos)
$routes->get('img/(:segment)/(:num)/(:segment)', 'Thumb::show/$1/$2/$3');
$routes->get('property', 'Property::index');
$routes->get('property/grid', 'Property::grid');
$routes->post('property/grid', 'Property::grid');
$routes->get('property/state/(:num)', 'Property::byState/$1');
$routes->get('property/(:num)', 'Property::show/$1');
$routes->get('property/(:num)/ubicacion', 'Property::location/$1');
$routes->get('property/(:num)/cerca', 'Property::nearby/$1');
// Buscador de direcciones del formulario de propiedades (agente o admin con sesión)
$routes->get('geocode', 'Geocode::search', ['filter' => 'throttle:15,60']);
$routes->get('agents', 'Agent::index');
$routes->get('about', 'About::index');
$routes->get('contact', 'Contact::index');
$routes->post('contact', 'Contact::submit', ['filter' => ['throttle:5,600', 'bottrap:3']]);
$routes->get('calculator', 'Calculator::index');
$routes->post('calculator', 'Calculator::calculate');

// ─── Autenticación de usuario ─────────────────────────────────────────────────
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::doLogin', ['filter' => 'throttle:5,60']);
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::doRegister', ['filter' => ['throttle:5,600', 'bottrap:4']]);
$routes->get('logout', 'Auth::logout');
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendResetLink', ['filter' => ['throttle:3,300', 'bottrap:2']]);
$routes->get('reset-password/(:hash)', 'Auth::resetPassword/$1');
$routes->post('reset-password/(:hash)', 'Auth::doResetPassword/$1', ['filter' => 'throttle:5,300']);

// ─── Área de usuario (requiere sesión) ───────────────────────────────────────
$routes->group('', ['filter' => 'user-auth'], static function ($routes) {
    $routes->get('profile', 'User\Profile::index');
    $routes->post('profile/feedback', 'User\Profile::submitFeedback');

    $routes->get('my-properties', 'User\MyProperty::index');
    $routes->get('my-properties/add', 'User\MyProperty::create');
    $routes->post('my-properties/add', 'User\MyProperty::store');
    $routes->get('my-properties/edit/(:num)', 'User\MyProperty::edit/$1');
    $routes->post('my-properties/edit/(:num)', 'User\MyProperty::update/$1');
    $routes->post('my-properties/delete/(:num)', 'User\MyProperty::delete/$1');
});

// ─── Área de agentes: todas las propiedades y fichas técnicas ────────────────
$routes->group('agente', ['filter' => 'agent-only'], static function ($routes) {
    $routes->get('propiedades', 'AgentArea::properties');
    $routes->get('ficha/(:num)', 'AgentArea::ficha/$1');
    // Mi inmobiliaria (solo la encargada): identidad y agentes
    $routes->get('inmobiliaria', 'AgencyPanel::index');
    $routes->post('inmobiliaria', 'AgencyPanel::save');
    $routes->get('inmobiliaria/agentes', 'AgencyPanel::agents');
    $routes->post('inmobiliaria/agentes/agregar', 'AgencyPanel::addAgent');
    $routes->post('inmobiliaria/agentes/(:num)/quitar', 'AgencyPanel::removeAgent/$1');
    $routes->get('agenda', 'AgentAgenda::index');
    $routes->get('agenda/conflictos', 'AgentAgenda::conflicts');
    $routes->post('agenda/guardar', 'AgentAgenda::save');
    $routes->post('agenda/(:num)/eliminar', 'AgentAgenda::delete/$1');
});

// Enlace corto de las fichas técnicas (código QR): /p/25 → detalle
$routes->get('p/(:num)', 'Property::short/$1');

// ─── API para la App Móvil ───────────────────────────────────────────────────
$routes->post('api/v1/auth/login', 'Api\Auth::login');
$routes->get('api/v1/lookups/cities', 'Api\Lookups::cities');

$routes->group('api/v1', ['filter' => 'api-auth'], static function ($routes) {
    $routes->get('me', 'Api\Auth::me');
    $routes->post('me/photo', 'Api\Auth::photo');
    $routes->post('auth/logout', 'Api\Auth::logout');
    $routes->get('my-properties', 'Api\Properties::mine');
    $routes->get('properties/(:num)', 'Api\Properties::show/$1');
    $routes->post('properties', 'Api\Properties::store');
    $routes->post('properties/(:num)', 'Api\Properties::update/$1');
    $routes->get('geocode', 'Api\Lookups::geocode');
    // Área de agentes: todas las propiedades y agenda de citas (Api\Agent)
    $routes->get('agent/properties', 'Api\Agent::properties');
    $routes->get('agenda', 'Api\Agent::agenda');
    $routes->get('agenda/options', 'Api\Agent::options');
    $routes->get('agenda/conflicts', 'Api\Agent::conflicts');
    $routes->get('agenda/(:num)', 'Api\Agent::show/$1');
    $routes->post('agenda', 'Api\Agent::save');
    $routes->post('agenda/(:num)', 'Api\Agent::save/$1');
    $routes->post('agenda/(:num)/delete', 'Api\Agent::delete/$1');
});

// ─── Panel de administración ──────────────────────────────────────────────────
$routes->get('admin', 'Admin\Dashboard::index');
$routes->get('admin/login', 'Admin\Auth::login');
$routes->post('admin/login', 'Admin\Auth::doLogin', ['filter' => 'throttle:5,60']);
$routes->get('admin/logout', 'Admin\Auth::logout');
$routes->get('admin/forgot-password', 'Admin\Auth::forgotPassword');
$routes->post('admin/forgot-password', 'Admin\Auth::sendResetLink', ['filter' => 'throttle:3,300']);
$routes->get('admin/reset-password/(:hash)', 'Admin\Auth::resetPassword/$1');
$routes->post('admin/reset-password/(:hash)', 'Admin\Auth::doResetPassword/$1', ['filter' => 'throttle:5,300']);

$routes->group('admin', ['filter' => 'admin-auth'], static function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // Perfil admin
    $routes->get('profile', 'Admin\Profile::index');

    // Propiedades
    $routes->get('property', 'Admin\Property::index');
    $routes->get('property/create', 'Admin\Property::create');
    $routes->post('property/create', 'Admin\Property::store');
    $routes->get('property/edit/(:num)', 'Admin\Property::edit/$1');
    $routes->post('property/edit/(:num)', 'Admin\Property::update/$1');
    $routes->post('property/delete/(:num)', 'Admin\Property::delete/$1');

    // Inmobiliarias (Admin\Agency)
    $routes->get('agencies', 'Admin\Agency::index');
    $routes->get('agencies/create', 'Admin\Agency::create');
    $routes->post('agencies/create', 'Admin\Agency::store');
    $routes->get('agencies/edit/(:num)', 'Admin\Agency::edit/$1');
    $routes->post('agencies/edit/(:num)', 'Admin\Agency::update/$1');
    $routes->post('agencies/delete/(:num)', 'Admin\Agency::delete/$1');

    // Usuarios / agentes / builders
    $routes->get('users', 'Admin\User::index');
    $routes->get('users/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('users/edit/(:num)', 'Admin\User::update/$1');
    $routes->post('users/delete/(:num)', 'Admin\User::delete/$1');
    $routes->get('agents', 'Admin\User::agents');
    $routes->get('agents/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('agents/edit/(:num)', 'Admin\User::update/$1');
    $routes->post('agents/delete/(:num)', 'Admin\User::deleteAgent/$1');
    $routes->get('builders', 'Admin\User::builders');
    $routes->get('builders/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('builders/edit/(:num)', 'Admin\User::update/$1');
    $routes->post('builders/delete/(:num)', 'Admin\User::deleteBuilder/$1');

    // Estados
    $routes->get('states', 'Admin\State::index');
    $routes->post('states', 'Admin\State::store');
    $routes->get('states/edit/(:num)', 'Admin\State::edit/$1');
    $routes->post('states/edit/(:num)', 'Admin\State::update/$1');
    $routes->post('states/delete/(:num)', 'Admin\State::delete/$1');

    // Ciudades
    $routes->get('cities', 'Admin\City::index');
    $routes->post('cities', 'Admin\City::store');
    $routes->get('cities/edit/(:num)', 'Admin\City::edit/$1');
    $routes->post('cities/edit/(:num)', 'Admin\City::update/$1');
    $routes->post('cities/delete/(:num)', 'Admin\City::delete/$1');

    // Contactos
    $routes->get('contacts', 'Admin\Contact::index');
    $routes->post('contacts/delete/(:num)', 'Admin\Contact::delete/$1');

    // Feedback
    $routes->get('feedback', 'Admin\Feedback::index');
    $routes->post('feedback/edit/(:num)', 'Admin\Feedback::update/$1');
    $routes->post('feedback/delete/(:num)', 'Admin\Feedback::delete/$1');

    // About
    $routes->get('about', 'Admin\About::index');
    $routes->get('about/create', 'Admin\About::create');
    $routes->post('about/create', 'Admin\About::store');
    $routes->get('about/edit/(:num)', 'Admin\About::edit/$1');
    $routes->post('about/edit/(:num)', 'Admin\About::update/$1');
    $routes->post('about/delete/(:num)', 'Admin\About::delete/$1');

    // Admins
    $routes->get('admins', 'Admin\AdminManager::index');
    $routes->get('admins/create', 'Admin\AdminManager::create');
    $routes->post('admins/create', 'Admin\AdminManager::store');
    $routes->post('admins/delete/(:num)', 'Admin\AdminManager::delete/$1');
});
