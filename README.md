# Sistem Pengelolaan Kas Kelas

**Tugas Framework Laravel** — Program Studi S1 Informatika, Fakultas Teknik dan Ilmu Komputer (FTIK), Universitas Pancasakti Tegal.

## Identitas Kelompok

**Kelompok G**

1. Tegar Satria
2. Nahel Ahya Ramadhani
3. Octoryo Qoffanus Shidqi

## Fitur Utama

- **Autentikasi & Hak Akses (Role-Based Access Control)**: Login berbasis NPM dengan 4 role (`Admin`, `Ketua Kelas`, `Bendahara`, dan `Mahasiswa`) serta ganti kata sandi mandiri.
- **Dashboard Kelas**: Status tunggakan iuran kas bulanan pribadi (Minggu 1–4) dan daftar anggota kelas dengan indikator status online real-time.
- **Buku Kas Kelas**: Matriks iuran mingguan mahasiswa (M1–M4) sinkron dengan rentang tanggal kalender, pencatatan pemasukan/pengeluaran beserta bukti kuitansi, pengaturan tarif iuran, cetak nota, serta ekspor rekap ke Excel.
- **Arsip & Rekapitulasi Kas**: Ringkasan saldo, pemasukan, dan pengeluaran berdasarkan mode *Per Bulan*, *Per Semester*, dan *Semua Periode (Semester 1–8)*.
- **Kelola Akun & Semester**: Manajemen akun mahasiswa/pengurus, ubah role, reset password, sinkronisasi semester aktif kelas, serta kenaikan semester massal.

## Fitur Unggulan: AI Chatbot Asisten Kas (CRUD & Otomasi)

Aplikasi dilengkapi asisten AI interaktif (popup *draggable* di desktop maupun mobile) yang terhubung langsung ke database Laravel secara real-time—bukan sekadar tanya jawab teks biasa:

- **Eksekusi CRUD Langsung via Chat (Two-Tier Intent Parser + Konfirmasi)**:
  - **Bendahara (Manajemen Kas)**: Catat pemasukan/pengeluaran kas, otomatis melunasi iuran mingguan mahasiswa, edit transaksi, hapus transaksi, batalkan iuran mingguan, hingga atur tarif iuran per minggu langsung dari kolom chat.
  - **Admin & Ketua Kelas (Akun & Semester)**: Tambah akun pengguna baru, ubah role akun, hapus akun, serta ubah semester aktif kelas via perintah bahasa alami.
  - **Alur Konfirmasi & Audit Trail**: Setiap perintah perubahan data (CRUD) menampilkan ringkasan konfirmasi terlebih dahulu sebelum dieksekusi dan otomatis dicatat ke tabel `audit_logs`.
- **Arsitektur Dual-Provider AI (Auto-Fallback)**: Menggunakan **Groq API** sebagai provider utama dengan mekanisme *fallback* otomatis ke **OpenRouter API** jika terjadi kendala kuota atau limit.
- **Utilitas Cerdas Berbasis Data Real-Time**:
  - **Broadcast WhatsApp Tagihan**: Menyusun draf pengingat iuran kas siap salin ke grup WA berdasarkan daftar mahasiswa yang menunggak.
  - **Simulasi Anggaran Kas**: Menghitung estimasi saldo dan proyeksi kecukupan dana kelas untuk rencana pengeluaran.
  - **Tautan Rekap Excel & Jejak Audit**: Akses cepat unduhan laporan Excel dan pemantauan riwayat aktivitas sistem langsung dari chat.
  - **Context-Aware Quick Prompts**: Pilihan tombol cepat otomatis menyesuaikan halaman yang sedang dibuka dan hak akses pengguna.
- **Guardrails Keamanan & Moderasi**:
  - **Kontrol Akses Ketat per Role (RBAC)**: Role `Mahasiswa` dibatasi pada mode baca (*read-only*) dan tidak diizinkan menjalankan aksi perubahan data (CRUD).
  - **Proteksi Privasi**: Menolak otomatis permintaan yang menanyakan password, hash, token, atau API key.
  - **Moderasi Bahasa Otomatis**: Mendeteksi bahasa kasar/toxic/vulgar dengan sanksi pemblokiran akses chatbot sementara otomatis setelah 3 kali pelanggaran.

## Panduan Instalasi Lokal

1. Clone repositori:
   ```bash
   git clone https://github.com/tegarsatria632-svg/kas-klas.git
   cd kas-klas
   ```
2. Install dependensi PHP:
   ```bash
   composer install
   ```
3. Salin file konfigurasi environment dan buat application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Atur konfigurasi database (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) dan API key AI (`GROQ_API_KEY` / `OPENROUTER_API_KEY`) pada file `.env`.
5. Jalankan migrasi dan seeder (atau impor file `db_kas_inventaris.sql`):
   ```bash
   php artisan migrate --seed
   ```
6. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
