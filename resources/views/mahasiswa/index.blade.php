@extends('layouts.app')

@section('title', 'Daftar Mahasiswa Informatika Kelas ' . $kelasLabel)

@section('content')
<div class="space-y-6">
    <!-- Open Header & Toolbar (Unboxed & Fluid) -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-1">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Direktori Mahasiswa</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Daftar Mahasiswa Informatika {{ $kelasLabel }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Direktori data anggota terdaftar dan pengurus kelas &bull; Total: <strong class="text-slate-900 font-mono tabular-nums">{{ $students->total() }} Orang</strong>
            </p>
        </div>

        <!-- Toolbar Pencarian & Filter Terbuka -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-wrap">
            <form method="GET" action="{{ route('mahasiswa.index') }}" class="flex items-center gap-2 flex-wrap">
                <div class="relative w-full sm:w-60">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NPM..." 
                           class="w-full text-xs pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900 bg-white shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white font-medium text-slate-700 shadow-2xs focus:ring-2 focus:ring-slate-900">
                    <option value="all">Semua Semester</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>
                            {{ $sem }} {{ $sem === $activeClassSemester ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                    Cari
                </button>

                @if(request('q') || request('semester'))
                    <a href="{{ route('mahasiswa.index') }}" class="text-xs text-rose-600 hover:underline font-semibold ml-1">Reset</a>
                @endif
            </form>

            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ketua']))
            <a href="{{ route('admin.users.index') }}" 
               class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-2xs transition self-start sm:self-auto">
                Kelola Akun
            </a>
            @endif
        </div>
    </div>

    <!-- Panel Statistik Terpadu (Satu Panel Bersih, Bukan 3 Kotak Terpisah) -->
    <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 text-center p-4 sm:p-5">
            <div class="sm:pr-4 pb-3 sm:pb-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Mahasiswa Terdaftar</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 font-mono tabular-nums block">{{ $totalStudents }}</span>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Kelas {{ $kelasLabel }}</span>
            </div>
            <div class="py-3 sm:py-0 sm:px-4">
                <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Status Aktif</span>
                <span class="text-2xl font-extrabold text-emerald-700 mt-1 font-mono tabular-nums block">{{ $activeStudents }}</span>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Tahun Akademik 2024/2025</span>
            </div>
            <div class="sm:pl-4 pt-3 sm:pt-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Semester Berjalan</span>
                <span class="text-xl font-extrabold text-slate-900 mt-1 font-mono tabular-nums block">{{ $selectedSemester }}</span>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Periode Akademik Aktif</span>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Mahasiswa Informatika -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Mahasiswa &bull; {{ $selectedSemester }}</h3>
                <span class="text-[11px] text-slate-500">Sinkron dengan akun terdaftar</span>
            </div>
        </div>

        <!-- Tampilan Kartu Khusus Mobile (Tidak Perlu Geser Layar) -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($students as $idx => $mhs)
            <div class="p-4 space-y-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-bold text-slate-900 text-sm leading-snug">{{ $mhs->name }}</div>
                        <div class="text-xs text-slate-600 font-mono font-bold mt-0.5">{{ $mhs->npm }}</div>
                    </div>
                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold shrink-0
                        @if($mhs->role === 'admin') bg-purple-50 text-purple-700 border border-purple-200
                        @elseif($mhs->role === 'bendahara') bg-emerald-50 text-emerald-700 border border-emerald-200
                        @elseif($mhs->role === 'ketua') bg-sky-50 text-sky-700 border border-sky-200
                        @else bg-slate-100 text-slate-700 @endif">
                        {{ ucfirst($mhs->role) }}
                    </span>
                </div>

                <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                        {{ \App\Services\AcademicService::getKelasLabel($mhs->semester) }} &bull; {{ $mhs->semester }}
                    </span>
                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold 
                        {{ $mhs->status_akademik === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $mhs->status_akademik ?? 'Aktif' }}
                    </span>
                    <span class="text-[11px] text-slate-500 font-mono ml-auto">
                        Tahun: {{ $mhs->tahun_ajaran ?? '2024/2025' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-600">
                <p class="text-xs font-semibold text-slate-800">Tidak ada data anggota kelas yang ditemukan.</p>
            </div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop (Layar MD ke Atas) -->
        <div class="hidden md:block overflow-x-auto custom-scrollbar w-full">
            <table class="w-full text-left text-xs border-collapse min-w-[650px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3 border-r border-slate-200 w-12 text-center">No</th>
                        <th class="px-4 py-3 border-r border-slate-200 w-32">NPM</th>
                        <th class="px-4 py-3 border-r border-slate-200">Nama Mahasiswa</th>
                        <th class="px-4 py-3 border-r border-slate-200 w-28 text-center">Peran</th>
                        <th class="px-4 py-3 border-r border-slate-200 w-32 text-center">Kelas & Semester</th>
                        <th class="px-4 py-3 text-center w-28">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($students as $idx => $mhs)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 border-r border-slate-200 text-center text-slate-600 font-medium">
                            {{ $students->firstItem() + $idx }}
                        </td>
                        <td class="px-4 py-3 border-r border-slate-200 font-mono font-bold text-slate-900">
                            {{ $mhs->npm }}
                        </td>
                        <td class="px-4 py-3 border-r border-slate-200">
                            <div class="font-bold text-slate-900 text-sm">{{ $mhs->name }}</div>
                            <div class="text-[11px] text-slate-600 font-mono">Tahun Ajaran: {{ $mhs->tahun_ajaran ?? '2024/2025' }}</div>
                        </td>
                        <td class="px-4 py-3 border-r border-slate-200 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold 
                                @if($mhs->role === 'admin') bg-purple-50 text-purple-700 border border-purple-200
                                @elseif($mhs->role === 'bendahara') bg-emerald-50 text-emerald-700 border border-emerald-200
                                @elseif($mhs->role === 'ketua') bg-sky-50 text-sky-700 border border-sky-200
                                @else bg-slate-100 text-slate-700 @endif">
                                {{ ucfirst($mhs->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 border-r border-slate-200 text-center">
                            <span class="inline-block px-2.5 py-1 rounded text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                {{ \App\Services\AcademicService::getKelasLabel($mhs->semester) }} &bull; {{ $mhs->semester }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold 
                                {{ $mhs->status_akademik === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $mhs->status_akademik ?? 'Aktif' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-600">
                            <p class="text-sm font-semibold text-slate-800">Tidak ada data anggota kelas yang ditemukan.</p>
                            <p class="text-xs text-slate-600 mt-1">Coba sesuaikan kata kunci pencarian atau pilih semester lain.</p>
                            <div class="mt-4 flex items-center justify-center">
                                <a href="{{ route('mahasiswa.index') }}" class="px-4 py-2 min-h-[44px] bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-xl border border-slate-300 transition inline-flex items-center justify-center">
                                    Reset Pencarian &amp; Tampilkan Semua
                                </a>
                            </div>
                            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ketua']))
                            <p class="text-[11px] text-slate-600 mt-3">Super Admin &amp; Ketua Kelas dapat mendaftarkan akun baru melalui menu <strong>Kelola Akun &amp; Semester</strong>.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $students->links() }}
        </div>
    </div>
</div>
@endsection
