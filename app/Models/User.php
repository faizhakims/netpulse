<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'is_active', 'last_login_at'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Backward-compat helper — still works, now also delegates to Spatie.
     */
    public function isAdmin(): bool { return $this->hasRole('admin'); }

    public function roleBadgeClass(): string
    {
        $r = $this->getRoleNames()->first() ?? $this->role;
        return match($r) {
            'admin'    => 'role-admin',
            'operator' => 'role-operator',
            default    => 'role-viewer',
        };
    }

    public function currentRoleName(): string
    {
        return $this->getRoleNames()->first() ?? $this->role ?? 'viewer';
    }
}
