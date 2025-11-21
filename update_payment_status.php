<?php
// update_payment_status.php
// Handles updating the payment status in the database.

// Set header agar outputnya selalu JSON
header('Content-Type: application/json');

// Panggil koneksi database
require_once 'includes/db.php';

// Ambil data mentah yang dikirim via POST (dalam format JSON)
$data = json_decode(file_get_contents("php://input"));

// Validasi input dengan lebih teliti
if (!isset($data->invoice) || !isset($data->status) || empty(trim($data->invoice)) || empty(trim($data->status))) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$invoice = $data->invoice;
$new_status = $data->status;

try {
    $sql = "UPDATE service SET status_pembayaran = ? WHERE invoice = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_status, $invoice);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diperbarui.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengeksekusi query.']);
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi error: ' . $e->getMessage()]);
}
?>
