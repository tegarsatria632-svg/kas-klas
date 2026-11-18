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
- **AI Chatbot Asisten Kas**: Asisten interaktif untuk menanyakan informasi saldo, tunggakan, dan pembuatan draf pengingat iuran kas.

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
