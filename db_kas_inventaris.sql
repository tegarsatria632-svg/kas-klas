-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Sep 2026 pada 14.22
-- Versi server: 10.4.24-MariaDB
-- Versi PHP: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kas_inventaris`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `dues_payments`
--

CREATE TABLE `dues_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `kas_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `week_number` tinyint(3) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Semester 1',
  `status` enum('lunas','belum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `dues_payments`
--

INSERT INTO `dues_payments` (`id`, `user_id`, `kas_transaction_id`, `week_number`, `month`, `year`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(7, 37, 6, 1, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 09:13:20', '2026-09-26 09:13:20'),
(8, 37, 6, 2, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 09:13:20', '2026-09-26 09:13:20'),
(9, 37, 6, 3, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 09:13:20', '2026-09-26 09:13:20'),
(14, 37, 6, 4, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 13:37:14', '2026-09-26 13:37:14'),
(15, 1, 15, 1, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 14:09:56', '2026-09-26 14:09:56'),
(16, 1, 16, 2, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 14:10:44', '2026-09-26 14:10:44'),
(17, 1, 17, 3, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 14:21:56', '2026-09-26 14:21:56'),
(18, 1, 17, 4, 9, 2026, 'Semester 5', 'lunas', '2026-09-26 14:21:56', '2026-09-26 14:21:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `items`
--

CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `qty_rusak_berat` int(11) NOT NULL DEFAULT 0,
  `qty_rusak_ringan` int(11) NOT NULL DEFAULT 0,
  `condition` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'baik',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Semester 1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `items`
--

INSERT INTO `items` (`id`, `name`, `code`, `qty`, `qty_rusak_berat`, `qty_rusak_ringan`, `condition`, `notes`, `semester`, `created_at`, `updated_at`) VALUES
(15, 'Arduino', 'INV-001', 1, 0, 0, 'baik', '[Ditambahkan via AI oleh Ketua gilang putra]', 'Semester 5', '2026-09-26 12:42:02', '2026-09-26 12:42:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `item_loans`
--

CREATE TABLE `item_loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `borrower_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loan_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('dipinjam','kembali') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dipinjam',
  `return_condition` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `item_loans`
--

INSERT INTO `item_loans` (`id`, `item_id`, `borrower_name`, `loan_date`, `return_date`, `status`, `return_condition`, `created_at`, `updated_at`) VALUES
(1, 15, 'tegar satria', '2026-09-26', '2026-09-26', 'kembali', 'baik', '2026-09-26 14:18:33', '2026-09-26 14:18:33'),
(2, 15, 'tegar satria', '2026-09-26', '2026-09-26', 'kembali', 'baik', '2026-09-26 14:18:51', '2026-09-26 14:18:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kas_transactions`
--

CREATE TABLE `kas_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('pemasukan','pengeluaran') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Semester 1',
  `tahun_ajaran` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kas_transactions`
--

INSERT INTO `kas_transactions` (`id`, `user_id`, `type`, `amount`, `date`, `description`, `semester`, `tahun_ajaran`, `proof_image`, `created_at`, `updated_at`) VALUES
(2, NULL, 'pemasukan', '5000.00', '2026-09-26', '[Via AI] Iuran kas mingguan', 'Semester 5', NULL, NULL, '2026-09-26 08:08:32', '2026-09-26 08:08:32'),
(6, 37, 'pemasukan', '20000.00', '2026-09-26', '[Via AI] Iuran Kas: gilang putra (Mg 1, Mg 2, Mg 3, Mg 4 Bln 9/2026 - Semester 5)', 'Semester 5', NULL, NULL, '2026-09-26 08:55:57', '2026-09-26 13:37:14'),
(15, 1, 'pemasukan', '5000.00', '2026-09-26', '[Via AI] Iuran Kas: Tegar Satria (Mg 1 Bln 9/2026 - Semester 5)', 'Semester 5', '2026/2027', NULL, '2026-09-26 14:09:55', '2026-09-26 14:09:55'),
(16, 1, 'pemasukan', '5000.00', '2026-09-26', '[Via AI] Iuran Kas: Tegar Satria (Mg 2 Bln 9/2026 - Semester 5)', 'Semester 5', '2026/2027', NULL, '2026-09-26 14:10:44', '2026-09-26 14:10:44'),
(17, 1, 'pemasukan', '20000.00', '2026-09-26', '[Via AI] Iuran Kas: Tegar Satria (Mg 3, Mg 4 Bln 9/2026 - Semester 5)', 'Semester 5', '2026/2027', NULL, '2026-09-26 14:21:56', '2026-09-26 14:21:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '2026_01_01_000001_create_kas_transactions_table', 1),
(3, '2026_01_01_000002_create_dues_payments_table', 1),
(4, '2026_01_01_000003_create_items_table', 1),
(5, '2026_01_01_000004_create_item_loans_table', 1),
(6, '2026_01_01_000005_add_damage_and_return_condition_to_items', 2),
(7, '2026_01_01_000006_add_multi_semester_support', 3),
(8, '2026_09_26_000001_create_settings_table', 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'dues_rate_per_week', '5000', '2026-09-26 11:24:12', '2026-09-26 11:32:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `npm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','bendahara','ketua','mahasiswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mahasiswa',
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Semester 1',
  `status_akademik` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `tahun_ajaran` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2024/2025',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `npm`, `email`, `password`, `role`, `semester`, `status_akademik`, `tahun_ajaran`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Tegar Satria', '6624600021', 'tegarsatria@ups.ac.id', '$2y$10$pfydGYOOiAiOMSTBtavNDOqYof7L.ResZxxPDzZ4NrXbi52CUEX9q', 'admin', 'Semester 5', 'Aktif', '2026/2027', 'OdkCuSY0Z8RQDR8n6i5Fy3h2xv0NS4TuewNmPLmI9bgle5aaUptyd6E0xlCW', '2026-09-22 14:15:17', '2026-09-26 14:18:51'),
(37, 'gilang putra', '6624600001', '6624600001@mail.upstegal.ac.id', '$2y$10$BYij3RzAwfOcHcLacPaTk.bjFRjQyBxNCm0I.y0xLkSctw2rRyBV2', 'ketua', 'Semester 5', 'Aktif', '2026/2027', NULL, '2026-09-22 23:01:03', '2026-09-26 14:18:51'),
(38, 'lutfi muslim', '6624600003', '6624600003@mail.upstegal.ac.id', '$2y$10$f/OY8YWxN6VBg1LwSdh4/.KttHFFvUyIZjM/LdOxprynUzguyViCu', 'mahasiswa', 'Semester 5', 'Aktif', '2026/2027', NULL, '2026-09-22 23:01:34', '2026-09-26 14:18:51'),
(42, 'Nur Azizah', '6624600013', '6624600013@mail.upstegal.ac.id', '$2y$10$Le8pieXG9H9i3MpRNumT5ezbpvhWSPs4BfD7rQ2ZUUcViu5u20vpi', 'bendahara', 'Semester 5', 'Aktif', '2026/2027', NULL, '2026-09-26 10:29:28', '2026-09-26 14:18:51'),
(43, 'Angga Pradiya', '6624600010', '6624600010@mail.upstegal.ac.id', '$2y$10$IsZ2d9zx9zJCrWWyn3WrY.LxTHvftLYKRb5L/1dLJIgowtgfHkrXe', 'mahasiswa', 'Semester 5', 'Aktif', '2026/2027', NULL, '2026-09-26 13:47:06', '2026-09-26 14:18:51');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `dues_payments`
--
ALTER TABLE `dues_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dues_payments_user_id_foreign` (`user_id`),
  ADD KEY `dues_payments_kas_transaction_id_foreign` (`kas_transaction_id`);

--
-- Indeks untuk tabel `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `items_code_unique` (`code`);

--
-- Indeks untuk tabel `item_loans`
--
ALTER TABLE `item_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_loans_item_id_foreign` (`item_id`);

--
-- Indeks untuk tabel `kas_transactions`
--
ALTER TABLE `kas_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kas_transactions_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_npm_unique` (`npm`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `dues_payments`
--
ALTER TABLE `dues_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `item_loans`
--
ALTER TABLE `item_loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `kas_transactions`
--
ALTER TABLE `kas_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `dues_payments`
--
ALTER TABLE `dues_payments`
  ADD CONSTRAINT `dues_payments_kas_transaction_id_foreign` FOREIGN KEY (`kas_transaction_id`) REFERENCES `kas_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dues_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `item_loans`
--
ALTER TABLE `item_loans`
  ADD CONSTRAINT `item_loans_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kas_transactions`
--
ALTER TABLE `kas_transactions`
  ADD CONSTRAINT `kas_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
