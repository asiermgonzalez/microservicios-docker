<?php

use MongoDB\Client;

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version() . ' - Microservicios con Lumen 10 - inventory-service';
});


//$router->group(['prefix' => 'products'], function () use ($router) {
$router->group(['prefix' =>'products', 'middleware'=>'jwt.verify'], function () use ($router) {
    $router->get('/', 'ProductController@index');
    $router->post('/', 'ProductController@store');
    $router->get('/{id}', 'ProductController@show');
    $router->get('/search/searchByName', 'ProductController@searchByName');
    $router->put('/{id}', 'ProductController@update');
    $router->delete('/{id}', 'ProductController@destroy');
});
