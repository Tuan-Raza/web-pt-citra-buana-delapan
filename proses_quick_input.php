<?php
session_start();
require 'koneksi.php'; // Menggunakan $koneksi

// Proteksi: Pastikan yang masuk benar-benar staff
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php?pesan=akses_ditolak");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_barang = mysqli_real_escape_string($koneksi, $_POST['id_barang']);
    $tipe_mutasi = mysqli_real_escape_string($koneksi, $_POST['tipe_mutasi']);
    $jumlah = (int)$_POST['jumlah'];
    
    // Tangkap input Keterangan yang lu tambahin di form
    $keterangan = isset($_POST['keterangan']) && !empty($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : 'Direct Input Staff';

    // NAMA PENG-INPUT UNTUK PERTANGGUNGJAWABAN
    $nama_staff = isset($_SESSION['nama_lengkap']) ? mysqli_real_escape_string($koneksi, $_SESSION['nama_lengkap']) : 'Staff';
    
    // Ambil tanggal aja (karena tipe datanya DATE di DB lu)
    $tanggal_input = date('Y-m-d');

    // 1. Cek Stok Terakhir di Tabel data_barang
    $cek_stok = mysqli_query($koneksi, "SELECT stok_tersedia FROM data_barang WHERE id_barang='$id_barang'");
    if(!$cek_stok || mysqli_num_rows($cek_stok) == 0) {
        tampilkanPesan('error', 'Gagal!', 'Barang tidak ditemukan di database.', 'staff_dashboard.php');
    }
    $row_stok = mysqli_fetch_assoc($cek_stok);
    $stok_lama = $row_stok['stok_tersedia'];

    // Gabungkan Keterangan dengan Nama Staff
    $catatan_lengkap = $keterangan . " (Diinput Oleh: " . $nama_staff . ")";

    // 2. Logika Penambahan/Pengurangan Stok
    if ($tipe_mutasi == 'masuk') {
        $stok_baru = $stok_lama + $jumlah;
        $id_trx = 'TRX-IN-' . date('ymdHis');
        
        // Simpan ke tabel barang_masuk (Pakai kolom: tanggal_masuk, no_surat_jalan, supplier)
        $q_insert = "INSERT INTO barang_masuk (id_transaksi, id_barang, jumlah, tanggal_masuk, no_surat_jalan, supplier) 
                     VALUES ('$id_trx', '$id_barang', '$jumlah', '$tanggal_input', '-', '$catatan_lengkap')";
                     
    } elseif ($tipe_mutasi == 'keluar') {
        // Cek apakah stok cukup untuk dikeluarkan
        if ($jumlah > $stok_lama) {
            tampilkanPesan('error', 'Stok Tidak Cukup!', 'Jumlah barang keluar melebihi sisa stok yang ada.', 'staff_dashboard.php');
        }
        $stok_baru = $stok_lama - $jumlah;
        $id_trx = 'TRX-OUT-' . date('ymdHis');
        
        // Simpan ke tabel barang_keluar (Pakai kolom: tanggal_keluar, tujuan)
        $q_insert = "INSERT INTO barang_keluar (id_transaksi, id_barang, jumlah, tanggal_keluar, tujuan) 
                     VALUES ('$id_trx', '$id_barang', '$jumlah', '$tanggal_input', '$catatan_lengkap')";
    }

    // 3. Eksekusi Perubahan Stok Utama
    $update_stok = mysqli_query($koneksi, "UPDATE data_barang SET stok_tersedia='$stok_baru' WHERE id_barang='$id_barang'");

    // 4. Eksekusi Pencatatan Log Transaksi
    if($update_stok) {
        try {
            $insert_log = mysqli_query($koneksi, $q_insert);
            if(!$insert_log) {
                throw new Exception(mysqli_error($koneksi));
            }
            tampilkanPesan('sukses', 'Berhasil!', 'Mantap! Stok berhasil di-update dan tercatat atas nama lu.', 'staff_dashboard.php');
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            // Rollback jika gagal simpan riwayat
            mysqli_query($koneksi, "UPDATE data_barang SET stok_tersedia='$stok_lama' WHERE id_barang='$id_barang'");
            tampilkanPesan('error', 'Gagal Simpan Riwayat!', 'Pesan MySQL: ' . addslashes($error_msg), 'staff_dashboard.php');
        }
    } else {
        tampilkanPesan('error', 'Gagal!', 'Sistem gagal mengupdate stok utama di data_barang.', 'staff_dashboard.php');
    }
}

// =========================================================================
// FUNGSI HELPER UNTUK TAMPILAN POPUP MODERN (GANTIIN ALERT BAWAAN BROWSER)
// =========================================================================
function tampilkanPesan($tipe, $judul, $pesan, $redirect) {
    $icon = $tipe == 'sukses' ? '&#10004;' : '&#10006;'; // Ikon Centang atau Silang
    $iconClass = $tipe == 'sukses' ? 'icon-success' : 'icon-error';
    $loaderColor = $tipe == 'sukses' ? '#10b981' : '#ef4444';
    
    echo "<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$judul</title>
        <style>
            * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            body { background-color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .modal-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); max-width: 400px; width: 90%; border: 1px solid #f1f5f9; }
            .icon { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto; font-weight: bold; }
            .icon-success { background: #dcfce7; color: #10b981; }
            .icon-error { background: #fee2e2; color: #ef4444; }
            h3 { color: #0f172a; font-size: 22px; margin: 0 0 10px 0; }
            p { color: #64748b; margin: 0 0 25px 0; font-size: 15px; line-height: 1.5; }
            .loader { border: 3px solid #f1f5f9; border-top: 3px solid $loaderColor; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto; }
            @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
    </head>
    <body>
        <div class='modal-box'>
            <div class='icon $iconClass'>$icon</div>
            <h3>$judul</h3>
            <p>$pesan</p>
            <div class='loader'></div>
            <script>
                // Auto redirect setelah 1.8 detik
                setTimeout(function() { window.location.href = '$redirect'; }, 1800);
            </script>
        </div>
    </body>
    </html>";
    exit();
}
?>