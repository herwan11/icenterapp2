<?php
// add_suplier.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['nama']) && !empty($data['nama'])) {
    $nama = $data['nama'];
    $kontak = $data['kontak'] ?? '';
    $alamat = $data['alamat'] ?? '';
    $keterangan = $data['keterangan'] ?? '';

    $stmt = $conn->prepare("INSERT INTO suplier (nama_suplier, kontak, alamat, keterangan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $kontak, $alamat, $keterangan);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Suplier berhasil ditambahkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nama suplier wajib diisi']);
}
?>