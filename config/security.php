<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers Master Switch
    |--------------------------------------------------------------------------
    | Memungkinkan nonaktifkan cepat (emergency rollback) via environment
    | tanpa mengubah kode sumber aplikasi.
    */
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    | Default '*' mempertahankan kompatibilitas dengan Vercel, Caddy/Docker,
    | dan shared hosting cPanel yang IP proxy-nya dinamis.
    | Pada infrastruktur dengan IP reverse proxy statis, atur TRUSTED_PROXIES
    | di .env menjadi daftar IP/CIDR dipisah koma (misal: 127.0.0.1,10.0.0.0/8).
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', '*'),

    /*
    |--------------------------------------------------------------------------
    | Core HTTP Security Headers
    |--------------------------------------------------------------------------
    */
    'x_content_type_options' => 'nosniff',
    'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    'permissions_policy' => env(
        'SECURITY_PERMISSIONS_POLICY',
        'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
    ),

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Isolation Headers (COOP / CORP / COEP)
    |--------------------------------------------------------------------------
    | - COOP (same-origin): aman karena tidak ada OAuth popup cross-origin.
    | - CORP (same-origin): aman karena respons Laravel tidak di-embed domain lain.
    | - COEP (false/off): sengaja tidak diaktifkan karena Google Fonts eksternal
    |   berpotensi terblokir di sebagian browser tanpa dukungan CORP penuh.
    */
    'coop' => env('SECURITY_COOP', 'same-origin'),
    'corp' => env('SECURITY_CORP', 'same-origin'),
    'coep' => env('SECURITY_COEP', null),

    /*
    |--------------------------------------------------------------------------
    | Hide Server / Runtime Fingerprint Headers
    |--------------------------------------------------------------------------
    */
    'remove_powered_by' => true,

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    | Mode yang didukung:
    | - 'report-only' : mengirim Content-Security-Policy-Report-Only (uji aman)
    | - 'enforce'     : mengirim Content-Security-Policy (penegakan aktif)
    | - 'off'         : menonaktifkan header CSP
    |
    | Catatan teknis 'unsafe-inline':
    | Saat ini aplikasi memiliki 167 inline event handler (onclick/onsubmit/
    | onchange/onkeydown/onerror/onload) dan 6 blok <script> inline serta
    | inline <style> pada Blade view. Menambahkan 'nonce-...' pada script-src
    | akan membuat browser mengabaikan 'unsafe-inline' dan memblokir seluruh
    | event handler tersebut. Oleh karena itu 'unsafe-inline' dipertahankan
    | sementara hingga migrasi bertahap ke event listener eksternal selesai.
    */
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'mode' => env('SECURITY_CSP_MODE', 'enforce'),
        'upgrade_insecure_requests' => env('SECURITY_CSP_UPGRADE_INSECURE', true),
        'report_uri' => env('SECURITY_CSP_REPORT_URI', null),

        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'", 'https://fonts.gstatic.com', 'data:'],
            'connect-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
    ],
];
