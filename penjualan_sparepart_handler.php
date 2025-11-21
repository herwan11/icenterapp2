<?php
// penjualan_sparepart_handler.php - LOGIKA DITERAPKAN DARI ALUR SPAREPART KELUAR
session_start();
require_once 'includes/db.php';

// Aktifkan error reporting agar pesan muncul saat ada kegagalan sintaks/logika
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: penjualan_sparepart_view.php");
    exit();
}

$pelanggan_id = $_POST['pelanggan_id'] ?? null;
$code_sparepart = $_POST['code_sparepart'] ?? null;
$jumlah_jual = intval($_POST['jumlah_jual'] ?? 0);
$total_jual = floatval($_POST['total_jual'] ?? 0);
$status_pembayaran = $_POST['status_pembayaran_jual'] ?? 'Belum Lunas';
$harga_satuan_jual = floatval($_POST['harga_satuan_jual'] ?? 0);

// Placeholder unik untuk penjualan langsung di tabel sparepart_keluar
// Format: DIRECT-P[ID Pelanggan]-T[Timestamp]
$unique_transaction_id = 'DIRECT-P' . $pelanggan_id . '-T' . date('ymdHis');
$keterangan_jual = "Penjualan Langsung ke Pelanggan ID: " . $pelanggan_id;


if (empty($pelanggan_id) || empty($code_sparepart) || $jumlah_jual <= 0 || $total_jual <= 0) {
    header("Location: penjualan_sparepart_view.php?status=fail&message=Data tidak lengkap.");
    exit();
}

$conn->begin_transaction();

try {
    // 1. Cek Stok
    $stmt_stok = $conn->prepare("SELECT stok_tersedia FROM master_sparepart WHERE code_sparepart = ?");
    if (!$stmt_stok) { throw new Exception("Gagal prepare cek stok: " . $conn->error); }
    $stmt_stok->bind_param("s", $code_sparepart);
    $stmt_stok->execute();
    $result_stok = $stmt_stok->get_result()->fetch_assoc();
    $stok_tersedia = $result_stok['stok_tersedia'] ?? 0;

    if ($jumlah_jual > $stok_tersedia) {
        throw new Exception("Stok tidak mencukupi untuk sparepart ini. Tersedia: " . $stok_tersedia);
    }
    
    // 2. Catat Pengeluaran Stok (Pencatatan Penjualan Langsung) ke tabel SPAREPART_KELUAR
    // Kita gunakan kolom 'invoice_service' untuk menyimpan ID Transaksi Unik
    $sql_keluar = "INSERT INTO sparepart_keluar (tanggal_keluar, code_sparepart, jumlah, invoice_service, keterangan) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt_keluar = $conn->prepare($sql_keluar);
    if (!$stmt_keluar) { throw new Exception("Gagal prepare sparepart keluar: " . $conn->error); }
    // FIX LINE 54: Mengubah "sis" menjadi "siss"
    $stmt_keluar->bind_param("siss", $code_sparepart, $jumlah_jual, $unique_transaction_id, $keterangan_jual); 
    if (!$stmt_keluar->execute()) { throw new Exception("Gagal eksekusi sparepart keluar: " . $stmt_keluar->error); }

    // 3. Kurangi Stok di master_sparepart
    $sql_update_stok = "UPDATE master_sparepart SET stok_tersedia = stok_tersedia - ? WHERE code_sparepart = ?";
    $stmt_update_stok = $conn->prepare($sql_update_stok);
    if (!$stmt_update_stok) { throw new Exception("Gagal prepare update stok: " . $conn->error); }
    $stmt_update_stok->bind_param("is", $jumlah_jual, $code_sparepart);
    
    if (!$stmt_update_stok->execute()) { throw new Exception("Gagal eksekusi update stok: " . $stmt_update_stok->error); }


    // 4. Catat Pemasukan Kas jika Lunas
    if ($status_pembayaran === 'Lunas') {
        $keterangan_kas = "Penjualan Langsung: " . $unique_transaction_id;
        
        $saldo_sebelumnya = 0;
        $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
        if ($result_saldo->num_rows > 0) {
            $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir'];
        }
        $saldo_terakhir = $saldo_sebelumnya + $total_jual;
        
        $stmt_kas = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES ('masuk', ?, ?, ?)");
        if (!$stmt_kas) { throw new Exception("Gagal prepare transaksi kas: " . $conn->error); }
        $stmt_kas->bind_param("dsd", $total_jual, $keterangan_kas, $saldo_terakhir);
        
        if (!$stmt_kas->execute()) { throw new Exception("Gagal eksekusi transaksi kas: " . $stmt_kas->error); }
    }
    
    $conn->commit();
    header("Location: penjualan_sparepart_view.php?status=success");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    // Redirect dengan pesan error yang sangat detail
    header("Location: penjualan_sparepart_view.php?status=fail&message=" . urlencode("Transaksi gagal: " . $e->getMessage()));
    exit();
}
$conn->close();
?>