<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'is_super_admin' => 'boolean',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Pour le moment, on autorise tous les utilisateurs
    }

    // Méthodes helper
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin || $this->hasRole('super_admin');
    }

    public function canManageAdmins(): bool
    {
        return $this->hasPermissionTo('manage_admins') || $this->isSuperAdmin();
    }
}
