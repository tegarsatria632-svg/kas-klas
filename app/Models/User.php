<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'npm',
        'email',
        'password',
        'role',
        'semester',
        'status_akademik',
        'tahun_ajaran',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function isOnline(): bool
    {
        if (auth()->check() && $this->id === auth()->id()) {
            return true;
        }

        return $this->last_seen_at && $this->last_seen_at->gt(now()->subSeconds(10));
    }

    public function kasTransactions()
    {
        return $this->hasMany(KasTransaction::class);
    }

    public function duesPayments()
    {
        return $this->hasMany(DuesPayment::class);
    }
}
