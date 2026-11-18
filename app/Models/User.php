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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function kasTransactions()
    {
        return $this->hasMany(KasTransaction::class);
    }

    public function duesPayments()
    {
        return $this->hasMany(DuesPayment::class);
    }
}
