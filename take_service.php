<?php
// take_service.php
// Handler untuk Ambil Alih (Antrian -> Proses) dan Join Team (Status Proses)

require_once 'includes/db.php';
session_start();
header('Content-Type: application/json');

// Cek sesi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login.']);
    exit;
}

// Cek Role (Teknisi, Owner, Admin)
$allowed_roles = ['teknisi', 'owner', 'admin'];
$user_role = strtolower($_SESSION['role']);

if (!in_array($user_role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$invoice = $data['invoice'] ?? '';
$action = $data['action'] ?? 'take'; // 'take' or 'join'

if (empty($invoice)) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak valid.']);
    exit;
}

$teknisi_id = $_SESSION['user_id'];
$teknisi_nama = $_SESSION['nama'];

$conn->begin_transaction();

try {
    // 1. Insert ke tabel service_teams (Untuk 'take' maupun 'join' sama saja: tambah ke tim)
    // Gunakan INSERT IGNORE agar jika sudah ada tidak error (karena UNIQUE constraint)
    $stmt_team = $conn->prepare("INSERT IGNORE INTO service_teams (invoice, user_id) VALUES (?, ?)");
    $stmt_team->bind_param("si", $invoice, $teknisi_id);
    
    if (!$stmt_team->execute()) {
        throw new Exception("Gagal bergabung ke tim: " . $stmt_team->error);
    }

    // 2. Jika Action 'take' (Ambil Alih Pertama Kali) -> Ubah Status jadi 'Proses'
    if ($action === 'take') {
        $sql_update = "UPDATE service SET status_service = 'Proses' WHERE invoice = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("s", $invoice);
        if (!$stmt_update->execute()) {
            throw new Exception("Gagal update status service.");
        }
    }

    // 3. (Optional Back-Compatibility) Update teknisi_id di tabel service dengan ID user terakhir yg join
    // Ini agar kolom lama tetap terisi (misal untuk laporan sederhana yg cuma support 1 teknisi)
    $sql_legacy = "UPDATE service SET teknisi_id = ? WHERE invoice = ?";
    $stmt_legacy = $conn->prepare($sql_legacy);
    $stmt_legacy->bind_param("is", $teknisi_id, $invoice);
    $stmt_legacy->execute();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => ($action === 'take') ? 'Job diambil alih!' : 'Berhasil bergabung dengan tim.',
        'teknisi_nama' => $teknisi_nama
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>