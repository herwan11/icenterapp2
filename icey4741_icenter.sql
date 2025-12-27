-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 27, 2025 at 03:39 PM
-- Server version: 11.4.9-MariaDB-cll-lve
-- PHP Version: 8.4.16

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
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `user_id`, `tanggal`, `waktu_masuk`, `waktu_keluar`, `created_at`) VALUES
(1, 6, '2025-12-09', '2025-12-09 15:42:42', '2025-12-09 15:48:59', '2025-12-09 08:42:42'),
(2, 6, '2025-12-12', '2025-12-12 08:18:57', '2025-12-12 10:49:42', '2025-12-12 01:18:57');

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
(4, 'galigo', 'jl jalanan', '08738568298263', '2025-08-27 07:49:36'),
(5, '0000', 'LANGIT KE 7 [Keluhan Awal: LAYAR PECAH/KDRT]', '08111', '2025-12-09 07:12:12'),
(6, 'BACO KUTTU', 'PINGGIR KANAL [Keluhan Awal: MENINGGOI]', '091', '2025-12-09 07:16:20');

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
-- Table structure for table `device_bindings`
--

CREATE TABLE `device_bindings` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `device_fingerprint` varchar(255) NOT NULL,
  `device_info` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_bindings`
--

INSERT INTO `device_bindings` (`id`, `user_id`, `device_fingerprint`, `device_info`, `created_at`, `status`) VALUES
(1, 6, '47174a11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36 (Linux armv81)', '2025-12-09 08:40:57', 'aktif');

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
(5, 'SP-C-0001', 'Kamera Depan', 'xxxxxx', 'Kamera Oppo', 'pcs', 50000.00, 70000.00, 'CV. Sparepart KW mas', 8, 5, '2025-12-02 09:00:23'),
(6, 'LC-L-00001', 'LCD iphone 13', 'awadaw LQ', 'LCD', 'pcs', 30000.00, 80000.00, 'CV. Sparepart KW mas', 9, 5, '2025-12-09 05:30:22'),
(8, '02', 'ANU', 'AGAK LAIN', 'APA DI ???', 'PCS', 1000.00, 5000.00, 'CV. Sparepart KW mas', 19, 2, '2025-12-09 07:06:28');

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
(6, 'ibu sisil', '12345', 'taumi', 'banyak seklai'),
(8, 'BACO KUTTU', '00011111', 'DIMANA-MANA', 'MATI TOTAL');

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
('SPL-251208-155036', 'INV-081225-142615', '2025-12-08 15:50:36', 'kolor', 1, 20000.00, 30000.00, 20000.00, 30000.00),
('SPL-251209-132551', 'INV-091225-132406', '2025-12-09 13:25:51', 'Sempak Merah', 1, 50000.00, 70000.00, 50000.00, 70000.00),
('SPL-251209-132606', 'INV-091225-132406', '2025-12-09 13:26:06', 'Sempak putih', 1, 30000.00, 50000.00, 30000.00, 50000.00),
('SPL-251209-160852', 'INV-091225-160606', '2025-12-09 16:08:52', 'LCD', 1, 20000.00, 25000.00, 20000.00, 25000.00),
('SPL-251209-161957', 'INV-091225-161752', '2025-12-09 16:19:57', 'LCD', 1, 10000.00, 15000.00, 10000.00, 15000.00);

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
-- Table structure for table `qr_tokens`
--

CREATE TABLE `qr_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` int(11) NOT NULL,
  `expires_at` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_tokens`
--

INSERT INTO `qr_tokens` (`id`, `token`, `created_at`, `expires_at`, `is_used`) VALUES
(1, '7c80fc118fd5a13fba5574f51479c26f', 1765269541, 1765269576, 0),
(2, '860be7c10cbecd07e8bddf07e3fea2ab', 1765269541, 1765269576, 0),
(3, 'addfa9bbeb5b6b7fa667908183b514da', 1765269591, 1765269626, 0),
(4, '11e3f67deb9f6086ae683b67fdaab1df', 1765269594, 1765269629, 0),
(5, 'b680f2506f56330703da1cbc26e1e0be', 1765269601, 1765269636, 0),
(6, '1f830b76de10614a4238a4b7f1ddd18c', 1765269624, 1765269659, 0),
(7, 'fe48acd5dcac06919ff9130b5c0454b5', 1765269654, 1765269689, 0),
(8, '0c3e563fcbe1b442aea6d94f87651762', 1765269661, 1765269696, 0),
(9, 'd6dc935bb91af4f2f8d2417313ac44c1', 1765269684, 1765269719, 0),
(10, '10fc958ed61030d13cef3db2fd6b6a13', 1765269700, 1765269735, 0),
(11, '1c5f522b7b6b86c33ee91b477d5925c8', 1765269714, 1765269749, 0),
(12, '5ea5b0d7286c9bb509a17a7271f2b395', 1765269719, 1765269754, 0),
(13, 'bc15ca8246e2bb33c5c863d92fd58538', 1765269745, 1765269780, 0),
(14, '08e9310438d50aa86a1259b435a77716', 1765269749, 1765269784, 0),
(15, '98e85d949b08a63bb8bdcf97d343328f', 1765269776, 1765269811, 0),
(16, 'bf230ae70f5db8ad89bea08e2c475a4a', 1765269778, 1765269813, 0),
(17, '1ee2088dd548d3aaf4ac3167b04ed366', 1765269806, 1765269841, 0),
(18, '2b795fd67ebd7ff5c7052186a00fdf77', 1765269809, 1765269844, 0),
(19, '2e3d2868f56fe4b7ce546f50ba31f3ab', 1765269839, 1765269874, 0),
(20, 'ce423641ab13c55d0f3d839720b92cc4', 1765269841, 1765269876, 0),
(21, '77171e85c3130f3f9d3e23b8856071ae', 1765269901, 1765269936, 0),
(22, '4139379e64b0163ff258d6277cd64b95', 1765269901, 1765269936, 0),
(23, 'e92b169b0fece9ea9e2c4021a6da1cbf', 1765269929, 1765269964, 0),
(24, 'd714bae9dec2ad1ef217914df2a72c3b', 1765269959, 1765269994, 0),
(25, '1ee9a73b1b1afa196e267fa78846b9fc', 1765269961, 1765269996, 0),
(26, 'c7de259c8d26c439f8697f44ed418346', 1765270021, 1765270056, 0),
(27, 'f15b7e5760b8609d8f14bdd75372379a', 1765270021, 1765270056, 0),
(28, 'eb8b82d9497eb4b05928306200a58412', 1765270081, 1765270116, 0),
(29, 'fee7556f7b2a4fad748cbb7cd6b97d0d', 1765270082, 1765270117, 0),
(30, '0f21640454eb0700e402c46c3945c05b', 1765270096, 1765270131, 0),
(31, 'df3c7032acef15c773a73d658b5f89dd', 1765270113, 1765270148, 0),
(32, 'b57584d125c5435ffc1c8f838442449e', 1765270141, 1765270176, 0),
(33, 'e75c75f221f60857172ac42b3079420c', 1765270143, 1765270178, 0),
(34, 'e488b592c4fc0d1e79747948f9649550', 1765270173, 1765270208, 0),
(35, 'd883decb9bd08e5a258e513cb0f8c66d', 1765270201, 1765270236, 0),
(36, '945f66d9ba91df8608bbfb00335b290c', 1765270203, 1765270238, 0),
(37, '22b14c25c430cf14414fba81c668dd9c', 1765270234, 1765270269, 0),
(38, '2073576436fd728a03b228cfae270962', 1765270261, 1765270296, 0),
(39, '567c66f9daa6c69b6dad6e8b0ae57bd9', 1765270265, 1765270300, 0),
(40, '92008018c8c48c122171b744e3354b68', 1765270321, 1765270356, 0),
(41, 'e1cf305acfe489545f61495e64f8455c', 1765270373, 1765270408, 0),
(42, '128af8c216e4b9c4d61e19749f48ee11', 1765270381, 1765270416, 0),
(43, '272131c61aa72234829c3faa20c1787c', 1765270403, 1765270438, 0),
(44, '03c72e21c5a02cb83c194a3d1ed5df0f', 1765270433, 1765270468, 0),
(45, '8343cec7302c97a6a3a7d3899b08ec5b', 1765270441, 1765270476, 0),
(46, '0283f5b6358d93e863adc1c26f57d8f0', 1765270502, 1765270537, 0),
(47, 'ef4313ee71c379a166744ce12e197e50', 1765270561, 1765270596, 0),
(48, '29f94dc161ce82b49e93b40cf86249af', 1765270621, 1765270656, 0),
(49, '98597ba0bccc89af4d1324666391e0ca', 1765270681, 1765270716, 0),
(50, '4553bb7150ca552bbcd020541ecb1799', 1765270741, 1765270776, 0),
(51, 'bfcb55b7a339a087c39d11eca2f7c611', 1765270801, 1765270836, 0),
(52, 'a734e1c9dd66d17c52823f08702a8bbc', 1765270861, 1765270896, 0),
(53, 'b5a4872a54bfcfc4c65348ba6cd28a0a', 1765270921, 1765270956, 0),
(54, '40c965ffaf67fb82e2c01a29f98ffb04', 1765270984, 1765271019, 0),
(55, '3bf508f956d35d6d9e35bb3b55e0521c', 1765271014, 1765271049, 0),
(56, 'bb522a59b86aaa6cff3dcd9770b29041', 1765271044, 1765271079, 0),
(57, 'e2755f95d07b8df4bfdeac559d4f9418', 1765271075, 1765271110, 0),
(58, '88989d5274e64db0a65215a685d32e98', 1765271104, 1765271139, 0),
(59, 'dc9213a5196f1de77dfa6a32a762cfcc', 1765502284, 1765502319, 0),
(60, '71f5654089359f900a7ff3493155fd8f', 1765502314, 1765502349, 0),
(61, 'be27e2de936b7b98355689609953be67', 1765502344, 1765502379, 0),
(62, 'c00207c06f7db2aea9b848152892b190', 1765502374, 1765502409, 0),
(63, 'b132d37d56e533acac8482b9a5ac7fec', 1765502404, 1765502439, 0),
(64, '5e087e94e3d2f896f49c9044447a5182', 1765502434, 1765502469, 0),
(65, 'da979355c808f9e950bf56ac341d4c6c', 1765502464, 1765502499, 0),
(66, '6bd696df094095162d5cafca97e96ff0', 1765511381, 1765511416, 0),
(67, 'd8707957acd6e4f894346a2fb334e45e', 1765524817, 1765524852, 0),
(68, '324d37c02130f12bebef2cd01a2c53c4', 1765524848, 1765524883, 0),
(69, '800af615bac09127f6a800717f20f342', 1765524885, 1765524920, 0);

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
('INV-091225-122637', '2025-12-09 12:27:44', 5, 'Apple', 'iPhone 13', '2323-2385932852-25', 'Tidak bisah nonton bokep', 'dus dan charger', 1, 4, 'tidak', 'kerusakan parah', '10 Hari', 350000.00, NULL, 50000.00, 'cash', 'Lunas', 'Diambil', 300000.00),
('INV-091225-132406', '2025-12-09 13:25:07', 5, 'samsung', 'S3 ultra', 'segwet232623', 'tidak ada sempak nya', 'owjlwaj', 4, 3, 'ya', 'eweee', '3 Hari', 130000.00, NULL, 70000.00, 'cash', 'Lunas', 'Diambil', 60000.00),
('INV-091225-141104', '2025-12-09 14:13:54', 1, 'APPLE', 'IPHONE 11', '00000', 'LAYAR PECAH/KDRT', 'UNIT ', 4, 5, 'tidak', '', '10 Bulan', 0.00, NULL, 200000.00, 'cash', 'Belum Lunas', 'Batal', 0.00),
('INV-091225-141449', '2025-12-09 14:18:17', 1, 'ODDO', 'ODDO 7', '00000111', 'MENINGGOI', 'UNIT', 1, 6, 'tidak', 'JANGAN DATANG LAGI', '60 Hari', 0.00, NULL, 1000000.00, 'cash', 'Lunas', 'Diambil', 0.00),
('INV-091225-160606', '2025-12-09 16:07:34', 1, 'APPLE', 'IPHONE 11', '00000', 'LAYAR PECAH', 'UNIT', 1, 6, 'ya', '', '0 Hari', 20000.00, NULL, 0.00, 'cash', 'Lunas', 'Diambil', 25000.00),
('INV-091225-161752', '2025-12-09 16:18:58', 1, 'ODDO', 'ODDO 7', '00000', 'TIDAK JELAS', 'UNIT', 1, 6, 'tidak', '', '0 Hari', 15000.00, NULL, 0.00, 'cash', 'Lunas', 'Diambil', 15000.00),
('INV-161225-084224', '2025-12-16 08:43:02', 5, 'ipongggg', 'IP 15', '346346', 'sdsgg', 'ewgerhwa', 2, 6, 'tidak', 'wtwetwet', '0 Hari', 55000.00, NULL, 50000.00, 'cash', 'Lunas', 'Diambil', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `service_teams`
--

CREATE TABLE `service_teams` (
  `id` int(11) NOT NULL,
  `invoice` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_teams`
--

INSERT INTO `service_teams` (`id`, `invoice`, `user_id`, `joined_at`) VALUES
(1, 'INV-081225-142615', 1, '2025-12-16 08:51:17'),
(2, 'INV-091225-122637', 1, '2025-12-16 08:51:17'),
(3, 'INV-091225-141449', 1, '2025-12-16 08:51:17'),
(4, 'INV-091225-160606', 1, '2025-12-16 08:51:17'),
(5, 'INV-091225-161752', 1, '2025-12-16 08:51:17'),
(6, 'INV-081225-141602', 4, '2025-12-16 08:51:17'),
(7, 'INV-091225-132406', 4, '2025-12-16 08:51:17'),
(8, 'INV-091225-141104', 4, '2025-12-16 08:51:17'),
(9, 'INV-161225-084224', 5, '2025-12-16 08:55:10'),
(10, 'INV-161225-084224', 2, '2025-12-16 08:56:06');

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
(18, '2025-12-08 14:17:11', 'SP-C-0001', NULL, NULL, 1, 'INV-081225-141602', 'Ditambahkan saat proses service'),
(19, '2025-12-09 12:31:01', 'SP-C-0001', NULL, NULL, 1, 'INV-091225-122637', 'Ditambahkan saat proses service'),
(20, '2025-12-09 12:31:04', 'LC-L-00001', NULL, NULL, 1, 'INV-091225-122637', 'Ditambahkan saat proses service'),
(24, '2025-12-16 08:56:21', '02', NULL, NULL, 1, 'INV-161225-084224', 'Ditambahkan saat proses service');

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
(2, '2025-12-02 16:00:56', 'SP-C-0001', NULL, NULL, 10),
(3, '2025-12-09 12:30:42', 'LC-L-00001', NULL, NULL, 10),
(4, '2025-12-09 14:08:21', '02', NULL, NULL, 10);

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
(2, 'CV. Sparepart KW mas', '0821445667', 'wdwa awd awd awd aw', 'a wdwad ', '2025-12-02 08:58:59'),
(3, 'CV.GACOR', '00000000', 'LUPA ALAMAT', '-DUNIA LAIN', '2025-12-09 07:07:43'),
(4, 'CV.TAKUT LAPAR', '09555', 'THAILAND', 'HARGA BERUBAH UBAH', '2025-12-09 07:31:12');

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
(24, '2025-12-08 15:50:36', 'keluar', 20000.00, 1970000.00, 'Beli sparepart luar: kolor untuk service INV-081225-142615'),
(25, '2025-12-09 13:25:51', 'keluar', 50000.00, 1920000.00, 'Beli sparepart luar: Sempak Merah untuk service INV-091225-132406'),
(26, '2025-12-09 13:26:06', 'keluar', 30000.00, 1890000.00, 'Beli sparepart luar: Sempak putih untuk service INV-091225-132406'),
(27, '2025-12-09 14:32:24', 'masuk', 150000.00, 2040000.00, 'BAYAR KARCIS SURGA'),
(28, '2025-12-09 14:33:37', 'keluar', 2500.00, 2037500.00, 'BAYAR ANU'),
(29, '2025-12-09 16:08:52', 'keluar', 20000.00, 2017500.00, 'Beli sparepart luar: LCD untuk service INV-091225-160606'),
(30, '2025-12-09 16:19:57', 'keluar', 10000.00, 2007500.00, 'Beli sparepart luar: LCD untuk service INV-091225-161752');

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
  `foto` varchar(255) DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `foto`, `qr_token`, `created_at`) VALUES
(1, 'Admin Utama', 'admin', 'admin123', 'admin', 'assets/uploads/employees/EMP_693b917c47f4c.png', 'b413aa72ecb7c649a3379970751f6e0b', '2025-08-07 07:45:56'),
(2, 'Rian Teknisi', 'rian', 'admin123', 'Teknisi', NULL, '80ee34c2a24ebc75f38a2149c6581835', '2025-08-07 07:45:56'),
(3, 'Tono Kasir', 'tono', 'admin123', 'Teknisi', NULL, 'b48f553417d12215e2be04d41ddb4639', '2025-08-07 07:45:56'),
(4, 'Yuni Admin', 'yuni', 'admin123', 'Teknisi', NULL, 'b660114c355b5b01056f4d9410ed0fee', '2025-08-07 07:45:56'),
(5, 'Owner Toko', 'owner', 'admin123', 'owner', NULL, '0854e029e4c391d9519af96134f5f88b', '2025-08-07 07:45:56'),
(6, 'test', 'test', 'admin123', 'admin', NULL, 'b4ff5b253c6fa3ce9bc0d547f17ad5d3', '2025-12-09 08:20:18');

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
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `device_bindings`
--
ALTER TABLE `device_bindings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`invoice`),
  ADD KEY `teknisi_id` (`teknisi_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `service_teams`
--
ALTER TABLE `service_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team` (`invoice`,`user_id`),
  ADD KEY `idx_invoice` (`invoice`),
  ADD KEY `idx_user` (`user_id`);

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
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `detail_penjualan_sparepart`
--
ALTER TABLE `detail_penjualan_sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `device_bindings`
--
ALTER TABLE `device_bindings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT for table `qr_tokens`
--
ALTER TABLE `qr_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `service_teams`
--
ALTER TABLE `service_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sparepart`
--
ALTER TABLE `sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sparepart_keluar`
--
ALTER TABLE `sparepart_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sparepart_masuk`
--
ALTER TABLE `sparepart_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suplier`
--
ALTER TABLE `suplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transaksi_kas`
--
ALTER TABLE `transaksi_kas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
