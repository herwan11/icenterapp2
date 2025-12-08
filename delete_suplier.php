<?php
// delete_suplier.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $id = intval($data['id']);
    
    // Cek apakah suplier masih terpakai di tabel lain (opsional, misal di master_sparepart jika ada relasi)
    // Untuk saat ini langsung hapus saja
    
    $stmt = $conn->prepare("DELETE FROM suplier WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Suplier dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>