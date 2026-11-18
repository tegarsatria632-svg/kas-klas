@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5">
    <!-- Header Dashboard Kelas -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-1 border-b border-slate-200/70">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">S1 Informatika</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Dashboard Kelas &bull; {{ $kelasLabel }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Sistem pengelolaan kas kelas &bull; Angkatan 2024 &bull; Periode 2024–2028
            </p>
        </div>
        <div class="inline-flex items-center gap-2 self-start sm:self-auto text-xs font-semibold text-slate-700 bg-white border border-slate-200/90 px-3 py-1.5 rounded-xl shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>{{ $activeClassSemester }} &bull; Kelas {{ $kelasLabel }}</span>
        </div>
    </div>

    <!-- Status Iuran Kas Bulanan Pribadi (Sinkron 4 Minggu Sesuai Menu Kas) -->
    @if($isDuesPending)
    <div class="p-3.5 sm:p-5 rounded-xl sm:rounded-2xl bg-rose-50/70 border border-rose-200 flex items-start gap-2.5 sm:gap-3.5 shadow-2xs">
        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5" aria-hidden="true">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2">
                <h2 class="text-[11px] sm:text-xs font-bold text-rose-950 uppercase tracking-wide">
                    Tunggakan Kas &bull; {{ $namaBulan }} {{ $currentYear }} ({{ $activeClassSemester }})
                </h2>
                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <span class="text-[10px] sm:text-xs bg-rose-100 text-rose-900 border border-rose-200 px-2 py-0.5 rounded-md sm:rounded-lg font-bold font-mono tabular-nums">
                        {{ count($paidWeeks ?? []) }}/4 Mgg ({{ count($pendingWeeks) }} Belum)
                    </span>
                    <span class="text-[10px] sm:text-xs bg-rose-600 text-white px-2 py-0.5 rounded-md sm:rounded-lg font-bold font-mono tabular-nums shadow-2xs">
                        Tunggakan: Rp {{ number_format($tunggakanNominal, 0, ',', '.') }}
                    </span>
                </div>
            </div>
            <p class="text-[11px] sm:text-xs text-rose-900 mt-1 leading-snug">
                <strong>{{ $user->name ?? 'Mahasiswa' }}</strong> (<span class="font-mono">{{ $user->npm ?? '-' }}</span>) &bull; Tarif Kas: Rp {{ number_format($duesRate, 0, ',', '.') }}/minggu
            </p>

            <!-- Matriks 4 Minggu (1 Baris Ramping di Mobile & Desktop) -->
            <div class="mt-2.5 sm:mt-3.5 grid grid-cols-4 gap-1.5 sm:gap-2.5">
                @for($w = 1; $w <= 4; $w++)
                @php
                    $isPaid = in_array($w, $paidWeeks ?? []);
                    $isCurrentWeek = ($w === $activeWeek);
                @endphp
                <div class="py-1.5 px-1 sm:p-3 rounded-lg sm:rounded-xl border text-center transition
                    {{ $isPaid ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-white border-rose-300 text-rose-800' }}
                    {{ $isCurrentWeek ? 'ring-[1.5px] sm:ring-2 ring-slate-900 border-slate-900 shadow-2xs font-bold' : '' }}">
                    <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                        <span class="text-[9.5px] sm:text-xs font-bold font-mono">
                            <span class="sm:hidden">M{{ $w }}</span>
                            <span class="hidden sm:inline">Minggu {{ $w }}</span>
                        </span>
                        @if($isCurrentWeek)
                        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-emerald-500" title="Minggu Berjalan"></span>
                        @endif
                    </div>
                    @if(!empty($weekRanges[$w]['date_range']))
                    <div class="text-[7.5px] sm:text-[10px] text-slate-500 font-mono leading-none mt-0.5 truncate">
                        {{ $weekRanges[$w]['date_range'] }}
                    </div>
                    @endif
                    <div class="mt-1 text-[9.5px] sm:text-xs font-bold leading-none">
                        @if($isPaid)
                            <span class="text-emerald-700">Lunas</span>
                        @else
                            <span class="text-rose-700">Belum</span>
                        @endif
                    </div>
                </div>
                @endfor
            </div>
            <p class="text-[10px] sm:text-xs text-rose-700 mt-2 font-medium leading-snug">Silakan hubungi Bendahara Kelas untuk konfirmasi pembayaran iuran.</p>
        </div>
    </div>
    @else
    <div class="p-3.5 sm:p-5 rounded-xl sm:rounded-2xl bg-emerald-50/70 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3.5 shadow-2xs">
        <div class="flex items-center gap-2.5 sm:gap-3.5">
            <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h2 class="text-[11px] sm:text-xs font-bold text-emerald-950 uppercase tracking-wide">Status Iuran Kas: Lunas 4 Minggu (100%)</h2>
                <p class="text-[11px] sm:text-xs text-emerald-900 mt-0.5 leading-snug">
                    Terima kasih <strong>{{ $user->name ?? 'Mahasiswa' }}</strong>! Kewajiban kas pada <strong>{{ $activeClassSemester }}</strong> (Bulan {{ $namaBulan }}) telah lunas tercatat.
                </p>
            </div>
        </div>
        <div class="sm:text-right shrink-0">
            <span class="inline-flex items-center px-2.5 py-1 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                4/4 Minggu Lunas
            </span>
        </div>
    </div>
    @endif
</div>
@endsection
