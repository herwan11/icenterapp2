<?php
// update_nominal.php
// Digunakan untuk mengupdate kolom uang_muka (sebagai Total Bayar) secara AJAX

require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['invoice'], $data['nominal'])) {
    $invoice = $data['invoice'];
    $nominal = floatval($data['nominal']);

    // Update kolom uang_muka di database
    $stmt = $conn->prepare("UPDATE service SET uang_muka = ? WHERE invoice = ?");
    $stmt->bind_param("ds", $nominal, $invoice);

    if ($stmt->execute()) {
        // Cek logic pelunasan sederhana
        // Ambil sub_total untuk perbandingan status
        $cek = $conn->query("SELECT sub_total FROM service WHERE invoice = '$invoice'");
        $row = $cek->fetch_assoc();
        
        // Jika bayar >= sub_total, set Lunas. Jika tidak, Belum Lunas
        $status_bayar = ($nominal >= $row['sub_total']) ? 'Lunas' : 'Belum Lunas';
        $conn->query("UPDATE service SET status_pembayaran = '$status_bayar' WHERE invoice = '$invoice'");

        echo json_encode(['success' => true, 'message' => 'Nominal diperbarui', 'status_bayar' => $status_bayar]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>