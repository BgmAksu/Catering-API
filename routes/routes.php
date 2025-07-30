<?php

/** @var Bramus\Router\Router $router */

// Define routes here
$router->get('/test', App\Controllers\IndexController::class . '@test');
$router->get('/', App\Controllers\IndexController::class . '@test');
$router->get('/testdb', App\Controllers\IndexController::class . '@testdb');

// Facilities
$router->get('/api/facilities',        'App\Controllers\FacilityController@list');
$router->get('/api/facilities/(\d+)',  'App\Controllers\FacilityController@detail');
$router->post('/api/facilities',       'App\Controllers\FacilityController@create');
$router->put('/api/facilities/(\d+)',  'App\Controllers\FacilityController@update');
$router->delete('/api/facilities/(\d+)', 'App\Controllers\FacilityController@delete');
$router->get('/api/facilities/search', 'App\Controllers\FacilityController@search');

// Tags
$router->get('/api/tags',            'App\Controllers\TagController@list');
$router->get('/api/tags/(\d+)',      'App\Controllers\TagController@detail');
$router->post('/api/tags',           'App\Controllers\TagController@create');
$router->put('/api/tags/(\d+)',      'App\Controllers\TagController@update');
$router->delete('/api/tags/(\d+)',   'App\Controllers\TagController@delete');

// Employees
$router->get('/api/facilities/(\d+)/employees', 'App\Controllers\EmployeeController@list');
$router->get('/api/employees/(\d+)',            'App\Controllers\EmployeeController@detail');
$router->post('/api/facilities/(\d+)/employees', 'App\Controllers\EmployeeController@create');
$router->put('/api/employees/(\d+)',            'App\Controllers\EmployeeController@update');
$router->delete('/api/employees/(\d+)',         'App\Controllers\EmployeeController@delete');

// Locations
$router->get('/api/locations',            'App\Controllers\LocationController@list');
$router->get('/api/locations/(\d+)',      'App\Controllers\LocationController@detail');
$router->post('/api/locations',           'App\Controllers\LocationController@create');
$router->put('/api/locations/(\d+)',      'App\Controllers\LocationController@update');
$router->delete('/api/locations/(\d+)',   'App\Controllers\LocationController@delete');

// Add or Remove tag from a Facility
$router->post('/api/facilities/(\d+)/tags', 'App\Controllers\FacilityController@addTags');
$router->delete('/api/facilities/(\d+)/tags', 'App\Controllers\FacilityController@removeTags');

