<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Dapatkan nilai pengaturan berdasarkan key
     */
    public static function get(string $key, $default = null)
    {
        // 1. Coba dari database jika tabel tersedia
        try {
            if (Schema::hasTable('settings')) {
                $setting = static::where('key', $key)->first();
                if ($setting !== null && $setting->value !== null) {
                    return $setting->value;
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // 2. Coba dari file storage fallback
        try {
            $path = storage_path('app/settings.json');
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                if (is_array($data) && isset($data[$key])) {
                    return $data[$key];
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $default;
    }

    /**
     * Simpan nilai pengaturan (tersimpan di database dan file backup)
     */
    public static function set(string $key, $value): void
    {
        $strValue = (string) $value;

        // 1. Simpan ke database jika tabel tersedia
        try {
            if (Schema::hasTable('settings')) {
                static::updateOrCreate(['key' => $key], ['value' => $strValue]);
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // 2. Simpan juga ke file fallback
        try {
            $dir = storage_path('app');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $path = $dir . '/settings.json';
            $data = [];
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true) ?: [];
            }
            $data[$key] = $strValue;
            @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            // fallback
        }
    }
}
