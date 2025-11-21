<?php
// get_customer_detail.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 1. Ambil Data Profil Pelanggan
    $stmt = $conn->prepare("SELECT * FROM pelanggan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    if ($customer) {
        // 2. Ambil Riwayat Service (5 Terakhir)
        $services = [];
        $stmt_service = $conn->prepare("SELECT invoice, tanggal, merek_hp, tipe_hp, kerusakan, status_service, sub_total FROM service WHERE customer_id = ? ORDER BY tanggal DESC LIMIT 5");
        $stmt_service->bind_param("i", $id);
        $stmt_service->execute();
        $res_service = $stmt_service->get_result();
        while ($row = $res_service->fetch_assoc()) {
            $services[] = $row;
        }

        // 3. Ambil Riwayat Pembelian Sparepart Langsung (5 Terakhir)
        $purchases = [];
        $stmt_purchase = $conn->prepare("SELECT id, tanggal, total, status_pembayaran FROM penjualan_sparepart WHERE pelanggan_id = ? ORDER BY tanggal DESC LIMIT 5");
        $stmt_purchase->bind_param("i", $id);
        $stmt_purchase->execute();
        $res_purchase = $stmt_purchase->get_result();
        while ($row = $res_purchase->fetch_assoc()) {
            $purchases[] = $row;
        }

        echo json_encode([
            'success' => true,
            'customer' => $customer,
            'services' => $services,
            'purchases' => $purchases
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pelanggan tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>