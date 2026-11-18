@extends('layouts.app')

@section('title', 'Buku Kas & Iuran Kelas')

@section('content')
<div class="space-y-6">
    <!-- Open Header & Toolbar (Unboxed & Fluid - Tanpa Kotak Bertumpuk) -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-1">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">
                    {{ auth()->user()->role === 'bendahara' ? 'Akses Bendahara' : 'Mode Baca' }}
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Buku Kas &amp; Iuran &bull; Kelas {{ $kelasLabel }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Pencatatan kas dan iuran mingguan bulan <strong class="text-slate-800">{{ $namaBulan }} {{ $selectedYear }}</strong> &bull; {{ $selectedSemester }}
            </p>
        </div>

        <!-- Toolbar Aksi & Filter Periode -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-wrap">
            <form method="GET" action="{{ route('kas.index') }}" class="flex items-center gap-1.5 flex-wrap">
                <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Semester">
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>
                            {{ $sem }} {{ $sem === $activeClassSemester ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="month" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Bulan">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }} {{ ($m === $realCurrentMonth && $selectedYear === $realCurrentYear) ? '(Bulan Ini)' : '' }}
                        </option>
                    @endfor
                </select>

                <select name="year" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Tahun">
                    @foreach(\App\Services\AcademicService::TAHUN_LIST as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                            {{ $y }} {{ $y === $realCurrentYear ? '(Tahun Ini)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('arsip.index') }}" 
                   class="px-3 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition shadow-2xs"
                   title="Buka Pusat Arsip &amp; Rekapitulasi Kas">
                    Arsip Kas
                </a>

                @if(auth()->user()->role === 'bendahara')
                <button type="button" 
                        onclick="document.getElementById('modal-setting-dues-rate').classList.remove('hidden')" 
                        class="px-2.5 py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition"
                        title="Atur Besaran Nominal Iuran Mingguan">
                    Rp {{ number_format($duesRate, 0, ',', '.') }}/mgg
                </button>

                <button onclick="document.getElementById('modal-kas').classList.remove('hidden')" 
                        class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition shadow-xs">
                    Catat Transaksi
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- MATRIKS IURAN MINGGUAN (MINGGU 1 S/D 4) -->
    <section class="pt-4 border-t border-slate-200 space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                        Matriks Iuran Mingguan &bull; {{ $namaBulan }} {{ $selectedYear }}
                    </h2>
                    @if($isCurrentRealMonth)
                    <span class="text-slate-300 hidden sm:inline">|</span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <span>Minggu ke-{{ $realActiveWeek }} ({{ $weekRanges[$realActiveWeek]['date_range'] ?? '' }} {{ $namaBulan }})</span>
                    </span>
                    @elseif($selectedYear < $realCurrentYear || ($selectedYear == $realCurrentYear && $selectedMonth < $realCurrentMonth))
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-[11px] font-medium text-slate-500">Periode Lampau</span>
                    @else
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-[11px] font-medium text-slate-500">Periode Mendatang</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-600 mt-1">
                    {{ $selectedSemester }} (Kelas {{ $kelasLabel }}) &bull; Lunas: <strong class="text-emerald-700 font-mono tabular-nums">{{ $totalPaidStudentsInPeriod }}</strong> mhs &bull; Belum Lunas: <strong class="text-rose-700 font-mono tabular-nums">{{ $totalUnpaidStudentsInPeriod }}</strong> mhs
                </p>
            </div>
        </div>

        <!-- Tampilan Daftar Ringkas Mobile (Flat, 1 Strip Terpadu Per Mahasiswa) -->
        <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
            @forelse($students as $idx => $mhs)
            @php
                $paidWeeksList = $mhs->duesPayments->pluck('week_number')->map(function($w){ return (int)$w; })->toArray();
                $totalPaidCount = count($paidWeeksList);
            @endphp
            <div class="px-4 py-3 sm:p-3.5 space-y-2">
                <div class="flex items-baseline justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-xs sm:text-sm leading-tight truncate">{{ $mhs->name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }}</div>
                    </div>
                    <span class="text-[11px] font-bold shrink-0 font-mono tabular-nums {{ $totalPaidCount === 4 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $totalPaidCount }}/4 Minggu
                    </span>
                </div>

                <!-- 1 Strip Segmented 4 Minggu (Bukan 4 Kotak Terpisah) -->
                <div class="grid grid-cols-4 divide-x divide-slate-200 border border-slate-200 rounded-lg bg-white overflow-hidden">
                    @for($w = 1; $w <= 4; $w++)
                    @php 
                        $isPaid = in_array($w, $paidWeeksList); 
                        $isCurrentWeek = ($isCurrentRealMonth && $w === $realActiveWeek);
                    @endphp
                    <div class="py-1.5 px-1 text-center {{ $isCurrentWeek ? 'bg-slate-50' : '' }}">
                        <div class="flex items-center justify-center gap-0.5">
                            <span class="text-[9.5px] font-bold uppercase font-mono leading-none {{ $isCurrentWeek ? 'text-slate-900' : 'text-slate-600' }}">M{{ $w }}</span>
                            @if($isCurrentWeek)
                            <span class="w-1 h-1 rounded-full bg-emerald-600 shrink-0" title="Minggu Berjalan"></span>
                            @endif
                        </div>
                        @if(!empty($weekRanges[$w]['date_range']))
                        <div class="text-[8px] text-slate-500 font-mono leading-none mt-0.5 truncate">
                            {{ $weekRanges[$w]['date_range'] }}
                        </div>
                        @endif
                        <div class="text-[10px] font-bold leading-tight mt-0.5 {{ $isPaid ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $isPaid ? 'Lunas' : 'Belum' }}
                        </div>
                    </div>
                    @endfor
                </div>

                @if(auth()->user()->role === 'bendahara')
                <!-- Tombol Aksi Bendahara Mobile -->
                <div class="pt-0.5 flex items-center gap-2">
                    @if($totalPaidCount < 4)
                    <button type="button" onclick="openPayDuesModal({{ $mhs->id }}, '{{ addslashes($mhs->name) }}', '{{ $mhs->npm }}', {{ json_encode($paidWeeksList) }})" 
                            class="flex-1 py-1.5 px-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition text-center min-h-[38px] flex items-center justify-center">
                        Bayar Iuran
                    </button>
                    @else
                    <span class="flex-1 text-xs font-semibold text-emerald-700 py-1.5">
                        Lunas Penuh (100%)
                    </span>
                    @endif

                    @if($totalPaidCount > 0)
                    <button type="button" onclick="openCancelDuesModal({{ $mhs->id }}, '{{ addslashes($mhs->name) }}', '{{ $mhs->npm }}', {{ json_encode($paidWeeksList) }})" 
                            class="py-1.5 px-2.5 text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-semibold transition min-h-[38px] flex items-center justify-center">
                        Batal
                    </button>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <div class="p-6 text-center text-slate-600">
                <p class="text-xs font-semibold text-slate-700">Belum ada mahasiswa terdaftar pada semester ini.</p>
                <p class="text-[11px] text-slate-600 mt-0.5">Data iuran akan tampil otomatis setelah akun mahasiswa ditambahkan.</p>
            </div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop (Flat & Editorial) -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar w-full">
                <table class="w-full text-left text-xs border-collapse min-w-[650px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-4 py-3 border-r border-slate-200 w-12 text-center">No</th>
                            <th class="px-4 py-3 border-r border-slate-200 min-w-[180px]">Mahasiswa (NPM)</th>
                            @for($w = 1; $w <= 4; $w++)
                            @php $isCurrentWeek = ($isCurrentRealMonth && $w === $realActiveWeek); @endphp
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center min-w-[105px] {{ $isCurrentWeek ? 'bg-slate-900 text-white font-bold' : '' }}">
                                <div class="flex items-center justify-center gap-1">
                                    <span>Minggu {{ $w }}</span>
                                    @if($isCurrentWeek)
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    @endif
                                </div>
                                <div class="text-[10px] {{ $isCurrentWeek ? 'text-slate-300' : 'text-slate-500' }} font-mono font-normal mt-0.5">
                                    {{ $weekRanges[$w]['date_range'] ?? '' }} {{ $namaBulan }}
                                </div>
                            </th>
                            @endfor
                            <th class="px-4 py-3 border-r border-slate-200 text-center w-28">Status</th>
                            @if(auth()->user()->role === 'bendahara')
                            <th class="px-4 py-3 text-center w-36">Aksi Iuran</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($students as $idx => $mhs)
                        @php
                            $paidWeeksList = $mhs->duesPayments->pluck('week_number')->map(function($w){ return (int)$w; })->toArray();
                            $totalPaidCount = count($paidWeeksList);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 border-r border-slate-200 text-center text-slate-500 font-mono whitespace-nowrap">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 border-r border-slate-200 min-w-[180px]">
                                <div class="font-semibold text-slate-900 leading-snug">{{ $mhs->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }}</div>
                            </td>

                            @for($w = 1; $w <= 4; $w++)
                            @php $isPaid = in_array($w, $paidWeeksList); @endphp
                            <td class="px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                                @if($isPaid)
                                    <span class="text-[11px] font-bold text-emerald-700">Lunas</span>
                                @else
                                    <span class="text-[11px] font-medium text-rose-600">Belum</span>
                                @endif
                            </td>
                            @endfor

                            <td class="px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                                <span class="text-xs font-bold font-mono tabular-nums {{ $totalPaidCount === 4 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $totalPaidCount }}/4 Minggu
                                </span>
                            </td>

                            @if(auth()->user()->role === 'bendahara')
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2.5">
                                    @if($totalPaidCount < 4)
                                    <button onclick="openPayDuesModal({{ $mhs->id }}, '{{ addslashes($mhs->name) }}', '{{ $mhs->npm }}', {{ json_encode($paidWeeksList) }})" 
                                            class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-medium rounded-md transition">
                                        Bayar
                                    </button>
                                    @else
                                    <span class="text-[11px] text-emerald-700 font-semibold">Lunas</span>
                                    @endif

                                    @if($totalPaidCount > 0)
                                    <button onclick="openCancelDuesModal({{ $mhs->id }}, '{{ addslashes($mhs->name) }}', '{{ $mhs->npm }}', {{ json_encode($paidWeeksList) }})" 
                                            class="text-rose-600 hover:text-rose-800 hover:underline text-[11px] font-semibold transition"
                                            title="Batalkan pembayaran minggu tertentu">
                                        Batal
                                    </button>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'bendahara' ? '8' : '7' }}" class="px-4 py-10 text-center text-slate-600">
                                <p class="text-xs font-semibold text-slate-700">Belum ada mahasiswa terdaftar pada semester ini.</p>
                                <p class="text-[11px] text-slate-600 mt-0.5">Data iuran akan tampil otomatis setelah akun mahasiswa ditambahkan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>


</div>

<!-- MODAL INPUT & BAYAR KAS (HANYA BENDAHARA) -->
@if(auth()->user()->role === 'bendahara' && !$isReadOnly)
<div id="modal-kas" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 {{ (session('error_modal') === 'kas' || ($errors->any() && $errors->hasAny(['type', 'amount', 'date', 'description', 'proof_image']))) ? '' : 'hidden' }} p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md max-h-[90dvh] flex flex-col p-5 sm:p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Catat Transaksi Kas Baru</h3>
                <span class="text-[11px] text-slate-500 font-semibold">{{ $selectedSemester }} &bull; Kelas {{ $kelasLabel }}</span>
            </div>
            <button onclick="document.getElementById('modal-kas').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold cursor-pointer p-1" aria-label="Tutup modal catat transaksi">&times;</button>
        </div>

        @if(session('error_modal') === 'kas' && $errors->any())
        <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs shrink-0">
            <p class="font-bold text-rose-900 mb-1">Gagal Menyimpan Transaksi:</p>
            <ul class="list-disc pl-4 space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('kas.store') }}" enctype="multipart/form-data" class="space-y-4 overflow-y-auto flex-1 custom-scrollbar pr-1" onsubmit="return validateAndConfirmKas(event, this)">
            @csrf
            <input type="hidden" name="semester" value="{{ $selectedSemester }}">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Transaksi <span class="text-rose-500">*</span></label>
                <select name="type" id="type-select" required onchange="handleTypeChange(this.value)"
                        class="w-full text-xs px-3 py-2 border {{ $errors->has('type') ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                    <option value="pemasukan" {{ old('type') === 'pemasukan' ? 'selected' : '' }}>Pemasukan (Debit / Kas Masuk)</option>
                    <option value="pengeluaran" {{ old('type') === 'pengeluaran' ? 'selected' : '' }}>Pengeluaran (Kredit / Belanja Operasional)</option>
                </select>
                @error('type')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="amount" id="input-amount" required min="500" value="{{ old('amount') }}" placeholder="Contoh: 50000" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('amount') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('amount')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p id="liquidity-warning" class="text-[11px] text-rose-600 mt-1 font-semibold hidden items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0 inline-block text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Peringatan: Nominal ini melebihi sisa saldo kas aktif semester ini!</span>
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" min="2024-01-01" max="2028-12-31" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('date') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('date')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Transaksi <span class="text-rose-500">*</span></label>
                <input type="text" name="description" required value="{{ old('description') }}" placeholder="Contoh: Pembelian Spidol & Kebutuhan Kelas" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('description') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('description')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Upload Foto Nota / Bukti <span class="text-rose-500 font-normal">(JPG/PNG, Maks. 2MB)</span>
                </label>
                <input type="file" name="proof_image" accept="image/png, image/jpeg, image/jpg" 
                       class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @error('proof_image')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-kas').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" id="btn-submit-kas" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL BAYAR IURAN MINGGUAN -->
<div id="modal-pay-dues" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md max-h-[90dvh] flex flex-col p-5 sm:p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Pembayaran Iuran Kas Mahasiswa</h3>
                <span class="text-[11px] text-slate-500 font-semibold">{{ $selectedSemester }} &bull; Kelas {{ $kelasLabel }}</span>
            </div>
            <button onclick="document.getElementById('modal-pay-dues').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold p-1 cursor-pointer" aria-label="Tutup modal bayar iuran">&times;</button>
        </div>

        <form method="POST" action="{{ route('kas.payDues') }}" class="space-y-4 overflow-y-auto flex-1 custom-scrollbar pr-1" onsubmit="return validateAndConfirmPayDues(event, this)">
            @csrf
            <input type="hidden" name="user_id" id="dues-user-id">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="semester" value="{{ $selectedSemester }}">

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <div class="text-xs text-slate-500">Mahasiswa:</div>
                <div class="font-bold text-slate-900 text-sm" id="dues-student-name">-</div>
                <div class="text-xs text-slate-600 font-mono" id="dues-student-npm">-</div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Periode: <strong>{{ \Carbon\Carbon::create(null, $selectedMonth, 1)->translatedFormat('F') }} {{ $selectedYear }}</strong> &bull; {{ $selectedSemester }}
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Pilih Minggu yang Akan Dibayar:</label>
                    <button type="button" onclick="selectAllUnpaidWeeks()" class="text-[11px] text-blue-600 font-semibold hover:underline cursor-pointer">Pilih Semua Belum Lunas</button>
                </div>
                
                <div class="grid grid-cols-2 gap-2" id="weeks-checkbox-container">
                    @for($w = 1; $w <= 4; $w++)
                    @php $isCurrentWeek = ($isCurrentRealMonth && $w === $realActiveWeek); @endphp
                    <label id="label-week-{{ $w }}" class="flex items-center p-2.5 border rounded-lg cursor-pointer hover:bg-slate-50 transition {{ $isCurrentWeek ? 'border-slate-800 bg-slate-50/70' : 'border-slate-200' }}">
                        <input type="checkbox" name="weeks[]" value="{{ $w }}" id="checkbox-week-{{ $w }}" 
                               onchange="calculateTotalDues()" 
                               class="week-checkbox rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <div class="ml-2 min-w-0">
                            <div class="text-xs font-semibold text-slate-800 flex items-center gap-1">
                                <span>Minggu {{ $w }}</span>
                                @if($isCurrentWeek)
                                <span class="text-[9px] font-bold text-slate-900 bg-white border border-slate-300 px-1 rounded">Ini</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono">{{ $weekRanges[$w]['date_range'] ?? '' }} {{ $namaBulan }}</div>
                        </div>
                        <span id="badge-week-{{ $w }}" class="ml-auto text-[10px] hidden font-semibold text-emerald-600 shrink-0">Lunas</span>
                    </label>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Per Minggu (Rp)</label>
                    <input type="number" name="amount_per_week" id="dues-rate" value="{{ $duesRate }}" min="500" step="500" 
                           oninput="calculateTotalDues()" required
                           class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Bayar</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" min="2024-01-01" max="2028-12-31" required
                           class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between">
                <div>
                    <span class="text-xs text-emerald-800 font-semibold">Total Tagihan:</span>
                    <div class="text-[11px] text-emerald-600" id="total-weeks-label">0 Minggu dipilih</div>
                </div>
                <div class="text-base font-black text-emerald-800" id="total-dues-amount">Rp 0</div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 shrink-0">
                <button type="button" onclick="document.getElementById('modal-pay-dues').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50 cursor-pointer">Batal</button>
                <button type="submit" id="btn-submit-dues" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition cursor-pointer">Konfirmasi Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL BATALKAN IURAN MINGGUAN (BENDAHARA) -->
<div id="modal-cancel-dues" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md max-h-[90dvh] flex flex-col p-5 sm:p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-rose-600">Batalkan Status Iuran</h3>
                <span class="text-[11px] text-slate-500 font-medium">{{ $selectedSemester }} &bull; Kelas {{ $kelasLabel }}</span>
            </div>
            <button onclick="document.getElementById('modal-cancel-dues').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold p-1 cursor-pointer" aria-label="Tutup modal pembatalan iuran">&times;</button>
        </div>

        <form method="POST" action="{{ route('kas.cancelDues') }}" class="space-y-4 overflow-y-auto flex-1 custom-scrollbar pr-1" onsubmit="return validateAndConfirmCancelDues(event, this)">
            @csrf
            <input type="hidden" name="user_id" id="cancel-dues-user-id">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="semester" value="{{ $selectedSemester }}">

            <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs">
                <div class="text-slate-500">Mahasiswa:</div>
                <div class="font-bold text-slate-900 text-sm" id="cancel-dues-student-name">-</div>
                <div class="text-slate-600 font-mono" id="cancel-dues-student-npm">-</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Minggu yang Dibatalkan:</label>
                <div id="cancel-weeks-container" class="space-y-1.5">
                    <!-- Populated by JS -->
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 shrink-0">
                <button type="button" onclick="document.getElementById('modal-cancel-dues').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50 cursor-pointer">Tutup</button>
                <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition cursor-pointer">Batalkan Iuran</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PENGATURAN TARIF IURAN MINGGUAN (BENDAHARA) -->
<div id="modal-setting-dues-rate" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs {{ (session('error_modal') === 'dues_rate' || $errors->has('amount_per_week')) ? '' : 'hidden' }} p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-sm p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Tarif Iuran Mingguan</h3>
                <p class="text-[11px] text-slate-500">Atur nominal kas kelas per minggu</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-setting-dues-rate').classList.add('hidden')" class="text-slate-500 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100" aria-label="Tutup modal pengaturan tarif">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if(session('error_modal') === 'dues_rate' && $errors->any())
        <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs">
            <ul class="list-disc pl-4 space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('kas.updateDuesRate') }}" class="space-y-4" onsubmit="return confirm('Apakah Anda yakin ingin memperbarui tarif uang kas mingguan menjadi Rp ' + parseInt(document.getElementById('input-setting-dues-rate').value || 0).toLocaleString('id-ID') + ' per minggu?')">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Nominal Iuran Baru (Rp/Minggu) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-500">Rp</span>
                    <input type="number" 
                           name="amount_per_week" 
                           id="input-setting-dues-rate" 
                           value="{{ old('amount_per_week', $duesRate) }}" 
                           min="500" 
                           max="1000000"
                           step="500" 
                           required 
                           class="w-full text-xs pl-9 pr-3 py-2.5 border {{ $errors->has('amount_per_week') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold text-slate-900">
                </div>
                @error('amount_per_week')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p class="text-[10px] text-slate-500 mt-1">Tarif saat ini: <strong>Rp {{ number_format($duesRate, 0, ',', '.') }}</strong> / minggu.</p>
            </div>

            <!-- Quick Choice Buttons -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1.5">Pilihan Cepat:</label>
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach([2000, 5000, 10000, 15000] as $preset)
                    <button type="button" 
                            onclick="document.getElementById('input-setting-dues-rate').value = {{ $preset }}" 
                            class="px-2 py-1.5 text-[11px] font-semibold rounded border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 transition {{ $duesRate == $preset ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold' : 'text-slate-600 bg-slate-50' }}">
                        {{ number_format($preset / 1000, 0) }}k
                    </button>
                    @endforeach
                </div>
            </div>


            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-setting-dues-rate').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 shadow-sm transition">Simpan Tarif</button>
            </div>
        </form>
    </div>
</div>


@endif

@push('scripts')
<script>

    function validateAndConfirmKas(e, form) {
        const type = form.type.value;
        const amount = parseFloat(form.amount.value) || 0;
        const desc = form.description.value.trim();
        if (amount < 500) {
            alert('Nominal transaksi kas minimal Rp 500.');
            return false;
        }
        if (!desc) {
            alert('Mohon isi keterangan transaksi.');
            return false;
        }
        const typeLabel = type === 'pemasukan' ? 'Pemasukan (Debit)' : 'Pengeluaran (Kredit)';
        return confirm(`Konfirmasi Transaksi Kas:\n\n• Jenis: ${typeLabel}\n• Nominal: Rp ${amount.toLocaleString('id-ID')}\n• Keterangan: ${desc}\n\nApakah data ini sudah benar dan ingin disimpan ke sistem?`);
    }

    function validateAndConfirmPayDues(e, form) {
        const checked = form.querySelectorAll('.week-checkbox:checked:not(:disabled)');
        if (checked.length === 0) {
            alert('Peringatan: Silakan pilih minimal 1 minggu yang akan dibayarkan.');
            return false;
        }
        const student = document.getElementById('dues-student-name').innerText;
        const total = document.getElementById('total-dues-amount').innerText;
        return confirm(`Konfirmasi Pembayaran Uang Kas:\n\n• Mahasiswa: ${student}\n• Total: ${total} (${checked.length} minggu)\n\nApakah Anda yakin ingin memproses pembayaran ini?`);
    }

    function validateAndConfirmCancelDues(e, form) {
        const selected = form.querySelector('input[name="week_number"]:checked');
        if (!selected) {
            alert('Pilih minggu iuran yang ingin dibatalkan.');
            return false;
        }
        const student = document.getElementById('cancel-dues-student-name').innerText;
        return confirm(`PERINGATAN PEMBATALAN IURAN:\n\nApakah Anda yakin ingin membatalkan status lunas Minggu ke-${selected.value} untuk mahasiswa ${student}?`);
    }

    let activePaidWeeks = [];

    function openPayDuesModal(userId, name, npm, paidWeeks) {
        document.getElementById('dues-user-id').value = userId;
        document.getElementById('dues-student-name').textContent = name;
        document.getElementById('dues-student-npm').textContent = 'NPM: ' + npm;
        activePaidWeeks = paidWeeks.map(Number);

        for (let w = 1; w <= 4; w++) {
            const chk = document.getElementById('checkbox-week-' + w);
            const badge = document.getElementById('badge-week-' + w);
            const label = document.getElementById('label-week-' + w);

            if (activePaidWeeks.includes(w)) {
                chk.checked = false;
                chk.disabled = true;
                badge.classList.remove('hidden');
                label.classList.add('bg-slate-100', 'opacity-60', 'cursor-not-allowed');
            } else {
                chk.checked = false;
                chk.disabled = false;
                badge.classList.add('hidden');
                label.classList.remove('bg-slate-100', 'opacity-60', 'cursor-not-allowed');
            }
        }

        for (let w = 1; w <= 4; w++) {
            const chk = document.getElementById('checkbox-week-' + w);
            if (!chk.disabled) {
                chk.checked = true;
                break;
            }
        }

        calculateTotalDues();
        document.getElementById('modal-pay-dues').classList.remove('hidden');
    }

    function openCancelDuesModal(userId, name, npm, paidWeeks) {
        document.getElementById('cancel-dues-user-id').value = userId;
        document.getElementById('cancel-dues-student-name').textContent = name;
        document.getElementById('cancel-dues-student-npm').textContent = 'NPM: ' + npm;

        const container = document.getElementById('cancel-weeks-container');
        container.innerHTML = '';

        paidWeeks.forEach(function(w) {
            const div = document.createElement('label');
            div.className = 'flex items-center p-2.5 border border-rose-200 rounded-lg cursor-pointer hover:bg-rose-50 transition text-xs';
            div.innerHTML = `
                <input type="radio" name="week_number" value="${w}" required class="text-rose-600 focus:ring-rose-500">
                <span class="ml-2 font-medium text-slate-800">Batalkan Minggu ke-${w}</span>
            `;
            container.appendChild(div);
        });

        document.getElementById('modal-cancel-dues').classList.remove('hidden');
    }

    function selectAllUnpaidWeeks() {
        for (let w = 1; w <= 4; w++) {
            const chk = document.getElementById('checkbox-week-' + w);
            if (!chk.disabled) {
                chk.checked = true;
            }
        }
        calculateTotalDues();
    }

    function calculateTotalDues() {
        const rate = parseFloat(document.getElementById('dues-rate')?.value) || 0;
        let selectedCount = 0;

        for (let w = 1; w <= 4; w++) {
            const chk = document.getElementById('checkbox-week-' + w);
            if (chk && chk.checked && !chk.disabled) {
                selectedCount++;
            }
        }

        const total = selectedCount * rate;
        const totalWeeksLabel = document.getElementById('total-weeks-label');
        const totalDuesAmount = document.getElementById('total-dues-amount');
        if (totalWeeksLabel) totalWeeksLabel.textContent = selectedCount + ' Minggu dipilih';
        if (totalDuesAmount) totalDuesAmount.textContent = 'Rp ' + total.toLocaleString('id-ID');

        const btn = document.getElementById('btn-submit-dues');
        if (btn) {
            btn.disabled = selectedCount === 0;
            if (selectedCount === 0) {
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Keyboard navigation: Escape key closes any open modal (R-32)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = ['modal-kas', 'modal-pay-dues', 'modal-cancel-dues', 'modal-setting-dues-rate'];
            modals.forEach(function(id) {
                const el = document.getElementById(id);
                if (el && !el.classList.contains('hidden')) {
                    el.classList.add('hidden');
                }
            });
        }
    });
</script>
@endpush
@endsection
