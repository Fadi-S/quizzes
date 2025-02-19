<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'games' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin() : bool
    {
        return $this->role === "admin";
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if($panel->getId() === 'admin') {
            return $this->isAdmin();
        }
        return true;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if($tenant instanceof Game) {
            return $this->isAdmin() || in_array($tenant->id, $this->games);
        }

        return true;
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if($panel->getId() !== 'game') return [];

        if ($this->isAdmin()) return Game::all();

        return Game::whereIn("id", $this->games)->get();
    }
}
