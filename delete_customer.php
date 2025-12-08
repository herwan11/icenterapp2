<?php
// delete_customer.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

// Cek apakah pelanggan memiliki riwayat service
$stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM service WHERE customer_id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();

if ($result_check['count'] > 0) {
    // Opsional: Blokir penghapusan jika ada riwayat service demi integritas data
    // Atau: Lanjutkan delete jika foreign key di DB di-set ON DELETE CASCADE (tapi di sini kita pakai cara aman: tolak)
    echo json_encode(['success' => false, 'message' => 'Gagal: Pelanggan ini memiliki ' . $result_check['count'] . ' riwayat service. Data tidak bisa dihapus demi keamanan data transaksi. Silakan edit saja namanya jika perlu.']);
    exit;
}

// Cek riwayat pembelian sparepart
$stmt_check_part = $conn->prepare("SELECT COUNT(*) as count FROM penjualan_sparepart WHERE pelanggan_id = ?");
$stmt_check_part->bind_param("i", $id);
$stmt_check_part->execute();
$res_part = $stmt_check_part->get_result()->fetch_assoc();

if ($res_part['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Gagal: Pelanggan memiliki riwayat pembelian sparepart.']);
    exit;
}

// Jika aman, lakukan delete
$stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pelanggan berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$conn->close();
?>