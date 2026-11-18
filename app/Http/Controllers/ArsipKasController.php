<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\DuesPayment;
use App\Models\User;
use App\Services\AcademicService;
use Illuminate\Http\Request;

class ArsipKasController extends Controller
{
    /**
     * Halaman Menu Khusus: Pusat Arsip & Rekapitulasi Pendataan Kas
     * Mode Arsip:
     * 1. Per Bulan (Pendataan Kas & Iuran Mingguan per Bulan)
     * 2. Per Semester (Akumulasi Kas & Iuran per Semester)
     * 3. Semua Periode (Rekapitulasi Komparasi Semester 1 s/d 8 & Seluruh Waktu)
     */
    public function index(Request $request)
    {
        $periodMode = $request->get('period_mode', 'semester');
        if (!in_array($periodMode, ['bulan', 'semester', 'semua'])) {
            $periodMode = 'semester';
        }

        $activeClassSemester = AcademicService::getActiveSemester();
        $selectedSemester = $request->get('semester', $activeClassSemester);
        if (!in_array($selectedSemester, AcademicService::SEMESTERS)) {
            $selectedSemester = $activeClassSemester;
        }

        $realCurrentMonth = AcademicService::getRealCurrentMonth();
        $realCurrentYear = AcademicService::getRealCurrentYear();
        $realCurrentDay = AcademicService::getRealCurrentDay();
        $realActiveWeek = AcademicService::getRealActiveWeek();

        $selectedMonth = (int) $request->get('month', $realCurrentMonth);
        $selectedYear = (int) $request->get('year', $realCurrentYear);
        $duesRate = AcademicService::getDuesRatePerWeek();
        $namaBulan = AcademicService::getNamaBulan($selectedMonth);
        $isCurrentRealMonth = ($selectedMonth === $realCurrentMonth && $selectedYear === $realCurrentYear);
        $weekRanges = AcademicService::getWeekDateRanges($selectedMonth, $selectedYear);

        // Filter Transaksi Kas Berdasarkan Mode
        $query = KasTransaction::with('user')->latest('date')->latest('id');

        if ($periodMode === 'bulan') {
            $query->where('semester', $selectedSemester)
                  ->whereMonth('date', $selectedMonth)
                  ->whereYear('date', $selectedYear);
        } elseif ($periodMode === 'semester') {
            $query->where('semester', $selectedSemester);
        } elseif ($periodMode === 'semua') {
            // Seluruh data historis dari semua semester (1 s/d 8)
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(15)->withQueryString();

        // 1. Finansial All-Time (Semua Periode 1 s/d 8)
        $totalPemasukanAll = KasTransaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranAll = KasTransaction::where('type', 'pengeluaran')->sum('amount');
        $totalSaldoAll = $totalPemasukanAll - $totalPengeluaranAll;

        // 2. Finansial Semester Terpilih
        $totalPemasukanSemester = KasTransaction::where('semester', $selectedSemester)->where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranSemester = KasTransaction::where('semester', $selectedSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldoSemester = $totalPemasukanSemester - $totalPengeluaranSemester;

        // 3. Finansial Bulan Terpilih
        $pemasukanBulan = KasTransaction::where('semester', $selectedSemester)
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->where('type', 'pemasukan')
            ->sum('amount');
        $pengeluaranBulan = KasTransaction::where('semester', $selectedSemester)
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->where('type', 'pengeluaran')
            ->sum('amount');
        $saldoBulan = $pemasukanBulan - $pengeluaranBulan;

        // Metrik Aktif Sesuai Mode
        if ($periodMode === 'bulan') {
            $periodLabel = "Bulan " . $namaBulan . " " . $selectedYear;
            $periodSubLabel = "Pencatatan bulan ini (diriset otomatis setiap ganti bulan)";
            $periodPemasukan = $pemasukanBulan;
            $periodPengeluaran = $pengeluaranBulan;
            $periodSaldo = $saldoBulan;
        } elseif ($periodMode === 'semester') {
            $periodLabel = $selectedSemester;
            $periodSubLabel = "Pencatatan semester ini (diriset otomatis setiap naik semester)";
            $periodPemasukan = $totalPemasukanSemester;
            $periodPengeluaran = $totalPengeluaranSemester;
            $periodSaldo = $saldoSemester;
        } else { // 'semua'
            $periodLabel = "Rekap Semua Periode (Semester 1 - 8)";
            $periodSubLabel = "Akumulasi permanen tanpa pernah diriset (menghitung terus sampai lulus)";
            $periodPemasukan = $totalPemasukanAll;
            $periodPengeluaran = $totalPengeluaranAll;
            $periodSaldo = $totalSaldoAll;
        }

        // Rekapitulasi Tambahan Spesifik Mode:
        $semestersRecap = [];
        $semesterMonthlyRecap = [];

        if ($periodMode === 'semua') {
            // Rekap komparasi lengkap seluruh Semester 1 s/d Semester 8
            foreach (AcademicService::SEMESTERS as $sem) {
                $sIn = KasTransaction::where('semester', $sem)->where('type', 'pemasukan')->sum('amount');
                $sOut = KasTransaction::where('semester', $sem)->where('type', 'pengeluaran')->sum('amount');
                $semestersRecap[] = [
                    'semester' => $sem,
                    'pemasukan' => $sIn,
                    'pengeluaran' => $sOut,
                    'saldo' => $sIn - $sOut,
                    'tx_count' => KasTransaction::where('semester', $sem)->count(),
                ];
            }
        } elseif ($periodMode === 'semester') {
            for ($m = 1; $m <= 12; $m++) {
                $smIn = KasTransaction::where('semester', $selectedSemester)->whereMonth('date', $m)->where('type', 'pemasukan')->sum('amount');
                $smOut = KasTransaction::where('semester', $selectedSemester)->whereMonth('date', $m)->where('type', 'pengeluaran')->sum('amount');
                $smCount = KasTransaction::where('semester', $selectedSemester)->whereMonth('date', $m)->count();
                if ($smCount > 0 || $smIn > 0 || $smOut > 0) {
                    $semesterMonthlyRecap[] = [
                        'month_number' => $m,
                        'month_name' => AcademicService::getNamaBulan($m),
                        'pemasukan' => $smIn,
                        'pengeluaran' => $smOut,
                        'selisih' => $smIn - $smOut,
                        'tx_count' => $smCount,
                    ];
                }
            }
        }

        // Data Mahasiswa & Rekap Iuran (Hanya diperlukan pada mode bulan & semua periode)
        $students = collect();
        $totalPaidStudentsInPeriod = 0;
        $totalUnpaidStudentsInPeriod = 0;

        if ($periodMode === 'bulan') {
            $students = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
                ->orderBy('npm', 'asc')
                ->with(['duesPayments' => function ($q) use ($selectedSemester, $selectedMonth, $selectedYear) {
                    $q->where('semester', $selectedSemester)
                      ->where('month', $selectedMonth)
                      ->where('year', $selectedYear);
                }])->get();

            $targetWeeks = range(1, 4); // Selalu 4 minggu per bulan sesuai menu kas
            foreach ($students as $st) {
                $paidCount = $st->duesPayments->count();
                if ($paidCount >= count($targetWeeks)) {
                    $totalPaidStudentsInPeriod++;
                } else {
                    $totalUnpaidStudentsInPeriod++;
                }
            }
        } elseif ($periodMode === 'semua') {
            $students = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
                ->orderBy('npm', 'asc')
                ->with('duesPayments')
                ->get();

            foreach ($students as $st) {
                if ($st->duesPayments->count() > 0) {
                    $totalPaidStudentsInPeriod++;
                } else {
                    $totalUnpaidStudentsInPeriod++;
                }
            }
        }

        $semesters = AcademicService::SEMESTERS;
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $selectedKelasLabel = AcademicService::getKelasLabel($selectedSemester);

        return view('arsip.index', compact(
            'periodMode',
            'periodLabel',
            'periodSubLabel',
            'periodPemasukan',
            'periodPengeluaran',
            'periodSaldo',
            'totalPemasukanAll',
            'totalPengeluaranAll',
            'totalSaldoAll',
            'totalPemasukanSemester',
            'totalPengeluaranSemester',
            'saldoSemester',
            'pemasukanBulan',
            'pengeluaranBulan',
            'saldoBulan',
            'transactions',
            'students',
            'selectedMonth',
            'selectedYear',
            'selectedSemester',
            'activeClassSemester',
            'kelasLabel',
            'selectedKelasLabel',
            'semesters',
            'duesRate',
            'namaBulan',
            'realCurrentMonth',
            'realCurrentYear',
            'realCurrentDay',
            'realActiveWeek',
            'isCurrentRealMonth',
            'weekRanges',
            'totalPaidStudentsInPeriod',
            'totalUnpaidStudentsInPeriod',
            'semestersRecap',
            'semesterMonthlyRecap'
        ));
    }
}
