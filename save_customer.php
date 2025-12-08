<?php
// save_customer.php
// Menangani Tambah Baru dan Edit Pelanggan

require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : null;
$nama = $data['nama'] ?? '';
$no_hp = $data['kontak'] ?? '';
$alamat = $data['alamat'] ?? '';
$keluhan = $data['keluhan'] ?? ''; // Disimpan di kolom 'keluhan' di tabel pelanggan

if (empty($nama) || empty($no_hp)) {
    echo json_encode(['success' => false, 'message' => 'Nama dan Kontak wajib diisi.']);
    exit;
}

try {
    if ($id) {
        // --- MODE EDIT (UPDATE) ---
        // Cek apakah nomor HP bentrok dengan orang lain
        $stmt_check = $conn->prepare("SELECT id FROM pelanggan WHERE no_hp = ? AND id != ?");
        $stmt_check->bind_param("si", $no_hp, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("Nomor kontak sudah digunakan oleh pelanggan lain.");
        }

        $stmt = $conn->prepare("UPDATE pelanggan SET nama = ?, no_hp = ?, alamat = ?, keluhan = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama, $no_hp, $alamat, $keluhan, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Data pelanggan diperbarui.']);
        } else {
            throw new Exception("Gagal memperbarui database: " . $conn->error);
        }

    } else {
        // --- MODE TAMBAH (INSERT) ---
        // Cek apakah nomor HP sudah ada
        $stmt_check = $conn->prepare("SELECT id FROM pelanggan WHERE no_hp = ?");
        $stmt_check->bind_param("s", $no_hp);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("Nomor kontak sudah terdaftar.");
        }

        $stmt = $conn->prepare("INSERT INTO pelanggan (nama, no_hp, alamat, keluhan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $no_hp, $alamat, $keluhan);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pelanggan baru ditambahkan.', 'new_id' => $conn->insert_id]);
        } else {
            throw new Exception("Gagal menyimpan ke database: " . $conn->error);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>