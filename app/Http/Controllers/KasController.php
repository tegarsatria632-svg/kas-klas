<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\DuesPayment;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KasController extends Controller
{
    public function index(Request $request)
    {
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

        // Fleksibel: Tidak dikunci arsip agar pengguna bebas memilih dan mencatat data
        $isReadOnly = false;

        // Query transaksi kas berdasarkan bulan & semester terpilih
        $query = KasTransaction::with('user')
            ->where('semester', $selectedSemester)
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->latest('date')
            ->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(15)->withQueryString();

        // Finansial Bulan Terpilih
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

        // Finansial Semester Terpilih (Total Kumulatif Semester)
        $totalPemasukanSemester = KasTransaction::where('semester', $selectedSemester)->where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranSemester = KasTransaction::where('semester', $selectedSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldoSemester = $totalPemasukanSemester - $totalPengeluaranSemester;

        // Finansial Keseluruhan (All-Time)
        $totalPemasukanAll = KasTransaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranAll = KasTransaction::where('type', 'pengeluaran')->sum('amount');
        $totalSaldoAll = $totalPemasukanAll - $totalPengeluaranAll;

        // Variabel untuk Kartu Ringkasan Buku Kas
        $totalPemasukan = $pemasukanBulan;
        $totalPengeluaran = $pengeluaranBulan;
        $saldo = $saldoBulan;

        // Mahasiswa & Matriks Iuran Mingguan Bulan Terpilih
        $students = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
            ->orderBy('npm', 'asc')
            ->with(['duesPayments' => function ($q) use ($selectedSemester, $selectedMonth, $selectedYear) {
                $q->where('semester', $selectedSemester)
                  ->where('month', $selectedMonth)
                  ->where('year', $selectedYear);
            }])->get();

        $targetWeeks = range(1, 4); // Selalu 4 minggu per bulan sesuai menu kas
        $totalPaidStudentsInPeriod = 0;
        $totalUnpaidStudentsInPeriod = 0;
        foreach ($students as $st) {
            $paidCount = $st->duesPayments->count();
            if ($paidCount >= count($targetWeeks)) {
                $totalPaidStudentsInPeriod++;
            } else {
                $totalUnpaidStudentsInPeriod++;
            }
        }

        // Cek apakah terdapat saldo carry forward awal semester
        $carryForwardTx = KasTransaction::where('semester', $selectedSemester)
            ->where('type', 'pemasukan')
            ->where('description', 'like', '%Carry Forward%')
            ->first();

        $semesters = AcademicService::SEMESTERS;
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $selectedKelasLabel = AcademicService::getKelasLabel($selectedSemester);

        return view('kas.index', compact(
            'totalPemasukan',
            'totalPengeluaran',
            'saldo',
            'pemasukanBulan',
            'pengeluaranBulan',
            'saldoBulan',
            'totalPemasukanSemester',
            'totalPengeluaranSemester',
            'saldoSemester',
            'totalPemasukanAll',
            'totalPengeluaranAll',
            'totalSaldoAll',
            'transactions',
            'students',
            'selectedMonth',
            'selectedYear',
            'selectedSemester',
            'activeClassSemester',
            'kelasLabel',
            'selectedKelasLabel',
            'isReadOnly',
            'carryForwardTx',
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
            'totalUnpaidStudentsInPeriod'
        ));
    }


    public function store(Request $request)
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $targetSemester = $request->get('semester', $activeClassSemester);

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:pemasukan,pengeluaran'],
            'user_id' => ['nullable', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:500'],
            'date' => ['required', 'date', 'after_or_equal:2024-01-01', 'before_or_equal:2028-12-31'],
            'description' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
            'proof_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'type.required' => 'Jenis transaksi wajib dipilih (Pemasukan atau Pengeluaran).',
            'type.in' => 'Pilihan jenis transaksi tidak valid.',
            'amount.required' => 'Nominal transaksi kas wajib diisi.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka yang valid.',
            'amount.min' => 'Nominal transaksi kas minimal Rp 500.',
            'date.required' => 'Tanggal transaksi wajib diisi.',
            'date.after_or_equal' => 'Tanggal transaksi minimal tahun 2024.',
            'date.before_or_equal' => 'Tanggal transaksi maksimal tahun 2028.',
            'description.required' => 'Keterangan transaksi kas wajib diisi.',
            'description.max' => 'Keterangan transaksi maksimal 255 karakter.',
            'semester.required' => 'Semester tujuan transaksi kas wajib ditentukan.',
            'proof_image.mimes' => 'Format file bukti nota harus berupa JPG, JPEG, atau PNG.',
            'proof_image.max' => 'Ukuran file foto bukti nota maksimal 2 MB.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error_modal', 'kas');
        }

        $validated = $validator->validated();

        // Proteksi Duplikasi Transaksi (Duplicate Transaction Detection):
        // Mencegah double submit atau transaksi kembar identik yang tidak disengaja
        $duplicateQuery = KasTransaction::where('semester', $validated['semester'])
            ->where('type', $validated['type'])
            ->where('amount', $validated['amount'])
            ->whereDate('date', $validated['date'])
            ->whereRaw('LOWER(TRIM(description)) = ?', [strtolower(trim($validated['description']))]);

        if (!empty($validated['user_id'])) {
            $duplicateQuery->where('user_id', $validated['user_id']);
        }

        $existingDuplicate = $duplicateQuery->first();
        if ($existingDuplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'description' => "Transaksi serupa sudah pernah dicatat pada tanggal {$validated['date']} dengan nominal Rp " . number_format($validated['amount'], 0, ',', '.') . " dan keterangan '{$validated['description']}' (ID Transaksi #{$existingDuplicate->id}). Untuk mencegah transaksi ganda tidak sengaja, mohon periksa mutasi atau tambahkan pembeda pada keterangan.",
                ])
                ->with('error_modal', 'kas');
        }

        // Proteksi Likuiditas Kas: Mencegah saldo semester menjadi negatif
        if ($validated['type'] === 'pengeluaran') {
            $currentIn = KasTransaction::where('semester', $validated['semester'])->where('type', 'pemasukan')->sum('amount');
            $currentOut = KasTransaction::where('semester', $validated['semester'])->where('type', 'pengeluaran')->sum('amount');
            $currentBalance = $currentIn - $currentOut;

            if ($validated['amount'] > $currentBalance) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'amount' => 'Pengeluaran ditolak! Nominal (Rp ' . number_format($validated['amount'], 0, ',', '.') . ') melebihi saldo aktif ' . $validated['semester'] . ' (Rp ' . number_format($currentBalance, 0, ',', '.') . '). Saldo kas tidak boleh defisit/minus.',
                    ])
                    ->with('error_modal', 'kas');
            }
        }

        // Simpan file bukti nota ke public/storage/notes
        if ($request->hasFile('proof_image')) {
            $validated['proof_image'] = $request->file('proof_image')->store('notes', 'public');
        }

        $transaction = KasTransaction::create($validated);

        // Jika pencatatan manual pemasukan iuran dari form utama
        if ($validated['type'] === 'pemasukan' && !empty($validated['user_id'])) {
            $txDate = \Carbon\Carbon::parse($validated['date']);
            $weekNum = min(4, (int) ceil($txDate->day / 7));

            DuesPayment::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'week_number' => $weekNum,
                    'month' => (int) $txDate->month,
                    'year' => (int) $txDate->year,
                    'semester' => $validated['semester'],
                ],
                [
                    'kas_transaction_id' => $transaction->id,
                    'status' => 'lunas',
                ]
            );
        }

        AuditLog::record(
            'catat_transaksi',
            "Mencatat {$transaction->type} sebesar Rp " . number_format($transaction->amount, 0, ',', '.') . " ({$transaction->description})",
            auth()->user(),
            'web_form',
            ['id' => $transaction->id, 'type' => $transaction->type, 'amount' => $transaction->amount, 'semester' => $transaction->semester]
        );

        return redirect()->route('kas.index', ['semester' => $validated['semester']])
            ->with('success', "Transaksi kas berhasil dicatat pada {$validated['semester']}.");
    }

    /**
     * UPDATE Transaksi Kas (CRUD Bendahara)
     */
    public function update(Request $request, KasTransaction $ka)
    {
        $activeClassSemester = AcademicService::getActiveSemester();

        // Proteksi Read-Only untuk Arsip Lama
        if (AcademicService::isHistoricalArchive($ka->semester, $activeClassSemester)) {
            return back()->withErrors([
                'error' => "Transaksi pada {$ka->semester} tidak dapat diubah karena merupakan arsip historis yang telah terkunci.",
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:500'],
            'date' => ['required', 'date', 'after_or_equal:2024-01-01', 'before_or_equal:2028-12-31'],
            'description' => ['required', 'string', 'max:255'],
            'proof_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        // Cek likuiditas jika tipe pengeluaran diubah nominalnya
        if ($ka->type === 'pengeluaran') {
            $currentIn = KasTransaction::where('semester', $ka->semester)->where('type', 'pemasukan')->sum('amount');
            $currentOutWithoutThis = KasTransaction::where('semester', $ka->semester)
                ->where('type', 'pengeluaran')
                ->where('id', '!=', $ka->id)
                ->sum('amount');
            $availableBalance = $currentIn - $currentOutWithoutThis;

            if ($validated['amount'] > $availableBalance) {
                return back()->withInput()->withErrors([
                    'amount' => 'Pembaruan ditolak! Perubahan nominal (Rp ' . number_format($validated['amount'], 0, ',', '.') . ') melebihi batas saldo kas yang tersedia (Rp ' . number_format($availableBalance, 0, ',', '.') . ').',
                ]);
            }
        }

        if ($request->hasFile('proof_image')) {
            if ($ka->proof_image) {
                Storage::disk('public')->delete($ka->proof_image);
            }
            $validated['proof_image'] = $request->file('proof_image')->store('notes', 'public');
        }

        $ka->update($validated);

        AuditLog::record(
            'edit_transaksi',
            "Memperbarui transaksi #{$ka->id} {$ka->type} menjadi Rp " . number_format($validated['amount'], 0, ',', '.') . " ({$validated['description']})",
            auth()->user(),
            'web_form',
            ['id' => $ka->id, 'type' => $ka->type, 'amount' => $validated['amount'], 'semester' => $ka->semester]
        );

        return redirect()->route('kas.index', ['semester' => $ka->semester])
            ->with('success', 'Data transaksi kas berhasil diperbarui.');
    }

    /**
     * Pembayaran Iuran Mahasiswa Multi-Minggu (DB::transaction atomic)
     */
    public function payDues(Request $request)
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $targetSemester = $request->get('semester', $activeClassSemester);

        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'weeks' => ['required', 'array', 'min:1'],
            'weeks.*' => ['integer', 'between:1,4'],
            'amount_per_week' => ['required', 'numeric', 'min:500'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2024,2028'],
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
            'date' => ['required', 'date', 'after_or_equal:2024-01-01', 'before_or_equal:2028-12-31'],
        ], [
            'user_id.required' => 'Mahasiswa pembayar iuran kas wajib dipilih.',
            'user_id.exists' => 'Data mahasiswa yang dipilih tidak ditemukan dalam sistem.',
            'weeks.required' => 'Pilihlah minimal satu minggu iuran kas yang akan dibayarkan.',
            'weeks.min' => 'Pilihlah minimal satu minggu iuran kas yang akan dibayarkan.',
            'amount_per_week.required' => 'Tarif nominal iuran mingguan wajib ditentukan.',
            'amount_per_week.min' => 'Tarif nominal iuran minimal Rp 500.',
            'date.required' => 'Tanggal pencatatan bayar kas wajib diisi.',
            'date.after_or_equal' => 'Tanggal pembayaran minimal tahun 2024.',
            'date.before_or_equal' => 'Tanggal pembayaran maksimal tahun 2028.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $student = User::findOrFail($validated['user_id']);
        $weeks = array_values(array_unique($validated['weeks']));
        sort($weeks);

        // Proteksi Pembayaran Iuran Ganda (Duplicate Dues Payment Detection)
        $alreadyPaidWeeks = DuesPayment::where('user_id', $student->id)
            ->where('semester', $validated['semester'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->where('status', 'lunas')
            ->whereIn('week_number', $weeks)
            ->pluck('week_number')
            ->toArray();

        if (!empty($alreadyPaidWeeks)) {
            $alreadyPaidList = implode(', ', array_map(fn($w) => "Minggu ke-{$w}", $alreadyPaidWeeks));
            return back()->withInput()->withErrors([
                'weeks' => "Pembayaran iuran ganda terdeteksi! Mahasiswa {$student->name} ({$student->npm}) sudah melunasi {$alreadyPaidList} pada periode {$validated['semester']} (Bulan {$validated['month']}/{$validated['year']}). Harap batalkan atau hapus centang minggu yang sudah lunas tersebut.",
            ]);
        }

        $totalAmount = count($weeks) * $validated['amount_per_week'];

        DB::transaction(function () use ($student, $weeks, $totalAmount, $validated) {
            $weeksLabel = implode(', ', array_map(function ($w) {
                return 'Mg ' . $w;
            }, $weeks));

            $transaction = KasTransaction::create([
                'user_id' => $student->id,
                'type' => 'pemasukan',
                'amount' => $totalAmount,
                'date' => $validated['date'],
                'semester' => $validated['semester'],
                'description' => "Iuran Kas: {$student->name} ({$weeksLabel} Bln {$validated['month']}/{$validated['year']} - {$validated['semester']})",
            ]);

            foreach ($weeks as $week) {
                DuesPayment::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'week_number' => $week,
                        'month' => $validated['month'],
                        'year' => $validated['year'],
                        'semester' => $validated['semester'],
                    ],
                    [
                        'kas_transaction_id' => $transaction->id,
                        'status' => 'lunas',
                    ]
                );
            }
        });

        AuditLog::record(
            'bayar_iuran',
            "Mencatat pembayaran iuran {$student->name} ({$weeksLabel} Bln {$validated['month']}/{$validated['year']} - {$validated['semester']}) total Rp " . number_format($totalAmount, 0, ',', '.'),
            auth()->user(),
            'web_form',
            ['user_id' => $student->id, 'student_name' => $student->name, 'weeks' => $weeks, 'amount' => $totalAmount, 'semester' => $validated['semester']]
        );

        return redirect()->route('kas.index', [
            'semester' => $validated['semester'],
            'month' => $validated['month'],
            'year' => $validated['year'],
        ])->with('success', "Pembayaran iuran {$student->name} (" . count($weeks) . " minggu) berhasil dicatat pada {$validated['semester']}.");
    }

    /**
     * Pembatalan Iuran Kas Mahasiswa (CRUD Bendahara)
     */
    public function cancelDues(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'week_number' => ['required', 'integer', 'between:1,4'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2024,2028'],
            'semester' => ['required', 'in:' . implode(',', AcademicService::SEMESTERS)],
        ], [
            'user_id.required' => 'ID mahasiswa pembatalan iuran wajib disertakan.',
            'week_number.required' => 'Nomor minggu yang akan dibatalkan wajib ditentukan.',
            'week_number.between' => 'Nomor minggu harus antara Minggu 1 s.d Minggu 4.',
            'semester.in' => 'Semester tidak valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();

        $student = User::findOrFail($validated['user_id']);

        $due = DuesPayment::where('user_id', $student->id)
            ->where('week_number', $validated['week_number'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->where('semester', $validated['semester'])
            ->first();

        if (!$due) {
            return back()->withErrors(['error' => 'Data iuran minggu tersebut tidak ditemukan.']);
        }

        DB::transaction(function () use ($due) {
            $txId = $due->kas_transaction_id;
            $due->delete();

            if ($txId) {
                $otherDuesCount = DuesPayment::where('kas_transaction_id', $txId)->count();
                if ($otherDuesCount === 0) {
                    KasTransaction::where('id', $txId)->delete();
                } else {
                    $tx = KasTransaction::find($txId);
                    $rate = AcademicService::getDuesRatePerWeek();
                    if ($tx && $tx->amount > $rate) {
                        $tx->decrement('amount', $rate);
                    }
                }
            }
        });

        AuditLog::record(
            'batal_iuran',
            "Membatalkan status iuran Minggu ke-{$validated['week_number']} mahasiswa {$student->name} (Bln {$validated['month']}/{$validated['year']} - {$validated['semester']})",
            auth()->user(),
            'web_form',
            ['user_id' => $student->id, 'student_name' => $student->name, 'week_number' => $validated['week_number'], 'semester' => $validated['semester']]
        );

        return redirect()->route('kas.index', [
            'semester' => $validated['semester'],
            'month' => $validated['month'],
            'year' => $validated['year'],
        ])->with('success', "Status iuran Minggu ke-{$validated['week_number']} mahasiswa {$student->name} berhasil dibatalkan.");
    }

    /**
     * Pengaturan Besaran Tarif Iuran Mingguan (Khusus Bendahara)
     */
    public function updateDuesRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount_per_week' => ['required', 'numeric', 'min:500', 'max:1000000'],
        ], [
            'amount_per_week.required' => 'Nominal iuran mingguan wajib diisi.',
            'amount_per_week.numeric' => 'Nominal iuran harus berupa angka yang valid.',
            'amount_per_week.min' => 'Nominal iuran minimal Rp 500.',
            'amount_per_week.max' => 'Nominal iuran maksimal Rp 1.000.000.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error_modal', 'dues_rate');
        }

        $validated = $validator->validated();

        $amount = (int) $validated['amount_per_week'];
        AcademicService::setDuesRatePerWeek($amount);

        AuditLog::record(
            'atur_tarif_iuran',
            "Memperbarui tarif iuran kas mingguan menjadi Rp " . number_format($amount, 0, ',', '.') . " / minggu",
            auth()->user(),
            'web_form',
            ['amount' => $amount]
        );

        return back()->with('success', 'Besaran tarif iuran kas mingguan berhasil diperbarui menjadi Rp ' . number_format($amount, 0, ',', '.') . ' / minggu.');
    }

    /**
     * DELETE Transaksi Kas (CRUD Bendahara)
     */
    public function destroy(KasTransaction $ka)
    {
        $activeClassSemester = AcademicService::getActiveSemester();

        // Proteksi Read-Only untuk Arsip Lama
        if (AcademicService::isHistoricalArchive($ka->semester, $activeClassSemester)) {
            return back()->withErrors([
                'error' => "Transaksi pada {$ka->semester} tidak dapat dihapus karena merupakan arsip historis yang telah terkunci.",
            ]);
        }

        if ($ka->proof_image) {
            Storage::disk('public')->delete($ka->proof_image);
        }

        // Hapus juga riwayat dues_payment terkait jika ada
        DuesPayment::where('kas_transaction_id', $ka->id)->delete();

        AuditLog::record(
            'hapus_transaksi',
            "Menghapus transaksi #{$ka->id} {$ka->type} Rp " . number_format($ka->amount, 0, ',', '.') . " ({$ka->description})",
            auth()->user(),
            'web_form',
            ['id' => $ka->id, 'type' => $ka->type, 'amount' => $ka->amount, 'semester' => $ka->semester]
        );

        $ka->delete();

        return redirect()->route('kas.index', ['semester' => $ka->semester])
            ->with('success', 'Transaksi kas berhasil dihapus dari pembukuan.');
    }

    /**
     * Lihat Foto Bukti Nota (Tanpa perlu symlink storage)
     */
    public function showNota($id)
    {
        $tx = KasTransaction::findOrFail($id);
        if (!$tx->proof_image) {
            abort(404, 'Bukti nota tidak ditemukan pada transaksi ini.');
        }

        $path = storage_path('app/public/' . $tx->proof_image);
        if (!file_exists($path)) {
            abort(404, 'File gambar fisik tidak ditemukan di server.');
        }

        return response()->file($path);
    }

    public function exportExcel(Request $request)
    {
        $selectedSemester = $request->get('semester', AcademicService::getActiveSemester());
        if (!in_array($selectedSemester, AcademicService::SEMESTERS)) {
            $selectedSemester = AcademicService::getActiveSemester();
        }

        $transactions = KasTransaction::with('user')
            ->where('semester', $selectedSemester)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $students = User::whereIn('role', ['mahasiswa', 'bendahara', 'ketua', 'admin'])
            ->orderBy('npm', 'asc')
            ->with(['duesPayments' => function ($q) use ($selectedSemester) {
                $q->where('semester', $selectedSemester);
            }])
            ->get();

        $duesRate = AcademicService::getDuesRatePerWeek();

        AuditLog::record(
            'ekspor_excel',
            "Mengunduh Rekapitulasi Kas & Iuran Excel (.csv) periode {$selectedSemester}",
            auth()->user(),
            'web_form',
            ['semester' => $selectedSemester, 'transactions_count' => $transactions->count()]
        );

        $filename = "Rekap_Kas_Informatika_UPS_{$selectedSemester}_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($transactions, $students, $selectedSemester, $duesRate) {
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM untuk Microsoft Excel

            // Header Instansi UPS Tegal
            fputcsv($output, ['UNIVERSITAS PANCASAKTI TEGAL (UPS TEGAL) - FTIK']);
            fputcsv($output, ['PROGRAM STUDI S1 INFORMATIKA - LAPORAN KEUANGAN KAS KELAS']);
            fputcsv($output, ['PERIODE LAPORAN: ' . strtoupper($selectedSemester) . ' | TANGGAL EKSPOR: ' . date('d/m/Y H:i:s')]);
            fputcsv($output, []);

            // TABEL 1: MUTASI BUKU KAS
            fputcsv($output, ['BAGIAN 1: BUKU KAS MUTASI RINCI (' . strtoupper($selectedSemester) . ')']);
            fputcsv($output, [
                'No',
                'Tanggal',
                'Semester',
                'Tipe Transaksi',
                'Keterangan',
                'Nama Mahasiswa',
                'NPM',
                'Debit / Masuk (Rp)',
                'Kredit / Keluar (Rp)',
                'Saldo Berjalan (Rp)',
            ]);

            $no = 1;
            $runningBalance = 0;
            $totalIn = 0;
            $totalOut = 0;

            foreach ($transactions as $tx) {
                $in = $tx->type === 'pemasukan' ? (float) $tx->amount : 0;
                $out = $tx->type === 'pengeluaran' ? (float) $tx->amount : 0;

                $totalIn += $in;
                $totalOut += $out;
                $runningBalance += ($in - $out);

                $mhsName = $tx->user ? $tx->user->name : 'Kas Umum';
                $mhsNpm = $tx->user ? $tx->user->npm : '-';

                fputcsv($output, [
                    $no++,
                    $tx->date->format('d/m/Y'),
                    $tx->semester,
                    strtoupper($tx->type),
                    $tx->description,
                    $mhsName,
                    $mhsNpm,
                    $in,
                    $out,
                    $runningBalance,
                ]);
            }

            fputcsv($output, ['', '', '', '', 'TOTAL MUTASI', '', '', $totalIn, $totalOut, '']);
            fputcsv($output, ['', '', '', '', 'SALDO AKHIR KAS BERJALAN', '', '', '', '', $runningBalance]);
            fputcsv($output, []);
            fputcsv($output, []);

            // TABEL 2: REKAPITULASI IURAN MAHASISWA
            fputcsv($output, ['BAGIAN 2: REKAPITULASI SETORAN IURAN MAHASISWA (' . strtoupper($selectedSemester) . ')']);
            fputcsv($output, [
                'No',
                'NPM',
                'Nama Mahasiswa',
                'Peran / Role',
                'Status Akademik',
                'Jumlah Minggu Lunas',
                'Total Nominal Setoran (Rp)',
                'Tarif Mingguan (Rp)',
            ]);

            $noMhs = 1;
            $totalDuesCollected = 0;
            foreach ($students as $st) {
                $paidWeeksCount = $st->duesPayments->count();
                $nominalSetor = $paidWeeksCount * $duesRate;
                $totalDuesCollected += $nominalSetor;

                fputcsv($output, [
                    $noMhs++,
                    $st->npm,
                    $st->name,
                    strtoupper($st->role),
                    $st->status_akademik ?: 'Aktif',
                    $paidWeeksCount . ' Minggu',
                    $nominalSetor,
                    $duesRate,
                ]);
            }

            fputcsv($output, ['', '', '', '', 'TOTAL ESTIMASI IURAN TERHIMPUN', '', $totalDuesCollected, '']);
            fputcsv($output, []);
            fputcsv($output, ['Dicetak otomatis oleh Sistem AI Keuangan S1 Informatika UPS Tegal']);

            fclose($output);
        }, 200, $headers);
    }
}
