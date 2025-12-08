-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 08, 2025 at 03:57 PM
-- Server version: 11.4.8-MariaDB-cll-lve
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `icey4741_icenter`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `nama`, `alamat`, `kontak`, `created_at`) VALUES
(1, 'Budi Santoso', 'Jl. Mawar No. 123, Makassar', '081234567890', '2025-08-01 03:00:00'),
(2, 'Siti Aminah', 'Jl. Anggrek No. 45, Manado', '082345678901', '2025-08-04 07:00:00'),
(3, 'Andi Prasetyo', 'Jl. Melati No. 88, Palu', '083456789012', '2025-08-07 01:45:00'),
(4, 'galigo', 'jl jalanan', '08738568298263', '2025-08-27 07:49:36');

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan_sparepart`
--

CREATE TABLE `detail_penjualan_sparepart` (
  `id` int(11) NOT NULL,
  `penjualan_id` int(11) DEFAULT NULL,
  `code_sparepart` varchar(50) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `harga_satuan` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `gaji` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `nama`, `jabatan`, `gaji`) VALUES
(1, 'Rian', 'Teknisi', 3000000.00),
(2, 'Tono', 'Kasir', 2500000.00),
(3, 'Yuni', 'Admin', 2800000.00),
(4, 'aril', 'Teknisi', 2800000.00);

-- --------------------------------------------------------

--
-- Table structure for table `komisi_karyawan`
--

CREATE TABLE `komisi_karyawan` (
  `id` int(11) NOT NULL,
  `karyawan_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `jumlah` decimal(12,2) DEFAULT NULL,
  `tanggal` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `komisi_karyawan`
--

INSERT INTO `komisi_karyawan` (`id`, `karyawan_id`, `service_id`, `jumlah`, `tanggal`) VALUES
(1, 1, 1, 50000.00, '2025-08-01'),
(2, 1, 2, 30000.00, '2025-08-02');

-- --------------------------------------------------------

--
-- Table structure for table `master_sparepart`
--

CREATE TABLE `master_sparepart` (
  `id` int(11) NOT NULL,
  `code_sparepart` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `satuan` varchar(20) DEFAULT 'pcs',
  `harga_beli` decimal(12,2) DEFAULT 0.00,
  `harga_jual` decimal(12,2) DEFAULT 0.00,
  `supplier_merek` varchar(100) DEFAULT NULL,
  `stok_tersedia` int(11) DEFAULT 0,
  `stok_minimum` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `master_sparepart`
--

INSERT INTO `master_sparepart` (`id`, `code_sparepart`, `nama`, `model`, `kategori`, `satuan`, `harga_beli`, `harga_jual`, `supplier_merek`, `stok_tersedia`, `stok_minimum`, `created_at`) VALUES
(5, 'SP-C-0001', 'Kamera Depan', 'xxxxxx', 'Kamera Oppo', 'pcs', 50000.00, 70000.00, 'CV. Sparepart KW mas', 9, 5, '2025-12-02 09:00:23');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `keluhan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama`, `no_hp`, `alamat`, `keluhan`) VALUES
(1, 'Andi Saputra', '081234567890', 'Jl. Mawar No. 10', 'Laptop tidak bisa nyala'),
(2, 'Budi Santoso', '081234567891', 'Jl. Melati No. 5', 'HP cepat panas'),
(3, 'Citra Dewi', '081234567892', 'Jl. Anggrek No. 3', 'Ganti baterai iPhone'),
(4, 'Hamka', '08123456789', 'dimana saja', 'HP tidak bisa rekam bokep'),
(5, 'labaco', '082194486847', 'pangkep', 'Tidak bisa pinjol'),
(6, 'ibu sisil', '12345', 'taumi', 'banyak seklai');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian_sparepart_luar`
--

CREATE TABLE `pembelian_sparepart_luar` (
  `id_pembelian` varchar(20) NOT NULL,
  `invoice_service` varchar(50) NOT NULL,
  `tanggal_beli` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_sparepart` varchar(255) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) DEFAULT 0.00,
  `total_harga` decimal(15,2) NOT NULL,
  `total_jual` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian_sparepart_luar`
--

INSERT INTO `pembelian_sparepart_luar` (`id_pembelian`, `invoice_service`, `tanggal_beli`, `nama_sparepart`, `jumlah`, `harga_satuan`, `harga_jual`, `total_harga`, `total_jual`) VALUES
('SPL-251208-155036', 'INV-081225-142615', '2025-12-08 15:50:36', 'kolor', 1, 20000.00, 30000.00, 20000.00, 30000.00);

-- --------------------------------------------------------

--
-- Table structure for table `penjualan_perangkat`
--

CREATE TABLE `penjualan_perangkat` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `nama_perangkat` varchar(100) DEFAULT NULL,
  `harga_modal` decimal(12,2) DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL,
  `status_pembayaran` varchar(20) DEFAULT 'Belum Lunas'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penjualan_perangkat`
--

INSERT INTO `penjualan_perangkat` (`id`, `tanggal`, `pelanggan_id`, `nama_perangkat`, `harga_modal`, `harga_jual`, `status_pembayaran`) VALUES
(1, '2025-08-03', 3, 'iPhone 11 Bekas', 3500000.00, 5000000.00, 'Lunas'),
(2, '2025-08-04', 2, 'Redmi Note 10', 2000000.00, 2800000.00, 'Belum Lunas');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan_sparepart`
--

CREATE TABLE `penjualan_sparepart` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `status_pembayaran` varchar(20) DEFAULT 'Belum Lunas'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penjualan_sparepart`
--

INSERT INTO `penjualan_sparepart` (`id`, `tanggal`, `pelanggan_id`, `total`, `status_pembayaran`) VALUES
(1, '2025-08-01', 1, 450000.00, 'Lunas'),
(2, '2025-08-02', 2, 250000.00, 'Belum Lunas');

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `invoice` varchar(50) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `kasir_id` int(11) DEFAULT NULL,
  `merek_hp` varchar(100) DEFAULT NULL,
  `tipe_hp` varchar(100) DEFAULT NULL,
  `imei_sn` varchar(100) DEFAULT NULL,
  `kerusakan` text DEFAULT NULL,
  `kelengkapan` text DEFAULT NULL,
  `teknisi_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `garansi` enum('ya','tidak') DEFAULT 'tidak',
  `keterangan` text DEFAULT NULL,
  `durasi_garansi` varchar(50) DEFAULT NULL,
  `sub_total` decimal(12,2) DEFAULT 0.00,
  `voucher_id` int(11) DEFAULT NULL,
  `uang_muka` decimal(12,2) DEFAULT 0.00,
  `metode_pembayaran` enum('cash','credit') DEFAULT 'cash',
  `status_pembayaran` varchar(20) DEFAULT 'Belum Lunas',
  `status_service` varchar(50) DEFAULT 'Antrian',
  `total_bayar` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`invoice`, `tanggal`, `kasir_id`, `merek_hp`, `tipe_hp`, `imei_sn`, `kerusakan`, `kelengkapan`, `teknisi_id`, `customer_id`, `garansi`, `keterangan`, `durasi_garansi`, `sub_total`, `voucher_id`, `uang_muka`, `metode_pembayaran`, `status_pembayaran`, `status_service`, `total_bayar`) VALUES
('INV-081225-141602', '2025-12-08 14:16:26', 5, 'sefsef', 'sfsfsef', 'sfsefsf', 'sfsfsefsfsf', 'sfsfsf', 4, 3, 'ya', 'sefseg', '10 Hari', 1270000.00, NULL, 50000.00, 'cash', 'Lunas', 'Diambil', 1220000.00),
('INV-081225-142615', '2025-12-08 14:26:35', 5, 'sdgdsg', 'sgsdgs', 'sdgsdgds', 'gsdgsdg', 'sdgsdgsdg', 1, 2, 'ya', 'sdgsdg', '4 Hari', 140000.00, NULL, 100000.00, 'cash', 'Lunas', 'Diambil', 40000.00),
('INV-120925-184447', '2025-09-12 18:45:30', 5, '085554545454', 'm21', '648465464', 'HP cepat panas', 'cas, kartu dll', 4, 2, 'tidak', 'awdawdawdawd', '0 Hari', 1220000.00, NULL, 1220000.00, 'cash', 'Belum Lunas', 'Batal', 160000.00),
('INV-120925-190314', '2025-09-12 19:04:12', 5, 'blackjack', 'm21', '543543434354', 'HP tidak bisa rekam bokep', 'slefjsejf;esf', 1, 4, 'tidak', 'wfwefwefwefwef', '0 Hari', 1500000.00, NULL, 0.00, 'cash', 'Lunas', 'Selesai', 0.00),
('INV-211125-150205', '2025-11-21 15:02:49', 1, 'ipongggg', 'm21', '543543434354', 'HP tidak bisa rekam bokep', 'awdawd', 1, 4, 'ya', 'awdwafaf', '7 Hari', 500000.00, NULL, 100000.00, 'cash', 'Belum Lunas', 'Batal', 900000.00),
('INV-251125-204214', '2025-11-25 20:48:09', 1, 'iphone', 'iphone 11', '136788', 'HP tidak bisa rekam bokep', '-', 4, 4, 'tidak', 'matol', '1 Hari', 100000.00, NULL, 0.00, 'cash', 'Belum Lunas', 'Refund', 120000.00),
('INV-270825-144044', '2025-08-27 14:49:22', 5, 'OVO', 'm21', '313216215656', 'mati total', 'simcard', 1, 3, 'tidak', '1234', '0 Day', 10000.00, NULL, 0.00, 'cash', 'Belum Lunas', 'Batal', 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sparepart`
--

CREATE TABLE `sparepart` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `stok` int(11) DEFAULT NULL,
  `harga_modal` decimal(12,2) DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sparepart`
--

INSERT INTO `sparepart` (`id`, `nama`, `stok`, `harga_modal`, `harga_jual`) VALUES
(1, 'LCD Samsung A12', 10, 300000.00, 450000.00),
(2, 'Baterai iPhone 6', 15, 150000.00, 250000.00),
(3, 'Charger Vivo', 20, 50000.00, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sparepart_keluar`
--

CREATE TABLE `sparepart_keluar` (
  `id` int(11) NOT NULL,
  `tanggal_keluar` datetime DEFAULT current_timestamp(),
  `code_sparepart` varchar(50) NOT NULL,
  `nama_sparepart` varchar(100) DEFAULT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `invoice_service` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sparepart_keluar`
--

INSERT INTO `sparepart_keluar` (`id`, `tanggal_keluar`, `code_sparepart`, `nama_sparepart`, `satuan`, `jumlah`, `invoice_service`, `keterangan`) VALUES
(18, '2025-12-08 14:17:11', 'SP-C-0001', NULL, NULL, 1, 'INV-081225-141602', 'Ditambahkan saat proses service');

-- --------------------------------------------------------

--
-- Table structure for table `sparepart_masuk`
--

CREATE TABLE `sparepart_masuk` (
  `id` int(11) NOT NULL,
  `tanggal_masuk` datetime DEFAULT current_timestamp(),
  `code_sparepart` varchar(50) NOT NULL,
  `nama_sparepart` varchar(100) DEFAULT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sparepart_masuk`
--

INSERT INTO `sparepart_masuk` (`id`, `tanggal_masuk`, `code_sparepart`, `nama_sparepart`, `satuan`, `jumlah`) VALUES
(2, '2025-12-02 16:00:56', 'SP-C-0001', NULL, NULL, 10);

-- --------------------------------------------------------

--
-- Table structure for table `suplier`
--

CREATE TABLE `suplier` (
  `id` int(11) NOT NULL,
  `nama_suplier` varchar(100) NOT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suplier`
--

INSERT INTO `suplier` (`id`, `nama_suplier`, `kontak`, `alamat`, `keterangan`, `created_at`) VALUES
(2, 'CV. Sparepart KW mas', '0821445667', 'wdwa awd awd awd aw', 'a wdwad ', '2025-12-02 08:58:59');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_kas`
--

CREATE TABLE `transaksi_kas` (
  `id` int(11) NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `saldo_terakhir` decimal(15,2) DEFAULT 0.00,
  `keterangan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_kas`
--

INSERT INTO `transaksi_kas` (`id`, `tanggal`, `jenis`, `jumlah`, `saldo_terakhir`, `keterangan`) VALUES
(5, '2025-11-18 10:56:16', 'masuk', 100000.00, 100000.00, 'kas awal'),
(6, '2025-11-18 13:56:27', 'masuk', 1900000.00, 2000000.00, 'tambah kas'),
(7, '2025-11-18 14:10:44', 'keluar', 200000.00, 1800000.00, 'Beli sparepart luar: kolor untuk service INV-120925-190314'),
(8, '2025-11-18 14:11:10', 'masuk', 200000.00, 2000000.00, 'Refund pembelian sparepart luar untuk service INV-120925-190314'),
(9, '2025-11-18 15:43:04', 'masuk', 40000.00, 2040000.00, 'Penjualan Langsung: DIRECT-P4-T251118154304'),
(10, '2025-11-18 15:50:13', 'keluar', 40000.00, 2000000.00, 'Refund pembatalan Penjualan Langsung: DIRECT-P4-T251118154304'),
(11, '2025-11-18 15:50:27', 'masuk', 220000.00, 2220000.00, 'Penjualan Langsung: DIRECT-P1-T251118155027'),
(12, '2025-11-21 15:10:12', 'keluar', 400000.00, 1820000.00, 'Beli sparepart luar: kolor untuk service INV-211125-150205'),
(13, '2025-11-25 20:54:21', 'keluar', 10000.00, 1810000.00, 'bensin'),
(14, '2025-11-28 15:35:20', 'masuk', 400000.00, 2210000.00, 'Refund pembelian sparepart luar untuk service INV-211125-150205'),
(15, '2025-12-02 15:42:25', 'keluar', 220000.00, 1990000.00, 'Refund pembatalan Penjualan Langsung: DIRECT-P1-T251118155027'),
(16, '2025-12-08 14:27:24', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615'),
(17, '2025-12-08 14:28:35', 'masuk', 20000.00, 1990000.00, 'Refund pembelian sparepart luar untuk service INV-081225-142615'),
(18, '2025-12-08 15:08:48', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615'),
(19, '2025-12-08 15:10:51', 'masuk', 20000.00, 1990000.00, 'Refund pembelian sparepart luar untuk service INV-081225-142615'),
(20, '2025-12-08 15:18:45', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615'),
(21, '2025-12-08 15:32:50', 'masuk', 20000.00, 1990000.00, 'Refund pembelian sparepart luar untuk service INV-081225-142615'),
(22, '2025-12-08 15:33:07', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615'),
(23, '2025-12-08 15:50:25', 'masuk', 20000.00, 1990000.00, 'Refund pembelian sparepart luar untuk service INV-081225-142615'),
(24, '2025-12-08 15:50:36', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Admin Utama', 'admin', 'admin123', 'admin', '2025-08-07 07:45:56'),
(2, 'Rian Teknisi', 'rian', 'admin123', 'karyawan', '2025-08-07 07:45:56'),
(3, 'Tono Kasir', 'tono', 'admin123', 'karyawan', '2025-08-07 07:45:56'),
(4, 'Yuni Admin', 'yuni', 'admin123', 'karyawan', '2025-08-07 07:45:56'),
(5, 'Owner Toko', 'owner', 'admin123', 'owner', '2025-08-07 07:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `kode_voucher` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `jenis` enum('persen','nominal') NOT NULL,
  `nilai` decimal(12,2) NOT NULL,
  `berlaku_hingga` date DEFAULT NULL,
  `status` enum('aktif','tidak aktif','terpakai') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kontak` (`kontak`);

--
-- Indexes for table `detail_penjualan_sparepart`
--
ALTER TABLE `detail_penjualan_sparepart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sparepart_code` (`code_sparepart`),
  ADD KEY `fk_penjualan_id` (`penjualan_id`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `komisi_karyawan`
--
ALTER TABLE `komisi_karyawan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`karyawan_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `master_sparepart`
--
ALTER TABLE `master_sparepart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_sparepart` (`code_sparepart`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembelian_sparepart_luar`
--
ALTER TABLE `pembelian_sparepart_luar`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD KEY `idx_invoice_service` (`invoice_service`);

--
-- Indexes for table `penjualan_perangkat`
--
ALTER TABLE `penjualan_perangkat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indexes for table `penjualan_sparepart`
--
ALTER TABLE `penjualan_sparepart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`invoice`),
  ADD KEY `teknisi_id` (`teknisi_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `sparepart`
--
ALTER TABLE `sparepart`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `sparepart_keluar`
--
ALTER TABLE `sparepart_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code_sparepart` (`code_sparepart`),
  ADD KEY `fk_invoice_service` (`invoice_service`);

--
-- Indexes for table `sparepart_masuk`
--
ALTER TABLE `sparepart_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code_sparepart` (`code_sparepart`);

--
-- Indexes for table `suplier`
--
ALTER TABLE `suplier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi_kas`
--
ALTER TABLE `transaksi_kas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_voucher` (`kode_voucher`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `detail_penjualan_sparepart`
--
ALTER TABLE `detail_penjualan_sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `komisi_karyawan`
--
ALTER TABLE `komisi_karyawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `master_sparepart`
--
ALTER TABLE `master_sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `penjualan_perangkat`
--
ALTER TABLE `penjualan_perangkat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penjualan_sparepart`
--
ALTER TABLE `penjualan_sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sparepart`
--
ALTER TABLE `sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sparepart_keluar`
--
ALTER TABLE `sparepart_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sparepart_masuk`
--
ALTER TABLE `sparepart_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suplier`
--
ALTER TABLE `suplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi_kas`
--
ALTER TABLE `transaksi_kas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_penjualan_sparepart`
--
ALTER TABLE `detail_penjualan_sparepart`
  ADD CONSTRAINT `fk_penjualan_id` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan_sparepart` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sparepart_code` FOREIGN KEY (`code_sparepart`) REFERENCES `master_sparepart` (`code_sparepart`) ON UPDATE CASCADE;

--
-- Constraints for table `komisi_karyawan`
--
ALTER TABLE `komisi_karyawan`
  ADD CONSTRAINT `komisi_karyawan_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`);

--
-- Constraints for table `penjualan_perangkat`
--
ALTER TABLE `penjualan_perangkat`
  ADD CONSTRAINT `penjualan_perangkat_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`);

--
-- Constraints for table `penjualan_sparepart`
--
ALTER TABLE `penjualan_sparepart`
  ADD CONSTRAINT `penjualan_sparepart_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`);

--
-- Constraints for table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`teknisi_id`) REFERENCES `karyawan` (`id`),
  ADD CONSTRAINT `service_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `service_ibfk_3` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`);

--
-- Constraints for table `sparepart_keluar`
--
ALTER TABLE `sparepart_keluar`
  ADD CONSTRAINT `sparepart_keluar_ibfk_1` FOREIGN KEY (`code_sparepart`) REFERENCES `master_sparepart` (`code_sparepart`) ON UPDATE CASCADE;

--
-- Constraints for table `sparepart_masuk`
--
ALTER TABLE `sparepart_masuk`
  ADD CONSTRAINT `sparepart_masuk_ibfk_1` FOREIGN KEY (`code_sparepart`) REFERENCES `master_sparepart` (`code_sparepart`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
