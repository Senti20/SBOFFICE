<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function requestedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'requested_by');
    }

    public function processedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'processed_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
