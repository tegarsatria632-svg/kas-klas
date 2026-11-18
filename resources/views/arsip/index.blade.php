@extends('layouts.app')

@section('title', 'Pusat Arsip & Rekapitulasi Kas Kelas')

@section('content')
<div class="space-y-5 sm:space-y-6 max-w-full">
    <!-- Open Header & Mode Selector (Unboxed - Tanpa Kotak Bertumpuk) -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-1">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Arsip Kas &amp; Rekapitulasi</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Pusat Arsip Kas &bull; Kelas {{ $kelasLabel }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Mode Rekap: <strong class="text-slate-800">{{ $periodLabel }}</strong> &bull; {{ $periodSubLabel }}
            </p>
        </div>

        <!-- Mode Switcher Tabs + Form (Clean Open Pill Controls) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="inline-flex p-1 bg-slate-200/70 rounded-xl self-start sm:self-auto">
                <a href="{{ route('arsip.index', ['period_mode' => 'bulan', 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                   class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $periodMode === 'bulan' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Per Bulan
                </a>
                <a href="{{ route('arsip.index', ['period_mode' => 'semester', 'semester' => $selectedSemester]) }}" 
                   class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $periodMode === 'semester' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Per Semester
                </a>
                <a href="{{ route('arsip.index', ['period_mode' => 'semua']) }}" 
                   class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $periodMode === 'semua' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua Periode
                </a>
            </div>

            <!-- Dropdown Filter Sesuai Mode -->
            <form method="GET" action="{{ route('arsip.index') }}" class="flex items-center gap-1.5">
                <input type="hidden" name="period_mode" value="{{ $periodMode }}">
                @if($periodMode === 'bulan')
                    <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Semester">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                    <select name="month" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Bulan">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }} {{ ($m === $realCurrentMonth && $selectedYear === $realCurrentYear) ? '(Bulan Ini)' : '' }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900" aria-label="Pilih Tahun">
                        @foreach(\App\Services\AcademicService::TAHUN_LIST as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                {{ $y }} {{ $y === $realCurrentYear ? '(Tahun Ini)' : '' }}
                            </option>
                        @endforeach
                    </select>
                @elseif($periodMode === 'semester')
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-500">Pilih Semester:</span>
                        <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 shadow-2xs focus:ring-2 focus:ring-slate-900">
                            @foreach($semesters as $sem)
                                <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>
                                    {{ $sem }} {{ $sem === $activeClassSemester ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Panel Terpadu Finansial (Satu Panel Bersih dengan Hairline Dividers, Bukan 4 Kotak Terpisah) -->
    <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs">
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100 p-4 sm:p-5">
            <!-- Saldo Periode -->
            <div class="md:pr-5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Saldo Kas Periode</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $periodSaldo >= 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                        {{ $periodSaldo >= 0 ? 'Surplus' : 'Defisit' }}
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1.5 font-mono tabular-nums">
                    Rp {{ number_format($periodSaldo, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1 font-medium">{{ $periodLabel }}</p>
            </div>

            <!-- Total Pemasukan -->
            <div class="py-3 md:py-0 md:px-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Pemasukan</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700 mt-1.5 font-mono tabular-nums">
                    Rp {{ number_format($periodPemasukan, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1 font-medium">Iuran &amp; pendapatan periode ini</p>
            </div>

            <!-- Total Pengeluaran -->
            <div class="pt-3 md:pt-0 md:pl-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Pengeluaran</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-rose-700 mt-1.5 font-mono tabular-nums">
                    Rp {{ number_format($periodPengeluaran, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1 font-medium">Operasional &amp; belanja kelas</p>
            </div>
        </div>

        @if($periodMode !== 'semua')
        <div class="px-5 py-3 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-600">
            <span>Saldo Kumulatif {{ $selectedSemester }}: <strong class="text-slate-900 font-bold font-mono tabular-nums">Rp {{ number_format($saldoSemester, 0, ',', '.') }}</strong></span>
            <span>Total Saldo Seluruh Waktu (All-Time): <strong class="text-slate-900 font-bold font-mono tabular-nums">Rp {{ number_format($totalSaldoAll, 0, ',', '.') }}</strong></span>
        </div>
        @endif
    </div>

    {{-- KONTEN REKAPITULASI SESUAI MODE PERIODE --}}

    {{-- 1. MODE PER BULAN: MATRIKS IURAN MINGGUAN (BULAN TERPILIH) --}}
    @if($periodMode === 'bulan')
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Rekap Matriks Iuran Mingguan &bull; {{ $namaBulan }} {{ $selectedYear }}
                    </h2>
                    @if($isCurrentRealMonth)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-900 text-white shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Minggu ke-{{ $realActiveWeek }} ({{ $weekRanges[$realActiveWeek]['date_range'] ?? '' }} {{ $namaBulan }}) &bull; Minggu Ini</span>
                    </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">
                    {{ $selectedSemester }} (Kelas {{ $kelasLabel }}) &bull; Lunas: <strong class="text-emerald-700 font-mono tabular-nums">{{ $totalPaidStudentsInPeriod }}</strong> mhs &bull; Belum Lunas: <strong class="text-rose-700 font-mono tabular-nums">{{ $totalUnpaidStudentsInPeriod }}</strong> mhs
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-slate-600 font-medium">
                    Tarif: <strong>Rp {{ number_format($duesRate, 0, ',', '.') }}/mgg</strong> (Rp {{ number_format($duesRate * 4, 0, ',', '.') }}/bln)
                </span>
            </div>
        </div>

        <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($students as $idx => $mhs)
            @php
                $paidWeeksList = $mhs->duesPayments->pluck('week_number')->map(fn($w) => (int)$w)->toArray();
                $totalPaidCount = count($paidWeeksList);
            @endphp
            <div class="p-2.5 sm:p-3.5 space-y-1.5 sm:space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-xs sm:text-sm leading-tight truncate">{{ $mhs->name }}</div>
                        <div class="text-[10px] sm:text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                    </div>
                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-bold shrink-0 font-mono tabular-nums
                        {{ $totalPaidCount === 4 ? 'bg-emerald-100 text-emerald-800' : ($totalPaidCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                        {{ $totalPaidCount }}/4 Minggu
                    </span>
                </div>

                <div class="grid grid-cols-4 gap-1 sm:gap-1.5">
                    @for($w = 1; $w <= 4; $w++)
                    @php 
                        $isPaid = in_array($w, $paidWeeksList); 
                        $isCurrentWeek = ($isCurrentRealMonth && $w === $realActiveWeek);
                    @endphp
                    <div class="py-1 px-1 sm:py-1.5 sm:px-2 rounded-lg text-center border transition-all relative
                        {{ $isPaid ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50/60 border-rose-200 text-rose-700' }}
                        {{ $isCurrentWeek ? 'ring-[1.5px] ring-slate-900 border-slate-900 shadow-2xs font-bold' : '' }}">
                        <div class="flex items-center justify-center gap-0.5">
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase font-mono leading-none">M{{ $w }}</span>
                            @if($isCurrentWeek)
                            <span class="w-1 h-1 rounded-full bg-emerald-500 shrink-0"></span>
                            @endif
                        </div>
                        @if(!empty($weekRanges[$w]['date_range']))
                        <div class="text-[7.5px] sm:text-[8.5px] text-slate-500 font-mono leading-none mt-0.5 truncate">
                            {{ $weekRanges[$w]['date_range'] }}
                        </div>
                        @endif
                        <div class="text-[9.5px] sm:text-[10.5px] font-bold leading-tight mt-0.5">
                            @if($isPaid)
                                <span class="text-emerald-800">Lunas</span>
                            @else
                                <span class="text-rose-700">Belum</span>
                            @endif
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada data mahasiswa terdaftar pada periode ini.</div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
        <div class="hidden md:block overflow-x-auto w-full scrollbar-thin">
            <table class="w-full text-left text-xs border-collapse min-w-[620px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-12 text-center whitespace-nowrap">No</th>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[180px] whitespace-nowrap">Mahasiswa (NPM)</th>
                        @for($w = 1; $w <= 4; $w++)
                        @php $isCurrentWeek = ($isCurrentRealMonth && $w === $realActiveWeek); @endphp
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap {{ $isCurrentWeek ? 'bg-slate-900 text-white font-bold' : '' }}">
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
                        <th class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">Status Kepatuhan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($students as $idx => $mhs)
                    @php
                        $paidWeeksList = $mhs->duesPayments->pluck('week_number')->map(fn($w) => (int)$w)->toArray();
                        $totalPaidCount = count($paidWeeksList);
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-500 whitespace-nowrap">{{ $idx + 1 }}</td>
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[180px]">
                            <div class="font-bold text-slate-900 leading-snug">{{ $mhs->name }}</div>
                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                        </td>

                        @for($w = 1; $w <= 4; $w++)
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                            @if(in_array($w, $paidWeeksList))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    Lunas
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-600 border border-rose-200">
                                    Belum
                                </span>
                            @endif
                        </td>
                        @endfor

                        <td class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">
                            <span class="inline-block px-2.5 py-1 rounded text-xs font-bold {{ $totalPaidCount === 4 ? 'bg-emerald-100 text-emerald-800' : ($totalPaidCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                {{ $totalPaidCount }}/4 Minggu
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-600 font-medium">Belum ada data mahasiswa terdaftar pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 2. MODE PER SEMESTER: RINCIAN MUTASI KAS PER BULAN (FULL-WIDTH) --}}
    @elseif($periodMode === 'semester')
    <div class="space-y-5 sm:space-y-6">
        <!-- Rincian Mutasi Kas Per Bulan di Semester Terpilih (Full Width) -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Rincian Mutasi Kas Per Bulan &bull; {{ $selectedSemester }}
                    </h2>
                    <p class="text-[11px] text-slate-600 mt-0.5">Rangkuman finansial per bulan kalender sepanjang {{ $selectedSemester }}</p>
                </div>
            </div>

            <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($semesterMonthlyRecap as $idx => $sm)
                <div class="p-3.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm">{{ $sm['month_name'] }}</span>
                        <span class="text-xs text-slate-500 font-mono">{{ $sm['tx_count'] }} transaksi</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div class="bg-emerald-50/60 p-2 rounded-lg border border-emerald-100">
                            <span class="text-[10px] text-emerald-800 font-semibold uppercase block">Masuk</span>
                            <span class="font-bold text-emerald-700">+Rp {{ number_format($sm['pemasukan'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-rose-50/60 p-2 rounded-lg border border-rose-100">
                            <span class="text-[10px] text-rose-800 font-semibold uppercase block">Keluar</span>
                            <span class="font-bold text-rose-700">-Rp {{ number_format($sm['pengeluaran'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <span class="text-slate-600 font-medium">Selisih Bersih:</span>
                        <span class="font-bold {{ $sm['selisih'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                            Rp {{ number_format($sm['selisih'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada transaksi pada semester ini.</div>
                @endforelse
            </div>

            <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
            <div class="hidden md:block overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[560px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-12 text-center whitespace-nowrap">No</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 whitespace-nowrap">Bulan</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Total Pemasukan</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Total Pengeluaran</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Selisih Bersih</th>
                            <th class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">Jumlah Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($semesterMonthlyRecap as $idx => $sm)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-600 whitespace-nowrap">{{ $idx + 1 }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-bold text-slate-800 whitespace-nowrap">
                                {{ $sm['month_name'] }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold text-emerald-700 whitespace-nowrap">
                                +Rp {{ number_format($sm['pemasukan'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold text-rose-700 whitespace-nowrap">
                                -Rp {{ number_format($sm['pengeluaran'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold whitespace-nowrap {{ $sm['selisih'] >= 0 ? 'text-slate-800' : 'text-rose-700' }}">
                                Rp {{ number_format($sm['selisih'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-center text-slate-600 font-mono whitespace-nowrap">
                                {{ $sm['tx_count'] }} transaksi
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-600 font-medium">Belum ada transaksi pada semester ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 3. MODE SEMUA PERIODE (SEMESTER 1 S/D 8): REKAPITULASI KOMPARASI SELURUH SEMESTER --}}
    @elseif($periodMode === 'semua')
    <div class="space-y-5 sm:space-y-6">
        <!-- Tabel Komparasi Seluruh Semester -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Komparasi Finansial Seluruh Semester (Semester 1 s/d Semester 8)
                    </h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Seluruh data historis semester 1 s/d 8 tersimpan permanen di sini dan tidak hilang saat semester dinaikkan</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold px-2.5 py-1 bg-slate-900 text-white rounded-lg">
                        Saldo Kas Menyeluruh: Rp {{ number_format($totalSaldoAll, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
            <div class="block md:hidden divide-y divide-slate-100">
                @foreach($semestersRecap as $sr)
                <div class="p-3.5 space-y-2 {{ $sr['semester'] === $activeClassSemester ? 'bg-indigo-50/20' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="font-bold text-slate-900 text-sm">
                            {{ $sr['semester'] }}
                            @if($sr['semester'] === $activeClassSemester)
                                <span class="ml-1 text-[10px] text-indigo-700 font-bold bg-indigo-100 px-1.5 py-0.5 rounded">Aktif</span>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $sr['saldo'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $sr['saldo'] >= 0 ? 'Surplus' : 'Defisit' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div class="bg-emerald-50/60 p-2 rounded-lg border border-emerald-100">
                            <span class="text-[10px] text-emerald-800 font-semibold uppercase block">Masuk</span>
                            <span class="font-bold text-emerald-700">+Rp {{ number_format($sr['pemasukan'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-rose-50/60 p-2 rounded-lg border border-rose-100">
                            <span class="text-[10px] text-rose-800 font-semibold uppercase block">Keluar</span>
                            <span class="font-bold text-rose-700">-Rp {{ number_format($sr['pengeluaran'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <span class="text-slate-600 font-mono">{{ $sr['tx_count'] }} mutasi</span>
                        <span class="font-bold {{ $sr['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                            Saldo: Rp {{ number_format($sr['saldo'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
            <div class="hidden md:block overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[580px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 whitespace-nowrap">Semester</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Total Pemasukan</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Total Pengeluaran</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Saldo Semester</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">Status</th>
                            <th class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">Mutasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($semestersRecap as $sr)
                        <tr class="hover:bg-slate-50 transition-colors {{ $sr['semester'] === $activeClassSemester ? 'bg-indigo-50/30' : '' }}">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-bold text-slate-800 whitespace-nowrap">
                                {{ $sr['semester'] }}
                                @if($sr['semester'] === $activeClassSemester)
                                    <span class="ml-1 text-[10px] text-indigo-700 font-bold bg-indigo-100 px-1.5 py-0.5 rounded">Semester Aktif</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold text-emerald-700 whitespace-nowrap">
                                +Rp {{ number_format($sr['pemasukan'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold text-rose-700 whitespace-nowrap">
                                -Rp {{ number_format($sr['pengeluaran'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold whitespace-nowrap {{ $sr['saldo'] >= 0 ? 'text-slate-800' : 'text-rose-700' }}">
                                Rp {{ number_format($sr['saldo'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $sr['saldo'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $sr['saldo'] >= 0 ? 'Surplus' : 'Defisit' }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-center text-slate-600 font-mono whitespace-nowrap">
                                {{ $sr['tx_count'] }} mutasi
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Akumulasi Iuran Seluruh Mahasiswa (All-Time) -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Akumulasi Iuran Mahasiswa Sepanjang Waktu (All-Time)
                    </h2>
                    <p class="text-[11px] text-slate-600 mt-0.5">Total iuran yang telah dibayarkan oleh masing-masing anggota kelas sejak awal</p>
                </div>
            </div>

            <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($students as $idx => $mhs)
                @php
                    $paidWeeksCount = $mhs->duesPayments->count();
                    $totalNominalPaid = $paidWeeksCount * $duesRate;
                @endphp
                <div class="p-3.5 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-slate-900 text-sm leading-snug">{{ $mhs->name }}</div>
                            <div class="text-[11px] text-slate-600 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 shrink-0">
                            {{ $mhs->status_akademik ?? 'Aktif' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <span class="text-slate-600">Total Lunas: <strong class="text-slate-900">{{ $paidWeeksCount }} Minggu</strong></span>
                        <span class="font-bold text-emerald-700">Rp {{ number_format($totalNominalPaid, 0, ',', '.') }}</span>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada data mahasiswa terdaftar.</div>
                @endforelse
            </div>

            <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
            <div class="hidden md:block overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[560px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-12 text-center whitespace-nowrap">No</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[200px] whitespace-nowrap">Mahasiswa (NPM)</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">Total Minggu Lunas</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right whitespace-nowrap">Total Dana Masuk</th>
                            <th class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">Status Anggota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($students as $idx => $mhs)
                        @php
                            $paidWeeksCount = $mhs->duesPayments->count();
                            $totalNominalPaid = $paidWeeksCount * $duesRate;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-600 whitespace-nowrap">{{ $idx + 1 }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[200px]">
                                <div class="font-bold text-slate-900 leading-snug">{{ $mhs->name }}</div>
                                <div class="text-[11px] text-slate-600 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center font-bold text-slate-800 whitespace-nowrap">
                                {{ $paidWeeksCount }} Minggu
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold text-emerald-700 whitespace-nowrap">
                                Rp {{ number_format($totalNominalPaid, 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $mhs->status_akademik ?? 'Aktif' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-600 font-medium">Belum ada data mahasiswa terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Riwayat Mutasi Kas Sesuai Mode Periode (Murni Catatan Transaksi, Tanpa Aksi Mutasi) -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center flex-wrap gap-2">
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                    Riwayat Mutasi Kas &bull; {{ $periodLabel }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200/80 text-slate-800">
                    Mode {{ ucfirst($periodMode) }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <!-- Type Filter Tabs (Laporan Jurnal Kas) -->
                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-2xs self-start sm:self-auto">
                    <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                       class="px-2.5 py-1 text-xs rounded-md transition {{ !request('type') ? 'bg-slate-900 text-white font-semibold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">Semua</a>
                    <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear, 'type' => 'pemasukan']) }}" 
                       class="px-2.5 py-1 text-xs rounded-md transition {{ request('type') === 'pemasukan' ? 'bg-emerald-600 text-white font-semibold shadow-2xs' : 'text-slate-600 hover:text-emerald-700 font-medium' }}">Pemasukan</a>
                    <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear, 'type' => 'pengeluaran']) }}" 
                       class="px-2.5 py-1 text-xs rounded-md transition {{ request('type') === 'pengeluaran' ? 'bg-rose-600 text-white font-semibold shadow-2xs' : 'text-slate-600 hover:text-rose-700 font-medium' }}">Pengeluaran</a>
                </div>
            </div>
        </div>

        <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($transactions as $tx)
            <div class="p-3.5 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-600 font-mono">
                        {{ $tx->date->format('d M Y') }}
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                            {{ $tx->semester }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $tx->type === 'pemasukan' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                            {{ ucfirst($tx->type) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900 leading-snug break-words">{{ $tx->description }}</p>
                        <p class="text-[11px] text-slate-600 mt-0.5 font-medium">
                            {{ $tx->user ? $tx->user->name : 'Kas Kelas Umum' }}
                            @if($tx->user)<span class="text-slate-600 font-mono text-[10px]">({{ $tx->user->npm }})</span>@endif
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-bold {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->type === 'pemasukan' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="pt-1 flex items-center justify-between gap-2">
                    @if($tx->proof_image)
                    <button type="button" onclick="openChatbotImageModal('{{ route('kas.nota', $tx->id) }}', 'Bukti Transaksi: {{ addslashes($tx->description) }}')" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:text-blue-900 hover:bg-blue-100 rounded-lg text-xs font-semibold border border-blue-200 transition cursor-pointer min-h-[36px]" title="Lihat Bukti Nota">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Bukti Nota</span>
                    </button>
                    @else
                    <div></div>
                    @endif

                    @if(auth()->user()->role === 'bendahara' && !\App\Services\AcademicService::isHistoricalArchive($tx->semester, \App\Services\AcademicService::getActiveSemester()))
                    <form action="{{ route('kas.destroy', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-semibold border border-rose-200 transition min-h-[36px]" title="Batalkan/Hapus Transaksi">
                            Batalkan
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-600">
                <p class="text-xs font-semibold text-slate-700">Belum ada catatan mutasi kas pada {{ $periodLabel }}.</p>
            </div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
        <div class="hidden md:block overflow-x-auto w-full scrollbar-thin">
            <table class="w-full text-left text-xs border-collapse min-w-[620px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-28 whitespace-nowrap">Tanggal</th>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-24 whitespace-nowrap">Semester</th>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-24 whitespace-nowrap">Tipe</th>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[180px]">Keterangan</th>
                        <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[140px] whitespace-nowrap">Pihak / Mahasiswa</th>
                        <th class="px-3 sm:px-4 py-3 text-right w-36 whitespace-nowrap">Nominal</th>
                        <th class="px-3 sm:px-4 py-3 text-center w-28 whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-600 whitespace-nowrap">{{ $tx->date->format('d M Y') }}</td>
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-700 font-medium whitespace-nowrap">{{ $tx->semester }}</td>
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold {{ $tx->type === 'pemasukan' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ ucfirst($tx->type) }}
                            </span>
                        </td>
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-medium text-slate-800 break-words">
                            <div>{{ $tx->description }}</div>
                            @if($tx->proof_image)
                            <button type="button" onclick="openChatbotImageModal('{{ route('kas.nota', $tx->id) }}', 'Bukti Transaksi: {{ addslashes($tx->description) }}')" class="inline-flex items-center gap-1.5 text-[11px] text-blue-700 hover:text-blue-900 font-semibold mt-1 bg-blue-50 px-2 py-1 rounded-md border border-blue-200 transition cursor-pointer" title="Pratinjau foto nota atau bukti transaksi">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Bukti Nota</span>
                            </button>
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-600 whitespace-nowrap">
                            @if($tx->user)
                                <div class="font-medium text-slate-800 leading-snug">{{ $tx->user->name }}</div>
                                <div class="text-[11px] text-slate-600 font-mono mt-0.5">{{ $tx->user->npm }}</div>
                            @else
                                <span class="text-slate-600 italic">Kas Kelas Umum</span>
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-right font-bold whitespace-nowrap {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->type === 'pemasukan' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">
                            @if(auth()->user()->role === 'bendahara' && !\App\Services\AcademicService::isHistoricalArchive($tx->semester, \App\Services\AcademicService::getActiveSemester()))
                            <form action="{{ route('kas.destroy', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded text-[10px] font-semibold border border-rose-200 transition" title="Batalkan/Hapus Transaksi">
                                    Batal
                                </button>
                            </form>
                            @else
                                <span class="text-slate-500 font-bold">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-600 font-medium">
                            <p class="text-xs font-semibold text-slate-700">Belum ada catatan mutasi kas pada {{ $periodLabel }}.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 overflow-x-auto">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
