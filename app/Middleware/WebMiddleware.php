<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class WebMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $session = new Session();
        if (!$session->has('user_id')) {
            // For API, return JSON error; for web, redirect to login
            if (strpos($request->getPath(), '/api') === 0) {
                $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
                exit;
            } else {
                $response->redirect('/login');
                exit;
            }
        }
    }
}