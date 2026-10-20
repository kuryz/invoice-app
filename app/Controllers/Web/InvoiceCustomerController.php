<?php

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;
use PDO;

/** Customer-facing merchant portal. Sessions are isolated from the host framework's user sessions. */
class InvoiceCustomerController extends Controller
{
    private PDO $db;
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }
    private function token(): string
    {
        if (!$this->session->has('invoice_csrf')) $this->session->set('invoice_csrf', bin2hex(random_bytes(32)));
        return (string)$this->session->get('invoice_csrf');
    }
    private function csrf(array $d): void
    {
        if (!isset($d['_token']) || !hash_equals($this->token(), (string)$d['_token'])) {
            http_response_code(419);
            exit('Session expired. Please go back and try again.');
        }
    }
    private function customer(bool $redirect = true): ?array
    {
        $id = (int)$this->session->get('invoice_customer_id', 0);
        if (!$id) {
            if ($redirect) $this->redirect('/customer/login');
            return null;
        }
        $s = $this->db->prepare("SELECT u.*,w.name AS workspace_name,w.slug AS workspace_slug,w.default_currency,w.status AS workspace_status FROM invoice_workspace_users u JOIN invoice_workspaces w ON w.id=u.workspace_id WHERE u.id=? LIMIT 1");
        $s->execute([$id]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if (!$u) {
            $this->session->remove('invoice_customer_id');
            if ($redirect) $this->redirect('/customer/login');
            return null;
        }
        if ($u['workspace_status'] !== 'active') {
            http_response_code(403);
            exit('This workspace is suspended. Contact platform support.');
        }
        return $u;
    }
    public function register(): string
    {
        if ($this->session->has('invoice_customer_id')) $this->redirect('/customer/dashboard');
        return $this->render('invoice/customer-auth', ['mode' => 'register', 'token' => $this->token(), 'pageTitle' => 'Create your NairaBill account']);
    }
    public function registerSubmit(Request $request): void
    {
        $d = $request->getBody();
        $this->csrf($d);
        $name = trim((string)($d['name'] ?? ''));
        $business = trim((string)($d['business_name'] ?? ''));
        $email = strtolower(trim((string)($d['email'] ?? '')));
        if (mb_strlen($name) < 2 || mb_strlen($business) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Enter your name, business name and a valid email address.');
            $this->redirect('/customer/register');
        }
        $q = $this->db->prepare('SELECT id FROM invoice_workspace_users WHERE email=? LIMIT 1');
        $q->execute([$email]);
        if ($q->fetch()) {
            $this->session->setFlash('error', 'An account with this email already exists. Sign in using an emailed magic link.');
            $this->redirect('/customer/login');
        }
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($business));
        $slug = trim($slug, '-') ?: 'business';
        $slug = substr($slug, 0, 140) . '-' . bin2hex(random_bytes(3));
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('INSERT INTO invoice_workspaces (name,slug,support_email,default_currency,status,created_at,updated_at) VALUES (?,?,?,"NGN","active",NOW(),NOW())');
            $s->execute([$business, $slug, $email]);
            $wid = (int)$this->db->lastInsertId();
            // Password login is disabled; keep a random hash for compatibility with the existing schema.
            $s = $this->db->prepare('INSERT INTO invoice_workspace_users (workspace_id,name,email,password_hash,role,status,created_at,updated_at) VALUES (?,?,?, ?,"owner","active",NOW(),NOW())');
            $s->execute([$wid, $name, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
            $uid = (int)$this->db->lastInsertId();
            $this->db->commit();
            if (!$this->sendMagicLink($uid, $email, $name)) {
                $this->session->setFlash('error', 'Your account was created, but the email could not be sent. Please contact support or check the mail server configuration.');
                $this->redirect('/customer/login');
            }
            $this->session->setFlash('success', 'Your workspace is ready. Check your email for a secure sign-in link.');
            $this->redirect('/customer/login');
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function login(): string
    {
        if ($this->session->has('invoice_customer_id')) $this->redirect('/customer/dashboard');
        return $this->render('invoice/customer-auth', ['mode' => 'login', 'token' => $this->token(), 'pageTitle' => 'Sign in to NairaBill']);
    }
    public function loginSubmit(Request $request): void
    {
        $d = $request->getBody();
        $this->csrf($d);
        $email = strtolower(trim((string)($d['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Enter a valid email address.');
            $this->redirect('/customer/login');
        }
        $s = $this->db->prepare('SELECT id,name,email,status FROM invoice_workspace_users WHERE email=? LIMIT 1');
        $s->execute([$email]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        // Do not reveal whether an email is registered.
        if ($u && $u['status'] === 'active') $this->sendMagicLink((int)$u['id'], $u['email'], $u['name']);
        $this->session->setFlash('success', 'If an active account exists for that email, a sign-in link is on its way. Check your inbox and spam folder.');
        $this->redirect('/customer/login');
    }
    private function sendMagicLink(int $userId, string $email, string $name): bool
    {
        $rate = $this->db->prepare('SELECT COUNT(*) FROM invoice_magic_links WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $rate->execute([$userId]);
        if ((int)$rate->fetchColumn() >= 5) return false;
        $this->db->prepare('UPDATE invoice_magic_links SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$userId]);
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expires = date('Y-m-d H:i:s', time() + 900);
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $s = $this->db->prepare('INSERT INTO invoice_magic_links (user_id,token_hash,expires_at,requested_ip,created_at) VALUES (?,?,?,?,NOW())');
        $s->execute([$userId, $hash, $expires, $ip]);
        $url = rtrim((string)($_ENV['APP_URL'] ?? ''), '/') . '/customer/magic-link?token=' . rawurlencode($raw);
        return \App\Services\InvoiceMagicLinkMailer::send($email, $name, $url);
    }
    public function showMagicLink(Request $request): string
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->session->setFlash('error', 'This sign-in link is invalid or has expired. Request a new one.');
            $this->redirect('/customer/login');
        }
        $s = $this->db->prepare("SELECT ml.id FROM invoice_magic_links ml JOIN invoice_workspace_users u ON u.id=ml.user_id WHERE ml.token_hash=? AND ml.used_at IS NULL AND ml.expires_at>NOW() AND u.status='active' LIMIT 1");
        $s->execute([hash('sha256', $token)]);
        if (!$s->fetch()) {
            $this->session->setFlash('error', 'This sign-in link is invalid or has expired. Request a new one.');
            $this->redirect('/customer/login');
        }
        return $this->render('invoice/magic-link-confirm', ['magicToken' => $token, 'token' => $this->token(), 'pageTitle' => 'Confirm secure sign-in']);
    }
    public function consumeMagicLink(Request $request): void
    {
        $d = $request->getBody();
        $this->csrf($d);
        $token = trim((string)($d['magic_token'] ?? ''));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->session->setFlash('error', 'This sign-in link is invalid or has expired. Request a new one.');
            $this->redirect('/customer/login');
        }
        $hash = hash('sha256', $token);
        $s = $this->db->prepare('SELECT ml.id AS magic_id,ml.user_id,u.status FROM invoice_magic_links ml JOIN invoice_workspace_users u ON u.id=ml.user_id WHERE ml.token_hash=? AND ml.used_at IS NULL AND ml.expires_at>NOW() LIMIT 1');
        $s->execute([$hash]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'active') {
            $this->session->setFlash('error', 'This sign-in link is invalid or has expired. Request a new one.');
            $this->redirect('/customer/login');
        }
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT id FROM invoice_magic_links WHERE id=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE');
            $lock->execute([(int)$row['magic_id']]);
            if (!$lock->fetch()) throw new \RuntimeException('Magic link already used');
            $this->db->prepare('UPDATE invoice_magic_links SET used_at=NOW() WHERE id=?')->execute([(int)$row['magic_id']]);
            $this->db->prepare('UPDATE invoice_workspace_users SET last_login_at=NOW() WHERE id=?')->execute([(int)$row['user_id']]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->session->setFlash('error', 'This sign-in link has already been used. Request a new one.');
            $this->redirect('/customer/login');
        }
        session_regenerate_id(true);
        $this->session->set('invoice_customer_id', (int)$row['user_id']);
        $this->session->set('invoice_csrf', bin2hex(random_bytes(32)));
        $this->redirect('/customer/dashboard');
    }
    public function logout(): void
    {
        $this->session->remove('invoice_customer_id');
        $this->session->remove('invoice_csrf');
        session_regenerate_id(true);
        $this->redirect('/customer/login');
    }
    public function dashboard(): string
    {
        $u = $this->customer();
        $wid = (int)$u['workspace_id'];
        $s = $this->db->prepare("SELECT COUNT(*) total, SUM(status='paid') paid_count, SUM(status='unpaid') unpaid_count, COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END),0) paid_volume FROM invoices WHERE workspace_id=?");
        $s->execute([$wid]);
        $stats = $s->fetch(PDO::FETCH_ASSOC);
        $s = $this->db->prepare('SELECT public_id,invoice_number,customer_name,currency,total_amount,amount_due,fee_payer,status,hosted_url,created_at,due_date FROM invoices WHERE workspace_id=? ORDER BY id DESC LIMIT 10');
        $s->execute([$wid]);
        $invoices = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $this->db->prepare('SELECT id,label,mode,key_prefix,last_used_at,created_at,revoked_at FROM invoice_api_keys WHERE workspace_id=? ORDER BY id DESC');
        $s->execute([$wid]);
        $keys = $s->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('invoice/customer-dashboard', ['customer' => $u, 'stats' => $stats, 'invoices' => $invoices, 'keys' => $keys, 'token' => $this->token(), 'pageTitle' => 'Business dashboard']);
    }
    public function invoices(): string
    {
        $u = $this->customer();
        $s = $this->db->prepare('SELECT public_id,invoice_number,customer_name,customer_email,currency,total_amount,amount_due,fee_payer,status,hosted_url,created_at,due_date FROM invoices WHERE workspace_id=? ORDER BY id DESC LIMIT 200');
        $s->execute([$u['workspace_id']]);
        return $this->render('invoice/customer-invoices', ['customer' => $u, 'invoices' => $s->fetchAll(PDO::FETCH_ASSOC), 'token' => $this->token(), 'pageTitle' => 'Invoices']);
    }
    public function createInvoice(Request $request): void
    {
        $u = $this->customer();
        $d = $request->getBody();
        $this->csrf($d);
        $name = trim((string)($d['customer_name'] ?? ''));
        $email = trim((string)($d['customer_email'] ?? ''));
        $desc = trim((string)($d['description'] ?? ''));
        $qty = (float)($d['quantity'] ?? 1);
        $price = (float)($d['unit_price'] ?? 0);
        $currency = strtoupper((string)($d['currency'] ?? $u['default_currency']));
        $payer = (string)($d['fee_payer'] ?? 'customer');
        $taxRate = (float)($d['tax_rate'] ?? 0);
        $discount = (float)($d['discount'] ?? 0);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $desc === '' || $qty <= 0 || $price < 0 || !preg_match('/^[A-Z]{3}$/', $currency) || !in_array($payer, ['customer', 'merchant', 'split'], true) || $taxRate < 0 || $taxRate > 100 || $discount < 0) {
            $this->session->setFlash('error', 'Please check the invoice fields and try again.');
            $this->redirect('/customer/invoices');
        }
        $subtotal = round($qty * $price, 2);
        $tax = round($subtotal * $taxRate / 100, 2);
        $discount = min($subtotal + $tax, $discount);
        $total = round($subtotal + $tax - $discount, 2);
        $fee = round($total * ((float)($_ENV['INVOICE_PLATFORM_FEE_PERCENT'] ?? 1.5)) / 100 + (float)($_ENV['INVOICE_PLATFORM_FEE_FIXED'] ?? 0), 2);
        $customerFee = $payer === 'customer' ? $fee : ($payer === 'split' ? round($fee / 2, 2) : 0);
        $merchantFee = round($fee - $customerFee, 2);
        $due = $d['due_date'] ?? '';
        if ($due !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
            $this->session->setFlash('error', 'Due date is invalid.');
            $this->redirect('/customer/invoices');
        }
        $number = 'INV-' . strtoupper(bin2hex(random_bytes(4)));
        $public = bin2hex(random_bytes(16));
        $base = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
        $hosted = $base . '/pay/' . $public;
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('INSERT INTO invoices (workspace_id,public_id,invoice_number,customer_name,customer_email,currency,subtotal,tax_rate,tax_amount,discount_amount,total_amount,amount_due,platform_fee,fee_payer,customer_fee,merchant_fee,status,due_date,description,hosted_url,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $s->execute([$u['workspace_id'], $public, $number, $name, $email, $currency, $subtotal, $taxRate, $tax, $discount, $total, round($total + $customerFee, 2), $fee, $payer, $customerFee, $merchantFee, 'unpaid', $due ?: null, $desc, $hosted]);
            $iid = (int)$this->db->lastInsertId();
            $s = $this->db->prepare('INSERT INTO invoice_items (invoice_id,description,quantity,unit_price,line_total,created_at) VALUES (?,?,?,?,?,NOW())');
            $s->execute([$iid, $desc, $qty, $price, $subtotal]);
            $s = $this->db->prepare('INSERT INTO invoice_events (invoice_id,event_type,payload_json,created_at) VALUES (?,"invoice.created",?,NOW())');
            $s->execute([$iid, json_encode(['source' => 'dashboard', 'fee_payer' => $payer, 'platform_fee' => $fee])]);
            $this->db->commit();
            $this->session->setFlash('success', 'Invoice created. Share the hosted invoice link with your customer.');
            $this->redirect('/customer/invoices');
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function createApiKey(Request $request): void
    {
        $u = $this->customer();
        $d = $request->getBody();
        $this->csrf($d);
        $label = trim((string)($d['label'] ?? 'API key'));
        $mode = ($d['mode'] ?? 'test') === 'live' ? 'live' : 'test';
        $secret = 'nb_' . $mode . '_' . bin2hex(random_bytes(20));
        $prefix = substr($secret, 0, 15);
        $s = $this->db->prepare('INSERT INTO invoice_api_keys (workspace_id,label,mode,key_prefix,key_hash,created_at) VALUES (?,?,?,?,?,NOW())');
        $s->execute([$u['workspace_id'], substr($label, 0, 100) ?: 'API key', $mode, $prefix, hash('sha256', $secret)]);
        $this->session->set('invoice_new_api_key', $secret);
        $this->redirect('/customer/dashboard#api-keys');
    }
    public function revokeApiKey(Request $request, string $id): void
    {
        $u = $this->customer();
        $d = $request->getBody();
        $this->csrf($d);
        $s = $this->db->prepare('UPDATE invoice_api_keys SET revoked_at=NOW() WHERE id=? AND workspace_id=? AND revoked_at IS NULL');
        $s->execute([(int)$id, $u['workspace_id']]);
        $this->session->setFlash('success', 'API key revoked.');
        $this->redirect('/customer/dashboard#api-keys');
    }
}
