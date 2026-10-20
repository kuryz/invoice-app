<?php

namespace App\Services;

use App\Models\User;
use App\Models\Country;
use App\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use App\Services\WalletService;

class AuthService
{
    private string $jwtSecret;
    private int $jwtExpiry;
    private WalletService $walletService;

    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        $this->jwtExpiry = (int) ($_ENV['JWT_EXPIRY'] ?? 3600);
        $this->walletService = new WalletService;
    }

    public function login(string $email, string $password): array
    {
        $userData = User::where('email', $email)->with('wallets')->first();
        if (!$userData) {
            throw new \RuntimeException('Invalid email credentials', 401);
        }
        
        
        if (!$userData->verifyPassword($password)) {
            throw new \RuntimeException('Invalid password credentials', 401);
        }
        if ($userData->status != 'active') {
            throw new \RuntimeException('Account is inactive', 403);
        }
        $token = $this->generateToken($userData);
        
        return [
            'token' => $token,
            'user' => $userData,
        ];
    }

    public function register(array $data): User
    {
        return Database::transaction(
            function () use ($data) {
            $userData = User::where('email', $data['email'])->first();
            
            if ($userData) {
                throw new \RuntimeException('Email already exists', 409);
            }
            if (!preg_match("/^[\p{L}\s'-]+$/u", $data['name'])) {
                throw new \RuntimeException('Name contains invalid characters', 422);
            }
            
            $user = new User();
            $user->fill([
                'name' => strtolower($data['name']),
                'email' => strtolower($data['email']),
                'phone' => $data['mobile'],
                'status' => 'active',
            ]);
            $user->setPassword($data['password']);
            $user->save();

            /*
             * Create wallets for all active countries.
             */
            $currencies = Country::where('status', 'active')->whereNotNull('currency_code')->pluck('currency_code');

            /*
             * Create the user's default wallet.
             */
            foreach ($currencies as $currency) {
                $this->walletService->create($user->id, $currency);
            }
            
            // return $user;
            return User::query()->with('wallets')->where('id', $user->id)->first();
        });
    }

    public function validateToken(string $token): ?User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            $user = User::find($decoded->user_id ?? 0);
            if (!$user) {
                throw new \RuntimeException('User not found');
            }
            
            return $user;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid token: ' . $e->getMessage(), 401);
        }
    }

    public function logout(string $token): void
    {
        // For stateless JWT, logout is handled client-side
        // Optionally, you could blacklist the token here
    }
    
    public function generatedToken(User $user): string
    {
        return $this->generateToken($user);
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getAttribute('id'),
            'username' => $user->getAttribute('username'),
            'email' => $user->getAttribute('email'),
            'iat' => time(),
            'exp' => time() + $this->jwtExpiry,
        ];
        
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    // for admin
    public function loginUsingId(int $id): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }
        session()->set('user_id', $user->id);
        session()->set('username', $user->username);
        return true;
    }


    public function user(): ?User
    {
        $id = session()->get('user_id') ?? null;
        if (!$id) {
            return null;
        }
        return User::find($id);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function logoutSession(): void
    {
        session()->remove('user_id');
    }
}