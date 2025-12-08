<?php
// buy_external_sparepart.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Data tidak valid.'];

// Mengambil parameter
$invoice = $data['invoice'] ?? '';
$nama_sparepart = trim($data['nama'] ?? '');
$jumlah = intval($data['jumlah'] ?? 0);
$harga_beli_satuan = floatval($data['harga_beli'] ?? ($data['harga'] ?? 0)); 
$harga_jual_satuan = floatval($data['harga_jual'] ?? $harga_beli_satuan); 

if ($invoice && $nama_sparepart && $jumlah > 0 && $harga_beli_satuan >= 0) {
    
    // Hitung Total
    $total_beli = $jumlah * $harga_beli_satuan; // Modal (Expense)
    $total_jual = $jumlah * $harga_jual_satuan; // Revenue (Tagihan ke Customer)

    // Generate kode transaksi unik
    $id_pembelian = 'SPL-' . date('ymd-His');

    $conn->begin_transaction();
    try {
        // 1. Simpan ke tabel pembelian_sparepart_luar
        // PERUBAHAN: Kita simpan lengkap.
        // harga_satuan & total_harga = MODAL (Beli)
        // harga_jual & total_jual = OMSET (Jual)
        $stmt_beli = $conn->prepare("INSERT INTO pembelian_sparepart_luar (id_pembelian, invoice_service, nama_sparepart, jumlah, harga_satuan, total_harga, harga_jual, total_jual) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_beli->bind_param("sssiiddd", $id_pembelian, $invoice, $nama_sparepart, $jumlah, $harga_beli_satuan, $total_beli, $harga_jual_satuan, $total_jual);
        $stmt_beli->execute();

        // 2. Potong saldo kas (Dicatat di transaksi_kas sebagai KELUAR)
        // Menggunakan Total BELI (Modal)
        $keterangan_kas = "Beli sparepart luar: " . $nama_sparepart . " untuk service " . $invoice;
        
        $saldo_sebelumnya = 0;
        $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
        if ($result_saldo->num_rows > 0) {
            $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir'];
        }
        $saldo_terakhir = $saldo_sebelumnya - $total_beli;
        
        $stmt_kas = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES ('keluar', ?, ?, ?)");
        $stmt_kas->bind_param("dsd", $total_beli, $keterangan_kas, $saldo_terakhir);
        $stmt_kas->execute();
        
        // 3. Update sub_total di tabel service (Tagihan Customer)
        // Menggunakan Total JUAL
        $stmt_service = $conn->prepare("UPDATE service SET sub_total = sub_total + ? WHERE invoice = ?");
        $stmt_service->bind_param("ds", $total_jual, $invoice);
        $stmt_service->execute();

        $conn->commit();
        $response = ['success' => true, 'message' => 'Pembelian berhasil dicatat dengan data lengkap (Modal & Jual).'];

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
$conn->close();
?>