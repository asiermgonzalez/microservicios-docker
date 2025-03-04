<?php

use Illuminate\Support\Facades\Route;

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    echo "<center> Microservicios con Lumen 10 </center>";
});

$router->get('/version', function () use ($router) {
    return $router->app->version();
});




//$router->group(['prefix' =>'auth', 'middleware'=> 'jwt.verify'], function () use ($router) {
$router->group(['prefix' =>'auth'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/register', 'AuthController@register');
    $router->post('/logout', 'AuthController@logout');
    $router->post('/refresh', 'AuthController@refresh');
    $router->get('/me', 'AuthController@me');
});