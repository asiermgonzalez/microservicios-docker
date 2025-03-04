<?php

use MongoDB\Client;

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version() . ' - Microservicios con Lumen 10 - order-service';
});

//$router->group(['prefix' => 'orders'], function () use ($router) {
$router->group(['prefix' =>'orders', 'middleware'=>'jwt.verify'], function () use ($router) {
    $router->get('/', 'OrderController@index');
    $router->post('/', 'OrderController@store');
    $router->get('/{id}', 'OrderController@show');
    $router->put('/{id}', 'OrderController@update');
    $router->delete('/{id}', 'OrderController@destroy');
});


