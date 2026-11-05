<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isCoach(): bool
    {
        return $this->role === UserRole::Coach;
    }

    /** Peut valider ou imposer un programme (coach ou gérant). */
    public function canReviewPrograms(): bool
    {
        return $this->isManager() || $this->isCoach();
    }
}
