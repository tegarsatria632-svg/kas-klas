<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Tangani TokenMismatchException dan HTTP 419 (Page Expired) terutama pada perangkat mobile
        $isCsrfMismatch = $e instanceof \Illuminate\Session\TokenMismatchException 
            || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 419);

        if ($isCsrfMismatch) {
            if ($request->expectsJson() || $request->ajax() || $request->is('chatbot/*')) {
                return response()->json([
                    'message' => 'Sesi telah berakhir atau diperbarui. Silakan coba kembali.',
                    'csrf_token' => csrf_token(),
                ], 419);
            }

            if (auth()->check()) {
                return redirect()->back()
                    ->withInput($request->except('_token', 'password', 'password_confirmation'))
                    ->with('warning', 'Sesi formulir telah diperbarui otomatis. Silakan klik simpan kembali.');
            }

            return redirect()->route('login')
                ->withInput($request->only('npm', 'username'))
                ->with('warning', 'Sesi peramban telah diperbarui. Silakan masukkan kata sandi dan klik Masuk kembali.');
        }

        return parent::render($request, $e);
    }
}
