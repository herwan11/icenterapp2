<?php
// update_service_full.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['invoice'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

// Ambil data dari form
$invoice = $data['invoice'];
$merek_hp = $data['merek_hp'];
$tipe_hp = $data['tipe_hp'];
$imei_sn = $data['imei_sn'];
$kerusakan = $data['kerusakan'];
$kelengkapan = $data['kelengkapan'];
$teknisi_id = $data['teknisi_id'];
$status_service = $data['status_service'];
$keterangan = $data['keterangan'];

// Update Database
$sql = "UPDATE service SET 
        merek_hp = ?, 
        tipe_hp = ?, 
        imei_sn = ?, 
        kerusakan = ?, 
        kelengkapan = ?, 
        teknisi_id = ?, 
        status_service = ?, 
        keterangan = ?
        WHERE invoice = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssisss", $merek_hp, $tipe_hp, $imei_sn, $kerusakan, $kelengkapan, $teknisi_id, $status_service, $keterangan, $invoice);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Data service berhasil diperbarui.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $stmt->error]);
}

$conn->close();
?>