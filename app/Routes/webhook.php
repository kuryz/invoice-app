<?php

use App\Core\Application;

use App\Controllers\Api\WebHookController;

$router = Application::getInstance()->getRouter();


$prefix = '/webhook';

/*
|--------------------------------------------------------------------------
| WebHooks
|--------------------------------------------------------------------------
*/

$router->post("{$prefix}/9psb/deposit-process", [WebHookController::class, 'ninePsbDepositWebhook']);
