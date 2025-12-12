<?php
// includes/header.php

// Panggil fungsi dan periksa sesi login
require_once 'functions.php';
check_login();

// Dapatkan role pengguna untuk mengatur menu
$user_role = get_user_role();

// --- Logika untuk menghitung notifikasi status ---
$status_counts = [
    'Antrian' => 0, 'Proses' => 0, 'Selesai' => 0,
    'Diambil' => 0, 'Batal' => 0, 'Refund' => 0
];
// Cek koneksi db sebelum query (jaga-jaga jika file ini dipanggil terpisah, meski biasanya sudah ada di functions/db)
if(isset($conn)) {
    $sql_counts = "SELECT status_service, COUNT(*) as count FROM service GROUP BY status_service";
    $result_counts = $conn->query($sql_counts);
    if ($result_counts) {
        while($row = $result_counts->fetch_assoc()){
            if (array_key_exists($row['status_service'], $status_counts)) {
                $status_counts[$row['status_service']] = $row['count'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - iCenter Apple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="sidebar-logo-text">iCenter</span>
            </div>
            <nav class="sidebar-nav">
                <!-- ====================================================== -->
                <!-- MENU UNTUK SEMUA ROLE -->
                <!-- ====================================================== -->
                <a href="index.php" class="nav-link"><i class="fas fa-store fa-fw"></i> <span>My Shop</span></a>

                <!-- Menu Repair -->
                <div class="nav-item-dropdown">
                    <a href="#" class="nav-link"><i class="fas fa-tools fa-fw"></i> <span>Repair</span> <i class="fas fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="repair_dashboard.php" class="nav-link sub-item">Dashboard</a>
                        <a href="all_data_service.php" class="nav-link sub-item">All data</a>
                        <a href="input_data_service.php" class="nav-link sub-item">Input Data</a>
                    </div>
                </div>

                <!-- Menu Waiting List -->
                <div class="nav-item-dropdown">
                    <a href="#" class="nav-link"><i class="fas fa-clipboard-list fa-fw"></i> <span>Waiting List</span> <i class="fas fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="waiting_list.php?status=Antrian" class="nav-link sub-item-nested">
                            <span>Antrian</span> 
                            <span class="badge" data-status="Antrian" style="<?php echo ($status_counts['Antrian'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Antrian']; ?></span>
                        </a>
                        <a href="waiting_list.php?status=Proses" class="nav-link sub-item-nested">
                            <span>Proses</span> 
                            <span class="badge" data-status="Proses" style="<?php echo ($status_counts['Proses'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Proses']; ?></span>
                        </a>
                        <a href="waiting_list.php?status=Selesai" class="nav-link sub-item-nested">
                            <span>Selesai</span> 
                            <span class="badge" data-status="Selesai" style="<?php echo ($status_counts['Selesai'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Selesai']; ?></span>
                        </a>
                        <a href="waiting_list.php?status=Diambil" class="nav-link sub-item-nested">
                            <span>Diambil</span> 
                            <span class="badge" data-status="Diambil" style="<?php echo ($status_counts['Diambil'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Diambil']; ?></span>
                        </a>
                        <a href="waiting_list.php?status=Batal" class="nav-link sub-item-nested">
                            <span>Batal</span> 
                            <span class="badge" data-status="Batal" style="<?php echo ($status_counts['Batal'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Batal']; ?></span>
                        </a>
                        <a href="waiting_list.php?status=Refund" class="nav-link sub-item-nested">
                            <span>Refund</span> 
                            <span class="badge" data-status="Refund" style="<?php echo ($status_counts['Refund'] > 0) ? '' : 'display:none;'; ?>"><?php echo $status_counts['Refund']; ?></span>
                        </a>
                    </div>
                </div>

                <!-- Menu Kas -->
                <a href="kas.php" class="nav-link"><i class="fas fa-wallet fa-fw"></i> <span>Kas</span></a>

                <!-- Menu Absensi (UPDATE: Tambah Absen Keluar) -->
                <div class="nav-item-dropdown">
                    <a href="#" class="nav-link"><i class="fas fa-clock fa-fw"></i> <span>Absensi</span> <i class="fas fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="absensi.php" class="nav-link sub-item">Scan Masuk</a>
                        <a href="absen_keluar.php" class="nav-link sub-item" style="color: var(--accent-danger);">Scan Keluar</a>
                        <?php if ($user_role === 'owner' || $user_role === 'admin'): ?>
                            <a href="generate_qr.php" class="nav-link sub-item">Monitor QR (Kantor)</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Menu Garansi -->
                <a href="#" class="nav-link"><i class="fas fa-shield-alt fa-fw"></i> <span>Garansi</span></a>

                <!-- Menu Customers -->
                <a href="customers.php" class="nav-link"><i class="fas fa-users fa-fw"></i> <span>Customers</span></a>
                
                <!-- Menu Suplier -->
                <a href="suplier.php" class="nav-link"><i class="fas fa-truck fa-fw"></i> <span>Suplier</span></a>

                <!-- Menu Laporan Service -->
                <div class="nav-item-dropdown">
                    <a href="#" class="nav-link"><i class="fas fa-file-pdf fa-fw"></i> <span>Laporan Service</span> <i class="fas fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="laporan_harian.php" class="nav-link sub-item">Laporan Harian</a>
                        <a href="laporan_mingguan.php" class="nav-link sub-item">Laporan Mingguan</a>
                    </div>
                </div>

                <!-- ====================================================== -->
                <!-- MENU UNTUK ADMIN & OWNER -->
                <!-- ====================================================== -->
                <?php if ($user_role === 'owner' || $user_role === 'admin'): ?>
                    <div class="nav-item-dropdown">
                        <a href="#" class="nav-link"><i class="fas fa-microchip fa-fw"></i> <span>Acc & Sparepart</span> <i class="fas fa-chevron-down dropdown-icon"></i></a>
                        <div class="dropdown-content">
                            <a href="sparepart_dashboard.php" class="nav-link sub-item">Dashboard</a>
                            <a href="sparepart_masuk.php" class="nav-link sub-item">Sparepart Masuk</a>
                            <a href="stok_sparepart.php" class="nav-link sub-item">Stok Sparepart</a>
                            <a href="penjualan_sparepart_view.php" class="nav-link sub-item">Penjualan Sparepart</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- MENU DEVICE BINDING (KHUSUS OWNER) -->
                <?php if ($user_role === 'owner'): ?>
                    <a href="device_binding.php" class="nav-link" style="color: var(--accent-warning);"><i class="fas fa-mobile-alt fa-fw"></i> <span>Device Binding</span></a>
                <?php endif; ?>

            </nav>
            <div class="sidebar-footer">
                <span>&copy; <?php echo date("Y"); ?> iCenter Apple</span>
            </div>
        </aside>

        <!-- Wrapper untuk Konten Utama -->
        <div class="main-wrapper">
            <!-- Overlay untuk mobile saat sidebar terbuka -->
            <div class="main-overlay"></div>

            <!-- Header Utama (Top Bar) -->
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <h1 class="header-title">iCenter Apple</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                            <span class="user-role"><?php echo ucwords(htmlspecialchars($_SESSION['role'])); ?></span>
                        </div>
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>
            </header>

            <!-- Konten Dinamis akan dimuat di sini -->
            <main class="main-content">