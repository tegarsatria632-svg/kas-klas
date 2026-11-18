<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KasTransaction;
use App\Models\AuditLog;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $selectedSemester = $request->get('semester');
        $selectedStatus = $request->get('status_akademik');

        $query = User::orderBy('role')->orderBy('semester')->orderBy('name');

        if ($selectedSemester) {
            $query->where('semester', $selectedSemester);
        }

        if ($selectedStatus) {
            $query->where('status_akademik', $selectedStatus);
        }

        $users = $query->paginate(20)->withQueryString();
        $semesters = AcademicService::SEMESTERS;
        $activeClassSemester = AcademicService::getActiveSemester();

        // Statistik Cepat S1 Informatika UPS Tegal
        $activeStudentsCount = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])->where('status_akademik', 'Aktif')->count();
        $alumniStudentsCount = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])->where('status_akademik', 'Lulus / Alumni')->count();
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);

        return view('admin.users', compact(
            'users',
            'semesters',
            'activeClassSemester',
            'kelasLabel',
            'activeStudentsCount',
            'alumniStudentsCount',
            'selectedSemester',
            'selectedStatus'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'npm' => ['required', 'string', 'max:30', 'unique:users,npm'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,bendahara,ketua,mahasiswa'],
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
            'status_akademik' => ['nullable', 'in:Aktif,Lulus / Alumni'],
            'tahun_ajaran' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama lengkap mahasiswa/pengurus wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 100 karakter.',
            'npm.required' => 'Nomor Pokok Mahasiswa (NPM) wajib diisi.',
            'npm.max' => 'NPM maksimal 30 karakter.',
            'npm.unique' => 'NPM ini sudah terdaftar untuk pengguna lain dalam sistem.',
            'password.required' => 'Kata sandi awal akun wajib diisi.',
            'password.min' => 'Kata sandi awal minimal 6 karakter.',
            'role.required' => 'Peran akun (role) wajib dipilih.',
            'role.in' => 'Pilihan peran akun tidak valid.',
            'semester.required' => 'Semester mahasiswa wajib dipilih.',
            'semester.in' => 'Pilihan semester tidak valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error_modal', 'user');
        }

        // Proteksi Akun Ganda: Cek apakah nama lengkap yang persis sama sudah terdaftar dalam sistem
        $nameTrimmed = trim((string) $request->name);
        $existingUserByName = User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($nameTrimmed)])->first();
        if ($existingUserByName) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' => "Nama '{$existingUserByName->name}' sudah terdaftar dalam sistem (NPM: {$existingUserByName->npm}, Role: {$existingUserByName->role}). Untuk menghindari akun ganda, gunakan nama yang berbeda atau berikan pembeda (seperti inisial nama belakang).",
                ])
                ->with('error_modal', 'user');
        }

        $validated = $validator->validated();
        $validated['email'] = $validated['npm'] . '@mail.upstegal.ac.id';
        $validated['password'] = Hash::make($validated['password']);
        $validated['status_akademik'] = $validated['status_akademik'] ?? 'Aktif';
        $validated['tahun_ajaran'] = $validated['tahun_ajaran'] ?? '2024/2025';

        $newUser = User::create($validated);

        AuditLog::record(
            'tambah_akun',
            "Mendaftarkan akun baru {$newUser->name} (NPM: {$newUser->npm}) role {$newUser->role}",
            auth()->user(),
            'web_form',
            ['user_id' => $newUser->id, 'npm' => $newUser->npm, 'role' => $newUser->role, 'semester' => $newUser->semester]
        );

        return redirect()->route('admin.users.index')->with('success', 'Akun mahasiswa/pengurus S1 Informatika berhasil didaftarkan.');
    }

    public function updateRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'in:admin,bendahara,ketua,mahasiswa'],
        ], [
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Pilihan peran akun tidak valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();
        $oldRole = $user->role;
        $user->update($validated);

        AuditLog::record(
            'ubah_role',
            "Mengubah peran akun {$user->name} (NPM: {$user->npm}) dari {$oldRole} menjadi {$validated['role']}",
            auth()->user(),
            'web_form',
            ['user_id' => $user->id, 'old_role' => $oldRole, 'new_role' => $validated['role']]
        );

        return redirect()->route('admin.users.index')->with('success', "Peran akun '{$user->name}' berhasil diperbarui menjadi {$validated['role']}.");
    }

    public function updateAcademic(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
            'status_akademik' => ['required', 'in:Aktif,Lulus / Alumni'],
            'tahun_ajaran' => ['nullable', 'string', 'max:30'],
        ], [
            'semester.required' => 'Semester akademik wajib dipilih.',
            'semester.in' => 'Pilihan semester tidak valid.',
            'status_akademik.required' => 'Status akademik wajib ditentukan.',
            'status_akademik.in' => 'Pilihan status akademik tidak valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error_modal', 'academic');
        }

        $validated = $validator->validated();
        $user->update($validated);

        AuditLog::record(
            'ubah_akademik',
            "Memperbarui data akademik {$user->name} (NPM: {$user->npm}) menjadi {$validated['semester']} ({$validated['status_akademik']})",
            auth()->user(),
            'web_form',
            ['user_id' => $user->id, 'semester' => $validated['semester'], 'status_akademik' => $validated['status_akademik']]
        );

        return redirect()->route('admin.users.index')->with('success', "Data akademik '{$user->name}' berhasil diperbarui ({$validated['semester']} - {$validated['status_akademik']}).");
    }

    /**
     * Algoritma Kenaikan Semester Massal S1 (Semester 1 s.d 8 -> Lulus/Alumni)
     * Beserta Opsi Tutup Buku & Carry Forward Saldo Kas Antar Semester
     */
    public function promoteSemester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun_ajaran_baru' => ['required', 'string', 'max:30'],
            'carry_forward_saldo' => ['nullable', 'boolean'],
        ], [
            'tahun_ajaran_baru.required' => 'Format tahun ajaran baru wajib diisi (Contoh: 2025/2026).',
            'tahun_ajaran_baru.max' => 'Tahun ajaran baru maksimal 30 karakter.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error_modal', 'promote');
        }

        $validated = $validator->validated();
        $tahunAjaranBaru = $validated['tahun_ajaran_baru'];
        $isCarryForward = $request->boolean('carry_forward_saldo', true);

        $fromSemester = AcademicService::getActiveSemester();
        $fromNum = AcademicService::parseSemesterNumber($fromSemester);
        $toNum = min(8, $fromNum + 1);
        $toSemester = "Semester {$toNum}";

        $promotedCount = 0;
        $graduatedCount = 0;
        $saldoCarryForward = 0;

        DB::transaction(function () use (
            $tahunAjaranBaru,
            $isCarryForward,
            $fromSemester,
            $toSemester,
            &$promotedCount,
            &$graduatedCount,
            &$saldoCarryForward
        ) {
            $activeStudents = User::where('role', 'mahasiswa')
                ->where('status_akademik', 'Aktif')
                ->get();

            foreach ($activeStudents as $student) {
                $currentNum = AcademicService::parseSemesterNumber($student->semester);

                if ($currentNum >= 8) {
                    // KONDISI KHUSUS SEMESTER 8: Mahasiswa menyelesaikan jenjang S1 -> Menjadi Alumni
                    $student->update([
                        'status_akademik' => 'Lulus / Alumni',
                        'tahun_ajaran' => $tahunAjaranBaru,
                    ]);
                    $graduatedCount++;
                } else {
                    // Kenaikan Semester Berjenjang: 1->2, 2->3, ..., 7->8
                    $nextNum = $currentNum + 1;
                    $student->update([
                        'semester' => "Semester {$nextNum}",
                        'tahun_ajaran' => $tahunAjaranBaru,
                    ]);
                    $promotedCount++;
                }
            }

            // FITUR CARRY FORWARD SALDO KAS ANTAR SEMESTER
            if ($isCarryForward && $fromSemester !== $toSemester) {
                $totalIn = KasTransaction::where('semester', $fromSemester)->where('type', 'pemasukan')->sum('amount');
                $totalOut = KasTransaction::where('semester', $fromSemester)->where('type', 'pengeluaran')->sum('amount');
                $saldoSisa = $totalIn - $totalOut;

                if ($saldoSisa > 0) {
                    $saldoCarryForward = $saldoSisa;
                    KasTransaction::create([
                        'user_id' => null,
                        'type' => 'pemasukan',
                        'amount' => $saldoSisa,
                        'date' => now()->toDateString(),
                        'semester' => $toSemester,
                        'tahun_ajaran' => $tahunAjaranBaru,
                        'description' => "Carry Forward: Saldo Awal Pindahan dari {$fromSemester} ke {$toSemester} (Tahun Akademik {$tahunAjaranBaru})",
                    ]);
                }
            }
        });

        $message = "Kenaikan semester massal berhasil! {$promotedCount} mahasiswa naik tingkat ke {$toSemester}.";
        if ($graduatedCount > 0) {
            $message .= " {$graduatedCount} mahasiswa Semester 8 telah dinyatakan Lulus / Alumni.";
        }
        if ($saldoCarryForward > 0) {
            $message .= " Saldo akhir sebesar Rp " . number_format($saldoCarryForward, 0, ',', '.') . " berhasil dipindahkan (Carry Forward) ke {$toSemester}.";
        }

        AuditLog::record(
            'kenaikan_semester',
            "Melakukan kenaikan semester massal ke {$toSemester} ({$promotedCount} naik tingkat, {$graduatedCount} lulus/alumni)" . ($saldoCarryForward > 0 ? " dengan Carry Forward kas Rp " . number_format($saldoCarryForward, 0, ',', '.') : ""),
            auth()->user(),
            'web_form',
            ['to_semester' => $toSemester, 'promoted' => $promotedCount, 'graduated' => $graduatedCount, 'carry_forward' => $saldoCarryForward]
        );

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Pengaturan Semester Aktif Kelas (Khusus Super Admin)
     */
    public function setActiveSemester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
            'apply_to_all_active' => ['nullable', 'boolean'],
        ], [
            'semester.required' => 'Pilihan semester aktif wajib ditentukan.',
            'semester.in' => 'Pilihan semester tidak valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();
        AcademicService::setActiveSemester($validated['semester']);

        // Update all admin users in database to guarantee single source of truth
        User::where('role', 'admin')->update(['semester' => $validated['semester']]);

        // Jika opsi dicentang, update seluruh akun aktif ke semester baru ini
        if ($request->boolean('apply_to_all_active')) {
            User::where('status_akademik', 'Aktif')->update(['semester' => $validated['semester']]);
        }

        // Pastikan session tersinkronisasi
        session(['selected_semester' => $validated['semester']]);

        $kelasLabel = AcademicService::getKelasLabel($validated['semester']);

        AuditLog::record(
            'ubah_semester_aktif',
            "Mengubah semester aktif kelas menjadi {$validated['semester']} (Kelas {$kelasLabel})",
            auth()->user(),
            'web_form',
            ['semester' => $validated['semester']]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Semester aktif kelas berhasil diubah menjadi {$validated['semester']} (Kelas {$kelasLabel}). Seluruh informasi dan data sistem kini tersinkronisasi.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error_modal', 'reset_password');
        }

        $validated = $validator->validated();
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        AuditLog::record(
            'reset_password',
            "Mereset kata sandi akun {$user->name} (NPM: {$user->npm})",
            auth()->user(),
            'web_form',
            ['user_id' => $user->id, 'npm' => $user->npm]
        );

        return redirect()->route('admin.users.index')->with('success', "Password akun '{$user->name}' berhasil disetel ulang.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        $deletedName = $user->name;
        $deletedNpm = $user->npm;
        $deletedRole = $user->role;

        $user->delete();

        AuditLog::record(
            'hapus_akun',
            "Menghapus akun {$deletedName} (NPM: {$deletedNpm}, Role: {$deletedRole}) dari sistem",
            auth()->user(),
            'web_form',
            ['npm' => $deletedNpm, 'role' => $deletedRole]
        );

        return redirect()->route('admin.users.index')->with('success', "Akun '{$user->name}' berhasil dihapus dari sistem.");
    }

    public function exportExcel()
    {
        $users = User::orderBy('semester', 'asc')->orderBy('role', 'asc')->orderBy('name', 'asc')->get();

        AuditLog::record(
            'ekspor_mahasiswa_excel',
            "Mengunduh file Excel (.csv) data mahasiswa dan pengurus S1 Informatika",
            auth()->user(),
            'web_form',
            ['total_records' => $users->count()]
        );

        $filename = "Data_Mahasiswa_Informatika_UPS_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($users) {
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($output, [
                'No',
                'NPM',
                'Nama Lengkap',
                'Program Studi',
                'Peran / Hak Akses',
                'Semester',
                'Status Akademik',
                'Tahun Ajaran',
                'Email Institusi',
                'Terdaftar Pada',
            ]);

            $no = 1;
            foreach ($users as $user) {
                fputcsv($output, [
                    $no++,
                    $user->npm,
                    $user->name,
                    AcademicService::PRODI . ' - ' . AcademicService::UNIVERSITAS,
                    strtoupper($user->role),
                    $user->semester,
                    $user->status_akademik,
                    $user->tahun_ajaran,
                    $user->email,
                    $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}
