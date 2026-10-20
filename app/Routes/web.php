<?php

use App\Controllers\Web\WelcomeController;
use App\Controllers\Web\AuthController;


use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\InvoicePlatformAdminController;
use App\Controllers\Api\InvoiceApiController;
use App\Controllers\Web\InvoicePortalController;
use App\Controllers\Web\InvoiceCustomerController;

$router = \App\Core\Application::getInstance()->getRouter();

/*
|--------------------------------------------------------------------------
| Public Web Routes
|--------------------------------------------------------------------------
*/
$router->get('/', [InvoicePortalController::class, 'landing']);
$router->get('/about-us', [WelcomeController::class, 'about']);
$router->get('/services', [WelcomeController::class, 'service']);
$router->get('/blogs', [WelcomeController::class, 'blog']);
$router->get('/blog/{slugs}', [WelcomeController::class, 'singleblog']);
$router->get('/contact-us', [WelcomeController::class, 'contact']);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

$router->get('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

$router->get('/exchange-history', [WelcomeController::class, 'exchangeHistory']);


// Authenticated user routes
$router->group('/user', function ($router) {
    // $router->get('/dashboard', [UserController::class, 'dashboard']);

}, [App\Middleware\WebMiddleware::class]); // Apply auth middleware to all routes in group

$router->group('/admin', function ($router) {
    // Guest
    $router->get('/login', [AdminAuthController::class, 'login']);
    $router->post('/login', [AdminAuthController::class, 'authenticate']);
});


/* NairaBill public invoice pages and API endpoints */
$router->get('/invoice-platform', [InvoicePortalController::class, 'landing']);

/* NairaBill customer workspace: account, invoice management and API keys */
$router->get('/customer/register', [InvoiceCustomerController::class, 'register']);
$router->post('/customer/register', [InvoiceCustomerController::class, 'registerSubmit']);
$router->get('/customer/login', [InvoiceCustomerController::class, 'login']);
$router->post('/customer/login', [InvoiceCustomerController::class, 'loginSubmit']);
$router->get('/customer/magic-link', [InvoiceCustomerController::class, 'showMagicLink']);
$router->post('/customer/magic-link', [InvoiceCustomerController::class, 'consumeMagicLink']);
$router->get('/customer/logout', [InvoiceCustomerController::class, 'logout']);
$router->get('/customer/dashboard', [InvoiceCustomerController::class, 'dashboard']);
$router->get('/customer/invoices', [InvoiceCustomerController::class, 'invoices']);
$router->post('/customer/invoices', [InvoiceCustomerController::class, 'createInvoice']);
$router->post('/customer/api-keys', [InvoiceCustomerController::class, 'createApiKey']);
$router->post('/customer/api-keys/{id}/revoke', [InvoiceCustomerController::class, 'revokeApiKey']);
$router->get('/pay/{public_id}', [InvoicePortalController::class, 'pay']);
$router->post('/api/v1/billing/invoices', [InvoiceApiController::class, 'create']);
$router->get('/api/v1/billing/invoices', [InvoiceApiController::class, 'index']);
$router->get('/api/v1/billing/invoices/{public_id}', [InvoiceApiController::class, 'show']);
$router->post('/api/v1/billing/invoices/{public_id}/cancel', [InvoiceApiController::class, 'cancel']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
$router->group('/admin', function ($router) {
    $router->get('/platform', [InvoicePlatformAdminController::class, 'dashboard']);
    $router->get('/platform/workspaces', [InvoicePlatformAdminController::class, 'workspaces']);
    $router->get('/platform/invoices', [InvoicePlatformAdminController::class, 'invoices']);
    $router->get('/platform/api-keys', [InvoicePlatformAdminController::class, 'apiKeys']);
    $router->get('/platform/webhooks', [InvoicePlatformAdminController::class, 'webhooks']);
    $router->get('/platform/events', [InvoicePlatformAdminController::class, 'events']);
    $router->post('/platform/workspaces/{id}/toggle', [InvoicePlatformAdminController::class, 'toggleWorkspace']);
    $router->post('/platform/api-keys/{id}/revoke', [InvoicePlatformAdminController::class, 'revokeKey']);
    $router->get('/logout', [AdminAuthController::class, 'logout']);
}, [App\Middleware\AdminAuthMiddleware::class]);
