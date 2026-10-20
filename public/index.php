<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('BASE_PATH', realpath(__DIR__ . '/../'));

use Dotenv\Dotenv;
use App\Core\Application;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set error reporting
if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$app = Application::getInstance();
$app->setConfig(require __DIR__ . '/../config/app.php');

// Determine route file based on request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = preg_replace('/^\/backend/', '', $requestUri);
$isApi = strpos($requestUri, '/api') === 0;
$isWebhook = strpos($requestUri, '/webhook') === 0;

if ($isApi) {
    // web.php owns the NairaBill invoice routes; load it for API requests too.
    require_once __DIR__ . '/../app/Routes/web.php';
    require_once __DIR__ . '/../app/Routes/api.php';
}elseif ($isWebhook) {
    require_once __DIR__ . '/../app/Routes/webhook.php';
} else {
    require_once __DIR__ . '/../app/Routes/web.php';
}


$app->run();