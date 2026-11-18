<?php

namespace App\Http\Controllers;

use App\Models\DuesPayment;
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

        // 3. Status Iuran Kas Bulanan Pribadi (4 Minggu Sesuai Menu Kas)
        $isDuesPending = false;
        $pendingWeeks = [];
        $paidWeeks = [];

        if ($user) {
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

            $expectedWeeks = range(1, 4);
            $pendingWeeks = array_values(array_diff($expectedWeeks, $paidWeeks));
            $isDuesPending = !empty($pendingWeeks);
        }

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
            'paidWeeks'
        ));
    }
}
