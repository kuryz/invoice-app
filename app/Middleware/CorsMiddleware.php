<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        // Check if the origin is allowed
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            // Always echo back the exact origin when credentials are allowed
            // Do NOT use "*" together with credentials
            if ($origin) {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
            } else {
                // No Origin header (e.g., server-to-server) – you may omit this header
                // or set it to "*" but then credentials must be false
                // $response->setHeader('Access-Control-Allow-Origin', '*');
            }
        }
        // If origin is not allowed, do not set the header – the browser will block it

        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Idempotency-Key');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Vary', 'Origin');

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response->json([], 200);
        }
    }
}