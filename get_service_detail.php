<?php
// get_service_detail.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$invoice = $_GET['invoice'] ?? '';

if (empty($invoice)) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak valid.']);
    exit;
}

// 1. Ambil Data Utama Service
$sql_service = "SELECT s.*, c.nama as nama_customer, c.kontak as kontak_customer, c.alamat as alamat_customer, k.nama as nama_teknisi 
                FROM service s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN karyawan k ON s.teknisi_id = k.id
                WHERE s.invoice = ?";
$stmt = $conn->prepare($sql_service);
$stmt->bind_param("s", $invoice);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
    exit;
}

// 2. Ambil Sparepart Internal
$parts_internal = [];
$sql_int = "SELECT sk.*, ms.nama as nama_part, ms.harga_jual 
            FROM sparepart_keluar sk 
            JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart 
            WHERE sk.invoice_service = ?";
$stmt_int = $conn->prepare($sql_int);
$stmt_int->bind_param("s", $invoice);
$stmt_int->execute();
$res_int = $stmt_int->get_result();
while ($row = $res_int->fetch_assoc()) {
    $row['tipe'] = 'Internal';
    $row['subtotal'] = $row['jumlah'] * $row['harga_jual'];
    $parts_internal[] = $row;
}

// 3. Ambil Sparepart Eksternal
$parts_external = [];
$sql_ext = "SELECT *, total_jual as subtotal FROM pembelian_sparepart_luar WHERE invoice_service = ?";
$stmt_ext = $conn->prepare($sql_ext);
$stmt_ext->bind_param("s", $invoice);
$stmt_ext->execute();
$res_ext = $stmt_ext->get_result();
while ($row = $res_ext->fetch_assoc()) {
    $row['tipe'] = 'Eksternal';
    // Mapping agar strukturnya mirip internal untuk kemudahan di JS
    $row['nama_part'] = $row['nama_sparepart'];
    $row['harga_jual'] = $row['harga_jual']; // Pastikan kolom ini ada sesuai update DB terakhir
    $parts_external[] = $row;
}

echo json_encode([
    'success' => true,
    'service' => $service,
    'parts' => array_merge($parts_internal, $parts_external)
]);

$conn->close();
?>