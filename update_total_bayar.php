<?php
// update_total_bayar.php
// Menyimpan Total Bayar dan memperbarui Status Pembayaran otomatis

require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['invoice'], $data['total_bayar'])) {
    $invoice = $data['invoice'];
    $total_bayar = floatval($data['total_bayar']);

    // 1. Update kolom total_bayar di database
    $stmt = $conn->prepare("UPDATE service SET total_bayar = ? WHERE invoice = ?");
    $stmt->bind_param("ds", $total_bayar, $invoice);

    if ($stmt->execute()) {
        // 2. Cek logic pelunasan (MEMPERHITUNGKAN UANG MUKA)
        $cek = $conn->query("SELECT sub_total, uang_muka FROM service WHERE invoice = '$invoice'");
        $row = $cek->fetch_assoc();
        
        $sub_total = floatval($row['sub_total']);
        $uang_muka = floatval($row['uang_muka']);
        
        // Logic Lunas: Total Bayar (Baru) + Uang Muka >= Sub Total
        $status_bayar = (($total_bayar + $uang_muka) >= $sub_total && $sub_total > 0) ? 'Lunas' : 'Belum Lunas';
        
        $conn->query("UPDATE service SET status_pembayaran = '$status_bayar' WHERE invoice = '$invoice'");

        echo json_encode(['success' => true, 'message' => 'Total bayar diperbarui', 'status_bayar' => $status_bayar]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>