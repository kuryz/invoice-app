<?php

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\Request;
use App\Models\User;
use App\Services\AuthService;
use App\Repositories\MlmRepository;

class AuthController extends Controller
{
    private AuthService $authService;
    

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        
    }

    /**
     * Show the login form
     */
    public function showLogin(): void
    {
        // If already logged in, redirect to dashboard
        if ($this->session->has('user_id')) {
            $this->redirect('/user/dashboard');
        }
        if (!$this->session->has('_token')) {
            $this->session->set('_token', bin2hex(random_bytes(32)));
        }
        $pageTitle = 'Login';
        
        $general = ['site_name' => 'GMCGlobal'];
        $old = []; // for old input
        // print_r($content->data_values['background_image']);
        // exit;
        echo $this->render('auth/login', compact('pageTitle', 'content', 'general'));
    }

    /**
     * Handle login POST request
     */
    public function login(Request $request): void
    {
        $data = $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Attempt login via AuthService
            $result = $this->authService->login($data['username'], $data['password']);
            
            // Store user ID in session
            $user = $result['user'];
            $this->session->set('user_id', $user->getAttribute('id'));
            $this->session->set('username', $user->getAttribute('username'));

            $this->session->setFlash('success', 'Welcome back, ' . $user->getAttribute('username') . '!');
            $this->redirect('/user/dashboard');
        } catch (\Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            $this->redirect('/login');
        }
    }

    // In app/Controllers/Web/AuthController.php

    public function showRegister(Request $request): string
    {
        if ($this->session->has('user_id')) {
            $this->redirect('/user/dashboard');
        }
        // Generate CSRF token
        if (!$this->session->has('_token')) {
            $this->session->set('_token', bin2hex(random_bytes(32)));
        }

        $pageTitle = 'Register';
        $general = gs();
        $refUser = null;
        $pos = 1;
        $mobileCode = null;
        $old = [];
        // $policyPages = getContent('policy_pages.element', false, null, true);
        $policyPages = [];

        // Check if referral is provided via query string
        $ref = $request->query('ref');
        if ($ref) {
            $userModel = new \App\Models\User();
            $refUser = $userModel->where('username', $ref)->first();
            if ($refUser) {
                $pos = 1; // default position
            }
        }

        return $this->render('auth/register', compact(
            'pageTitle', 'content', 'general', 'refUser', 'pos', 'mobileCode', 'policyPages'
        ));
    }

    public function register(Request $request): void
    {
        // Validate CSRF
        $data = $request->getBody();
        if (!isset($data['_token']) || $data['_token'] !== $this->session->get('_token')) {
            $this->session->setFlash('error', 'Invalid CSRF token.');
            $this->redirect('/register');
            return;
        }

        $validated = $this->validate($request, [
            'username' => 'required|string|min:3|max:40',
            'email' => 'required|email|max:40',
            'mobile' => 'required|string|max:40',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password',
            'referral' => 'nullable|string',
            'position' => 'required|integer|in:1,2',
            'agree' => 'required|accepted',
        ]);

        // Check if username or email exists
        $userModel = new \App\Models\User();
        $existingUser = $userModel->where('username', $validated['username'])->first();
        if ($existingUser) {
            $this->session->setFlash('error', 'Username already exists.');
            $this->redirect('/register');
            return;
        }
        $existingEmail = $userModel->where('email', $validated['email'])->first();
        if ($existingEmail) {
            $this->session->setFlash('error', 'Email already exists.');
            $this->redirect('/register');
            return;
        }

        // Determine referrer
        $refUserId = null;
        if (!empty($validated['referral'])) {
            $referrer = $userModel->where('username', $validated['referral'])->first();
            if ($referrer) {
                $refUserId = $referrer['id'];
            }
        }

        // Create user
        $user = new \App\Models\User();
        $user->create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'password' => password_hash($validated['password'], PASSWORD_DEFAULT),
            'ref_by' => $refUserId,
            'position' => $validated['position'],
            'pos_id' => $refUserId ?? 0, // placeholder, can be updated
            'status' => 1,
            'ev' => 0,
            'sv' => 0,
            'profile_complete' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Auto-login
        $this->session->set('user_id', $user->getAttribute('id'));
        $this->session->setFlash('success', 'Registration successful! Welcome.');
        $this->redirect('/user/dashboard');
    }

    public function checkUser(Request $request): void
    {
        $data = $request->getBody();
        $field = key($data);
        $value = $data[$field];
        $userModel = new \App\Models\User();
        $exists = $userModel->where($field, $value)->first();
        $this->json([
            'data' => $exists ? $value : false,
            'type' => $field,
        ]);
    }

    public function checkReferral(Request $request): void
    {
        $data = $request->getBody();
        $refId = $data['ref_id'] ?? 'rootuser';
        $userModel = new \App\Models\User();
        $user = $userModel->where('username', $refId)->first();
        
        if ($user) {
            
            // print_r($user);
            $this->json([
                'success' => true,
                'position' => [
                    1 => $user->username,
                    2 => $user->username,
                ],
            ]);
        } else {
            $this->json(['success' => false]);
        }
    }

    /**
     * Logout and destroy session
     */
    public function logout(): void
    {
        $this->session->destroy();
        $this->session->setFlash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}