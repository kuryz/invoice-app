<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class JsonMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $response->setHeader('Content-Type', 'application/json');
    }
}