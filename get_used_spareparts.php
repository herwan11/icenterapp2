<?php
// get_used_spareparts.php (Diperbarui)
require_once 'includes/db.php';
header('Content-Type: application/json');

$invoice = $_GET['invoice'] ?? '';

if (empty($invoice)) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak valid.']);
    exit;
}

$all_parts = [];

// 1. Ambil dari stok internal (sparepart_keluar)
$sql_internal = "SELECT sk.id, sk.code_sparepart, ms.nama, sk.jumlah, ms.harga_jual, 'internal' as tipe
                 FROM sparepart_keluar sk
                 JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
                 WHERE sk.invoice_service = ?";
$stmt_internal = $conn->prepare($sql_internal);
$stmt_internal->bind_param("s", $invoice);
$stmt_internal->execute();
$result_internal = $stmt_internal->get_result();
while ($row = $result_internal->fetch_assoc()) {
    $all_parts[] = $row;
}

// 2. Ambil dari pembelian luar
$sql_external = "SELECT id_pembelian as id, '-' as code_sparepart, nama_sparepart as nama, jumlah, harga_satuan as harga_jual, 'external' as tipe
                 FROM pembelian_sparepart_luar
                 WHERE invoice_service = ?";
$stmt_external = $conn->prepare($sql_external);
$stmt_external->bind_param("s", $invoice);
$stmt_external->execute();
$result_external = $stmt_external->get_result();
while ($row = $result_external->fetch_assoc()) {
    $all_parts[] = $row;
}

echo json_encode(['success' => true, 'data' => $all_parts]);

$conn->close();
?>

