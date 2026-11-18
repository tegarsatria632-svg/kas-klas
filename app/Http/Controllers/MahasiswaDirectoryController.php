<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MahasiswaDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $selectedSemester = $request->get('semester', $activeClassSemester);
        $search = $request->get('q');

        // Sertakan seluruh anggota kelas (Mahasiswa, Admin, Ketua, Bendahara)
        $query = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara']);

        if ($selectedSemester && $selectedSemester !== 'all') {
            $query->where('semester', $selectedSemester);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('npm', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('npm', 'asc')->paginate(20)->withQueryString();

        $countQuery = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara']);
        if ($selectedSemester && $selectedSemester !== 'all') {
            $countQuery->where('semester', $selectedSemester);
        }
        $totalStudents = (clone $countQuery)->count();
        $activeStudents = (clone $countQuery)->where('status_akademik', 'Aktif')->count();
        $semesters = AcademicService::SEMESTERS;
        $currentSem = ($selectedSemester && $selectedSemester !== 'all') ? $selectedSemester : $activeClassSemester;
        $className = AcademicService::getNamaKelas($activeClassSemester);
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $selectedKelasLabel = AcademicService::getKelasLabel($currentSem);
        $activeKelasLabel = $kelasLabel;

        return view('mahasiswa.index', compact(
            'students',
            'selectedSemester',
            'activeClassSemester',
            'totalStudents',
            'activeStudents',
            'semesters',
            'className',
            'kelasLabel',
            'selectedKelasLabel',
            'activeKelasLabel',
            'search'
        ));
    }

    public function exportExcel(Request $request)
    {
        $selectedSemester = $request->get('semester', AcademicService::getActiveSemester());

        $students = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
            ->when($selectedSemester !== 'all', function ($q) use ($selectedSemester) {
                $q->where('semester', $selectedSemester);
            })
            ->orderBy('npm', 'asc')
            ->get();

        $exportKelasLabel = AcademicService::getKelasLabel($selectedSemester !== 'all' ? $selectedSemester : null);
        $filename = "Daftar_Mahasiswa_Informatika_{$exportKelasLabel}_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($students, $selectedSemester, $exportKelasLabel) {
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($output, ['UNIVERSITAS PANCASAKTI TEGAL (UPS TEGAL) - FTIK']);
            fputcsv($output, ['PROGRAM STUDI S1 INFORMATIKA - DAFTAR MAHASISWA KELAS ' . $exportKelasLabel]);
            fputcsv($output, ['PERIODE / SEMESTER: ' . strtoupper($selectedSemester)]);
            fputcsv($output, []);

            fputcsv($output, [
                'No',
                'NPM',
                'Nama Lengkap',
                'Program Studi',
                'Kelas',
                'Semester',
                'Peran',
                'Status Akademik',
                'Tahun Ajaran',
            ]);

            $no = 1;
            foreach ($students as $mhs) {
                fputcsv($output, [
                    $no++,
                    $mhs->npm,
                    $mhs->name,
                    AcademicService::PRODI,
                    AcademicService::getNamaKelas($mhs->semester),
                    $mhs->semester,
                    ucfirst($mhs->role),
                    $mhs->status_akademik ?? 'Aktif',
                    $mhs->tahun_ajaran ?? '2024/2025',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}
