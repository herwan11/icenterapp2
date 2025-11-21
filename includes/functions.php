<?php
// includes/functions.php

// Mulai sesi di sini agar tersedia di semua halaman yang menyertakan file ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan koneksi database
require_once 'db.php';

/**
 * Memeriksa apakah pengguna sudah login.
 * Jika belum, akan diarahkan ke halaman login.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Mendapatkan role pengguna yang sedang login.
 * @return string Role pengguna (e.g., 'owner', 'admin') atau 'guest' jika tidak login.
 */
function get_user_role() {
    return $_SESSION['role'] ?? 'guest';
}

// --- FUNGSI PLACEHOLDER UNTUK DATA DASHBOARD ---
// Nilai-nilai ini akan diganti dengan query database asli nanti.

function get_total_pemasukan($conn) {
    // TODO: Buat query SQL untuk menghitung total pemasukan dari tabel service, penjualan_sparepart, dll.
    return 15000000; // Contoh data
}

function get_total_pengeluaran($conn) {
    // TODO: Buat query SQL untuk menghitung total pengeluaran dari harga modal, gaji, dll.
    return 8000000; // Contoh data
}

function get_laba_kotor($conn) {
    // TODO: Hitung laba kotor dari pemasukan - pengeluaran.
    return get_total_pemasukan($conn) - get_total_pengeluaran($conn); // Contoh data
}

function get_komisi_karyawan($conn) {
    // TODO: Buat query SQL untuk menghitung total komisi dari tabel komisi_karyawan.
    return 1500000; // Contoh data
}

?>
