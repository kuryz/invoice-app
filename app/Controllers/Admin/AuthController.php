<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class AuthController extends Controller
{
    public function login(Request $request): string
    {
        if ($this->session->has('admin_id')) $this->redirect('/admin/platform');
        return $this->render('admin/invoice-platform/login', ['pageTitle'=>'Admin sign in','error'=>null]);
    }

    public function authenticate(Request $request, Response $response): void
    {
        $data=$request->getBody(); $email=trim((string)($data['email']??'')); $password=(string)($data['password']??'');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') { $this->renderLoginError('Enter a valid admin email and password.'); return; }
        $db=Database::getInstance()->getConnection();
        $s=$db->prepare('SELECT user_id,email,password,groupp,status FROM users WHERE email=? LIMIT 1'); $s->execute([$email]); $user=$s->fetch(PDO::FETCH_ASSOC);
        // This framework's legacy users table uses groupp=1 for administrators.
        if (!$user || !password_verify($password,(string)$user['password']) || (int)$user['groupp']!==1 || (isset($user['status']) && (int)$user['status']!==1)) { $this->renderLoginError('Invalid credentials or account is not an active administrator.'); return; }
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        session_regenerate_id(true); $this->session->set('admin_id',(int)$user['user_id']);
        $this->redirect('/admin/platform');
    }

    private function renderLoginError(string $error): void
    {
        http_response_code(422); echo $this->render('admin/invoice-platform/login',['pageTitle'=>'Admin sign in','error'=>$error]);
    }

    public function logout(): void { $this->session->remove('admin_id'); $this->redirect('/admin/login'); }
}
