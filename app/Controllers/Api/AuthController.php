<?php
namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function login(Request $request, Response $response)
    {
        $data = $request->all();
        $user = User::query()->where('email', $data['email'])->first();
        if (!$user) {
            return $response->error('User not found', 404);
        }
        // $result = $this->authService->login($user->email, $data['password']);
        // return $response->success($result);
    }
}