<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function login(Request $request)
    {
        if (!$request->filled('npm') && $request->filled('username')) {
            $request->merge(['npm' => $request->input('username')]);
        }

        // Validasi input awal: Memastikan kedua field tidak kosong
        $validator = Validator::make($request->all(), [
            'npm' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'npm.required' => 'Nomor Pokok Mahasiswa (NPM) atau Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('npm'));
        }

        $inputNpm = trim($request->input('npm'));
        $password = (string) $request->input('password');

        // Throttle Key berdasarkan kombinasi identitas (NPM/Username) dan IP Address
        $throttleKey = 'login:' . Str::lower($inputNpm) . '|' . $request->ip();

        // 0. Cek apakah sudah terkena blokir sementara (3x gagal atau lebih)
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            AuditLog::record(
                'login_diblokir',
                "Percobaan login ditolak karena identitas '{$inputNpm}' sedang diblokir sementara ({$seconds} detik tersisa)",
                null,
                'web_form',
                ['npm' => $inputNpm, 'ip' => $request->ip(), 'seconds_remaining' => $seconds]
            );

            return back()->withErrors([
                'npm' => "Akses masuk diblokir sementara karena telah 3x gagal login. Silakan tunggu {$seconds} detik (" . ($minutes > 1 ? "sekitar {$minutes} menit" : "kurang dari 1 menit") . ") sebelum mencoba kembali.",
            ])->withInput($request->only('npm'));
        }

        // Cari pengguna berdasarkan NPM, Email, atau Nama Lengkap
        $user = User::where('npm', $inputNpm)
            ->orWhere('email', $inputNpm)
            ->orWhere('name', $inputNpm)
            ->first();

        // 1. Validasi: Username / NPM tidak ditemukan
        if (!$user) {
            RateLimiter::hit($throttleKey, 300); // Kunci 5 menit (300 detik) jika tembus batas 3x
            $attempts = RateLimiter::attempts($throttleKey);
            $remaining = max(0, 3 - $attempts);

            if ($remaining === 0) {
                $seconds = RateLimiter::availableIn($throttleKey);
                AuditLog::record(
                    'login_diblokir',
                    "Akses masuk diblokir 5 menit untuk identitas '{$inputNpm}' setelah 3x gagal login",
                    null,
                    'web_form',
                    ['npm' => $inputNpm, 'ip' => $request->ip(), 'lockout_seconds' => $seconds]
                );

                return back()->withErrors([
                    'npm' => "Anda telah 3x gagal login. Akses login Anda diblokir sementara selama 5 menit ({$seconds} detik).",
                ])->withInput($request->only('npm'));
            }

            return back()->withErrors([
                'npm' => "Username atau NPM '{$inputNpm}' tidak ditemukan atau belum terdaftar. (Peringatan: Sisa kesempatan mencoba {$remaining}x lagi sebelum akun diblokir sementara).",
            ])->withInput($request->only('npm'));
        }

        // 2. Validasi: Cek kecocokan kata sandi
        $passwordMatches = Hash::check($password, $user->password);
        if (!$passwordMatches && $user->password === $password) {
            // Self-healing jika password di database pernah diinput dalam format teks biasa (plain-text):
            // Otomatis upgrade ke bcrypt hash
            $user->update(['password' => Hash::make($password)]);
            $passwordMatches = true;
        }

        if (!$passwordMatches) {
            RateLimiter::hit($throttleKey, 300); // Kunci 5 menit (300 detik) jika tembus batas 3x
            $attempts = RateLimiter::attempts($throttleKey);
            $remaining = max(0, 3 - $attempts);

            if ($remaining === 0) {
                $seconds = RateLimiter::availableIn($throttleKey);
                AuditLog::record(
                    'login_diblokir',
                    "Akses masuk diblokir 5 menit untuk akun {$user->name} ({$user->npm}) setelah 3x gagal memasukkan kata sandi",
                    $user,
                    'web_form',
                    ['npm' => $user->npm, 'ip' => $request->ip(), 'lockout_seconds' => $seconds]
                );

                return back()->withErrors([
                    'password' => "Anda telah 3x gagal login. Akses login Anda diblokir sementara selama 5 menit ({$seconds} detik).",
                ])->withInput($request->only('npm'));
            }

            return back()->withErrors([
                'password' => "Kata sandi yang Anda masukkan salah. (Peringatan: Sisa kesempatan mencoba {$remaining}x lagi sebelum akun diblokir sementara).",
            ])->withInput($request->only('npm'));
        }

        // 3. Login berhasil: Bersihkan limiter percobaan gagal
        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        AuditLog::record(
            'login_berhasil',
            "Pengguna {$user->name} ({$user->npm}) berhasil masuk ke sistem",
            $user,
            'web_form',
            ['ip' => $request->ip()]
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('status', 'Anda telah berhasil keluar dari sistem.')
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
            ]);
    }

    /**
     * Pengaturan Ganti Password Mandiri oleh Pengguna
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Kata sandi saat ini (lama) wajib diisi.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'new_password.different' => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.',
        ]);

        $user = $request->user();

        $currentMatches = Hash::check($validated['current_password'], $user->password) || $user->password === $validated['current_password'];
        if (!$currentMatches) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
            ])->with('error_modal', 'change_password');
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui. Gunakan kata sandi baru untuk login berikutnya.');
    }
}
