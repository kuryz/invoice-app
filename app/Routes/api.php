<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\HomeController;
use App\Core\Application;



use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

$router = Application::getInstance()->getRouter();

$prefix = '/api/v1';

$authMiddleware = [AuthMiddleware::class];
$corsAuthMiddleware =[CorsMiddleware::class, AuthMiddleware::class];
/*
|--------------------------------------------------------------------------
| Public Authentication
|--------------------------------------------------------------------------
*/
$router->post($prefix . '/login', [AuthController::class, 'postLogin']);
$router->post($prefix . '/register', [AuthController::class, 'postregister']);

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

$router->get($prefix . '/live-rate', [HomeController::class, 'btc_rate']);
$router->post($prefix . '/subscribe', [HomeController::class, 'subcribe']);
/*
|--------------------------------------------------------------------------
| Currency & Exchange Routes
|--------------------------------------------------------------------------
*/
$router->post($prefix . '/currency', [HomeController::class, 'postCurrency']);
$router->post($prefix . '/rate', [HomeController::class, 'postRate']);
$router->get($prefix . '/get-key/{from}/{to}', [HomeController::class, 'getRatekey']);
$router->post($prefix . '/exchange', [HomeController::class, 'postExchange']);

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

$router->post($prefix . '/logout', [AuthController::class, 'logout']);