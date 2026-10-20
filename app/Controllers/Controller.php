<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;

abstract class Controller
{
    protected View $view;
    protected Session $session;

    public function __construct()
    {
        $this->view = new View();
        $this->session = new Session();
    }

    protected function render(string $view, array $data = []): string
    {
        // Always inject session into view data
        $data['session'] = $this->session;
        return $this->view->render($view, $data);
    }

    protected function validate(Request $request, array $rules): array
    {
        $data = $request->getBody();
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $ruleItem) {
                if ($ruleItem === 'required' && empty($value)) {
                    $errors[$field][] = "The $field field is required";
                }
                if (str_starts_with($ruleItem, 'min:') && ($value !== null && strlen($value) < (int) substr($ruleItem, 4))) {
                    $errors[$field][] = "The $field must be at least " . substr($ruleItem, 4) . " characters";
                }
                if (str_starts_with($ruleItem, 'max:') && ($value !== null && strlen($value) > (int) substr($ruleItem, 4))) {
                    $errors[$field][] = "The $field must not exceed " . substr($ruleItem, 4) . " characters";
                }
                if ($ruleItem === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "The $field must be a valid email address";
                }
                if ($ruleItem === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "The $field must be numeric";
                }
                if ($ruleItem === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $errors[$field][] = "The $field must be an integer";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException('Validation failed', 422, $errors);
        }
        
        return $data;
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    protected function redirectBack(string $fallback = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($referer) && parse_url($referer, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
            header('Location: ' . $referer);
        } else {
            header('Location: ' . $fallback);
        }
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function authMiddleware(): void
    {
        if (!$this->session->has('user_id')) {
            $this->redirect('/login');
        }
    }

    protected function getUser(): ?\App\Models\User
    {
        $userId = $this->session->get('user_id');
        return $userId ? \App\Models\User::find($userId) : null;
    }

    protected function getAuth(): ?\App\Models\User
    {
       return request()->param('user');
    }
}