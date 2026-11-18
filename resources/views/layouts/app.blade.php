<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Pengelolaan Kas') - S1 Informatika UPS Tegal</title>
    <!-- Production Compiled Tailwind CSS (High-Performance Static Asset, Zero-JS) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    </noscript>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        /* Custom scrollbar halus untuk area scroll */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Accessible interactive cursor affordances (R-39) */
        button:not(:disabled), [role="button"], select, a[href], label[for] {
            cursor: pointer;
        }
        button:disabled, [disabled] {
            cursor: not-allowed;
        }

        /* Focus visible accessibility standard (R-26, WCAG 2.4.7) */
        :focus-visible {
            outline: 2px solid #0f172a !important;
            outline-offset: 2px !important;
        }

        /* Respect prefers-reduced-motion (R-18, R-37, WCAG 2.3.3) */
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen md:h-screen md:overflow-hidden flex antialiased">

    <!-- Mobile Backdrop Overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-30 hidden md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar (Tetap Diam di Tempat / Fixed Sticky, Tidak Ikut Bergerak Saat Layar Digeser) -->
    <aside id="main-sidebar" 
           class="fixed md:sticky top-0 bottom-0 self-start inset-y-0 left-0 z-40 w-72 md:w-72 h-[100dvh] max-h-[100dvh] bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-hidden transform -translate-x-full md:translate-x-0 transition-all duration-200 ease-in-out">
        
        <!-- Sidebar Brand Header (Diperbesar Khusus Mobile & Desktop agar Elegan & Jelas) -->
        <div class="h-16 md:h-[72px] flex items-center justify-between px-4 md:px-5 border-b border-slate-800 bg-slate-900 text-white shrink-0">
            <div class="flex items-center space-x-3 md:space-x-3.5 min-w-0 flex-1">
                <div class="w-11 h-11 md:w-11 md:h-11 flex items-center justify-center shrink-0">
                    <picture>
                        <source srcset="{{ asset('images/logo_ups.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo_ups.png') }}" alt="UPS Tegal" width="44" height="44" loading="eager" decoding="async" class="w-full h-full object-contain">
                    </picture>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[15px] md:text-[15px] font-extrabold text-white tracking-tight leading-tight">S1 Informatika</div>
                    <div class="text-[11px] md:text-xs font-medium text-slate-300 truncate mt-0.5">Kelas {{ \App\Services\AcademicService::getKelasLabel() }} &bull; UPS Tegal</div>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden text-slate-300 hover:text-white p-2 hover:bg-slate-800 rounded-lg transition shrink-0 ml-1" title="Tutup Menu" aria-label="Tutup Menu Navigasi">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Menu & Actions (Area Menu Tengah) -->
        <div class="p-1.5 md:p-4 flex-1 overflow-y-auto custom-scrollbar">
            <nav class="space-y-0.5 md:space-y-1.5">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center px-3 py-2 md:px-4 md:py-2.5 rounded-lg md:rounded-xl text-[12px] md:text-[14px] transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-semibold' }}">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2.5 md:mr-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('kas.index') }}" 
                   class="flex items-center px-3 py-2 md:px-4 md:py-2.5 rounded-lg md:rounded-xl text-[12px] md:text-[14px] transition-all duration-150 {{ request()->routeIs('kas.*') ? 'bg-slate-900 text-white font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-semibold' }}">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2.5 md:mr-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Buku Kas Kelas</span>
                </a>

                <a href="{{ route('arsip.index') }}" 
                   class="flex items-center px-3 py-2 md:px-4 md:py-2.5 rounded-lg md:rounded-xl text-[12px] md:text-[14px] transition-all duration-150 {{ request()->routeIs('arsip.*') ? 'bg-slate-900 text-white font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-semibold' }}">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2.5 md:mr-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Arsip & Rekapitulasi</span>
                </a>

                @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ketua']))
                <a href="{{ route('admin.users.index') }}" 
                   class="flex items-center px-3 py-2 md:px-4 md:py-2.5 rounded-lg md:rounded-xl text-[12px] md:text-[14px] transition-all duration-150 {{ request()->routeIs('admin.users.*') ? 'bg-slate-900 text-white font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-semibold' }}">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2.5 md:mr-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Kelola Akun & Semester</span>
                </a>
                @endif

                <!-- Pengaturan Sandi Mandiri -->
                @if(auth()->check())
                <button type="button" 
                        onclick="openChangePasswordModal()" 
                        class="w-full flex items-center px-3 py-2 md:px-4 md:py-2.5 rounded-lg md:rounded-xl text-[12px] md:text-[14px] font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-all duration-150 group text-left focus:outline-none"
                        title="Ubah kata sandi akun Anda">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2.5 md:mr-3.5 text-slate-500 group-hover:text-blue-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <span>Pengaturan Sandi</span>
                </button>
                @endif
            </nav>
        </div>

        <!-- 3. FOOTER PINNED (Seamless & Clean, Tanpa Kotak Bertumpuk) -->
        @if(auth()->check())
        <div class="p-3 border-t border-slate-200 bg-white shrink-0 space-y-2">
            <!-- Info Pengguna Bersih & Menyatu -->
            <div class="flex items-center justify-between px-1">
                <div class="min-w-0 pr-2">
                    <div class="text-xs md:text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-500 font-mono">{{ auth()->user()->npm }}</div>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wide
                    @if(auth()->user()->role === 'admin') bg-purple-50 text-purple-700 border border-purple-200
                    @elseif(auth()->user()->role === 'bendahara') bg-emerald-50 text-emerald-700 border border-emerald-200
                    @elseif(auth()->user()->role === 'ketua') bg-amber-50 text-amber-800 border border-amber-200
                    @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                    {{ auth()->user()->role }}
                </span>
            </div>

            <!-- Tombol Logout Langsung (Metode Link GET: 100% Bebas Error 419) -->
            <a href="{{ route('logout') }}" 
               onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')"
               class="w-full min-h-[44px] flex items-center justify-center px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition-all duration-150 group border border-slate-200 hover:border-rose-200" 
               title="Keluar dari akun">
                <svg class="w-4 h-4 mr-2 text-rose-500 group-hover:text-rose-700 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Keluar / Logout</span>
            </a>
        </div>
        @endif
    </aside>

    <!-- Main Workspace (Hanya Halaman Ini yang Bergerak / Bergulir Saat Layar Digeser) -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen md:min-h-0 md:h-screen md:overflow-y-auto custom-scrollbar">
        <!-- Top App Bar -->
        <header class="h-14 bg-white border-b border-slate-200 flex items-center px-3 sm:px-5 justify-between sticky top-0 z-20 shrink-0">
            <div class="flex items-center space-x-2.5">
                <button onclick="toggleSidebar()" 
                        id="sidebar-toggle-btn"
                        class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 transition shrink-0 cursor-pointer"
                        title="Buka/Tutup Menu Navigasi"
                        aria-label="Buka/Tutup Menu Navigasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-900 sm:hidden">
                        Kelas {{ \App\Services\AcademicService::getKelasLabel() }}
                    </span>
                    <span class="text-xs font-bold text-slate-800 hidden sm:inline">
                        S1 Informatika &bull; UPS Tegal
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-1.5 sm:space-x-2">
                <!-- Live Clock (Desktop Only) -->
                <div class="hidden lg:flex items-center space-x-1.5 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                    <span id="live-sync-clock">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} &bull; {{ date('H:i') }} WIB</span>
                </div>

                <span class="inline-flex items-center px-2 py-1 sm:px-2.5 sm:py-1 rounded text-[11px] sm:text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                    <span class="hidden sm:inline">{{ \App\Services\AcademicService::getActiveSemester() }} (Kelas {{ \App\Services\AcademicService::getKelasLabel() }})</span>
                    <span class="sm:hidden">{{ \App\Services\AcademicService::getActiveSemester() }}</span>
                </span>

                @if(auth()->check())
                <span class="inline-flex items-center px-2 py-1 sm:px-2.5 sm:py-1 rounded text-[11px] sm:text-xs font-semibold whitespace-nowrap
                    @if(auth()->user()->role === 'admin') bg-purple-100 text-purple-700 border border-purple-200
                    @elseif(auth()->user()->role === 'bendahara') bg-emerald-100 text-emerald-700 border border-emerald-200
                    @elseif(auth()->user()->role === 'ketua') bg-sky-100 text-sky-700 border border-sky-200
                    @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                    {{ strtoupper(auth()->user()->role) }}
                </span>
                @endif
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mx-3 sm:mx-6 mt-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
                <span class="leading-relaxed">{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-base leading-none ml-2">&times;</button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mx-3 sm:mx-6 mt-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Page Content (Tidak Bergerak / Geser Sembarangan di Layar Mobile) -->
        <main class="p-3 sm:p-5 md:p-6 lg:p-8 flex-1 min-w-0">
            @yield('content')
        </main>
    </div>

    <!-- Modal Ganti Kata Sandi Mandiri (User) -->
    @if(auth()->check())
    <div id="modal-change-password" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md max-h-[90dvh] flex flex-col overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pengaturan Sandi Akun</h3>
                    <p class="text-[11px] text-slate-600">Ubah kata sandi login untuk akun Anda sendiri</p>
                </div>
                <button type="button" onclick="closeChangePasswordModal()" class="text-slate-500 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 transition" title="Tutup Modal" aria-label="Tutup Modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="{{ route('password.update') }}" class="p-5 space-y-4 overflow-y-auto flex-1 custom-scrollbar" onsubmit="return validateAndConfirmPasswordChange(event, this)">
                @csrf

                <!-- User Info Card -->
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono">NPM: {{ auth()->user()->npm }}</div>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded font-semibold bg-slate-200 text-slate-700">
                        {{ strtoupper(auth()->user()->role) }}
                    </span>
                </div>

                @if(session('error_modal') === 'change_password' && $errors->any())
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs">
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Password Lama -->
                <div>
                    <label for="pwd_current" class="block text-xs font-semibold text-slate-700 mb-1">
                        Kata Sandi Saat Ini (Lama) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="current_password" 
                               id="pwd_current" 
                               required 
                               placeholder="Masukkan sandi aktif Anda"
                               class="w-full text-xs rounded-lg border {{ $errors->has('current_password') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility('pwd_current', 'eye_current')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-700 focus:outline-none cursor-pointer"
                                aria-label="Tampilkan atau sembunyikan kata sandi saat ini">
                            <svg id="eye_current" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Password Baru -->
                <div>
                    <label for="pwd_new" class="block text-xs font-semibold text-slate-700 mb-1">
                        Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="new_password" 
                               id="pwd_new" 
                               required 
                               minlength="6"
                               placeholder="Minimal 6 karakter"
                               class="w-full text-xs rounded-lg border {{ $errors->has('new_password') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility('pwd_new', 'eye_new')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-700 focus:outline-none cursor-pointer"
                                aria-label="Tampilkan atau sembunyikan kata sandi baru">
                            <svg id="eye_new" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('new_password')
                        <p class="text-rose-600 text-[11px] mt-1 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                    <p class="text-[10px] text-slate-500 mt-1">Gunakan minimal 6 karakter kombinasi yang mudah Anda ingat.</p>
                </div>

                <!-- Konfirmasi Password Baru -->
                <div>
                    <label for="pwd_confirm" class="block text-xs font-semibold text-slate-700 mb-1">
                        Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="new_password_confirmation" 
                               id="pwd_confirm" 
                               required 
                               minlength="6"
                               placeholder="Ketik ulang sandi baru"
                               class="w-full text-xs rounded-lg border {{ $errors->has('new_password_confirmation') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300' }} px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility('pwd_confirm', 'eye_confirm')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-700 focus:outline-none cursor-pointer"
                                aria-label="Tampilkan atau sembunyikan konfirmasi kata sandi baru">
                            <svg id="eye_confirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Info Hak Akses Admin -->
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-2.5 flex items-start space-x-2 text-[11px] text-blue-800">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Jika Anda lupa kata sandi yang telah diubah, Super Admin dan Ketua Kelas tetap memiliki wewenang untuk mereset kata sandi akun Anda.</span>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" 
                            onclick="closeChangePasswordModal()" 
                            class="px-3.5 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 border border-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 shadow-sm transition">
                        Simpan Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- AI Chatbot Widget -->
    @include('partials.chatbot')

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth < 768) {
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                if (isOpen) {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                }
            } else {
                sidebar.classList.toggle('md:hidden');
            }
        }
        function toggleMobileSidebar() {
            toggleSidebar();
        }

        // Live Real-Time Indonesian Clock
        function updateLiveClock() {
            const el = document.getElementById('live-sync-clock');
            if (!el) return;
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const day = days[now.getDay()];
            const date = now.getDate();
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            el.innerHTML = `${day}, ${date} ${month} ${year} &bull; ${hours}:${minutes}:${seconds} WIB`;
        }
        if (window.innerWidth >= 1024) {
            updateLiveClock();
            setInterval(updateLiveClock, 1000);
        }

        // Real-Time Cross-Role & Cross-Tab Data Synchronization
        (function() {
            let isSyncing = false;
            function triggerSyncReload() {
                if (isSyncing) return;
                // Hindari reload jika user sedang aktif mengetik di form tabel utama
                const activeEl = document.activeElement;
                const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA') && activeEl.id !== 'chatbot-input';
                if (isTyping) return;

                isSyncing = true;
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }

            if (typeof window.BroadcastChannel !== 'undefined') {
                const bc = new BroadcastChannel('ups_class_data_sync_channel');
                bc.onmessage = function(ev) {
                    if (ev.data && ev.data.type === 'DATA_SYNC') {
                        triggerSyncReload();
                    }
                };
            }

            window.addEventListener('storage', function(e) {
                if (e.key === 'ups_class_data_sync_timestamp') {
                    triggerSyncReload();
                }
            });

            @if(session('success'))
            // Jika halaman memuat pesan sukses mutasi form, broadcast ke role/tab lain
            try {
                if (typeof window.BroadcastChannel !== 'undefined') {
                    const bc = new BroadcastChannel('ups_class_data_sync_channel');
                    bc.postMessage({ type: 'DATA_SYNC', timestamp: Date.now() });
                }
                localStorage.setItem('ups_class_data_sync_timestamp', Date.now().toString());
            } catch(e) {}
            @endif
        })();

        // Modal Pengaturan Sandi Mandiri (User)
        function openChangePasswordModal() {
            const modal = document.getElementById('modal-change-password');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function validateAndConfirmPasswordChange(e, form) {
            const cur = form.current_password.value;
            const newPwd = form.new_password.value;
            const conf = form.new_password_confirmation.value;
            if (!cur) {
                alert('Mohon masukkan kata sandi saat ini.');
                return false;
            }
            if (newPwd.length < 6) {
                alert('Kata sandi baru minimal 6 karakter.');
                return false;
            }
            if (newPwd !== conf) {
                alert('Konfirmasi kata sandi tidak cocok dengan kata sandi baru.');
                return false;
            }
            return confirm('Apakah Anda yakin ingin memperbarui kata sandi akun Anda?');
        }

        function closeChangePasswordModal() {
            const modal = document.getElementById('modal-change-password');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function togglePasswordVisibility(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (!field) return;

            if (field.type === 'password') {
                field.type = 'text';
                if (icon) {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
                }
            } else {
                field.type = 'password';
                if (icon) {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                }
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeChangePasswordModal();
            }
        });

        @if((isset($errors) && ($errors->has('current_password') || $errors->has('new_password'))) || session('error_modal') === 'change_password')
        document.addEventListener('DOMContentLoaded', function() {
            openChangePasswordModal();
        });
        @endif

        // Anti-419: Auto-Refresh CSRF Token & Mitigasi Mobile BFCache
        function refreshCsrfToken(forceReloadIfFailed) {
            fetch('{{ route("csrf.token") }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function(data) {
                if (data && data.token) {
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.token);

                    document.querySelectorAll('input[name="_token"]').forEach(function(input) {
                        input.value = data.token;
                    });

                    if (typeof window.CSRF_TOKEN !== 'undefined') {
                        window.CSRF_TOKEN = data.token;
                    }
                }
            })
            .catch(function() {
                if (forceReloadIfFailed) {
                    window.location.reload();
                }
            });
        }

        // 1. Mobile BFCache & Navigasi Riwayat: Trigger refresh saat pageshow
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                refreshCsrfToken(true);
            }
        });

        // 2. Mobile Tab Switch & Layar HP Nyala: Refresh token saat tab aktif kembali
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                refreshCsrfToken(false);
            }
        });

        // 3. Jendela browser kembali fokus
        window.addEventListener('focus', function() {
            refreshCsrfToken(false);
        });

        // 4. Heartbeat interval: Refresh token setiap 5 menit agar sesi tidak kedaluwarsa
        setInterval(function() {
            refreshCsrfToken(false);
        }, 300000);

        // 5. Pre-submit guard: pastikan token terbaru sebelum form dikirim
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form && form.method && form.method.toUpperCase() === 'POST') {
                var metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (metaToken) {
                    var tokenInput = form.querySelector('input[name="_token"]');
                    if (tokenInput && (!tokenInput.value || tokenInput.value !== metaToken)) {
                        tokenInput.value = metaToken;
                    }
                }
            }
        }, true);

        @auth
        // 6. Real-Time Presence Sync (Sinkronisasi Kehadiran 5 Detik)
        function sendPresenceHeartbeat() {
            var metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!metaToken) return;
            fetch('{{ route("heartbeat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': metaToken,
                    'Accept': 'application/json'
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (window.handlePresenceSync && typeof window.handlePresenceSync === 'function') {
                    window.handlePresenceSync(data);
                }
            })
            .catch(function() {});
        }
        setInterval(sendPresenceHeartbeat, 5000);
        setTimeout(sendPresenceHeartbeat, 500);

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                sendPresenceHeartbeat();
            }
        });

        // Mobile BFCache & Tab Restore
        window.addEventListener('pageshow', function() {
            sendPresenceHeartbeat();
        });
        @endauth
    </script>
    @stack('scripts')
</body>
</html>
