<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $token = $request->getBearerToken();
        
        if (!$token) {
            $response->error('Unauthorized - No token provided', 500);
        }
        
        try {
            $authService = new AuthService();
            $user = $authService->validateToken($token);
            
            if (!$user) {
                $response->error('Unauthorized - Invalid token', 500);
            }
            
            // Attach user to request
            $request->setParams(['user' => $user]);
        } catch (\Exception $e) {
            $response->error('Unauthorized - ' . $e->getMessage(), 500);
        }
    }
}