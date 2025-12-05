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

$routes->get('/home/(:any)', 'Home::general/$1');
$routes->get('/cetak/(:any)', 'Cetak::General/$1');
$routes->get('/cetak/nota/(:any)/(:any)', 'Cetak::Nota/$1/$2');

$routes->get('/settings/(:any)', 'Settings::general/$1');
$routes->get('/menu/(:any)', 'Menu::general/$1');
$routes->get('/user/(:any)', 'User::general/$1');
$routes->get('/options/(:any)', 'Options::general/$1');
$routes->get('/profile/(:any)', 'Profile::general/$1');
$routes->get('/barang/(:any)', 'Barang::general/$1');
$routes->get('/pengeluaran/(:any)', 'Pengeluaran::general/$1');
$routes->get('/inv/(:any)', 'Inv::general/$1');
$routes->get('/landing/(:any)', 'Landing::general/$1');
$routes->get('/transaksi/(:any)', 'Transaksi::general/$1');
$routes->get('/hutang/(:any)', 'Hutang::general/$1');

$routes->get('/bayar/(:any)', 'Bayar::general/$1');
