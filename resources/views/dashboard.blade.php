@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5">
    <!-- Page Header (Unboxed & Fluid - Terbuka Alami di Kanvas) -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-1">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">S1 Informatika</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Dashboard Kelas &bull; {{ $kelasLabel }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Sistem informasi dan administrasi kelas &bull; Angkatan 2024 &bull; Periode 2024–2028
            </p>
        </div>
        <div class="inline-flex items-center gap-2 self-start sm:self-auto text-xs font-semibold text-slate-700 bg-white border border-slate-200/90 px-3 py-1.5 rounded-xl shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>{{ $activeClassSemester }} &bull; Kelas {{ $kelasLabel }}</span>
        </div>
    </div>

    <!-- DETEKSI TUNGGAKAN IURAN KAS (SINKRON 4 MINGGU SESUAI MENU KAS) -->
    @if($isDuesPending)
    <div class="p-3 sm:p-5 rounded-xl sm:rounded-2xl bg-rose-50/70 border border-rose-200 flex items-start gap-2.5 sm:gap-3.5 shadow-2xs">
        <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5" aria-hidden="true">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2">
                <h3 class="text-[11px] sm:text-xs font-bold text-rose-950 uppercase tracking-wide">
                    Tunggakan Kas &bull; {{ $namaBulan }} {{ $currentYear }} ({{ $activeClassSemester }})
                </h3>
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
            
            <!-- Matriks 4 Minggu Penuh (1 Baris Ramping di Mobile & Desktop) -->
            <div class="mt-2 sm:mt-3 grid grid-cols-4 gap-1 sm:gap-2">
                @for($w = 1; $w <= 4; $w++)
                @php
                    $isPaid = in_array($w, $paidWeeks ?? []);
                    $isCurrentWeek = ($w === $activeWeek);
                @endphp
                <div class="py-1 px-1 sm:p-2.5 rounded-md sm:rounded-xl border text-center transition
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
                    <div class="mt-0.5 sm:mt-1 text-[9.5px] sm:text-xs font-bold leading-none">
                        @if($isPaid)
                            <span class="text-emerald-700">Lunas</span>
                        @else
                            <span class="text-rose-700">Belum</span>
                        @endif
                    </div>
                </div>
                @endfor
            </div>
            <p class="text-[10px] sm:text-xs text-rose-700 mt-1.5 font-medium leading-snug">Silakan hubungi Bendahara Kelas untuk konfirmasi pembayaran iuran.</p>
        </div>
    </div>
    @else
    <div class="p-3 sm:p-5 rounded-xl sm:rounded-2xl bg-emerald-50/70 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3.5 shadow-2xs">
        <div class="flex items-center gap-2.5 sm:gap-3.5">
            <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg sm:rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0" aria-hidden="true">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h3 class="text-[11px] sm:text-xs font-bold text-emerald-950 uppercase tracking-wide">Status Iuran Kas: Lunas 4 Minggu (100%)</h3>
                <p class="text-[11px] sm:text-xs text-emerald-900 mt-0.5 leading-snug">
                    Terima kasih <strong>{{ $user->name ?? 'Mahasiswa' }}</strong>! Kewajiban kas pada <strong>{{ $activeClassSemester }}</strong> (Bulan {{ $namaBulan }}) telah lunas tercatat.
                </p>
            </div>
        </div>
        <div class="sm:text-right shrink-0">
            <span class="inline-flex items-center px-2 py-0.5 sm:px-3 sm:py-1 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                4/4 Minggu Lunas
            </span>
        </div>
    </div>
    @endif

    <!-- DAFTAR MAHASISWA & STATUS KEHADIRAN KELAS (ONLINE REAL-TIME) -->
    <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs">
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Daftar Anggota &bull; Kelas {{ $kelasLabel }}
                    </h2>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span id="online-counter-label">{{ $onlineCount }} Online</span>
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Anggota aktif {{ $activeClassSemester }} &bull; Total: <strong class="text-slate-800 font-mono">{{ $classStudents->count() }} Mahasiswa</strong>
                </p>
            </div>

            <!-- Toolbar Pencarian & Filter Kehadiran (Full-Width di Mobile, Baris Rapi di Desktop) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                <!-- Filter Tab -->
                <div class="inline-flex p-0.5 bg-slate-200/70 rounded-lg text-xs w-full sm:w-auto">
                    <button type="button" onclick="filterPresence('all')" id="filter-btn-all" 
                            class="flex-1 sm:flex-none justify-center px-3 py-1.5 sm:py-1 font-bold rounded-md transition text-slate-900 bg-white shadow-2xs">
                        Semua ({{ $classStudents->count() }})
                    </button>
                    <button type="button" onclick="filterPresence('online')" id="filter-btn-online" 
                            class="flex-1 sm:flex-none justify-center px-3 py-1.5 sm:py-1 font-bold rounded-md transition text-slate-600 hover:text-slate-900">
                        Online ({{ $onlineCount }})
                    </button>
                </div>

                <!-- Input Pencarian Instan -->
                <div class="relative w-full sm:w-48">
                    <input type="text" id="presence-search" oninput="searchPresence(this.value)" placeholder="Cari nama atau NPM..." 
                           class="w-full text-xs border border-slate-300 rounded-lg pl-7 pr-2.5 py-1.5 bg-white text-slate-900 focus:ring-2 focus:ring-slate-900 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Grid Mahasiswa (Responsive Card Grid: 1 Kolom di HP, 2 di Tablet, 3 di Desktop) -->
        <div class="p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-[480px] overflow-y-auto custom-scrollbar" id="presence-grid">
            @forelse($classStudents as $mhs)
            @php $isMhsOnline = $mhs->isOnline(); @endphp
            <div class="p-3 sm:p-3 rounded-xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50/50 transition flex items-center justify-between gap-2.5 student-card min-h-[56px]"
                 id="student-card-{{ $mhs->id }}"
                 data-id="{{ $mhs->id }}"
                 data-name="{{ strtolower($mhs->name) }}" 
                 data-npm="{{ $mhs->npm }}"
                 data-online="{{ $isMhsOnline ? '1' : '0' }}">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="presence-dot w-2 h-2 rounded-full shrink-0 {{ $isMhsOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        <div class="font-bold text-slate-900 text-xs truncate leading-snug">{{ $mhs->name }}</div>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500 font-mono pl-3.5">
                        <span>{{ $mhs->npm }}</span>
                        @if($mhs->role !== 'mahasiswa')
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase
                            @if($mhs->role === 'admin') text-purple-700 bg-purple-50
                            @elseif($mhs->role === 'bendahara') text-emerald-700 bg-emerald-50
                            @elseif($mhs->role === 'ketua') text-sky-700 bg-sky-50 @endif">
                            {{ $mhs->role }}
                        </span>
                        @endif
                    </div>
                </div>

                <div class="presence-badge-container shrink-0 text-right">
                    @if($isMhsOnline)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            Online
                        </span>
                    @else
                        <span class="text-[10px] font-medium text-slate-500">
                            Offline
                        </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-6 col-span-full text-center text-slate-500 text-xs">Belum ada data anggota kelas terdaftar.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentPresenceFilter = 'all';
    let currentPresenceSearch = '';

    function updatePresenceCards() {
        const cards = document.querySelectorAll('.student-card');
        cards.forEach(function(card) {
            const isOnline = card.getAttribute('data-online') === '1';
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const npm = (card.getAttribute('data-npm') || '').toLowerCase();
            
            const matchFilter = currentPresenceFilter === 'all' || (currentPresenceFilter === 'online' && isOnline);
            const matchSearch = !currentPresenceSearch || name.includes(currentPresenceSearch) || npm.includes(currentPresenceSearch);
            
            card.style.display = (matchFilter && matchSearch) ? 'flex' : 'none';
        });
    }

    function filterPresence(type) {
        currentPresenceFilter = type;
        const btnAll = document.getElementById('filter-btn-all');
        const btnOnline = document.getElementById('filter-btn-online');
        
        if (type === 'all') {
            btnAll.className = 'px-2.5 py-1 font-bold rounded-md transition text-slate-900 bg-white shadow-2xs';
            btnOnline.className = 'px-2.5 py-1 font-bold rounded-md transition text-slate-600 hover:text-slate-900';
        } else {
            btnOnline.className = 'px-2.5 py-1 font-bold rounded-md transition text-slate-900 bg-white shadow-2xs';
            btnAll.className = 'px-2.5 py-1 font-bold rounded-md transition text-slate-600 hover:text-slate-900';
        }
        updatePresenceCards();
    }

    function searchPresence(query) {
        currentPresenceSearch = (query || '').trim().toLowerCase();
        updatePresenceCards();
    }

    // Handler sinkronisasi kehadiran real-time dari heartbeat (interval 5 detik)
    window.handlePresenceSync = function(data) {
        if (!data || !Array.isArray(data.online_ids)) return;

        // 1. Perbarui counter online di header dan tombol filter
        const counterLabel = document.getElementById('online-counter-label');
        if (counterLabel) counterLabel.textContent = data.online_count + ' Online';

        const filterBtnOnline = document.getElementById('filter-btn-online');
        if (filterBtnOnline) filterBtnOnline.textContent = 'Online (' + data.online_count + ')';

        // 2. Perbarui status setiap kartu anggota kelas
        const onlineSet = new Set(data.online_ids.map(function(id) { return String(id); }));
        const cards = document.querySelectorAll('.student-card');

        cards.forEach(function(card) {
            const studentId = card.getAttribute('data-id');
            const isOnline = onlineSet.has(studentId);

            card.setAttribute('data-online', isOnline ? '1' : '0');

            // Indikator visual titik (dot)
            const dot = card.querySelector('.presence-dot');
            if (dot) {
                if (isOnline) {
                    dot.className = 'presence-dot w-2 h-2 rounded-full shrink-0 bg-emerald-500';
                } else {
                    dot.className = 'presence-dot w-2 h-2 rounded-full shrink-0 bg-slate-300';
                }
            }

            // Lencana badge teks Online / Offline
            const badgeContainer = card.querySelector('.presence-badge-container');
            if (badgeContainer) {
                if (isOnline) {
                    badgeContainer.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Online</span>';
                } else {
                    badgeContainer.innerHTML = '<span class="text-[10px] font-medium text-slate-500">Offline</span>';
                }
            }
        });

        // 3. Terapkan filter & pencarian yang sedang aktif
        updatePresenceCards();
    };
</script>
@endpush
