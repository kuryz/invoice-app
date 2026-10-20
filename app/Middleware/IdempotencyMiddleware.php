<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\IdempotencyKey;

class IdempotencyMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $key = $request->getHeader('Idempotency-Key');

        if (!$key) {
            $response->error(
                'Idempotency-Key header is required',
                400
            );
            return;
        }

        // AuthMiddleware should have already attached the user
        $user = $request->param('user');

        if (!$user || empty($user->id)) {
            $response->error(
                'Unauthorized - User not found',
                401
            );
            return;
        }

        $method = strtoupper($request->getMethod());
        $path = $request->getPath();

        try {
            // Check if this request was already received
            $existing = IdempotencyKey::findValid(
                $key,
                (int) $user->id,
                $method,
                $path
            );

            if ($existing) {
                // The original request has already finished
                if ($existing->status === 'COMPLETED') {
                    $response->json(
                        json_decode($existing->response_body, true),
                        $existing->response_code
                    );
                    return;
                }

                // Another identical request is currently running
                if ($existing->status === 'PROCESSING') {
                    $response->error(
                        'This request is already being processed',
                        409
                    );
                    return;
                }

                // For FAILED requests, return the original failure
                if ($existing->status === 'FAILED') {
                    $response->json(
                        json_decode($existing->response_body, true),
                        $existing->response_code
                    );
                    return;
                }
            }

            // Reserve the key BEFORE the controller executes
            $idempotency = IdempotencyKey::reserve(
                $key,
                (int) $user->id,
                $method,
                $path,
                date('Y-m-d H:i:s', strtotime('+24 hours'))
            );

            // Attach the reservation for the controller to complete
            $request->setParams([
                'idempotency_key' => $idempotency
            ]);

        } catch (\Throwable $e) {
            $response->error(
                'Unable to process idempotency key: ' . $e->getMessage(),
                500
            );
        }
    }
}