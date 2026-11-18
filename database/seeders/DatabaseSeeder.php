<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (Tegar Satria) - Satu-satunya akun bawaan sistem fresh launching
        User::updateOrCreate(
            ['npm' => '6624600021'],
            [
                'name'            => 'Tegar Satria',
                'email'           => 'tegarsatria@ups.ac.id',
                'password'        => Hash::make('210606'),
                'role'            => 'admin',
                'semester'        => 'Semester 1',
                'status_akademik' => 'Aktif',
                'tahun_ajaran'    => '2024/2025',
            ]
        );
    }
}
