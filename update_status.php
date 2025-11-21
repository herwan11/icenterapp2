<?php
// update_status.php
// Handles updating the service status in the database, now with stock return logic.

header('Content-Type: application/json');
require_once 'includes/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->invoice) || !isset($data->status) || empty(trim($data->invoice)) || empty(trim($data->status))) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$invoice = $data->invoice;
$new_status = $data->status;

// Mulai transaksi untuk memastikan integritas data
$conn->begin_transaction();

try {
    // Cek jika status baru adalah Batal atau Refund, maka kembalikan stok
    if ($new_status === 'Batal' || $new_status === 'Refund') {
        
        // 1. Ambil semua sparepart yang terpakai untuk invoice ini dari sparepart_keluar
        $sql_get_parts = "SELECT sk.code_sparepart, sk.jumlah, ms.harga_jual 
                          FROM sparepart_keluar sk
                          JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
                          WHERE sk.invoice_service = ?";
        $stmt_get_parts = $conn->prepare($sql_get_parts);
        $stmt_get_parts->bind_param("s", $invoice);
        $stmt_get_parts->execute();
        $used_parts_result = $stmt_get_parts->get_result();
        
        $total_harga_dikembalikan = 0;

        // 2. Loop setiap sparepart untuk mengembalikan stok
        while ($part = $used_parts_result->fetch_assoc()) {
            $code = $part['code_sparepart'];
            $qty = $part['jumlah'];
            $harga_jual = $part['harga_jual'];
            
            // a. Tambahkan kembali stok ke master_sparepart
            $sql_return_stock = "UPDATE master_sparepart SET stok_tersedia = stok_tersedia + ? WHERE code_sparepart = ?";
            $stmt_return_stock = $conn->prepare($sql_return_stock);
            $stmt_return_stock->bind_param("is", $qty, $code);
            $stmt_return_stock->execute();

            // b. Akumulasi total harga yang akan dikurangkan dari sub_total service
            $total_harga_dikembalikan += ($harga_jual * $qty);
        }

        // 3. Jika ada sparepart yang dikembalikan, kurangi sub_total di tabel service
        if ($total_harga_dikembalikan > 0) {
            $sql_update_subtotal = "UPDATE service SET sub_total = sub_total - ? WHERE invoice = ?";
            $stmt_update_subtotal = $conn->prepare($sql_update_subtotal);
            $stmt_update_subtotal->bind_param("ds", $total_harga_dikembalikan, $invoice);
            $stmt_update_subtotal->execute();
        }

        // 4. Hapus catatan dari sparepart_keluar yang berelasi dengan invoice ini
        $sql_delete_usage = "DELETE FROM sparepart_keluar WHERE invoice_service = ?";
        $stmt_delete_usage = $conn->prepare($sql_delete_usage);
        $stmt_delete_usage->bind_param("s", $invoice);
        $stmt_delete_usage->execute();
    }

    // 5. Update status service (setelah semua proses stok selesai)
    $sql_update_status = "UPDATE service SET status_service = ? WHERE invoice = ?";
    $stmt_update_status = $conn->prepare($sql_update_status);
    $stmt_update_status->bind_param("ss", $new_status, $invoice);
    
    if ($stmt_update_status->execute()) {
        // Jika semua query berhasil, commit transaksi
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Status service berhasil diperbarui.']);
    } else {
        throw new Exception('Gagal memperbarui status service.');
    }

} catch (Exception $e) {
    // Jika ada error di salah satu langkah, batalkan semua perubahan
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Terjadi error: ' . $e->getMessage()]);
}

$conn->close();
?>

