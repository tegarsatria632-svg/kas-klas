<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArsipKasController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication (NPM based)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Anti-419 CSRF Token Auto-Refresh Endpoint (Guest & Auth)
Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
})->name('csrf.token');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengaturan Sandi Mandiri (User Ganti Password Sendiri)
    Route::post('/password/update', [AuthController::class, 'updatePassword'])->name('password.update');

    // AI Chatbot Routes
    Route::post('/chatbot/chat', [ChatbotController::class, 'chat'])->name('chatbot.chat');
    Route::post('/chatbot/clear', [ChatbotController::class, 'clearHistory'])->name('chatbot.clear');

    // Redirect Direktori Mahasiswa ke Kelola Akun & Semester
    Route::get('/mahasiswa', function () {
        return redirect()->route('admin.users.index');
    })->middleware('role:admin,ketua')->name('mahasiswa.index');

    // Modul Kas (CRUD Khusus Bendahara Kelas)
    Route::get('/kas', [KasController::class, 'index'])->name('kas.index');
    Route::get('/kas/export-excel', [KasController::class, 'exportExcel'])->name('kas.exportExcel');
    Route::get('/kas/nota/{id}', [KasController::class, 'showNota'])->name('kas.nota');
    Route::middleware('role:bendahara')->group(function () {
        Route::post('/kas', [KasController::class, 'store'])->name('kas.store');
        Route::patch('/kas/{ka}', [KasController::class, 'update'])->name('kas.update');
        Route::post('/kas/pay-dues', [KasController::class, 'payDues'])->name('kas.payDues');
        Route::post('/kas/cancel-dues', [KasController::class, 'cancelDues'])->name('kas.cancelDues');
        Route::post('/kas/dues-rate', [KasController::class, 'updateDuesRate'])->name('kas.updateDuesRate');
        Route::delete('/kas/{ka}', [KasController::class, 'destroy'])->name('kas.destroy');
    });

    // Modul Arsip & Rekapitulasi Kas (Menu Tersendiri: Per Bulan, Semester, Tahun, Semua Periode)
    Route::get('/arsip', [ArsipKasController::class, 'index'])->name('arsip.index');

    // Modul Admin Users & Manajemen Kenaikan Semester S1 (Khusus Super Admin & Ketua Kelas)
    Route::middleware('role:admin,ketua')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/set-active-semester', [UserController::class, 'setActiveSemester'])->name('users.setActiveSemester');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
        Route::patch('/users/{user}/academic', [UserController::class, 'updateAcademic'])->name('users.updateAcademic');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/promote-semester', [UserController::class, 'promoteSemester'])->name('users.promoteSemester');
    });
});
