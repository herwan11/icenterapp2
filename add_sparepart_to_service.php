<?php
session_start();
require_once 'includes/db.php'; // Koneksi ke database

// Pastikan request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

// Ambil data JSON dari body request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data yang diterima
if (!isset($data['invoice']) || !isset($data['spareparts']) || !is_array($data['spareparts'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau format salah.']);
    exit;
}

$invoice = $data['invoice'];
$spareparts_to_add = $data['spareparts'];
$total_harga_sparepart = 0;

// Mulai transaksi
$conn->begin_transaction();

try {
    foreach ($spareparts_to_add as $part) {
        $code = $part['code'];
        $qty = $part['qty'];
        $harga = $part['harga'];

        // 1. Catat di sparepart_keluar
        $sql_keluar = "INSERT INTO sparepart_keluar (tanggal_keluar, code_sparepart, jumlah, invoice_service, keterangan) VALUES (NOW(), ?, ?, ?, 'Ditambahkan saat proses service')";
        $stmt_keluar = $conn->prepare($sql_keluar);
        $stmt_keluar->bind_param("sis", $code, $qty, $invoice);
        $stmt_keluar->execute();

        // 2. Kurangi stok di master_sparepart
        $sql_update_stok = "UPDATE master_sparepart SET stok_tersedia = stok_tersedia - ? WHERE code_sparepart = ?";
        $stmt_update_stok = $conn->prepare($sql_update_stok);
        $stmt_update_stok->bind_param("is", $qty, $code);
        $stmt_update_stok->execute();

        // Akumulasi total harga sparepart yang ditambahkan
        $total_harga_sparepart += ($harga * $qty);
    }

    // 3. Tambahkan total harga sparepart ke sub_total di tabel service
    $sql_update_subtotal = "UPDATE service SET sub_total = sub_total + ? WHERE invoice = ?";
    $stmt_update_subtotal = $conn->prepare($sql_update_subtotal);
    $stmt_update_subtotal->bind_param("ds", $total_harga_sparepart, $invoice);
    $stmt_update_subtotal->execute();


    // Jika semua berhasil, commit
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Sparepart berhasil ditambahkan.']);

} catch (mysqli_sql_exception $e) {
    // Jika ada error, rollback
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>
