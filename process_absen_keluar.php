<?php
// process_absen_keluar.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['token']) || !isset($data['fingerprint'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$user_id = $data['user_id'];
$token = $data['token'];
$fingerprint = $data['fingerprint'];
$now = time();
$today = date('Y-m-d');

// --- 1. VALIDASI QR CODE (Sama dengan Absen Masuk) ---
$stmt = $conn->prepare("SELECT id, expires_at FROM qr_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$qr = $stmt->get_result()->fetch_assoc();

if (!$qr) {
    echo json_encode(['success' => false, 'message' => 'QR Code tidak valid.']);
    exit;
}

if ($qr['expires_at'] < $now) {
    echo json_encode(['success' => false, 'message' => 'QR Code kadaluarsa. Tunggu kode baru.']);
    exit;
}

// --- 2. VALIDASI DEVICE BINDING ---
$stmt_dev = $conn->prepare("SELECT device_fingerprint FROM device_bindings WHERE user_id = ? AND status = 'aktif'");
$stmt_dev->bind_param("i", $user_id);
$stmt_dev->execute();
$device = $stmt_dev->get_result()->fetch_assoc();

if (!$device) {
    echo json_encode(['success' => false, 'message' => 'Device tidak terdaftar. Hubungi Owner.']);
    exit;
}

if ($device['device_fingerprint'] !== $fingerprint) {
    echo json_encode(['success' => false, 'message' => 'DITOLAK: Gunakan HP yang terdaftar.']);
    exit;
}

// --- 3. CEK STATUS ABSENSI HARI INI ---
// Cari data absensi hari ini yang BELUM check-out (waktu_keluar masih NULL atau kosong)
$stmt_check = $conn->prepare("SELECT id, waktu_masuk, waktu_keluar FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmt_check->bind_param("is", $user_id, $today);
$stmt_check->execute();
$absen_data = $stmt_check->get_result()->fetch_assoc();

if (!$absen_data) {
    echo json_encode(['success' => false, 'message' => 'Anda belum melakukan absen MASUK hari ini.']);
    exit;
}

if ($absen_data['waktu_keluar'] != null) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah absen pulang sebelumnya (' . date('H:i', strtotime($absen_data['waktu_keluar'])) . ').']);
    exit;
}

// --- LULUS SEMUA VALIDASI -> UPDATE WAKTU KELUAR ---
$conn->begin_transaction();
try {
    $waktu_keluar = date('Y-m-d H:i:s');
    $absen_id = $absen_data['id'];
    
    // Hitung durasi kerja untuk pesan sukses
    $masuk = new DateTime($absen_data['waktu_masuk']);
    $keluar = new DateTime($waktu_keluar);
    $durasi = $masuk->diff($keluar);
    $durasi_str = $durasi->format('%h Jam %i Menit');

    $stmt_upd = $conn->prepare("UPDATE absensi SET waktu_keluar = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $waktu_keluar, $absen_id);
    
    if($stmt_upd->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Terima kasih atas kerja kerasnya!<br>Durasi Kerja: <strong>$durasi_str</strong>"]);
    } else {
        throw new Exception("Gagal update database.");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>