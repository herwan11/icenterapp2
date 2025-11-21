<?php
// remove_external_sparepart.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id_pembelian = $data['id'] ?? null;

if (empty($id_pembelian)) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Ambil data pembelian untuk tahu jumlah & invoice
    $stmt_get = $conn->prepare("SELECT * FROM pembelian_sparepart_luar WHERE id_pembelian = ?");
    $stmt_get->bind_param("s", $id_pembelian);
    $stmt_get->execute();
    $pembelian = $stmt_get->get_result()->fetch_assoc();

    if ($pembelian) {
        $total_harga = $pembelian['total_harga'];
        $invoice = $pembelian['invoice_service'];
        $nama_sparepart = $pembelian['nama_sparepart'];

        // 2. Hapus dari tabel pembelian
        $stmt_delete = $conn->prepare("DELETE FROM pembelian_sparepart_luar WHERE id_pembelian = ?");
        $stmt_delete->bind_param("s", $id_pembelian);
        $stmt_delete->execute();

        // 3. Kembalikan uang ke kas
        $keterangan_kas = "Refund pembelian sparepart luar untuk service " . $invoice;
        $saldo_sebelumnya = 0;
        $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
        if ($result_saldo->num_rows > 0) { $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir']; }
        $saldo_terakhir = $saldo_sebelumnya + $total_harga;
        $stmt_kas = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES ('masuk', ?, ?, ?)");
        $stmt_kas->bind_param("dsd", $total_harga, $keterangan_kas, $saldo_terakhir);
        $stmt_kas->execute();

        // 4. Kurangi sub_total di tabel service
        $stmt_service = $conn->prepare("UPDATE service SET sub_total = sub_total - ? WHERE invoice = ?");
        $stmt_service->bind_param("ds", $total_harga, $invoice);
        $stmt_service->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pembelian sparepart luar dibatalkan dan kas dikembalikan.']);
    } else {
        throw new Exception('Data pembelian tidak ditemukan.');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
$conn->close();
?>
