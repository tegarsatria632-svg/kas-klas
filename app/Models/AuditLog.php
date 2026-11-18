<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'user_npm',
        'action',
        'description',
        'source',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas jejak audit secara otomatis
     *
     * @param string $action Nama aksi (contoh: catat_transaksi, edit_transaksi, ubah_role, dll)
     * @param string $description Keterangan aktivitas dalam Bahasa Indonesia
     * @param User|null $user Pengguna yang melakukan aksi
     * @param string $source Jalur aksi (chatbot / web_form / system)
     * @param array $details Rincian atribut perubahan (nominal, id, dll)
     * @return static
     */
    public static function record($action, $description, $user = null, $source = 'web_form', array $details = [])
    {
        $actor = $user ?: auth()->user();
        $request = request();

        return self::create([
            'user_id' => $actor ? $actor->id : null,
            'user_name' => $actor ? $actor->name : 'Sistem',
            'user_role' => $actor ? $actor->role : 'system',
            'user_npm' => $actor ? $actor->npm : null,
            'action' => $action,
            'description' => $description,
            'source' => $source,
            'details' => !empty($details) ? $details : null,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr($request->userAgent() ?? '', 0, 255) : null,
        ]);
    }
}
