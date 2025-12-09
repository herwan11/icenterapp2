<?php
// delete_service.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$invoice = $data['invoice'] ?? '';

if (empty($invoice)) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak valid.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Ambil data sparepart yang terpakai untuk mengembalikan stok (Opsional, tergantung kebijakan toko)
    // Di sini kita asumsikan jika data service dihapus total, stok dikembalikan (Reversal).
    
    // Ambil sparepart internal
    $sql_get_parts = "SELECT code_sparepart, jumlah FROM sparepart_keluar WHERE invoice_service = ?";
    $stmt_parts = $conn->prepare($sql_get_parts);
    $stmt_parts->bind_param("s", $invoice);
    $stmt_parts->execute();
    $res_parts = $stmt_parts->get_result();

    while ($part = $res_parts->fetch_assoc()) {
        $code = $part['code_sparepart'];
        $qty = $part['jumlah'];
        // Kembalikan stok
        $stmt_restock = $conn->prepare("UPDATE master_sparepart SET stok_tersedia = stok_tersedia + ? WHERE code_sparepart = ?");
        $stmt_restock->bind_param("is", $qty, $code);
        $stmt_restock->execute();
    }

    // 2. Hapus data terkait
    // Hapus dari sparepart_keluar
    $stmt_del_part = $conn->prepare("DELETE FROM sparepart_keluar WHERE invoice_service = ?");
    $stmt_del_part->bind_param("s", $invoice);
    $stmt_del_part->execute();

    // Hapus dari pembelian_sparepart_luar
    $stmt_del_ext = $conn->prepare("DELETE FROM pembelian_sparepart_luar WHERE invoice_service = ?");
    $stmt_del_ext->bind_param("s", $invoice);
    $stmt_del_ext->execute();

    // Hapus dari service (Data Utama)
    $stmt_del_service = $conn->prepare("DELETE FROM service WHERE invoice = ?");
    $stmt_del_service->bind_param("s", $invoice);
    
    if ($stmt_del_service->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Data service berhasil dihapus.']);
    } else {
        throw new Exception("Gagal menghapus data service.");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>