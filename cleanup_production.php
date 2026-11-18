<?php
/**
 * Script untuk membersihkan data transaksi di database production.
 * HANYA menghapus data transaksi, TIDAK menghapus akun user.
 * Jalankan sekali saja via browser, lalu HAPUS file ini.
 */

// Auto detect bootstrap path
$autoload = null;
$bootstrap = null;
$paths = [
    __DIR__.'/../laravel_core',
    __DIR__.'/..',
    __DIR__,
];

foreach ($paths as $p) {
    if (file_exists($p . '/vendor/autoload.php') && file_exists($p . '/bootstrap/app.php')) {
        $autoload = $p . '/vendor/autoload.php';
        $bootstrap = $p . '/bootstrap/app.php';
        break;
    }
}

if (!$autoload) {
    die("<h3 style='color:red'>File vendor/autoload.php tidak ditemukan. Pastikan file ini berada di public_html atau sejajar dengan folder laravel_core.</h3>");
}

require $autoload;
$app = require_once $bootstrap;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "<h2>🧹 Membersihkan Data Transaksi...</h2>";
echo "<p><strong>Akun user TIDAK akan dihapus.</strong></p><hr>";

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Hapus semua transaksi kas
    $kas = DB::table('kas_transactions')->count();
    DB::table('kas_transactions')->truncate();
    echo "<p>✅ kas_transactions: $kas data berhasil dihapus</p>";

    // Hapus semua pembayaran iuran
    $dues = DB::table('dues_payments')->count();
    DB::table('dues_payments')->truncate();
    echo "<p>✅ dues_payments: $dues data berhasil dihapus</p>";

    // Hapus semua peminjaman barang jika tabel masih ada
    if (Schema::hasTable('item_loans')) {
        $loans = DB::table('item_loans')->count();
        DB::table('item_loans')->truncate();
        echo "<p>✅ item_loans: $loans riwayat pinjaman berhasil dihapus</p>";
    }

    // Bersihkan session lama
    if (Schema::hasTable('sessions')) {
        DB::table('sessions')->truncate();
        echo "<p>✅ sessions: sesi lama dibersihkan</p>";
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    echo "<hr><h2>🎉 Selesai! Semua transaksi berhasil dihapus.</h2>";
    echo "<p>Akun user tetap aman. Silakan <a href='/'>buka website</a>.</p>";
    echo "<p><strong style='color:red'>⚠️ HAPUS FILE INI (cleanup.php) SETELAH SELESAI!</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
