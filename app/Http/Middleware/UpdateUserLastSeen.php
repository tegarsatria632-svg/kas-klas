<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateUserLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Perbarui aktivitas jika belum tercatat atau sudah lewat 3 detik
            if (!$user->last_seen_at || now()->diffInSeconds($user->last_seen_at) >= 3) {
                $user->timestamps = false;
                $user->forceFill(['last_seen_at' => now()])->save();
            }
        }

        return $next($request);
    }
}
