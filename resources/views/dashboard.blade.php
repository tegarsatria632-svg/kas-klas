@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header Dashboard (Terbuka & Bersih Tanpa Kotak Badge) -->
    <div class="pb-4 border-b border-slate-200">
        <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
            <span>FTIK UPS Tegal</span>
            <span class="text-slate-300" aria-hidden="true">&bull;</span>
            <span>S1 Informatika</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
            Dashboard Kelas &bull; {{ $kelasLabel }}
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            {{ $activeClassSemester }} &bull; Angkatan 2024 &bull; Periode 2024–2028
        </p>
    </div>

    <!-- Status Iuran Kas Bulanan Pribadi (Flat Left-Accent, Tanpa Kotak di Dalam Kotak) -->
    @if($isDuesPending)
    <section class="border-l-2 border-rose-600 pl-3.5 sm:pl-4 py-0.5 space-y-3" aria-labelledby="heading-status-iuran">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1.5">
            <div>
                <h2 id="heading-status-iuran" class="text-xs font-bold text-rose-900 uppercase tracking-wide">
                    Tunggakan Kas &bull; {{ $namaBulan }} {{ $currentYear }}
                </h2>
                <p class="text-xs text-slate-600 mt-0.5">
                    <strong class="text-slate-900">{{ $user->name ?? 'Mahasiswa' }}</strong> (<span class="font-mono">{{ $user->npm ?? '-' }}</span>)
                    &bull; {{ count($paidWeeks ?? []) }}/4 Minggu Lunas
                </p>
            </div>
            <div class="text-xs sm:text-sm font-extrabold text-rose-700 font-mono tabular-nums">
                Tunggakan: Rp {{ number_format($tunggakanNominal, 0, ',', '.') }}
            </div>
        </div>

        <!-- Bar 4 Minggu Terpadu (1 Segmen Rata, Bukan 4 Kotak Mengambang) -->
        <div class="grid grid-cols-4 divide-x divide-slate-200 border border-slate-200 rounded-lg bg-white overflow-hidden">
            @for($w = 1; $w <= 4; $w++)
            @php
                $isPaid = in_array($w, $paidWeeks ?? []);
                $isCurrentWeek = ($w === $activeWeek);
            @endphp
            <div class="py-2 px-1.5 sm:py-2.5 sm:px-3 text-center {{ $isCurrentWeek ? 'bg-slate-50 border-t-2 border-t-slate-900' : '' }}">
                <div class="text-[10px] sm:text-xs font-bold font-mono text-slate-800">
                    <span class="sm:hidden">M{{ $w }}</span>
                    <span class="hidden sm:inline">Minggu {{ $w }}</span>
                </div>
                @if(!empty($weekRanges[$w]['date_range']))
                <div class="text-[8.5px] sm:text-[10px] text-slate-500 font-mono leading-tight mt-0.5 truncate">
                    {{ $weekRanges[$w]['date_range'] }}
                </div>
                @endif
                <div class="mt-1 text-[10px] sm:text-xs font-bold leading-none {{ $isPaid ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $isPaid ? 'Lunas' : 'Belum' }}
                </div>
            </div>
            @endfor
        </div>

        <p class="text-[11px] text-slate-600">
            Hubungi Bendahara Kelas di bawah untuk konfirmasi pembayaran (Tarif: <span class="font-mono font-semibold text-slate-800">Rp {{ number_format($duesRate, 0, ',', '.') }}/minggu</span>).
        </p>
    </section>
    @else
    <section class="border-l-2 border-emerald-600 pl-3.5 sm:pl-4 py-1 flex flex-col sm:flex-row sm:items-center justify-between gap-2" aria-labelledby="heading-status-iuran">
        <div>
            <h2 id="heading-status-iuran" class="text-xs font-bold text-emerald-900 uppercase tracking-wide">
                Status Iuran Kas &bull; Lunas 4 Minggu (100%)
            </h2>
            <p class="text-xs text-slate-600 mt-0.5">
                Terima kasih <strong class="text-slate-900">{{ $user->name ?? 'Mahasiswa' }}</strong>, kewajiban kas bulan {{ $namaBulan }} ({{ $activeClassSemester }}) telah lunas tercatat.
            </p>
        </div>
        <span class="text-xs font-bold text-emerald-700 font-mono tabular-nums shrink-0">
            4/4 Minggu Lunas
        </span>
    </section>
    @endif

    <!-- Info Pengurus Kelas (Terbuka dengan Garis Pemisah Tipis, Tanpa Kotak Card) -->
    <section class="pt-5 border-t border-slate-200 space-y-3" aria-labelledby="heading-pengurus-kelas">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h2 id="heading-pengurus-kelas" class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                    Pengurus Kelas &bull; {{ $kelasLabel }}
                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Kontak koordinasi kelas dan konfirmasi pembayaran kas
                </p>
            </div>
            <span class="text-xs text-slate-600 font-mono tabular-nums">
                Tarif aktif: <strong class="text-slate-900">Rp {{ number_format($duesRate, 0, ',', '.') }}/mgg</strong>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 pt-1">
            <!-- Ketua Kelas -->
            <div class="space-y-2.5">
                <div class="text-[10.5px] font-bold uppercase tracking-wider text-sky-700 border-b border-slate-200/80 pb-1">
                    Ketua Kelas
                </div>
                @forelse($ketuaList as $ketua)
                <div class="flex items-baseline justify-between gap-2">
                    <span class="text-sm font-bold text-slate-900 truncate">{{ $ketua->name }}</span>
                    <span class="text-xs text-slate-500 font-mono shrink-0">{{ $ketua->npm }}</span>
                </div>
                @empty
                <p class="text-xs text-slate-500">Belum ditetapkan (atur peran di menu Kelola Akun).</p>
                @endforelse
            </div>

            <!-- Bendahara Kelas -->
            <div class="space-y-2.5">
                <div class="text-[10.5px] font-bold uppercase tracking-wider text-emerald-700 border-b border-slate-200/80 pb-1">
                    Bendahara Kelas
                </div>
                @forelse($bendaharaList as $bendahara)
                <div class="flex items-baseline justify-between gap-2">
                    <span class="text-sm font-bold text-slate-900 truncate">{{ $bendahara->name }}</span>
                    <span class="text-xs text-slate-500 font-mono shrink-0">{{ $bendahara->npm }}</span>
                </div>
                @empty
                <p class="text-xs text-slate-500">Belum ditetapkan (atur peran di menu Kelola Akun).</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
