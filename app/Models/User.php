<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'is_super_admin',
        'activated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    /**
     * Rôles autorisés à se connecter au panel d'administration web.
     * Liste blanche volontaire : un nouveau rôle mobile/employé ne doit pas
     * obtenir l'accès par défaut si on oublie de l'exclure explicitement.
     */
    protected const PANEL_ROLES = [
        'super_admin',
        'admin',
        'drh',
        'daaf',
        'dg',
        'chef_service',
        'chef_service_nursing',
        'dat',
        'dmr_dmra',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(self::PANEL_ROLES);
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

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    // ========================================
    // RELATIONS
    // ========================================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
