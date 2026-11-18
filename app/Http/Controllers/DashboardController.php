<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\DuesPayment;
use App\Models\User;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        // 1. Data Akademik & Semester Aktif S1 Informatika UPS Tegal
        $activeClassSemester = AcademicService::getActiveSemester();

        // 2. Periode Berjalan & Algoritma Penentuan Minggu Aktif
        $today = now();
        $currentMonth = (int) $today->month;
        $currentYear = (int) $today->year;
        
        // Rumus: Minggu Aktif = min(4, ceil(Tanggal Hari Ini / 7))
        $activeWeek = min(4, (int) ceil($today->day / 7));

        // 3. Finansial Kas Real-Time Semester Aktif & Seluruh Waktu
        $totalPemasukanAll = KasTransaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranAll = KasTransaction::where('type', 'pengeluaran')->sum('amount');
        $totalSaldoAll = $totalPemasukanAll - $totalPengeluaranAll;

        $pemasukanSemesterAktif = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $pengeluaranSemesterAktif = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldoSemesterAktif = $pemasukanSemesterAktif - $pengeluaranSemesterAktif;

        $pemasukanBulanIni = KasTransaction::where('type', 'pemasukan')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        // 4. Deteksi Tunggakan Iuran Kas untuk Seluruh Role (All Roles: Admin, Bendahara, Ketua, Mahasiswa)
        $isDuesPending = false;
        $pendingWeeks = [];
        $paidWeeks = [];
        $personalDues = 0;

        if ($user) {
            $personalDues = KasTransaction::where('user_id', $user->id)
                ->where('semester', $activeClassSemester)
                ->where('type', 'pemasukan')
                ->sum('amount');

            $paidWeeks = DuesPayment::where('user_id', $user->id)
                ->where('semester', $activeClassSemester)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('status', 'lunas')
                ->pluck('week_number')
                ->map(function ($w) {
                    return (int) $w;
                })
                ->toArray();

            $expectedWeeks = range(1, 4); // Selalu 4 minggu per bulan sesuai menu kas
            $pendingWeeks = array_values(array_diff($expectedWeeks, $paidWeeks));
            $isDuesPending = !empty($pendingWeeks);
        }

        $duesRate = AcademicService::getDuesRatePerWeek();
        $tunggakanNominal = count($pendingWeeks) * $duesRate;
        $weekRanges = AcademicService::getWeekDateRanges($currentMonth, $currentYear);

        // 6. Khusus Pengurus (Admin, Bendahara, Ketua): Deteksi anggota kelas yang memiliki tunggakan (4 Minggu Penuh)
        $unpaidStudents = collect();
        if ($user && in_array($user->role, ['admin', 'bendahara', 'ketua'])) {
            $allMembers = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
                ->where('status_akademik', 'Aktif')
                ->where('semester', $activeClassSemester)
                ->with(['duesPayments' => function ($q) use ($activeClassSemester, $currentMonth, $currentYear) {
                    $q->where('semester', $activeClassSemester)
                      ->where('month', $currentMonth)
                      ->where('year', $currentYear)
                      ->where('status', 'lunas');
                }])->get();

            $unpaidStudents = $allMembers->filter(function ($member) {
                $paid = $member->duesPayments->pluck('week_number')->map(function ($w) {
                    return (int) $w;
                })->toArray();
                $expected = range(1, 4); // Selalu 4 minggu per bulan sesuai menu kas
                return count(array_diff($expected, $paid)) > 0;
            });
        }

        // 7. Khusus Super Admin & Ketua Kelas: Statistik Pengguna & Multi-Semester
        $userStats = [];
        if ($user && in_array($user->role, ['admin', 'ketua'])) {
            $userStats = [
                'total' => User::count(),
                'admin' => User::where('role', 'admin')->count(),
                'bendahara' => User::where('role', 'bendahara')->count(),
                'ketua' => User::where('role', 'ketua')->count(),
                'mahasiswa_aktif' => User::where('role', 'mahasiswa')->where('status_akademik', 'Aktif')->count(),
                'alumni' => User::where('status_akademik', 'Lulus / Alumni')->count(),
            ];
        }

        // 8. Daftar Mahasiswa Kelas & Status Kehadiran Real-Time (Online/Offline)
        $classStudents = User::where('semester', $activeClassSemester)
            ->where('status_akademik', 'Aktif')
            ->orderByRaw("FIELD(role, 'ketua', 'bendahara', 'admin', 'mahasiswa')")
            ->orderBy('name', 'asc')
            ->get();

        $onlineCount = $classStudents->filter(function ($s) {
            return $s->isOnline();
        })->count();

        $namaBulan = AcademicService::getNamaBulan($currentMonth);
        $recentTransactions = KasTransaction::with('user')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->latest('date')
            ->latest('id')
            ->take(6)
            ->get();
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);

        return view('dashboard', compact(
            'user',
            'activeClassSemester',
            'kelasLabel',
            'totalSaldoAll',
            'saldoSemesterAktif',
            'pemasukanSemesterAktif',
            'pengeluaranSemesterAktif',
            'pemasukanBulanIni',
            'activeWeek',
            'currentMonth',
            'currentYear',
            'namaBulan',
            'isDuesPending',
            'pendingWeeks',
            'personalDues',
            'unpaidStudents',
            'userStats',
            'recentTransactions',
            'duesRate',
            'tunggakanNominal',
            'weekRanges',
            'paidWeeks',
            'classStudents',
            'onlineCount'
        ));
    }
}
