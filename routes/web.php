<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->get('/info', function (Request $request) {
    if (app()->environment('local')) {
        phpinfo();
    }
    exit;

});
$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->post('user/register', 'UserController@register');
    $router->patch('user/verify-email', 'UserController@verifyEmail');
    $router->post('user/resend-otp', 'UserController@resendOtp');
    $router->post('user/login', 'UserController@login');
    $router->post('user/change-password', 'UserController@changePassword');
    $router->patch('user/change-password', 'UserController@changePassword');
});

$router->group(['prefix' => 'api/v1', 'middleware' => 'auth'], function () use ($router) {
    $router->get('user/profile', 'UserController@profile');
    $router->put('user/update-profile', 'UserController@updateProfile');

    $router->post('user-device/store', 'UserDeviceController@store');
});
