<?php

namespace App\Http\Controllers;

use App\Models\DuesPayment;
use App\Models\User;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        // 1. Data Akademik & Semester Aktif S1 Informatika UPS Tegal
        $activeClassSemester = AcademicService::getActiveSemester();

        // 2. Periode Berjalan & Penentuan Minggu Aktif
        $today = now();
        $currentMonth = (int) $today->month;
        $currentYear = (int) $today->year;
        $activeWeek = min(4, (int) ceil($today->day / 7));

        // 3. Status Iuran Kas Bulanan Pribadi (4 Minggu Sesuai Menu Kas) & Riwayat Per Bulan Semester Aktif
        $isDuesPending = false;
        $pendingWeeks = [];
        $paidWeeks = [];
        $personalMonthlyHistory = [];
        $totalPaidWeeksSemester = 0;

        if ($user) {
            $allUserSemesterDues = DuesPayment::where('user_id', $user->id)
                ->where('semester', $activeClassSemester)
                ->where('status', 'lunas')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('week_number', 'asc')
                ->get();

            $totalPaidWeeksSemester = $allUserSemesterDues->count();

            $groupedByPeriod = [];
            foreach ($allUserSemesterDues as $due) {
                $key = sprintf('%04d-%02d', (int) $due->year, (int) $due->month);
                if (!isset($groupedByPeriod[$key])) {
                    $groupedByPeriod[$key] = [
                        'year' => (int) $due->year,
                        'month' => (int) $due->month,
                        'weeks' => [],
                    ];
                }
                $wNum = (int) $due->week_number;
                if (!in_array($wNum, $groupedByPeriod[$key]['weeks'])) {
                    $groupedByPeriod[$key]['weeks'][] = $wNum;
                }
            }

            // Pastikan bulan berjalan selalu tampil paling atas
            $currentPeriodKey = sprintf('%04d-%02d', $currentYear, $currentMonth);
            if (!isset($groupedByPeriod[$currentPeriodKey])) {
                $groupedByPeriod[$currentPeriodKey] = [
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'weeks' => [],
                ];
            }

            krsort($groupedByPeriod);

            foreach ($groupedByPeriod as $periodData) {
                sort($periodData['weeks']);
                $pMonth = $periodData['month'];
                $pYear = $periodData['year'];
                $isCurrent = ($pMonth === $currentMonth && $pYear === $currentYear);
                $paidCount = count($periodData['weeks']);

                if ($isCurrent) {
                    $paidWeeks = $periodData['weeks'];
                }

                $personalMonthlyHistory[] = [
                    'month' => $pMonth,
                    'year' => $pYear,
                    'label' => AcademicService::getNamaBulan($pMonth) . ' ' . $pYear,
                    'is_current' => $isCurrent,
                    'paid_weeks' => $periodData['weeks'],
                    'paid_count' => $paidCount,
                    'is_full' => $paidCount >= 4,
                ];
            }

            $expectedWeeks = range(1, 4);
            $pendingWeeks = array_values(array_diff($expectedWeeks, $paidWeeks));
            $isDuesPending = !empty($pendingWeeks);
        }

        // 4. Info Pengurus Kelas Aktif (Ketua Kelas & Bendahara Kelas)
        $ketuaList = User::where('role', 'ketua')
            ->where('status_akademik', 'Aktif')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'npm', 'role']);

        $bendaharaList = User::where('role', 'bendahara')
            ->where('status_akademik', 'Aktif')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'npm', 'role']);

        $duesRate = AcademicService::getDuesRatePerWeek();
        $tunggakanNominal = count($pendingWeeks) * $duesRate;
        $weekRanges = AcademicService::getWeekDateRanges($currentMonth, $currentYear);

        $namaBulan = AcademicService::getNamaBulan($currentMonth);
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);

        return view('dashboard', compact(
            'user',
            'activeClassSemester',
            'kelasLabel',
            'activeWeek',
            'currentYear',
            'namaBulan',
            'isDuesPending',
            'pendingWeeks',
            'duesRate',
            'tunggakanNominal',
            'weekRanges',
            'paidWeeks',
            'personalMonthlyHistory',
            'totalPaidWeeksSemester',
            'ketuaList',
            'bendaharaList'
        ));
    }
}
