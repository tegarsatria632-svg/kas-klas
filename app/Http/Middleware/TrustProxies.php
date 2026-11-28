<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Trust all proxies for shared hosting compatibility.
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Kembalikan daftar trusted proxies dari konfigurasi environment.
     * Default '*' menjaga kompatibilitas Vercel, Docker/Caddy, dan shared hosting cPanel,
     * namun dapat dipersempit lewat env TRUSTED_PROXIES (misal: "127.0.0.1,10.0.0.0/8").
     *
     * @return array|string|null
     */
    protected function proxies()
    {
        $configured = config('security.trusted_proxies', $this->proxies);

        if (is_string($configured)) {
            $trimmed = trim($configured);
            if ($trimmed === '' || $trimmed === 'null') {
                return null;
            }
            if ($trimmed === '*' || $trimmed === '**') {
                return $trimmed;
            }
            return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
        }

        return $configured;
    }

    /**
     * Set the trusted proxy to be the calling IP or safe fallback.
     */
    protected function setTrustedProxyIpAddressesToTheCallingIp(Request $request)
    {
        $callingIp = $request->server->get('REMOTE_ADDR') ?? '127.0.0.1';
        $request->setTrustedProxies([$callingIp], $this->getTrustedHeaderNames());
    }
}
