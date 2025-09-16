<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/general/data/(:any)/(:any)', 'General::data/$1/$2');
$routes->get('/general/data/(:any)/(:any)/(:any)', 'General::data/$1/$2/$3');
$routes->get('/general/data/(:any)/(:any)/(:any)/(:any)', 'General::data/$1/$2/$3/$4');
$routes->get('/general/data/(:any)/(:any)/(:any)/(:any)/(:any)', 'General::data/$1/$2/$3/$4/$5');

$routes->get('/general/profile/(:alphanum)', 'General::profile/$1');


$routes->get('/general/rangkuman/(:any)/(:num)', 'General::rangkuman/$1/$2');
$routes->get('/general/rangkuman/(:any)/(:num)/(:any)', 'General::rangkuman/$1/$2/$3');


$routes->get('/general/menu', 'General::menu');
$routes->get('/general/menu/(:any)', 'General::menu/$1');
