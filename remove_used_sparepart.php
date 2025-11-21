<?php
// remove_used_sparepart.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id_keluar = $data['id'] ?? 0;

if ($id_keluar === 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Ambil data sparepart yang akan dihapus untuk tahu jumlah & kodenya
    $sql_get = "SELECT sk.code_sparepart, sk.jumlah, sk.invoice_service, ms.harga_jual FROM sparepart_keluar sk JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart WHERE sk.id = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("i", $id_keluar);
    $stmt_get->execute();
    $part_data = $stmt_get->get_result()->fetch_assoc();

    if ($part_data) {
        $code = $part_data['code_sparepart'];
        $qty = $part_data['jumlah'];
        $invoice = $part_data['invoice_service'];
        $harga_total_dihapus = $part_data['harga_jual'] * $qty;

        // 2. Hapus dari sparepart_keluar
        $sql_delete = "DELETE FROM sparepart_keluar WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_keluar);
        $stmt_delete->execute();

        // 3. Kembalikan stok ke master_sparepart
        $sql_update_stok = "UPDATE master_sparepart SET stok_tersedia = stok_tersedia + ? WHERE code_sparepart = ?";
        $stmt_update_stok = $conn->prepare($sql_update_stok);
        $stmt_update_stok->bind_param("is", $qty, $code);
        $stmt_update_stok->execute();
        
        // 4. Kurangi sub_total di tabel service
        $sql_update_subtotal = "UPDATE service SET sub_total = sub_total - ? WHERE invoice = ?";
        $stmt_update_subtotal = $conn->prepare($sql_update_subtotal);
        $stmt_update_subtotal->bind_param("ds", $harga_total_dihapus, $invoice);
        $stmt_update_subtotal->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sparepart berhasil dihapus.']);
    } else {
        throw new Exception('Data sparepart keluar tidak ditemukan.');
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
