<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuesPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'kas_transaction_id',
        'week_number',
        'month',
        'year',
        'semester',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(KasTransaction::class, 'kas_transaction_id');
    }
}
