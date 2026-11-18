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

        <!-- Mode Switcher Underline Tabs + Form (Flat, Tanpa Pill Box) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
            <nav class="flex items-center gap-5 border-b border-slate-200 self-start sm:self-auto" aria-label="Mode Rekapitulasi">
                <a href="{{ route('arsip.index', ['period_mode' => 'bulan', 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                   class="pb-2 -mb-px text-xs transition-colors border-b-2 {{ $periodMode === 'bulan' ? 'border-slate-900 text-slate-900 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium' }}">
                    Per Bulan
                </a>
                <a href="{{ route('arsip.index', ['period_mode' => 'semester', 'semester' => $selectedSemester]) }}" 
                   class="pb-2 -mb-px text-xs transition-colors border-b-2 {{ $periodMode === 'semester' ? 'border-slate-900 text-slate-900 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium' }}">
                    Per Semester
                </a>
                <a href="{{ route('arsip.index', ['period_mode' => 'semua']) }}" 
                   class="pb-2 -mb-px text-xs transition-colors border-b-2 {{ $periodMode === 'semua' ? 'border-slate-900 text-slate-900 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium' }}">
                    Semua Periode
                </a>
            </nav>

            <!-- Dropdown Filter Sesuai Mode -->
            <form method="GET" action="{{ route('arsip.index') }}" class="flex items-center gap-1.5 flex-wrap">
                <input type="hidden" name="period_mode" value="{{ $periodMode }}">
                @if($periodMode === 'bulan')
                    <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-slate-900" aria-label="Pilih Semester">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                    <select name="month" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-slate-900" aria-label="Pilih Bulan">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }} {{ ($m === $realCurrentMonth && $selectedYear === $realCurrentYear) ? '(Bulan Ini)' : '' }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-slate-900" aria-label="Pilih Tahun">
                        @foreach(\App\Services\AcademicService::TAHUN_LIST as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                {{ $y }} {{ $y === $realCurrentYear ? '(Tahun Ini)' : '' }}
                            </option>
                        @endforeach
                    </select>
                @elseif($periodMode === 'semester')
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-600">Semester:</span>
                        <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-slate-900">
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

    <!-- Strip Finansial Terbuka (Tanpa Outer Card, Hairline Dividers) -->
    <section class="border-y border-slate-200 py-4 sm:py-5">
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-200/80">
            <!-- Saldo Periode -->
            <div class="pb-3.5 md:pb-0 md:pr-6">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Saldo Kas Periode</span>
                    <span class="text-[11px] font-bold {{ $periodSaldo >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $periodSaldo >= 0 ? 'Surplus' : 'Defisit' }}
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 font-mono tabular-nums">
                    Rp {{ number_format($periodSaldo, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">{{ $periodLabel }}</p>
            </div>

            <!-- Total Pemasukan -->
            <div class="py-3.5 md:py-0 md:px-6">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Pemasukan</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700 mt-1 font-mono tabular-nums">
                    Rp {{ number_format($periodPemasukan, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Iuran &amp; pendapatan periode ini</p>
            </div>

            <!-- Total Pengeluaran -->
            <div class="pt-3.5 md:pt-0 md:pl-6">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Pengeluaran</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-rose-700 mt-1 font-mono tabular-nums">
                    Rp {{ number_format($periodPengeluaran, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Operasional &amp; belanja kelas</p>
            </div>
        </div>

        @if($periodMode !== 'semua')
        <div class="pt-3 mt-4 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-xs text-slate-600">
            <span>Saldo Kumulatif {{ $selectedSemester }}: <strong class="text-slate-900 font-bold font-mono tabular-nums">Rp {{ number_format($saldoSemester, 0, ',', '.') }}</strong></span>
            <span>Total Saldo Seluruh Waktu (All-Time): <strong class="text-slate-900 font-bold font-mono tabular-nums">Rp {{ number_format($totalSaldoAll, 0, ',', '.') }}</strong></span>
        </div>
        @endif
    </section>

    {{-- KONTEN REKAPITULASI SESUAI MODE PERIODE --}}

    {{-- 1. MODE PER BULAN: MATRIKS IURAN MINGGUAN (BULAN TERPILIH) --}}
    @if($periodMode === 'bulan')
    <section class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                        Rekap Matriks Iuran Mingguan &bull; {{ $namaBulan }} {{ $selectedYear }}
                    </h2>
                    @if($isCurrentRealMonth)
                    <span class="text-slate-300 hidden sm:inline">|</span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <span>Minggu ke-{{ $realActiveWeek }} ({{ $weekRanges[$realActiveWeek]['date_range'] ?? '' }} {{ $namaBulan }})</span>
                    </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-600 mt-1">
                    {{ $selectedSemester }} (Kelas {{ $kelasLabel }}) &bull; Lunas: <strong class="text-emerald-700 font-mono tabular-nums">{{ $totalPaidStudentsInPeriod }}</strong> mhs &bull; Belum Lunas: <strong class="text-rose-700 font-mono tabular-nums">{{ $totalUnpaidStudentsInPeriod }}</strong> mhs
                </p>
            </div>
            <span class="text-[11px] text-slate-600 font-medium">
                Tarif: <strong class="text-slate-900 font-mono">Rp {{ number_format($duesRate, 0, ',', '.') }}/mgg</strong> (Rp {{ number_format($duesRate * 4, 0, ',', '.') }}/bln)
            </span>
        </div>

        <!-- Tampilan Daftar Ringkas Mobile -->
        <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
            @forelse($students as $idx => $mhs)
            @php
                $paidWeeksList = $mhs->duesPayments->pluck('week_number')->map(fn($w) => (int)$w)->toArray();
                $totalPaidCount = count($paidWeeksList);
            @endphp
            <div class="px-4 py-3 sm:p-3.5 space-y-2">
                <div class="flex items-baseline justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-xs sm:text-sm leading-tight truncate">{{ $mhs->name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                    </div>
                    <span class="text-[11px] font-bold shrink-0 font-mono tabular-nums
                        {{ $totalPaidCount === 4 ? 'text-emerald-700' : ($totalPaidCount > 0 ? 'text-amber-700' : 'text-rose-700') }}">
                        {{ $totalPaidCount }}/4 Minggu
                    </span>
                </div>

                <!-- 1 Strip Segmented 4 Minggu -->
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
                            <span class="w-1 h-1 rounded-full bg-emerald-600 shrink-0"></span>
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
            </div>
            @empty
            <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada data mahasiswa terdaftar pada periode ini.</div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[620px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
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
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-500 font-mono whitespace-nowrap">{{ $idx + 1 }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[180px]">
                                <div class="font-semibold text-slate-900 leading-snug">{{ $mhs->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                            </td>

                            @for($w = 1; $w <= 4; $w++)
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                                @if(in_array($w, $paidWeeksList))
                                    <span class="text-[11px] font-bold text-emerald-700">Lunas</span>
                                @else
                                    <span class="text-[11px] font-medium text-rose-600">Belum</span>
                                @endif
                            </td>
                            @endfor

                            <td class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">
                                <span class="text-xs font-bold font-mono tabular-nums {{ $totalPaidCount === 4 ? 'text-emerald-700' : ($totalPaidCount > 0 ? 'text-amber-700' : 'text-rose-700') }}">
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
    </section>

    {{-- 2. MODE PER SEMESTER: RINCIAN MUTASI KAS PER BULAN (FULL-WIDTH) --}}
    @elseif($periodMode === 'semester')
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                Rincian Mutasi Kas Per Bulan &bull; {{ $selectedSemester }}
            </h2>
            <p class="text-[11px] text-slate-600 mt-0.5">Rangkuman finansial per bulan kalender sepanjang {{ $selectedSemester }}</p>
        </div>

        <!-- Tampilan Daftar Ringkas Mobile (Flat, Tanpa Mini-Box Bertumpuk) -->
        <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
            @forelse($semesterMonthlyRecap as $idx => $sm)
            <div class="px-4 py-3 space-y-1.5">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-bold text-slate-900 text-sm">{{ $sm['month_name'] }}</span>
                    <span class="text-[11px] text-slate-500 font-mono">{{ $sm['tx_count'] }} transaksi</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600">Masuk: <strong class="font-mono tabular-nums text-emerald-700">+Rp {{ number_format($sm['pemasukan'], 0, ',', '.') }}</strong></span>
                    <span class="text-slate-600">Keluar: <strong class="font-mono tabular-nums text-rose-700">-Rp {{ number_format($sm['pengeluaran'], 0, ',', '.') }}</strong></span>
                </div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="text-slate-600 font-medium">Selisih Bersih</span>
                    <span class="font-bold font-mono tabular-nums {{ $sm['selisih'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                        Rp {{ number_format($sm['selisih'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada transaksi pada semester ini.</div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[560px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
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
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-500 font-mono whitespace-nowrap">{{ $idx + 1 }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $sm['month_name'] }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                +Rp {{ number_format($sm['pemasukan'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold font-mono tabular-nums text-rose-700 whitespace-nowrap">
                                -Rp {{ number_format($sm['pengeluaran'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold font-mono tabular-nums whitespace-nowrap {{ $sm['selisih'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
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
    </section>

    {{-- 3. MODE SEMUA PERIODE (SEMESTER 1 S/D 8): REKAPITULASI KOMPARASI SELURUH SEMESTER --}}
    @elseif($periodMode === 'semua')
    <div class="space-y-6">
        <!-- Komparasi Seluruh Semester -->
        <section class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
                <div>
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                        Komparasi Finansial Seluruh Semester (Semester 1 s/d Semester 8)
                    </h2>
                    <p class="text-[11px] text-slate-600 mt-0.5">Seluruh data historis semester 1 s/d 8 tersimpan permanen dan tidak hilang saat semester dinaikkan</p>
                </div>
                <span class="text-xs text-slate-600 font-medium">
                    Saldo Menyeluruh: <strong class="font-bold font-mono tabular-nums text-slate-900">Rp {{ number_format($totalSaldoAll, 0, ',', '.') }}</strong>
                </span>
            </div>

            <!-- Tampilan Daftar Ringkas Mobile -->
            <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
                @foreach($semestersRecap as $sr)
                <div class="px-4 py-3 space-y-1.5 {{ $sr['semester'] === $activeClassSemester ? 'bg-slate-50/80' : '' }}">
                    <div class="flex items-baseline justify-between gap-2">
                        <div class="font-bold text-slate-900 text-sm">
                            {{ $sr['semester'] }}
                            @if($sr['semester'] === $activeClassSemester)
                                <span class="ml-1.5 text-[11px] text-indigo-700 font-semibold">&bull; Aktif</span>
                            @endif
                        </div>
                        <span class="text-[11px] font-bold {{ $sr['saldo'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $sr['saldo'] >= 0 ? 'Surplus' : 'Defisit' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600">Masuk: <strong class="font-mono tabular-nums text-emerald-700">+Rp {{ number_format($sr['pemasukan'], 0, ',', '.') }}</strong></span>
                        <span class="text-slate-600">Keluar: <strong class="font-mono tabular-nums text-rose-700">-Rp {{ number_format($sr['pengeluaran'], 0, ',', '.') }}</strong></span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                        <span class="text-slate-500 font-mono">{{ $sr['tx_count'] }} mutasi</span>
                        <span class="font-bold font-mono tabular-nums {{ $sr['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                            Saldo: Rp {{ number_format($sr['saldo'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Tampilan Tabel Desktop -->
            <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto w-full scrollbar-thin">
                    <table class="w-full text-left text-xs border-collapse min-w-[580px]">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
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
                            <tr class="hover:bg-slate-50/70 transition-colors {{ $sr['semester'] === $activeClassSemester ? 'bg-slate-50/80' : '' }}">
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $sr['semester'] }}
                                    @if($sr['semester'] === $activeClassSemester)
                                        <span class="ml-1.5 text-[11px] text-indigo-700 font-semibold">&bull; Aktif</span>
                                    @endif
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                    +Rp {{ number_format($sr['pemasukan'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-semibold font-mono tabular-nums text-rose-700 whitespace-nowrap">
                                    -Rp {{ number_format($sr['pengeluaran'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold font-mono tabular-nums whitespace-nowrap {{ $sr['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                                    Rp {{ number_format($sr['saldo'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center whitespace-nowrap">
                                    <span class="text-[11px] font-bold {{ $sr['saldo'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
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
        </section>

        <!-- Akumulasi Iuran Seluruh Mahasiswa (All-Time) -->
        <section class="space-y-3">
            <div>
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    Akumulasi Iuran Mahasiswa Sepanjang Waktu (All-Time)
                </h2>
                <p class="text-[11px] text-slate-600 mt-0.5">Total iuran yang telah dibayarkan oleh masing-masing anggota kelas sejak awal</p>
            </div>

            <!-- Tampilan Daftar Ringkas Mobile -->
            <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
                @forelse($students as $idx => $mhs)
                @php
                    $paidWeeksCount = $mhs->duesPayments->count();
                    $totalNominalPaid = $paidWeeksCount * $duesRate;
                @endphp
                <div class="px-4 py-3 space-y-1.5">
                    <div class="flex items-baseline justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 text-sm leading-snug truncate">{{ $mhs->name }}</div>
                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }} &bull; {{ $mhs->status_akademik ?? 'Aktif' }}</div>
                        </div>
                        <span class="text-xs font-bold font-mono tabular-nums text-emerald-700 shrink-0">
                            Rp {{ number_format($totalNominalPaid, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-600">
                        Total Lunas: <strong class="text-slate-900 font-mono">{{ $paidWeeksCount }} Minggu</strong>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-slate-600 font-medium text-xs">Belum ada data mahasiswa terdaftar.</div>
                @endforelse
            </div>

            <!-- Tampilan Tabel Desktop -->
            <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto w-full scrollbar-thin">
                    <table class="w-full text-left text-xs border-collapse min-w-[560px]">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
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
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center text-slate-500 font-mono whitespace-nowrap">{{ $idx + 1 }}</td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[200px]">
                                    <div class="font-semibold text-slate-900 leading-snug">{{ $mhs->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $mhs->npm }} &bull; {{ ucfirst($mhs->role) }}</div>
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-center font-bold font-mono text-slate-800 whitespace-nowrap">
                                    {{ $paidWeeksCount }} Minggu
                                </td>
                                <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-right font-bold font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                    Rp {{ number_format($totalNominalPaid, 0, ',', '.') }}
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-center text-slate-700 font-medium whitespace-nowrap">
                                    {{ $mhs->status_akademik ?? 'Aktif' }}
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
        </section>
    </div>
    @endif

    <!-- Riwayat Mutasi Kas Sesuai Mode Periode (Flat Section) -->
    <section class="pt-4 border-t border-slate-200 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2.5">
            <div>
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    Riwayat Mutasi Kas &bull; {{ $periodLabel }}
                </h2>
                <p class="text-[11px] text-slate-600 mt-0.5">Jurnal pemasukan dan pengeluaran kas tercatat pada periode ini</p>
            </div>

            <!-- Type Filter Inline Links (Flat, Tanpa Kotak Pill) -->
            <div class="flex items-center gap-4 text-xs self-start sm:self-auto">
                <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                   class="transition {{ !request('type') ? 'text-slate-900 font-bold underline underline-offset-4 decoration-2' : 'text-slate-600 hover:text-slate-900 font-medium' }}">Semua</a>
                <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear, 'type' => 'pemasukan']) }}" 
                   class="transition {{ request('type') === 'pemasukan' ? 'text-emerald-700 font-bold underline underline-offset-4 decoration-2' : 'text-slate-600 hover:text-emerald-700 font-medium' }}">Pemasukan</a>
                <a href="{{ route('arsip.index', ['period_mode' => $periodMode, 'semester' => $selectedSemester, 'month' => $selectedMonth, 'year' => $selectedYear, 'type' => 'pengeluaran']) }}" 
                   class="transition {{ request('type') === 'pengeluaran' ? 'text-rose-700 font-bold underline underline-offset-4 decoration-2' : 'text-slate-600 hover:text-rose-700 font-medium' }}">Pengeluaran</a>
            </div>
        </div>

        <!-- Tampilan Daftar Ringkas Mobile -->
        <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
            @forelse($transactions as $tx)
            <div class="px-4 py-3 space-y-1.5">
                <div class="flex items-center justify-between gap-2 text-[11px]">
                    <span class="text-slate-500 font-mono">
                        {{ $tx->date->format('d M Y') }} &bull; {{ $tx->semester }}
                    </span>
                    <span class="font-bold {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ ucfirst($tx->type) }}
                    </span>
                </div>

                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900 leading-snug break-words">{{ $tx->description }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $tx->user ? $tx->user->name : 'Kas Kelas Umum' }}
                            @if($tx->user)<span class="font-mono text-[10px]">({{ $tx->user->npm }})</span>@endif
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-bold font-mono tabular-nums {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->type === 'pemasukan' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if($tx->proof_image || (auth()->user()->role === 'bendahara' && !\App\Services\AcademicService::isHistoricalArchive($tx->semester, \App\Services\AcademicService::getActiveSemester())))
                <div class="pt-1 flex items-center justify-between gap-3">
                    @if($tx->proof_image)
                    <button type="button" onclick="openChatbotImageModal('{{ route('kas.nota', $tx->id) }}', 'Bukti Transaksi: {{ addslashes($tx->description) }}')" class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-900 hover:underline text-xs font-semibold cursor-pointer py-1" title="Lihat Bukti Nota">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Lihat Bukti Nota</span>
                    </button>
                    @else
                    <div></div>
                    @endif

                    @if(auth()->user()->role === 'bendahara' && !\App\Services\AcademicService::isHistoricalArchive($tx->semester, \App\Services\AcademicService::getActiveSemester()))
                    <form action="{{ route('kas.destroy', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-700 hover:text-rose-900 hover:underline text-xs font-semibold py-1" title="Batalkan/Hapus Transaksi">
                            Batalkan
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <div class="p-6 text-center text-slate-600">
                <p class="text-xs font-semibold text-slate-700">Belum ada catatan mutasi kas pada {{ $periodLabel }}.</p>
            </div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto w-full scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse min-w-[620px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-28 whitespace-nowrap">Tanggal</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-24 whitespace-nowrap">Semester</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 w-24 whitespace-nowrap">Tipe</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[180px]">Keterangan</th>
                            <th class="px-3 sm:px-4 py-3 border-r border-slate-200 min-w-[140px] whitespace-nowrap">Pihak / Mahasiswa</th>
                            <th class="px-3 sm:px-4 py-3 text-right w-36 whitespace-nowrap">Nominal</th>
                            <th class="px-3 sm:px-4 py-3 text-center w-24 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-600 font-mono whitespace-nowrap">{{ $tx->date->format('d M Y') }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-700 font-medium whitespace-nowrap">{{ $tx->semester }}</td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 whitespace-nowrap">
                                <span class="text-[11px] font-bold {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ ucfirst($tx->type) }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 font-medium text-slate-900 break-words">
                                <div>{{ $tx->description }}</div>
                                @if($tx->proof_image)
                                <button type="button" onclick="openChatbotImageModal('{{ route('kas.nota', $tx->id) }}', 'Bukti Transaksi: {{ addslashes($tx->description) }}')" class="inline-flex items-center gap-1 text-[11px] text-blue-700 hover:text-blue-900 hover:underline font-semibold mt-1 cursor-pointer" title="Pratinjau foto nota atau bukti transaksi">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Lihat Bukti Nota</span>
                                </button>
                                @endif
                            </td>
                            <td class="px-3 sm:px-4 py-3 border-r border-slate-200 text-slate-600 whitespace-nowrap">
                                @if($tx->user)
                                    <div class="font-medium text-slate-800 leading-snug">{{ $tx->user->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $tx->user->npm }}</div>
                                @else
                                    <span class="text-slate-500 italic">Kas Kelas Umum</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-right font-bold font-mono tabular-nums whitespace-nowrap {{ $tx->type === 'pemasukan' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $tx->type === 'pemasukan' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-center whitespace-nowrap">
                                @if(auth()->user()->role === 'bendahara' && !\App\Services\AcademicService::isHistoricalArchive($tx->semester, \App\Services\AcademicService::getActiveSemester()))
                                <form action="{{ route('kas.destroy', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline text-[11px] font-semibold transition" title="Batalkan/Hapus Transaksi">
                                        Batal
                                    </button>
                                </form>
                                @else
                                    <span class="text-slate-500">-</span>
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
        </div>

        @if($transactions->hasPages())
        <div class="pt-2 overflow-x-auto">
            {{ $transactions->links() }}
        </div>
        @endif
    </section>
</div>
@endsection
