<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | Sistem Pengelolaan Kas</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f3f4f6;
            color: #374151;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            overflow-x: hidden;
        }
        .err404-container {
            width: 100%;
            max-width: 960px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 36px;
        }
        @media (min-width: 768px) {
            .err404-container {
                flex-direction: row;
                gap: 48px;
            }
        }
        .err404-copy {
            max-width: 440px;
            width: 100%;
            text-align: center;
        }
        @media (min-width: 768px) {
            .err404-copy {
                text-align: left;
            }
        }
        .err404-code {
            font-size: clamp(3.5rem, 8vw, 4.75rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #1f2937;
            margin: 0 0 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .err404-code .digit-mid {
            color: #514b82;
            display: inline-block;
            animation: err404-float 3.2s ease-in-out infinite;
        }
        .err404-headline {
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 300;
            line-height: 1.35;
            color: #374151;
            margin: 0 0 12px;
        }
        .err404-desc {
            font-size: 0.95rem;
            line-height: 1.65;
            color: #4b5563;
            margin: 0 0 28px;
        }
        .err404-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        @media (min-width: 768px) {
            .err404-actions {
                justify-content: flex-start;
            }
        }
        .err404-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            background: #2563eb;
            border: 1px solid transparent;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: background-color 0.15s ease, transform 0.15s ease;
            cursor: pointer;
        }
        .err404-btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .err404-btn-primary:active {
            background: #1e40af;
            transform: translateY(0);
        }
        .err404-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            text-decoration: none;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
        }
        .err404-btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        .err404-btn-primary:focus-visible,
        .err404-btn-secondary:focus-visible {
            outline: 3px solid #514b82;
            outline-offset: 2px;
        }

        /* Ilustrasi SVG Kucing 404 Beranimasi */
        .err404-visual {
            width: 100%;
            max-width: 390px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .err404-svg {
            width: 100%;
            height: auto;
            display: block;
            overflow: visible;
        }
        .cat-tail {
            transform-box: fill-box;
            transform-origin: left bottom;
            animation: cat-tail-sway 3.6s ease-in-out infinite;
        }
        .cat-ear-left {
            transform-box: fill-box;
            transform-origin: center bottom;
            animation: cat-ear-twitch 5.5s ease-in-out infinite;
        }
        .cat-eye-lid {
            transform-box: fill-box;
            transform-origin: center top;
            transform: scaleY(0);
            animation: cat-blink 4.8s ease-in-out infinite;
        }
        .cat-qmark {
            transform-box: fill-box;
            transform-origin: center center;
            animation: err404-float 3.4s ease-in-out infinite;
        }
        .cat-shadow {
            transform-box: fill-box;
            transform-origin: center center;
            animation: cat-shadow-pulse 3.4s ease-in-out infinite;
        }

        @keyframes err404-float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-7px); }
        }
        @keyframes cat-tail-sway {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(9deg); }
        }
        @keyframes cat-ear-twitch {
            0%, 84%, 100% { transform: rotate(0deg); }
            88% { transform: rotate(-10deg); }
            92% { transform: rotate(3deg); }
            96% { transform: rotate(-4deg); }
        }
        @keyframes cat-blink {
            0%, 42%, 48%, 100% { transform: scaleY(0); }
            45% { transform: scaleY(1); }
        }
        @keyframes cat-shadow-pulse {
            0%, 100% { transform: scaleX(1); opacity: 0.45; }
            50% { transform: scaleX(0.95); opacity: 0.35; }
        }

        @media (prefers-reduced-motion: reduce) {
            .err404-code .digit-mid,
            .cat-tail,
            .cat-ear-left,
            .cat-eye-lid,
            .cat-qmark,
            .cat-shadow {
                animation: none !important;
            }
        }
    </style>
</head>
<body>
    <main class="err404-container">
        <section class="err404-copy">
            <h1 class="err404-code" aria-label="Error 404">
                <span>4</span><span class="digit-mid">0</span><span>4</span>
            </h1>
            <p class="err404-headline">Maaf, halaman yang Anda cari tidak ditemukan.</p>
            <p class="err404-desc">
                Jangan khawatir, Anda dapat kembali ke halaman utama Sistem Pengelolaan Kas S1 Informatika UPS Tegal untuk melanjutkan aktivitas.
            </p>
            <div class="err404-actions">
                <a href="{{ url('/') }}" class="err404-btn-primary">
                    Kembali ke Beranda
                </a>
                <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/') }}'; }" class="err404-btn-secondary">
                    Halaman Sebelumnya
                </button>
            </div>
        </section>

        <div class="err404-visual" aria-hidden="true">
            <svg class="err404-svg" viewBox="0 0 420 360" width="420" height="360" xmlns="http://www.w3.org/2000/svg">
                {{-- Latar belakang lembut --}}
                <ellipse class="cat-shadow" cx="205" cy="316" rx="135" ry="18" fill="#cbd5e1" />
                <circle cx="210" cy="178" r="128" fill="#e5e7eb" opacity="0.7" />

                {{-- Awan tanda tanya 404 mengambang --}}
                <g class="cat-qmark">
                    <rect x="282" y="42" width="76" height="44" rx="16" fill="#ffffff" stroke="#cbd5e1" stroke-width="2" />
                    <path d="M298 86 L292 98 L310 86 Z" fill="#ffffff" stroke="#cbd5e1" stroke-width="2" stroke-linejoin="round" />
                    <path d="M296 85 L311 85" stroke="#ffffff" stroke-width="3" />
                    <text x="320" y="71" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-size="22" font-weight="800" fill="#514b82">404?</text>
                </g>

                {{-- Ekor kucing bergoyang lembut --}}
                <g class="cat-tail">
                    <path d="M262 276 C318 272 346 232 332 190 C324 168 302 164 300 182 C298 198 312 206 308 228 C304 250 284 258 258 260 Z" fill="#334155" />
                </g>

                {{-- Tubuh kucing duduk --}}
                <path d="M138 302 C132 232 152 178 205 178 C258 178 278 232 272 302 C271 312 262 316 250 316 L160 316 C148 316 139 312 138 302 Z" fill="#475569" />
                {{-- Dada putih lembut --}}
                <ellipse cx="205" cy="258" rx="34" ry="44" fill="#f8fafc" />

                {{-- Kaki depan --}}
                <rect x="168" y="276" width="24" height="40" rx="12" fill="#334155" />
                <rect x="218" y="276" width="24" height="40" rx="12" fill="#334155" />
                <ellipse cx="180" cy="312" rx="14" ry="7" fill="#f8fafc" />
                <ellipse cx="230" cy="312" rx="14" ry="7" fill="#f8fafc" />

                {{-- Syal aksen ungu-indigo (serasi dengan loader #514b82) --}}
                <path d="M158 186 Q205 206 252 186 L248 202 Q205 220 162 202 Z" fill="#514b82" />
                <path d="M222 198 L238 194 L244 234 L224 228 Z" fill="#514b82" />

                {{-- Telinga --}}
                <g class="cat-ear-left">
                    <path d="M148 136 L142 82 L186 112 Z" fill="#334155" stroke="#334155" stroke-width="6" stroke-linejoin="round" />
                    <path d="M153 127 L150 96 L176 114 Z" fill="#fda4af" opacity="0.8" />
                </g>
                <g>
                    <path d="M262 136 L268 82 L224 112 Z" fill="#334155" stroke="#334155" stroke-width="6" stroke-linejoin="round" />
                    <path d="M257 127 L260 96 L234 114 Z" fill="#fda4af" opacity="0.8" />
                </g>

                {{-- Kepala kucing --}}
                <ellipse cx="205" cy="146" rx="64" ry="52" fill="#475569" />

                {{-- Mata kucing berkedip realistis --}}
                <g>
                    <circle cx="180" cy="144" r="10.5" fill="#ffffff" />
                    <circle cx="181" cy="144" r="6.2" fill="#1e293b" />
                    <circle cx="178.5" cy="141.5" r="2.2" fill="#ffffff" />
                    <rect class="cat-eye-lid" x="168" y="132" width="24" height="24" rx="4" fill="#475569" />
                </g>
                <g>
                    <circle cx="230" cy="144" r="10.5" fill="#ffffff" />
                    <circle cx="231" cy="144" r="6.2" fill="#1e293b" />
                    <circle cx="228.5" cy="141.5" r="2.2" fill="#ffffff" />
                    <rect class="cat-eye-lid" x="218" y="132" width="24" height="24" rx="4" fill="#475569" />
                </g>

                {{-- Hidung, mulut, dan kumis --}}
                <polygon points="205,154 199,161 211,161" fill="#fb7185" />
                <path d="M196 165 Q205 172 205 162 Q205 172 214 165" fill="none" stroke="#f8fafc" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />

                <g stroke="#cbd5e1" stroke-width="2.2" stroke-linecap="round">
                    <line x1="126" y1="150" x2="156" y2="154" />
                    <line x1="124" y1="162" x2="155" y2="162" />
                    <line x1="254" y1="154" x2="284" y2="150" />
                    <line x1="255" y1="162" x2="286" y2="162" />
                </g>
            </svg>
        </div>
    </main>
</body>
</html>
