<?php
/**
 * Script Pemeriksaan & Pembersih Cache Khusus
 * Akses via: https://kasit.web.id/bersihkan.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$core = realpath(__DIR__ . '/../laravel_core');
if (!$core) {
    die("Folder laravel_core tidak ditemukan di /home/kasitweb/laravel_core");
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Status Perbaikan</title>";
echo "<style>
    body { font-family: sans-serif; background: #f1f5f9; padding: 24px; color: #1e293b; line-height: 1.6; }
    .card { background: white; border-radius: 12px; padding: 20px; max-width: 650px; margin: 0 auto 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
    .ok { color: #16a34a; font-weight: bold; }
    .err { color: #dc2626; font-weight: bold; }
    .btn { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-weight: bold; }
    pre { background: #0f172a; color: #f8fafc; padding: 12px; border-radius: 8px; font-size: 13px; max-height: 160px; overflow-y: auto; }
</style></head><body>";

echo "<div class='card'>";
echo "<h2>🔍 Pengecekan Status File di Hosting</h2>";

// 1. Cek File Sidebar
$appBlade = $core . '/resources/views/layouts/app.blade.php';
$sidebarOk = false;
if (file_exists($appBlade)) {
    $content = file_get_contents($appBlade);
    if (strpos($content, 'FOOTER PINNED') !== false || strpos($content, 'h-[100dvh]') !== false) {
        $sidebarOk = true;
        echo "<p>1. File Sidebar (<code>app.blade.php</code>): <span class='ok'>✓ SUDAH TERBARU (PINNED FOOTER AKTIF)</span></p>";
    } else {
        echo "<p>1. File Sidebar (<code>app.blade.php</code>): <span class='err'>✗ MASIH VERSI LAMA (Belum Ter-update)</span></p>";
    }
} else {
    echo "<p>1. File Sidebar: <span class='err'>✗ File tidak ditemukan</span></p>";
}

// 2. Cek File Session
$sessionConfig = $core . '/config/session.php';
$sessionOk = false;
if (file_exists($sessionConfig)) {
    $sContent = file_get_contents($sessionConfig);
    if (strpos($sContent, 'kasit.web.id') !== false || strpos($sContent, 'HTTP_X_FORWARDED_PROTO') !== false) {
        $sessionOk = true;
        echo "<p>2. Settingan Session Cookie: <span class='ok'>✓ SUDAH TERBARU (Secure Cookie Aktif)</span></p>";
    } else {
        echo "<p>2. Settingan Session Cookie: <span class='err'>✗ MASIH VERSI LAMA (Belum Ter-update)</span></p>";
    }
}

// 3. Cek .env
$envFile = $core . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/SESSION_DOMAIN=null/i', $envContent)) {
        echo "<p>3. Status <code>.env</code>: <span class='err'>✗ SESSION_DOMAIN=null (Harap kosongkan di .env)</span></p>";
    } else {
        echo "<p>3. Status <code>.env</code>: <span class='ok'>✓ SESSION_DOMAIN Aman</span></p>";
    }
}

echo "<hr style='margin: 16px 0; border: 0; border-top: 1px solid #e2e8f0;'>";

// 4. Bersihkan Cache Views & Bootstrap
echo "<h3>🧹 Pembersihan Cache Otomatis</h3>";

$viewFiles = glob($core . '/storage/framework/views/*.php');
$viewsDeleted = 0;
if ($viewFiles) {
    foreach ($viewFiles as $vf) {
        if (@unlink($vf)) $viewsDeleted++;
    }
}
echo "<p>• File Cache Blade Views: <span class='ok'>$viewsDeleted file berhasil dihapus</span></p>";

$bootFiles = glob($core . '/bootstrap/cache/*.php');
$bootDeleted = 0;
if ($bootFiles) {
    foreach ($bootFiles as $bf) {
        if (@unlink($bf)) $bootDeleted++;
    }
}
echo "<p>• File Cache Bootstrap: <span class='ok'>$bootDeleted file berhasil dihapus</span></p>";

echo "<p style='margin-top:20px;'><a href='/login' class='btn'>👉 Buka Website Sekarang</a></p>";
echo "</div></body></html>";
