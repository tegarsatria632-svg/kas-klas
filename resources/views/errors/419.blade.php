<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Kedaluwarsa - Sistem Pengelolaan Kas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        button, a[href] { cursor: pointer; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 text-slate-800 antialiased">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-md w-full text-center">
        <div class="w-12 h-12 bg-amber-50 border border-amber-200 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h1 class="text-lg font-bold text-slate-900 tracking-tight">Sesi Formulir Diperbarui</h1>
        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Demi keamanan data dan transaksi kas kelas, sesi formulir telah diperbarui secara otomatis. Silakan muat ulang halaman untuk melanjutkan.
        </p>

        <div class="mt-6 space-y-2.5">
            <button onclick="window.location.reload()" 
                    class="w-full py-2.5 px-4 bg-blue-700 hover:bg-blue-800 active:bg-blue-900 text-white text-xs font-bold rounded-lg shadow-xs transition">
                Muat Ulang Halaman
            </button>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" 
               class="block w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                {{ auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Halaman Login' }}
            </a>
        </div>
    </div>
</body>
</html>
