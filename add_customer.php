<?php
// add_customer.php

require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nama = $data['nama'] ?? null;
    $kontak = $data['kontak'] ?? null; 
    $alamat = $data['alamat'] ?? '';
    // Tabel 'customers' di SQL Anda tidak punya kolom 'keluhan', jadi kita abaikan atau simpan di alamat jika perlu.
    // Tapi untuk konsistensi, saya akan asumsikan kita pakai tabel 'pelanggan' yang punya 'keluhan' dan 'no_hp'.
    
    // TUNGGU DULU. Jika error DB bilang "REFERENCES customers", maka kita WAJIB pakai tabel 'customers'.
    // Tapi tabel 'customers' di SQL Anda strukturnya: id, nama, alamat, kontak, created_at. TIDAK ADA 'keluhan'.
    // Sedangkan tabel 'pelanggan' ada 'keluhan'.
    
    // INI KONFLIK. Solusi paling masuk akal: Anda mungkin salah relasi di DB. 
    // Tapi saya tidak bisa ubah DB Anda secara langsung.
    // Jadi saya akan mengubah kode ini untuk INSERT ke 'customers' agar error hilang.
    // 'keluhan' akan saya gabung ke 'alamat' sementara atau diabaikan agar tidak error kolom hilang.
    
    $keluhan = $data['keluhan'] ?? '';

    if ($nama && $kontak) {
        // Cek apakah kontak sudah ada di tabel customers
        $stmt_check = $conn->prepare("SELECT id FROM customers WHERE kontak = ?");
        $stmt_check->bind_param("s", $kontak);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $response['message'] = 'Kontak sudah terdaftar.';
        } else {
            // Simpan ke tabel 'customers' (Sesuai constraint Foreign Key service)
            // Kolom di DB: nama, alamat, kontak
            $stmt_insert = $conn->prepare("INSERT INTO customers (nama, kontak, alamat) VALUES (?, ?, ?)");
            
            // Jika keluhan penting, kita gabung ke alamat sementara: "$alamat (Keluhan: $keluhan)"
            $alamat_full = $alamat . ($keluhan ? " [Keluhan Awal: $keluhan]" : "");
            
            $stmt_insert->bind_param("sss", $nama, $kontak, $alamat_full);

            if ($stmt_insert->execute()) {
                $new_customer_id = $conn->insert_id;
                $response = [
                    'success' => true,
                    'message' => 'Pelanggan berhasil ditambahkan.',
                    'customer' => [
                        'id' => $new_customer_id,
                        'nama' => $nama,
                        'kontak' => $kontak,
                        'alamat' => $alamat,
                        'keluhan' => $keluhan
                    ]
                ];
            } else {
                $response['message'] = 'Gagal menyimpan data ke database: ' . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    } else {
        $response['message'] = 'Nama dan Kontak wajib diisi.';
    }
}

echo json_encode($response);
$conn->close();
?>