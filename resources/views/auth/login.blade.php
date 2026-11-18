<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Sistem Pengelolaan Kas - S1 Informatika UPS Tegal</title>
    <!-- Production Compiled Tailwind CSS (High-Performance Static Asset, Zero-JS) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
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
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-800 antialiased">

    <!-- Main Container -->
    <main class="w-full max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 sm:p-8">
            
            <!-- Header: Logo, Nama Web & Identitas Kampus -->
            <div class="text-center mb-6">
                <picture>
                    <source srcset="{{ asset('images/logo_ups.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo_ups.png') }}" 
                         alt="Logo Universitas Pancasakti Tegal" 
                         width="80" 
                         height="80"
                         loading="eager"
                         decoding="async"
                         class="w-20 h-20 object-contain mx-auto mb-3">
                </picture>
                
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
                    Sistem Pengelolaan Kas
                </h1>
                
                <div class="text-xs font-bold text-slate-700 tracking-wider uppercase mt-1">
                    UNIVERSITAS PANCASAKTI TEGAL
                </div>
                
                <div class="text-xs text-slate-500 mt-1">
                    Fakultas Teknik dan Ilmu Komputer &bull; S1 Informatika
                </div>

                <div class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                        Kelas {{ \App\Services\AcademicService::getKelasLabel() }} &bull; Angkatan 2024 &bull; {{ \App\Services\AcademicService::getActiveSemester() }}
                    </span>
                </div>
            </div>

            <!-- Alerts: Status & Warning -->
            @if(session('warning'))
                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl flex items-center">
                    <svg class="w-4 h-4 mr-2 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if(session('status'))
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl flex items-center">
                    <svg class="w-4 h-4 mr-2 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Alert Validasi Gagal (Username / Password Salah) -->
            @if(isset($errors) && $errors->any())
                <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-bold text-rose-900 mb-0.5">Validasi Login Tidak Sesuai</p>
                            @if($errors->count() === 1)
                                <p class="text-rose-700 leading-relaxed">{{ $errors->first() }}</p>
                            @else
                                <ul class="list-disc pl-4 space-y-0.5 text-rose-700">
                                    @foreach($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form Login -->
            <form method="POST" action="{{ route('login') }}" target="_self" class="space-y-4" onsubmit="return validateLoginForm(event, this)">
                @csrf

                <!-- Input NPM / Username -->
                <div>
                    <label for="npm" class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                        <span>Nomor Pokok Mahasiswa (NPM) / Username</span>
                        <span class="text-[10px] text-slate-500 font-medium">Wajib diisi</span>
                    </label>
                    <input type="text" 
                           id="npm" 
                           name="npm" 
                           value="{{ old('npm') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           placeholder="Masukkan NPM atau nama akun Anda" 
                           class="w-full px-3.5 py-2.5 text-xs bg-white border {{ $errors->has('npm') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300 focus:border-slate-900 focus:ring-slate-900/10' }} rounded-xl text-slate-900 focus:outline-none focus:ring-2 transition">
                    
                    @error('npm')
                        <p class="mt-1.5 text-[11px] font-semibold text-rose-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                        <span>Kata Sandi (Password)</span>
                        <span class="text-[10px] text-slate-500 font-medium">Wajib diisi</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="Masukkan kata sandi akun Anda" 
                               class="w-full px-3.5 py-2.5 pr-12 text-xs bg-white border {{ $errors->has('password') ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-300 focus:border-slate-900 focus:ring-slate-900/10' }} rounded-xl text-slate-900 focus:outline-none focus:ring-2 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-600 hover:text-slate-900 text-xs font-semibold cursor-pointer"
                                id="btn-toggle-text">
                            Lihat
                        </button>
                    </div>

                    @error('password')
                        <p class="mt-1.5 text-[11px] font-semibold text-rose-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Tombol Masuk -->
                <div class="pt-1">
                    <button type="submit" 
                            id="btn-login-submit"
                            class="w-full min-h-[44px] py-2.5 px-4 bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <span>Masuk ke Sistem</span>
                    </button>
                </div>
            </form>

            <!-- Bantuan Login Fungsional -->
            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-600">
                    Kendala akun atau lupa kata sandi? Hubungi <strong class="text-slate-800">Super Admin</strong> atau <strong class="text-slate-800">Ketua Kelas</strong>.
                </p>
            </div>

        </div>
    </main>

    <!-- Password Visibility Toggle & Client-Side Validation -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('btn-toggle-text');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Tutup';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Lihat';
            }
        }

        let isSubmitting = false;

        function validateLoginForm(e, form) {
            const npmInput = form.npm;
            const passwordInput = form.password;

            const npmVal = npmInput && npmInput.value ? npmInput.value.trim() : '';
            const passVal = passwordInput && passwordInput.value ? passwordInput.value : '';

            if (!npmVal) {
                e.preventDefault();
                if (npmInput) npmInput.focus();
                return false;
            }

            if (!passVal) {
                e.preventDefault();
                if (passwordInput) passwordInput.focus();
                return false;
            }

            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;

            const btn = document.getElementById('btn-login-submit');
            if (btn) {
                // Beri indikator visual loading tanpa mematikan (disable) tombol secara sinkron
                // agar event submit browser tidak dibatalkan oleh engine Blink/WebKit pada mobile view
                btn.classList.add('opacity-80', 'cursor-wait');
                btn.innerHTML = `
                    <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memverifikasi akun...</span>
                `;

                // Tunda disable ke tick berikutnya setelah form submit resmi dilepas oleh browser
                setTimeout(function() {
                    if (btn) btn.disabled = true;
                }, 150);
            }

            return true;
        }

        // Auto-focus pada input yang error jika ada
        window.addEventListener('DOMContentLoaded', () => {
            @if($errors->has('password'))
                const passField = document.getElementById('password');
                if (passField) passField.focus();
            @elseif($errors->has('npm'))
                const npmField = document.getElementById('npm');
                if (npmField) npmField.focus();
            @endif
        });

        // Anti-419: Auto-Refresh CSRF Token & Mitigasi Mobile BFCache pada Form Login
        function refreshLoginCsrfToken(forceReloadIfFailed) {
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
                }
            })
            .catch(function() {
                if (forceReloadIfFailed) {
                    window.location.reload();
                }
            });
        }

        // 1. Mobile BFCache: refresh token saat halaman dibuka dari riwayat/cache & reset status tombol submit
        window.addEventListener('pageshow', function (event) {
            isSubmitting = false;
            const btn = document.getElementById('btn-login-submit');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
                btn.innerHTML = '<span>Masuk ke Sistem</span>';
            }
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                refreshLoginCsrfToken(true);
            }
        });

        // 2. Heartbeat: refresh token setiap 5 menit jika halaman didiamkan
        setInterval(function() {
            refreshLoginCsrfToken(false);
        }, 300000);
    </script>
</body>
</html>
