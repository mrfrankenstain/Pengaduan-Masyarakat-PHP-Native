-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Jun 2025 pada 08.13
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pengaduan_masyarakat`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `masyarakat`
--

CREATE TABLE `masyarakat` (
  `nik` char(16) NOT NULL COMMENT 'Nomor Induk Kependudukan, unik dan 16 digit',
  `nama` varchar(35) NOT NULL COMMENT 'Nama lengkap masyarakat',
  `username` varchar(25) NOT NULL COMMENT 'Username untuk login, harus unik',
  `password` varchar(255) NOT NULL COMMENT 'Password yang di-hash menggunakan password_hash()',
  `telp` varchar(13) NOT NULL COMMENT 'Nomor telepon masyarakat, maksimal 13 digit',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pembuatan akun',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Waktu terakhir diperbarui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel untuk menyimpan data masyarakat';

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id_pengaduan` int(11) NOT NULL COMMENT 'ID unik untuk pengaduan',
  `tgl_pengaduan` date NOT NULL COMMENT 'Tanggal pengaduan diajukan',
  `nik` char(16) NOT NULL COMMENT 'NIK masyarakat yang mengajukan pengaduan',
  `isi_laporan` text NOT NULL COMMENT 'Isi laporan pengaduan',
  `foto` varchar(255) DEFAULT NULL COMMENT 'Nama file foto pendukung (opsional)',
  `status` enum('0','proses','selesai') NOT NULL DEFAULT '0' COMMENT 'Status pengaduan: 0 (pending), proses, selesai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pengaduan dibuat',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Waktu terakhir diperbarui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel untuk menyimpan data pengaduan masyarakat';

-- --------------------------------------------------------

--
-- Struktur dari tabel `petugas`
--

CREATE TABLE `petugas` (
  `id_petugas` int(11) NOT NULL COMMENT 'ID unik untuk petugas',
  `nama_petugas` varchar(35) NOT NULL COMMENT 'Nama lengkap petugas',
  `username` varchar(25) NOT NULL COMMENT 'Username untuk login, harus unik',
  `password` varchar(255) NOT NULL COMMENT 'Password yang di-hash menggunakan password_hash()',
  `telp` varchar(13) NOT NULL COMMENT 'Nomor telepon petugas, maksimal 13 digit',
  `status` enum('admin','petugas') NOT NULL COMMENT 'Status petugas: admin atau petugas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pembuatan akun',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Waktu terakhir diperbarui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel untuk menyimpan data petugas dan admin';

--
-- Dumping data untuk tabel `petugas`
--

INSERT INTO `petugas` (`id_petugas`, `nama_petugas`, `username`, `password`, `telp`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Baskoro', 'baskoro', 'baskoro', '081111111111', 'admin', '2025-06-09 03:22:22', '2025-06-09 04:12:12'),
(4, 'arkan', 'arkan', 'arkan', '123124', 'petugas', '2025-06-09 06:06:22', '2025-06-09 06:06:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tanggapan`
--

CREATE TABLE `tanggapan` (
  `id_tanggapan` int(11) NOT NULL COMMENT 'ID unik untuk tanggapan',
  `id_pengaduan` int(11) NOT NULL COMMENT 'ID pengaduan yang ditanggapi',
  `tgl_tanggapan` date NOT NULL COMMENT 'Tanggal tanggapan diberikan',
  `tanggapan` text NOT NULL COMMENT 'Isi tanggapan dari petugas',
  `id_petugas` int(11) NOT NULL COMMENT 'ID petugas yang memberikan tanggapan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu tanggapan dibuat',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Waktu terakhir diperbarui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel untuk menyimpan tanggapan petugas terhadap pengaduan';

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `masyarakat`
--
ALTER TABLE `masyarakat`
  ADD PRIMARY KEY (`nik`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id_pengaduan`),
  ADD KEY `nik` (`nik`);

--
-- Indeks untuk tabel `petugas`
--
ALTER TABLE `petugas`
  ADD PRIMARY KEY (`id_petugas`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `tanggapan`
--
ALTER TABLE `tanggapan`
  ADD PRIMARY KEY (`id_tanggapan`),
  ADD KEY `id_pengaduan` (`id_pengaduan`),
  ADD KEY `id_petugas` (`id_petugas`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id_pengaduan` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik untuk pengaduan', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `petugas`
--
ALTER TABLE `petugas`
  MODIFY `id_petugas` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik untuk petugas', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tanggapan`
--
ALTER TABLE `tanggapan`
  MODIFY `id_tanggapan` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik untuk tanggapan', AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`nik`) REFERENCES `masyarakat` (`nik`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tanggapan`
--
ALTER TABLE `tanggapan`
  ADD CONSTRAINT `tanggapan_ibfk_1` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tanggapan_ibfk_2` FOREIGN KEY (`id_petugas`) REFERENCES `petugas` (`id_petugas`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
