<?php

namespace App\Services;

use App\Models\User;

class AcademicService
{
    public const PRODI = 'S1 Informatika';
    public const FAKULTAS = 'Fakultas Teknik dan Ilmu Komputer';
    public const UNIVERSITAS = 'Universitas Pancasakti Tegal';
    public const KELAS = 'Informatika';
    public const ANGKATAN = '2024';
    public const ANGKATAN_LABEL = 'Angkatan 2024';
    public const ANGKATAN_FULL = 'S1 Informatika Angkatan 2024';
    public const DEFAULT_SEMESTER = 'Semester 1';
    public const DEFAULT_TAHUN_AJARAN = '2024/2025';
    public const TAHUN_AWAL = 2024;
    public const TAHUN_AKHIR = 2028;
    public const TAHUN_LIST = [2024, 2025, 2026, 2027, 2028];
    public const TAHUN_AJARAN_LIST = [
        '2024/2025',
        '2025/2026',
        '2026/2027',
        '2027/2028',
    ];

    public const SEMESTERS = [
        'Semester 1',
        'Semester 2',
        'Semester 3',
        'Semester 4',
        'Semester 5',
        'Semester 6',
        'Semester 7',
        'Semester 8',
    ];

    public const DEFAULT_DUES_RATE = 5000;

    /**
     * Dapatkan besaran tarif iuran kas mingguan per mahasiswa (Rp)
     */
    public static function getDuesRatePerWeek(): int
    {
        $val = \App\Models\Setting::get('dues_rate_per_week', self::DEFAULT_DUES_RATE);
        $intVal = (int) $val;
        return $intVal >= 500 ? $intVal : self::DEFAULT_DUES_RATE;
    }

    /**
     * Setel besaran tarif iuran kas mingguan per mahasiswa (Rp)
     * Hak akses: Bendahara Kelas / Super Admin
     */
    public static function setDuesRatePerWeek(int $amount): void
    {
        if ($amount >= 500) {
            \App\Models\Setting::set('dues_rate_per_week', $amount);
        }
    }

    /**
     * Dapatkan semester aktif kelas saat ini.
     * Tersinkronisasi dengan pengaturan Admin di Kelola Akun & Semester (Database).
     */
    public static function getActiveSemester(): string
    {
        try {
            $setting = \App\Models\Setting::get('active_semester');
            if ($setting && in_array($setting, self::SEMESTERS)) {
                return $setting;
            }
            $admin = User::where('role', 'admin')->first();
            if ($admin && !empty($admin->semester) && in_array($admin->semester, self::SEMESTERS)) {
                return $admin->semester;
            }
        } catch (\Throwable $e) {
            // fallback jika koneksi database belum siap
        }

        return self::DEFAULT_SEMESTER;
    }

    /**
     * Set semester aktif kelas pada database dan sesi.
     * Tersinkronisasi secara otomatis ke seluruh peran pengguna (Admin, Ketua, Bendahara, Mahasiswa).
     */
    public static function setActiveSemester(string $semester): void
    {
        if (in_array($semester, self::SEMESTERS)) {
            try {
                \App\Models\Setting::set('active_semester', $semester);

                // Update Admin sebagai sumber kebenaran primer
                User::where('role', 'admin')->update(['semester' => $semester]);

                // Sinkronkan seluruh anggota kelas berstatus Aktif agar data di semua role seragam
                User::where('status_akademik', 'Aktif')->update([
                    'semester' => $semester,
                    'tahun_ajaran' => self::getTahunAjaran($semester),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
            session(['selected_semester' => $semester]);
        }
    }

    /**
     * Konversi 'Semester X' menjadi angka integer (1 s/d 8)
     */
    public static function parseSemesterNumber(string $semester): int
    {
        preg_match('/\d+/', $semester, $matches);
        return isset($matches[0]) ? (int) $matches[0] : 1;
    }

    /**
     * Label Kelas Dinamis: Semester 1 -> 1A, Semester 2 -> 2A, ..., Semester 8 -> 8A
     */
    public static function getKelasLabel(?string $semester = null): string
    {
        $sem = $semester ?? self::getActiveSemester();
        $num = self::parseSemesterNumber($sem);
        return $num . 'A';
    }

    /**
     * Nama Lengkap Kelas Dinamis: Informatika 1A, Informatika 2A, dst.
     */
    public static function getNamaKelas(?string $semester = null): string
    {
        return 'Informatika ' . self::getKelasLabel($semester);
    }

    /**
     * Tahun Ajaran Sesuai Semester (Rentang 2024 s/d 2028 untuk Angkatan 2024)
     */
    public static function getTahunAjaran(?string $semester = null): string
    {
        $sem = $semester ?? self::getActiveSemester();
        $num = self::parseSemesterNumber($sem);
        if ($num <= 2) {
            return '2024/2025';
        } elseif ($num <= 4) {
            return '2025/2026';
        } elseif ($num <= 6) {
            return '2026/2027';
        } else {
            return '2027/2028';
        }
    }

    /**
     * Jangan diarsipkan semua agar admin dan bendahara bebas memilih semester sendiri
     */
    public static function isHistoricalArchive(string $selectedSemester, ?string $activeSemester = null): bool
    {
        return false;
    }

    /**
     * Dapatkan bulan nyata / real-time saat ini (1 s/d 12)
     */
    public static function getRealCurrentMonth(): int
    {
        return (int) now()->month;
    }

    /**
     * Dapatkan tahun nyata / real-time saat ini
     */
    public static function getRealCurrentYear(): int
    {
        return (int) now()->year;
    }

    /**
     * Dapatkan tanggal nyata / real-time hari ini (1 s/d 31)
     */
    public static function getRealCurrentDay(): int
    {
        return (int) now()->day;
    }

    /**
     * Dapatkan minggu aktif berjalan (1 s/d 4)
     * Rumus resmi perkuliahan: min(4, ceil(day / 7))
     */
    public static function getRealActiveWeek(): int
    {
        return min(4, (int) ceil(now()->day / 7));
    }

    /**
     * Dapatkan rentang tanggal untuk setiap minggu (M1 s/d M4) pada bulan dan tahun tertentu.
     * Standar pembagian kas 4 minggu perkuliahan:
     * - Minggu 1: Tanggal 1 s/d 7
     * - Minggu 2: Tanggal 8 s/d 14
     * - Minggu 3: Tanggal 15 s/d 21
     * - Minggu 4: Tanggal 22 s/d akhir bulan (28, 29, 30, atau 31)
     */
    public static function getWeekDateRanges(int $month, int $year): array
    {
        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
        return [
            1 => [
                'week' => 1,
                'label' => 'M1',
                'full_label' => 'Minggu 1',
                'start_day' => 1,
                'end_day' => 7,
                'date_range' => "1–7",
                'full_range' => "1–7 " . self::getNamaBulan($month),
            ],
            2 => [
                'week' => 2,
                'label' => 'M2',
                'full_label' => 'Minggu 2',
                'start_day' => 8,
                'end_day' => 14,
                'date_range' => "8–14",
                'full_range' => "8–14 " . self::getNamaBulan($month),
            ],
            3 => [
                'week' => 3,
                'label' => 'M3',
                'full_label' => 'Minggu 3',
                'start_day' => 15,
                'end_day' => 21,
                'date_range' => "15–21",
                'full_range' => "15–21 " . self::getNamaBulan($month),
            ],
            4 => [
                'week' => 4,
                'label' => 'M4',
                'full_label' => 'Minggu 4',
                'start_day' => 22,
                'end_day' => $daysInMonth,
                'date_range' => "22–{$daysInMonth}",
                'full_range' => "22–{$daysInMonth} " . self::getNamaBulan($month),
            ],
        ];
    }

    /**
     * Dapatkan nama bulan dalam bahasa Indonesia
     */
    public static function getNamaBulan(?int $month = null): string
    {
        $m = $month ?? self::getRealCurrentMonth();
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $bulanIndo[$m] ?? 'Bulan ' . $m;
    }
}
