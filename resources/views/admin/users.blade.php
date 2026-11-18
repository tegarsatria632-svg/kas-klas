@extends('layouts.app')

@section('title', 'Kelola Pengguna & Semester S1')

@section('content')
<div class="space-y-6">
    <!-- Open Header & Toolbar (Unboxed & Fluid) -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-1">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">FTIK UPS Tegal</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Super Admin</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                Kelola Pengguna &amp; Semester Kelas
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Semester Berjalan: <strong class="text-slate-800">{{ $activeClassSemester }} (Kelas {{ $kelasLabel }})</strong>
                &bull; Aktif: <strong class="font-mono tabular-nums text-emerald-700">{{ $activeStudentsCount }} orang</strong>
                &bull; Alumni: <strong class="font-mono tabular-nums text-slate-500">{{ $alumniStudentsCount }} orang</strong>
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start lg:self-auto">
            <button onclick="document.getElementById('modal-user').classList.remove('hidden')" 
                    class="px-3.5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-xs transition min-h-[44px]">
                Tambah Akun Baru
            </button>
        </div>
    </div>

    <!-- Administrative Semester Control & Filter Bar (Flat, Tanpa Outer Card) -->
    <section class="py-4 border-y border-slate-200 space-y-3.5">
        <form method="POST" action="{{ route('admin.users.setActiveSemester') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3" onsubmit="return confirm('PERINGATAN: Mengubah semester aktif akan memperbarui periode aktif dan menyinkronkan seluruh data sistem kelas. Lanjutkan perubahan?')">
            @csrf
            <div>
                <div class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    Sinkronisasi Semester Aktif Kelas
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Nama kelas (1A s.d 8A) dan periode kas seluruh web otomatis tersinkronkan.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-wrap">
                <select name="semester" aria-label="Pilih Semester Aktif" class="w-full sm:w-auto text-xs border border-slate-300 rounded-lg px-3 py-2 bg-white font-semibold text-slate-900 focus:ring-2 focus:ring-slate-900">
                    @foreach($semesters as $sem)
                        @php $lbl = \App\Services\AcademicService::getKelasLabel($sem); @endphp
                        <option value="{{ $sem }}" {{ $activeClassSemester === $sem ? 'selected' : '' }}>
                            {{ $sem }} (Kelas {{ $lbl }}) {{ $activeClassSemester === $sem ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <label class="flex items-center text-[11px] text-slate-700 font-medium cursor-pointer py-1">
                    <input type="checkbox" name="apply_to_all_active" value="1" checked class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 mr-1.5">
                    Sinkronkan mahasiswa aktif
                </label>

                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition text-center min-h-[38px]">
                    Terapkan Semester
                </button>
            </div>
        </form>

        <!-- Filter Bar Semester & Status Akademik -->
        <div class="pt-3 border-t border-slate-200/70 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 flex-wrap">
            <span class="text-xs font-semibold text-slate-600">Filter Data:</span>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2 flex-wrap flex-1">
                <select name="semester" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-700 focus:ring-2 focus:ring-slate-900">
                    <option value="">Semua Semester (1-8)</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                    @endforeach
                </select>

                <select name="status_akademik" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-medium text-slate-700 focus:ring-2 focus:ring-slate-900">
                    <option value="">Semua Status Akademik</option>
                    <option value="Aktif" {{ $selectedStatus === 'Aktif' ? 'selected' : '' }}>Mahasiswa Aktif</option>
                    <option value="Lulus / Alumni" {{ $selectedStatus === 'Lulus / Alumni' ? 'selected' : '' }}>Lulus / Alumni</option>
                </select>

                @if($selectedSemester || $selectedStatus)
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-rose-600 hover:underline font-semibold ml-1">Reset Filter</a>
                @endif
            </form>
        </div>
    </section>

    <!-- Daftar Akun Pengguna (Flat Section) -->
    <section class="space-y-3">
        <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Daftar Akun Pengguna</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Kelola peran, reset password, dan status akademik akun</p>
        </div>

        <!-- Tampilan Daftar Ringkas Mobile -->
        <div class="block md:hidden divide-y divide-slate-200/80 border-y border-slate-200 bg-white -mx-4 sm:mx-0 sm:border sm:rounded-lg">
            @forelse($users as $u)
            <div class="px-4 py-3.5 space-y-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-sm leading-snug truncate">{{ $u->name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                            {{ $u->npm }} &bull; <span class="font-sans font-semibold text-slate-700">{{ $u->semester }}</span> &bull; <span class="font-sans font-semibold {{ $u->status_akademik === 'Aktif' ? 'text-emerald-700' : 'text-slate-500' }}">{{ $u->status_akademik ?? 'Aktif' }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.users.updateRole', $u) }}" class="inline-block shrink-0">
                        @csrf
                        @method('PATCH')
                        <select name="role" onchange="if(confirm('Apakah Anda yakin ingin mengubah peran pengguna {{ addslashes($u->name) }} menjadi ' + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = '{{ $u->role }}'; }" class="text-xs border border-slate-300 rounded-md px-2 py-1 font-semibold bg-white
                            @if($u->role === 'admin') text-purple-700
                            @elseif($u->role === 'bendahara') text-emerald-700
                            @elseif($u->role === 'ketua') text-sky-700
                            @else text-slate-700 @endif">
                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="bendahara" {{ $u->role === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                            <option value="ketua" {{ $u->role === 'ketua' ? 'selected' : '' }}>Ketua</option>
                            <option value="mahasiswa" {{ $u->role === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        </select>
                    </form>
                </div>

                <!-- Tombol Tindakan Mobile (Flat Inline Links) -->
                <div class="flex items-center gap-4 pt-1 text-xs">
                    <button type="button" onclick="openAcademicModal({{ json_encode($u) }})" 
                            class="text-slate-700 hover:text-slate-900 hover:underline font-semibold py-1">
                        Akademik
                    </button>
                    @if($u->id !== auth()->id())
                    <button type="button" onclick="openResetModal('{{ $u->id }}', '{{ addslashes($u->name) }}', '{{ $u->npm }}')" 
                            class="text-slate-700 hover:text-slate-900 hover:underline font-semibold py-1">
                        Reset Sandi
                    </button>
                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline ml-auto" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus akun pengguna \'{{ addslashes($u->name) }}\' secara permanen dari sistem?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold py-1">
                            Hapus
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-600">
                <p class="text-sm font-semibold text-slate-800">Tidak ada pengguna yang cocok dengan kriteria filter.</p>
                <p class="text-xs text-slate-600 mt-1">Coba sesuaikan kata kunci atau reset filter.</p>
            </div>
            @endforelse
        </div>

        <!-- Tampilan Tabel Desktop (Flat & Editorial) -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar w-full">
                <table class="w-full text-left text-xs border-collapse min-w-[700px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="px-4 py-3 border-r border-slate-200 w-28">NPM</th>
                            <th class="px-4 py-3 border-r border-slate-200">Nama Lengkap</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-32">Peran / Role</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-32 text-center">Semester</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-32 text-center">Status Akademik</th>
                            <th class="px-4 py-3 text-right w-44">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($users as $u)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 border-r border-slate-200 text-slate-900 font-mono font-semibold">{{ $u->npm }}</td>
                            <td class="px-4 py-3 border-r border-slate-200">
                                <div class="font-semibold text-slate-900">{{ $u->name }}</div>
                            </td>
                            <td class="px-4 py-3 border-r border-slate-200">
                                <form method="POST" action="{{ route('admin.users.updateRole', $u) }}" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" onchange="if(confirm('Apakah Anda yakin ingin mengubah peran pengguna {{ addslashes($u->name) }} menjadi ' + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = '{{ $u->role }}'; }" class="text-xs border border-slate-300 rounded-md px-2 py-0.5 font-semibold bg-white
                                        @if($u->role === 'admin') text-purple-700
                                        @elseif($u->role === 'bendahara') text-emerald-700
                                        @elseif($u->role === 'ketua') text-sky-700
                                        @else text-slate-700 @endif">
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="bendahara" {{ $u->role === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                                        <option value="ketua" {{ $u->role === 'ketua' ? 'selected' : '' }}>Ketua</option>
                                        <option value="mahasiswa" {{ $u->role === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 border-r border-slate-200 text-center font-semibold text-slate-800">
                                {{ $u->semester }}
                            </td>
                            <td class="px-4 py-3 border-r border-slate-200 text-center">
                                <span class="text-[11px] font-bold {{ $u->status_akademik === 'Aktif' ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ $u->status_akademik ?? 'Aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button onclick="openAcademicModal({{ json_encode($u) }})" 
                                            class="text-slate-700 hover:text-slate-900 hover:underline text-[11px] font-semibold transition">
                                        Akademik
                                    </button>
                                    @if($u->id !== auth()->id())
                                    <button onclick="openResetModal('{{ $u->id }}', '{{ addslashes($u->name) }}', '{{ $u->npm }}')" 
                                            class="text-slate-700 hover:text-slate-900 hover:underline text-[11px] font-semibold transition">
                                        Sandi
                                    </button>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus akun pengguna \'{{ addslashes($u->name) }}\' secara permanen dari sistem?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline text-[11px] font-semibold transition">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-600">
                                <p class="text-sm font-semibold text-slate-800">Tidak ada pengguna yang cocok dengan kriteria filter.</p>
                                <p class="text-xs text-slate-600 mt-1">Coba sesuaikan kata kunci atau reset filter semester dan status.</p>
                                <div class="mt-4 flex items-center justify-center">
                                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 min-h-[44px] bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg border border-slate-300 transition">
                                        Reset Filter &amp; Tampilkan Semua Pengguna
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
        <div class="pt-2 overflow-x-auto">
            {{ $users->links() }}
        </div>
        @endif
    </section>
</div>

<!-- MODAL KENAIKAN SEMESTER MASSAL (S1 INFORMATIKA UPS TEGAL) -->
<div id="modal-promote" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-white rounded-xl border border-slate-200 w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">Promosi Kenaikan Semester Massal S1</h3>
                <span class="text-xs text-blue-600 font-semibold">Prodi Informatika &bull; FTIK Universitas Pancasakti Tegal</span>
            </div>
            <button onclick="document.getElementById('modal-promote').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold p-1 cursor-pointer" aria-label="Tutup modal promosi semester">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.users.promoteSemester') }}" class="space-y-4" onsubmit="return confirm('PERINGATAN BESAR: Anda akan menaikkan semester seluruh mahasiswa aktif S1 Informatika. Mahasiswa Semester 8 akan berstatus Lulus/Alumni. Lanjutkan proses ini?')">
            @csrf

            <!-- Penjelasan Alur Kenaikan -->
            <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-lg text-blue-900 text-xs leading-relaxed space-y-1.5">
                <div class="font-bold text-blue-950">
                    Alur Promosi Semester Otomatis:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-blue-800">
                    <li>Semester 1 naik ke Semester 2, Semester 2 naik ke Semester 3, dan seterusnya.</li>
                    <li><strong>Kondisi Khusus Semester 8:</strong> Mahasiswa yang sudah di Semester 8 otomatis berstatus <strong>"Lulus / Alumni"</strong>.</li>
                </ul>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran / Periode Akademik Baru</label>
                <input type="text" name="tahun_ajaran_baru" required 
                       value="{{ date('Y') }}/{{ date('Y') + 1 }} Ganjil"
                       placeholder="Contoh: 2024/2025 Genap atau 2025/2026 Ganjil" 
                       class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900">
            </div>

            <!-- Fitur Carry Forward Saldo Antar Semester -->
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-lg">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox" name="carry_forward_saldo" value="1" checked 
                           class="mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <div class="ml-2.5 text-xs text-emerald-900">
                        <strong class="font-bold block">Tutup Buku & Pindahkan Sisa Saldo Kas (Carry Forward)</strong>
                        <span class="text-[11px] text-emerald-700 block mt-0.5">
                            Sistem secara otomatis menghitung surplus/sisa kas akhir semester saat ini dan memasukkannya sebagai saldo awal di semester baru.
                        </span>
                    </div>
                </label>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-promote').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" onclick="return confirm('Konfirmasi kenaikan semester massal mahasiswa S1 Informatika?')" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition">
                    Eksekusi Kenaikan Semester
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH AKUN BARU -->
<div id="modal-user" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 {{ (session('error_modal') === 'user' || ($errors->any() && $errors->hasAny(['name', 'npm', 'password', 'role', 'semester']))) ? '' : 'hidden' }} p-4">
    <div class="bg-white rounded-xl border border-slate-200 w-full max-w-md max-h-[90dvh] flex flex-col p-5 sm:p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 shrink-0">
            <h3 class="text-sm font-bold text-slate-900">Buat Akun Mahasiswa / Pengurus Baru</h3>
            <button onclick="document.getElementById('modal-user').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold p-1 cursor-pointer" aria-label="Tutup modal buat akun">&times;</button>
        </div>

        @if(session('error_modal') === 'user' && $errors->any())
        <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs shrink-0">
            <p class="font-bold text-rose-900 mb-1">Gagal Menambahkan Akun:</p>
            <ul class="list-disc pl-4 space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 overflow-y-auto flex-1 custom-scrollbar pr-1" onsubmit="return confirm('Apakah Anda yakin data akun pengguna baru sudah benar dan ingin disimpan?')">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="Nama lengkap mahasiswa" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('name') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('name')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">NPM (Nomor Pokok Mahasiswa) <span class="text-rose-500">*</span></label>
                <input type="text" name="npm" required value="{{ old('npm') }}" placeholder="Nomor Pokok Mahasiswa UPS Tegal" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('npm') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('npm')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Awal <span class="text-rose-500">*</span></label>
                <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('password') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('password')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Peran / Role <span class="text-rose-500">*</span></label>
                    <select name="role" required class="w-full text-xs px-3 py-2 border {{ $errors->has('role') ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                        <option value="mahasiswa" {{ old('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="bendahara" {{ old('role') === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                        <option value="ketua" {{ old('role') === 'ketua' ? 'selected' : '' }}>Ketua Kelas</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Semester S1 <span class="text-rose-500">*</span></label>
                    <select name="semester" required class="w-full text-xs px-3 py-2 border {{ $errors->has('semester') ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem }}" {{ (old('semester') ? old('semester') === $sem : $sem === $activeClassSemester) ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-user').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition">Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL UPDATE STATUS AKADEMIK INDIVIDU -->
<div id="modal-academic" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-white rounded-xl border border-slate-200 w-full max-w-sm p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Perbarui Status Akademik</h3>
            <button onclick="document.getElementById('modal-academic').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 text-xl font-bold p-1 cursor-pointer" aria-label="Tutup modal status akademik">&times;</button>
        </div>

        <form method="POST" id="form-academic" action="" class="space-y-4" onsubmit="return confirm('Apakah Anda yakin ingin memperbarui data status akademik pengguna ini?')">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs text-slate-500 mb-0.5">Nama Mahasiswa:</label>
                <div class="font-bold text-slate-900 text-sm" id="academic-user-name">-</div>
                <div class="text-xs text-slate-500 font-mono" id="academic-user-npm">-</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Semester S1</label>
                <select name="semester" id="academic-semester-select" required class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900">
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}">{{ $sem }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akademik</label>
                <select name="status_akademik" id="academic-status-select" required class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900">
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus / Alumni">Lulus / Alumni</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran (Periode 2024 - 2028)</label>
                <select name="tahun_ajaran" id="academic-tahun-ajaran" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900 bg-white">
                    @foreach(\App\Services\AcademicService::TAHUN_AJARAN_LIST as $ta)
                        <option value="{{ $ta }}">{{ $ta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-academic').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div id="modal-reset" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 {{ (session('error_modal') === 'reset_password' || $errors->has('new_password')) ? '' : 'hidden' }} p-4">
    <div class="bg-white rounded-xl border border-slate-200 w-full max-w-sm p-6 shadow-xl">
        <h3 class="text-sm font-bold text-slate-900 mb-1">Reset Password</h3>
        <p id="reset-user-name" class="text-xs text-slate-500 mb-4"></p>

        @if(session('error_modal') === 'reset_password' && $errors->any())
        <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs">
            <ul class="list-disc pl-4 space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="form-reset" method="POST" action="" class="space-y-3" onsubmit="return confirm('Apakah Anda yakin ingin menyetel ulang kata sandi pengguna ini?')">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter" 
                       class="w-full text-xs px-3 py-2 border {{ $errors->has('new_password') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} rounded-lg focus:ring-2 focus:ring-slate-900">
                @error('new_password')
                    <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-reset').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs rounded-lg hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition">Setel Ulang</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openResetModal(userId, userName, userNpm) {
        document.getElementById('reset-user-name').innerText = 'Setel ulang sandi untuk ' + userName + ' (' + userNpm + ')';
        document.getElementById('form-reset').action = '/admin/users/' + userId + '/reset-password';
        document.getElementById('modal-reset').classList.remove('hidden');
    }

    function openAcademicModal(u) {
        document.getElementById('form-academic').action = '/admin/users/' + u.id + '/academic';
        document.getElementById('academic-user-name').textContent = u.name;
        document.getElementById('academic-user-npm').textContent = 'NPM: ' + u.npm;
        document.getElementById('academic-semester-select').value = u.semester || 'Semester 1';
        document.getElementById('academic-status-select').value = u.status_akademik || 'Aktif';
        document.getElementById('academic-tahun-ajaran').value = u.tahun_ajaran || '2024/2025';
        document.getElementById('modal-academic').classList.remove('hidden');
    }

    // Keyboard navigation: Escape key closes any open modal (R-32)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = ['modal-promote', 'modal-user', 'modal-academic', 'modal-reset'];
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
