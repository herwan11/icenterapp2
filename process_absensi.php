<?php
// process_absensi.php
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

// --- 1. VALIDASI QR CODE ---
$stmt = $conn->prepare("SELECT id, expires_at, is_used FROM qr_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$qr = $stmt->get_result()->fetch_assoc();

if (!$qr) {
    echo json_encode(['success' => false, 'message' => 'QR Code tidak valid.']);
    exit;
}

if ($qr['is_used'] == 1) {
    echo json_encode(['success' => false, 'message' => 'QR Code sudah digunakan. Scan kode terbaru.']);
    exit;
}

if ($qr['expires_at'] < $now) {
    echo json_encode(['success' => false, 'message' => 'QR Code kadaluarsa. Tunggu kode baru.']);
    exit;
}

// --- 2. VALIDASI DEVICE BINDING ---
// Ambil fingerprint yang terdaftar untuk user ini
$stmt_dev = $conn->prepare("SELECT device_fingerprint FROM device_bindings WHERE user_id = ? AND status = 'aktif'");
$stmt_dev->bind_param("i", $user_id);
$stmt_dev->execute();
$device = $stmt_dev->get_result()->fetch_assoc();

if (!$device) {
    echo json_encode(['success' => false, 'message' => 'Akun Anda belum mendaftarkan HP ini. Hubungi Owner.']);
    exit;
}

// Bandingkan Fingerprint
if ($device['device_fingerprint'] !== $fingerprint) {
    echo json_encode(['success' => false, 'message' => 'DITOLAK: HP tidak dikenali. Gunakan HP yang terdaftar.']);
    exit;
}

// --- 3. CEK SUDAH ABSEN HARI INI? ---
$stmt_check = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmt_check->bind_param("is", $user_id, $today);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah absen masuk hari ini.']);
    exit;
}

// --- LULUS SEMUA VALIDASI -> SIMPAN ABSEN ---
$conn->begin_transaction();
try {
    // 1. Simpan Absensi
    $waktu_masuk = date('Y-m-d H:i:s');
    $stmt_ins = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu_masuk) VALUES (?, ?, ?)");
    $stmt_ins->bind_param("iss", $user_id, $today, $waktu_masuk);
    $stmt_ins->execute();

    // 2. Tandai Token QR Used (Optional: agar tidak dipake double cepat)
    // Sebenarnya token expire 30s, tapi marking used lebih aman
    // Namun karena QR dipake rame-rame, token TIDAK BOLEH di-mark used global jika dipakai user A.
    // Tapi user A tidak boleh pakai token yang sama 2x.
    // Koreksi: QR Dinamis kantor biasanya berlaku untuk BANYAK user dalam 30 detik itu.
    // Jadi jangan mark `is_used` secara global, atau tabel absensi sudah mencegah duplikat harian user.
    // KITA SKIP mark `is_used` agar karyawan lain bisa scan kode yang sama dalam durasi 30 detik tersebut.
    
    // Commit
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Absensi tercatat pada ' . date('H:i')]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>