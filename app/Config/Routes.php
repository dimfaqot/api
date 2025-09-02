<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/nota/by_no_nota/(:any)/(:any)', 'Nota::by_no_nota/$1/$2');
