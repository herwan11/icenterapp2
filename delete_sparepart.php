<?php
// delete_sparepart.php

require_once 'includes/db.php';

// Cek jika ada data sparepart yang dikirim untuk dihapus
if (isset($_POST['selected_spareparts']) && is_array($_POST['selected_spareparts'])) {
    $sparepart_codes = $_POST['selected_spareparts'];

    if (!empty($sparepart_codes)) {
        // Buat placeholder (?) sebanyak jumlah item yang akan dihapus
        $placeholders = implode(',', array_fill(0, count($sparepart_codes), '?'));
        
        // Siapkan query DELETE yang aman
        $sql = "DELETE FROM master_sparepart WHERE code_sparepart IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind semua nilai code_sparepart ke placeholder
            $stmt->bind_param(str_repeat('s', count($sparepart_codes)), ...$sparepart_codes);
            
            // Eksekusi query
            if ($stmt->execute()) {
                // Redirect kembali ke halaman stok dengan pesan sukses
                header("Location: stok_sparepart.php?status=deletesuccess");
                exit();
            }
        }
    }
}

// Jika tidak ada data atau terjadi error, redirect kembali dengan pesan gagal
header("Location: stok_sparepart.php?status=deletefailed");
exit();
?>
