<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Rutas públicas ──────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('property', 'Property::index');
$routes->get('property/grid', 'Property::grid');
$routes->post('property/grid', 'Property::grid');
$routes->get('property/state/(:num)', 'Property::byState/$1');
$routes->get('property/(:num)', 'Property::show/$1');
$routes->get('agents', 'Agent::index');
$routes->get('about', 'About::index');
$routes->get('contact', 'Contact::index');
$routes->post('contact', 'Contact::submit');
$routes->get('calculator', 'Calculator::index');
$routes->post('calculator', 'Calculator::calculate');

// ─── Autenticación de usuario ─────────────────────────────────────────────────
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::doRegister');
$routes->get('logout', 'Auth::logout');
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendResetLink');
$routes->get('reset-password/(:hash)', 'Auth::resetPassword/$1');
$routes->post('reset-password/(:hash)', 'Auth::doResetPassword/$1');

// ─── Área de usuario (requiere sesión) ───────────────────────────────────────
$routes->group('', ['filter' => 'user-auth'], static function ($routes) {
    $routes->get('profile', 'User\Profile::index');
    $routes->post('profile/feedback', 'User\Profile::submitFeedback');

    $routes->get('my-properties', 'User\MyProperty::index');
    $routes->get('my-properties/add', 'User\MyProperty::create');
    $routes->post('my-properties/add', 'User\MyProperty::store');
    $routes->get('my-properties/edit/(:num)', 'User\MyProperty::edit/$1');
    $routes->post('my-properties/edit/(:num)', 'User\MyProperty::update/$1');
    $routes->get('my-properties/delete/(:num)', 'User\MyProperty::delete/$1');
});

// ─── Panel de administración ──────────────────────────────────────────────────
$routes->get('admin', 'Admin\Dashboard::index');
$routes->get('admin/login', 'Admin\Auth::login');
$routes->post('admin/login', 'Admin\Auth::doLogin');
$routes->get('admin/logout', 'Admin\Auth::logout');
$routes->post('admin/register', 'Admin\Register::create');
$routes->get('admin/forgot-password', 'Admin\Auth::forgotPassword');
$routes->post('admin/forgot-password', 'Admin\Auth::sendResetLink');
$routes->get('admin/reset-password/(:hash)', 'Admin\Auth::resetPassword/$1');
$routes->post('admin/reset-password/(:hash)', 'Admin\Auth::doResetPassword/$1');

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
    $routes->get('property/delete/(:num)', 'Admin\Property::delete/$1');

    // Usuarios / agentes / builders
    $routes->get('users', 'Admin\User::index');
    $routes->get('users/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('users/edit/(:num)', 'Admin\User::update/$1');
    $routes->get('users/delete/(:num)', 'Admin\User::delete/$1');
    $routes->get('agents', 'Admin\User::agents');
    $routes->get('agents/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('agents/edit/(:num)', 'Admin\User::update/$1');
    $routes->get('agents/delete/(:num)', 'Admin\User::deleteAgent/$1');
    $routes->get('builders', 'Admin\User::builders');
    $routes->get('builders/edit/(:num)', 'Admin\User::edit/$1');
    $routes->post('builders/edit/(:num)', 'Admin\User::update/$1');
    $routes->get('builders/delete/(:num)', 'Admin\User::deleteBuilder/$1');

    // Estados
    $routes->get('states', 'Admin\State::index');
    $routes->post('states', 'Admin\State::store');
    $routes->get('states/edit/(:num)', 'Admin\State::edit/$1');
    $routes->post('states/edit/(:num)', 'Admin\State::update/$1');
    $routes->get('states/delete/(:num)', 'Admin\State::delete/$1');

    // Ciudades
    $routes->get('cities', 'Admin\City::index');
    $routes->post('cities', 'Admin\City::store');
    $routes->get('cities/edit/(:num)', 'Admin\City::edit/$1');
    $routes->post('cities/edit/(:num)', 'Admin\City::update/$1');
    $routes->get('cities/delete/(:num)', 'Admin\City::delete/$1');

    // Contactos
    $routes->get('contacts', 'Admin\Contact::index');
    $routes->get('contacts/delete/(:num)', 'Admin\Contact::delete/$1');

    // Feedback
    $routes->get('feedback', 'Admin\Feedback::index');
    $routes->post('feedback/edit/(:num)', 'Admin\Feedback::update/$1');
    $routes->get('feedback/delete/(:num)', 'Admin\Feedback::delete/$1');

    // About
    $routes->get('about', 'Admin\About::index');
    $routes->get('about/create', 'Admin\About::create');
    $routes->post('about/create', 'Admin\About::store');
    $routes->get('about/edit/(:num)', 'Admin\About::edit/$1');
    $routes->post('about/edit/(:num)', 'Admin\About::update/$1');
    $routes->get('about/delete/(:num)', 'Admin\About::delete/$1');

    // Admins
    $routes->get('admins', 'Admin\AdminManager::index');
    $routes->get('admins/create', 'Admin\AdminManager::create');
    $routes->post('admins/create', 'Admin\AdminManager::store');
    $routes->get('admins/delete/(:num)', 'Admin\AdminManager::delete/$1');
});
