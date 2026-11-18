# Sistem Pengelolaan Kas Kelas

**Tugas Framework Laravel** — Program Studi S1 Informatika, Fakultas Teknik dan Ilmu Komputer (FTIK), Universitas Pancasakti Tegal.

## Identitas Kelompok

**Kelompok G**

1. Tegar Satria
2. Nahel Ahya Ramadhani
3. Octoryo Qoffanus Shidqi

## Fitur Utama

- **Autentikasi & Hak Akses (Role-Based Access Control)**: Login berbasis NPM dengan 4 role (`Admin`, `Ketua Kelas`, `Bendahara`, dan `Mahasiswa`) serta ganti kata sandi mandiri.
- **Dashboard Kelas**: Ringkasan identitas kelas & semester aktif, status iuran kas bulan berjalan (Minggu 1–4), serta info pengurus kelas aktif (Ketua & Bendahara Kelas).
- **Buku Kas Kelas**: Matriks iuran mingguan mahasiswa (M1–M4) sinkron dengan rentang tanggal kalender, pencatatan pemasukan/pengeluaran beserta bukti kuitansi, pengaturan tarif iuran, cetak nota, serta ekspor rekap ke Excel.
- **Arsip & Rekapitulasi Kas**: Ringkasan saldo, pemasukan, dan pengeluaran berdasarkan mode *Per Bulan*, *Per Semester*, dan *Semua Periode (Semester 1–8)*.
- **Kelola Akun & Semester**: Manajemen akun mahasiswa/pengurus, ubah role, reset password, sinkronisasi semester aktif kelas, serta kenaikan semester massal.

## Fitur Unggulan: AI Chatbot Asisten Kas (CRUD & Otomasi)

Aplikasi dilengkapi asisten AI interaktif (popup *draggable* di desktop maupun mobile) yang terhubung langsung ke database Laravel secara real-time—bukan sekadar tanya jawab teks biasa:

- **Eksekusi CRUD Langsung via Chat (Two-Tier Intent Parser + Konfirmasi)**:
  - **Bendahara (Manajemen Kas & Pemulihan Backup)**: Catat pemasukan/pengeluaran kas, otomatis melunasi iuran mingguan mahasiswa, edit transaksi, hapus transaksi, batalkan iuran mingguan, atur tarif iuran per minggu, serta **melihat daftar transaksi terhapus dan memulihkannya (*restore*)** langsung via chat (dilengkapi tombol pesan cepat *Backup Terhapus* khusus Bendahara).
  - **Admin & Ketua Kelas (Akun & Semester)**: Tambah akun pengguna baru, ubah role akun, hapus akun, serta ubah semester aktif kelas via perintah bahasa alami.
  - **Alur Konfirmasi & Audit Trail**: Setiap perintah perubahan data (CRUD) maupun pemulihan backup menampilkan ringkasan konfirmasi terlebih dahulu sebelum dieksekusi dan otomatis dicatat ke tabel `audit_logs`.
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

## Fitur Keamanan, Arsitektur & Teknis Laravel

Selain fungsionalitas utama kas kelas, sistem ini menerapkan standar keamanan dan arsitektur framework Laravel berikut:

- **Login Rate Limiting & Proteksi Brute-Force (`RateLimiter`)**: Membatasi percobaan login gagal berdasarkan kombinasi identitas (`NPM/Username`) dan `IP Address`. Setelah **3x gagal login**, akses otomatis diblokir sementara selama **5 menit (300 detik)** dengan hitungan mundur waktu tunggu dan pencatatan ke log audit.
- **Role-Based Access Control (Custom Middleware `CheckRole`)**: Proteksi rute tingkat server (`role:bendahara` untuk seluruh operasi transaksi & iuran kas; `role:admin,ketua` untuk manajemen akun & kenaikan semester) sehingga manipulasi URL langsung tetap ditolak.
- **Enkripsi Kata Sandi (Bcrypt Hashing)**: Seluruh kata sandi diamankan menggunakan `Hash::make()` / `Hash::check()` (Bcrypt) dilengkapi mekanisme *self-healing* otomatis jika terdapat data sandi lama belum ter-hash.
- **Proteksi CSRF & Auto-Refresh Token (Anti-419)**: Menggunakan middleware `VerifyCsrfToken`, endpoint penyegaran token otomatis (`/csrf-token`) untuk mencegah error *419 Page Expired* saat halaman lama terbuka, serta halaman error khusus `419.blade.php`.
- **Integritas Data dengan Database Transaction (`DB::transaction`)**: Operasi pembayaran dan pembatalan iuran dibungkus transaksi atomik agar sinkronisasi antara tabel `dues_payments` dan `kas_transactions` tetap konsisten dan bebas data timpang.
- **Backup & Pemulihan Transaksi Kas (`SoftDeletes` + Snapshot `Before`/`After`)**:
  - Setiap penghapusan transaksi pada `kas_transactions` dan `dues_payments` menggunakan **Soft Delete** (`deleted_at`) dan tidak menghapus file foto bukti nota dari storage, sehingga data tetap aman tersimpan sebagai backup.
  - Setiap pengeditan transaksi otomatis menyimpan snapshot data lama (**`before`**) dan data baru (**`after`**) dalam format JSON pada tabel `audit_logs`.
  - **Hak Akses Eksklusif Bendahara**: Melihat daftar transaksi terhapus dan memulihkannya (*restore*) kembali ke pembukuan aktif beserta status iuran mahasiswa terkait dapat dilakukan langsung melalui **AI Chatbot** (termasuk tombol pesan cepat khusus Bendahara) dan ditolak otomatis untuk role lain (`Admin`, `Ketua Kelas`, maupun `Mahasiswa`).
- **Audit Trail / Jejak Aktivitas (`AuditLog`)**: Setiap aksi penting (mutasi kas, iuran, ubah tarif, kelola akun, blokir login, maupun moderasi chatbot) terekam di tabel `audit_logs` beserta pelaku, sumber aksi (`web_form` / `chatbot`), payload JSON, IP Address, dan User Agent.
- **Service Layer & Dynamic Settings (`AcademicService`)**: Memisahkan logika kalender akademik (Semester 1–8, label kelas otomatis, rentang tanggal 4 minggu per bulan) serta konfigurasi tarif iuran dinamis dari controller agar kode tetap modular.
- **Validasi Server-Side Bahasa Indonesia**: Validasi input form dan berkas unggahan (format JPG/PNG, batas ukuran) dengan pesan error terlokalisasi penuh dalam Bahasa Indonesia (`resources/lang/id/validation.php`).
- **Optimasi Performa & Responsivitas**: Menggunakan aset produksi **Tailwind CSS** terkompilasi (*minified*), gambar format **WebP** dengan fallback PNG, serta tata letak adaptif (kartu kompak di mobile dan tabel penuh di desktop).

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
