<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');
$routes->get('/registro', 'Home::registrar');
$routes->post('/guardar', 'Home::guardar');
$routes->post('/login', 'Home::login');

$routes->get('/dashboard', 'Home::dashboard');

$routes->get('/usuarios', 'Home::usuarios');

$routes->get('/editar/(:num)', 'Home::editar/$1');
$routes->post('/actualizar/(:num)', 'Home::actualizar/$1');
$routes->get('/eliminar/(:num)', 'Home::eliminar/$1');

$routes->get('/logout', 'Home::logout');