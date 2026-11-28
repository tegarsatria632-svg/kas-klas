<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Terapkan HTTP security headers secara konsisten pada seluruh respons Laravel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if (!config('security.enabled', true) || !($response instanceof Response)) {
            return $response;
        }

        // 1. X-Content-Type-Options: mencegah MIME-type sniffing
        $nosniff = config('security.x_content_type_options', 'nosniff');
        if (!empty($nosniff) && !$response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', $nosniff);
        }

        // 2. X-Frame-Options: mencegah clickjacking (selaras dengan frame-ancestors)
        $frameOptions = config('security.x_frame_options', 'SAMEORIGIN');
        if (!empty($frameOptions) && !$response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', $frameOptions);
        }

        // 3. Referrer-Policy: melindungi path/query sensitif saat navigasi lintas-origin
        $referrerPolicy = config('security.referrer_policy', 'strict-origin-when-cross-origin');
        if (!empty($referrerPolicy) && !$response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        // 4. Permissions-Policy: membatasi API browser yang tidak digunakan aplikasi
        $permissionsPolicy = config('security.permissions_policy');
        if (!empty($permissionsPolicy) && !$response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        // 5. Cross-Origin Isolation headers yang aman dan kompatibel
        $coop = config('security.coop');
        if (!empty($coop) && !$response->headers->has('Cross-Origin-Opener-Policy')) {
            $response->headers->set('Cross-Origin-Opener-Policy', $coop);
        }

        $corp = config('security.corp');
        if (!empty($corp) && !$response->headers->has('Cross-Origin-Resource-Policy')) {
            $response->headers->set('Cross-Origin-Resource-Policy', $corp);
        }

        $coep = config('security.coep');
        if (!empty($coep) && !$response->headers->has('Cross-Origin-Embedder-Policy')) {
            $response->headers->set('Cross-Origin-Embedder-Policy', $coep);
        }

        // 6. Content Security Policy (Report-Only atau Enforce berdasarkan konfigurasi)
        $this->applyContentSecurityPolicy($request, $response);

        // 7. Sembunyikan versi runtime PHP (X-Powered-By)
        if (config('security.remove_powered_by', true)) {
            $response->headers->remove('X-Powered-By');
            if (function_exists('header_remove') && !headers_sent()) {
                @header_remove('X-Powered-By');
            }
        }

        return $response;
    }

    /**
     * Bangun dan pasang header Content-Security-Policy atau Content-Security-Policy-Report-Only.
     */
    protected function applyContentSecurityPolicy(Request $request, Response $response): void
    {
        if (!config('security.csp.enabled', true)) {
            return;
        }

        $mode = strtolower((string) config('security.csp.mode', 'enforce'));
        if ($mode === 'off' || $mode === 'disabled' || $mode === '') {
            return;
        }

        $headerName = $mode === 'report-only'
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        if ($response->headers->has($headerName)) {
            return;
        }

        $directives = config('security.csp.directives', []);
        if (!is_array($directives) || empty($directives)) {
            return;
        }

        $parts = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources)) {
                $cleanSources = array_values(array_filter(array_map('trim', $sources), function ($s) {
                    return $s !== '';
                }));
                if (!empty($cleanSources)) {
                    $parts[] = $directive . ' ' . implode(' ', $cleanSources);
                }
            } elseif (is_string($sources) && trim($sources) !== '') {
                $parts[] = $directive . ' ' . trim($sources);
            }
        }

        // Aktifkan upgrade-insecure-requests hanya pada HTTPS agar tidak mengganggu HTTP lokal (XAMPP/localhost)
        $isHttps = $request->isSecure()
            || strtolower((string) $request->header('X-Forwarded-Proto', '')) === 'https';

        if ($isHttps && config('security.csp.upgrade_insecure_requests', true) && $mode === 'enforce') {
            $parts[] = 'upgrade-insecure-requests';
        }

        $reportUri = trim((string) config('security.csp.report_uri', ''));
        if ($reportUri !== '') {
            $parts[] = 'report-uri ' . $reportUri;
        }

        if (!empty($parts)) {
            $response->headers->set($headerName, implode('; ', $parts));
        }
    }
}
