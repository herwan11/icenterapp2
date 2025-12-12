<?php
// delete_employee.php
require_once 'includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if ($id) {
    // Ambil info foto untuk dihapus
    $res = $conn->query("SELECT foto FROM users WHERE id = $id");
    $row = $res->fetch_assoc();
    
    if ($conn->query("DELETE FROM users WHERE id = $id")) {
        // Hapus file foto
        if (!empty($row['foto'])) {
            $path = "assets/uploads/employees/" . $row['foto'];
            if (file_exists($path)) unlink($path);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID Invalid']);
}
?>