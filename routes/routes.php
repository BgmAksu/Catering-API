<?php

/** @var Bramus\Router\Router $router */

// Define routes here
$router->get('/test', App\Controllers\IndexController::class . '@test');
$router->get('/', App\Controllers\IndexController::class . '@test');
$router->get('/testdb', App\Controllers\IndexController::class . '@testdb');
$router->get('/api/facilities',        'App\Controllers\FacilityController@list');
$router->get('/api/facilities/(\d+)',  'App\Controllers\FacilityController@detail');
$router->post('/api/facilities',       'App\Controllers\FacilityController@create');
$router->put('/api/facilities/(\d+)',  'App\Controllers\FacilityController@update');
$router->delete('/api/facilities/(\d+)', 'App\Controllers\FacilityController@delete');
$router->get('/api/facilities/search', 'App\Controllers\FacilityController@search');

