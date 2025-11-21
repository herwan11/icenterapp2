<?php
// delete_sparepart_transaction.php
require_once 'includes/db.php';
header('Content-Type: application/json');

// Mengambil data ID yang dipilih dari POST request
$data = json_decode(file_get_contents('php://input'), true);
$selected_ids = $data['selected_ids'] ?? [];

if (empty($selected_ids)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada transaksi yang dipilih.']);
    exit;
}

$conn->begin_transaction();
$success_count = 0;
$failed_messages = [];

try {
    // Memproses setiap ID yang dipilih
    foreach ($selected_ids as $id_transaksi) {
        // 1. Ambil data transaksi yang akan dihapus untuk tahu kode, jumlah, dan ID Invoice/Service
        $sql_get = "SELECT sk.code_sparepart, sk.jumlah, sk.invoice_service, ms.harga_jual FROM sparepart_keluar sk JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart WHERE sk.id = ?";
        $stmt_get = $conn->prepare($sql_get);
        if (!$stmt_get) { throw new Exception("Gagal prepare get data: " . $conn->error); }
        $stmt_get->bind_param("i", $id_transaksi);
        $stmt_get->execute();
        $part_data = $stmt_get->get_result()->fetch_assoc();

        if (!$part_data) {
            $failed_messages[] = "ID $id_transaksi tidak ditemukan.";
            continue;
        }

        $code = $part_data['code_sparepart'];
        $qty = $part_data['jumlah'];
        $invoice = $part_data['invoice_service'];
        $harga_total = $part_data['harga_jual'] * $qty;
        $is_direct_sell = (strpos($invoice, 'DIRECT-') === 0);
        
        // 2. Kembalikan stok ke master_sparepart
        $sql_update_stok = "UPDATE master_sparepart SET stok_tersedia = stok_tersedia + ? WHERE code_sparepart = ?";
        $stmt_update_stok = $conn->prepare($sql_update_stok);
        if (!$stmt_update_stok) { throw new Exception("Gagal prepare update stok: " . $conn->error); }
        $stmt_update_stok->bind_param("is", $qty, $code);
        if (!$stmt_update_stok->execute()) { throw new Exception("Gagal eksekusi update stok untuk ID $id_transaksi: " . $stmt_update_stok->error); }


        // 3. Tangani dampaknya pada Kas atau Service
        if ($is_direct_sell) {
            // Jika Penjualan Langsung (Kas dikembalikan)
            $keterangan_kas = "Refund pembatalan Penjualan Langsung: " . $invoice;
            $saldo_sebelumnya = 0;
            $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
            if ($result_saldo->num_rows > 0) { $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir']; }
            $saldo_terakhir = $saldo_sebelumnya - $harga_total; // Kurangi karena saat jual (Lunas) kas MASUK. Saat batal, kita harus anggap kas keluar.
            
            // Catatan: Asumsi Penjualan Langsung yang dibatalkan adalah penjualan Lunas yang dicatat sebagai MASUK. 
            // Jadi, saat dibatalkan, kita KURANGI SALDO (KELUAR)
            $stmt_kas = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES ('keluar', ?, ?, ?)");
            if (!$stmt_kas) { throw new Exception("Gagal prepare kas refund: " . $conn->error); }
            $stmt_kas->bind_param("dsd", $harga_total, $keterangan_kas, $saldo_terakhir);
            if (!$stmt_kas->execute()) { throw new Exception("Gagal eksekusi kas refund untuk ID $id_transaksi: " . $stmt_kas->error); }

        } else {
            // Jika Penggunaan Service (Kurangi sub_total di tabel service)
            $sql_update_subtotal = "UPDATE service SET sub_total = sub_total - ? WHERE invoice = ?";
            $stmt_update_subtotal = $conn->prepare($sql_update_subtotal);
            if (!$stmt_update_subtotal) { throw new Exception("Gagal prepare update subtotal: " . $conn->error); }
            $stmt_update_subtotal->bind_param("ds", $harga_total, $invoice);
            if (!$stmt_update_subtotal->execute()) { throw new Exception("Gagal eksekusi update subtotal untuk ID $id_transaksi: " . $stmt_update_subtotal->error); }
        }

        // 4. Hapus dari sparepart_keluar
        $sql_delete = "DELETE FROM sparepart_keluar WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        if (!$stmt_delete) { throw new Exception("Gagal prepare delete: " . $conn->error); }
        $stmt_delete->bind_param("i", $id_transaksi);
        if (!$stmt_delete->execute()) { throw new Exception("Gagal eksekusi delete untuk ID $id_transaksi: " . $stmt_delete->error); }
        
        $success_count++;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "$success_count transaksi berhasil dihapus.", 'failed_messages' => $failed_messages]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Transaksi dibatalkan: ' . $e->getMessage()]);
}

$conn->close();
?>