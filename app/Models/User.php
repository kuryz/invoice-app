<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'user_id';

    protected array $fillable = [
        'fullname',
        'email',
        'usericon',
        'password',
        'groupp',
        'status',
    ];

    /**
     * Never leak the password hash through JSON / toArray().
     */
    protected array $hidden = [
        'password',
    ];

    protected array $casts = [
        'user_id'    => 'integer',
        'groupp'     => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    
    /**
     * Convenience helper — the base Model does not run mutators,
     * so hash before assigning the attribute.
     */
    public function setPassword(string $plain): self
    {
        $this->attributes['password'] = password_hash($plain, PASSWORD_BCRYPT);
        return $this;
    }

    public function verifyPassword(string $plain): bool
    {
        $hash = $this->attributes['password'] ?? null;
        return is_string($hash) && password_verify($plain, $hash);
    }
}