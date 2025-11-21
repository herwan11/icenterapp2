<?php
// add_customer.php

require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nama = $data['nama'] ?? null;
    $no_hp = $data['kontak'] ?? null; // Nama field di frontend adalah 'kontak'
    $alamat = $data['alamat'] ?? '';
    $keluhan = $data['keluhan'] ?? null;

    if ($nama && $no_hp && $keluhan) {
        // Cek apakah kontak (no_hp) sudah ada di tabel pelanggan
        $stmt_check = $conn->prepare("SELECT id FROM pelanggan WHERE no_hp = ?");
        $stmt_check->bind_param("s", $no_hp);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $response['message'] = 'Kontak sudah terdaftar.';
        } else {
            // Simpan ke tabel 'pelanggan'
            $stmt_insert = $conn->prepare("INSERT INTO pelanggan (nama, no_hp, alamat, keluhan) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $nama, $no_hp, $alamat, $keluhan);

            if ($stmt_insert->execute()) {
                $new_customer_id = $conn->insert_id;
                $response = [
                    'success' => true,
                    'message' => 'Pelanggan berhasil ditambahkan.',
                    'customer' => [
                        'id' => $new_customer_id,
                        'nama' => $nama,
                        'kontak' => $no_hp,
                        'alamat' => $alamat,
                        'keluhan' => $keluhan
                    ]
                ];
            } else {
                $response['message'] = 'Gagal menyimpan data ke database.';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    } else {
        $response['message'] = 'Nama, Kontak, dan Keluhan wajib diisi.';
    }
}

echo json_encode($response);
$conn->close();
?>

