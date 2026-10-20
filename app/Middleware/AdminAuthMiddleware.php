<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AdminAuthMiddleware
{
    public function handle(Request $request, Response $response): void
    {
        $session = new \App\Core\Session();

        $adminId = $session->get('admin_id');
        // print_r($adminId);die;
        if (!$adminId) {
            $this->redirect('/admin/login');
            exit;
        }

        $admin = User::find($adminId);
        // print_r($admin);die;

        if (!$admin) {
            unset($_SESSION['admin_id']);

            $response->error('Authorization failed', 401);
            exit;
        }

        // Attach the authenticated admin to the request
        $request->setParams([
            'admin' => $admin
        ]);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}