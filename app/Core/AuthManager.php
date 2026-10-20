<?php
namespace App\Core;

class AuthManager
{
    private Session $session;
    public function __construct(private ?string $guard = null) 
    {
        $this->session = new Session();
    }

    public function user(): ?\App\Models\User
    {
        return match ($this->guard) {
            'api' => $this->apiUser(),
            default => $this->getUser(),
        };
    }

    private function apiUser(): ?\App\Models\User
    {
        $user = request()->param('user');

        if ($user instanceof \App\Models\User) {
            return $user;
        }

        if (is_array($user)) {
            return \App\Models\User::fromArray($user);
        }

        return null;
    }

    protected function getUser(): ?\App\Models\User
    {
        $userId = $this->session->get('user_id');
        return $userId ? \App\Models\User::find($userId) : null;
    }
}
