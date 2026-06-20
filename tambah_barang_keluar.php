<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

$auto_id = "TRX-OUT-" . date("dmyHis"); 
$error_msg = "";

if (isset($_POST['simpan'])) {
    $id_transaksi = mysqli_real_escape_string($koneksi, $_POST['id_transaksi']);
    $tanggal_keluar = mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']);
    $tujuan = mysqli_real_escape_string($koneksi, $_POST['tujuan']);
    $id_barang = mysqli_real_escape_string($koneksi, $_POST['id_barang']);
    $jumlah = mysqli_real_escape_string($koneksi, $_POST['jumlah']);
    
    // TANGKAPAN DATA BARU: NO SURAT JALAN
    $no_surat_jalan = mysqli_real_escape_string($koneksi, $_POST['no_surat_jalan']);
    if(empty($no_surat_jalan)) { $no_surat_jalan = '-'; } // Kasih strip kalau admin gak ngisi

    // 1. CEK STOK DULU SEBELUM DIKURANGI
    $cek_stok = mysqli_query($koneksi, "SELECT stok_tersedia FROM data_barang WHERE id_barang = '$id_barang'");
    $data_stok = mysqli_fetch_assoc($cek_stok);
    
    if ($jumlah > $data_stok['stok_tersedia']) {
        // Jika jumlah keluar melebihi stok, buat pesan error
        $error_msg = "Gagal! Stok tidak mencukupi. Sisa stok saat ini hanya: <strong>" . $data_stok['stok_tersedia'] . "</strong>";
    } else {
        // 2. Jika stok aman, masukkan data ke tabel barang_keluar (Query diupdate nambahin no_surat_jalan)
        $insert_mutasi = "INSERT INTO barang_keluar (id_transaksi, tanggal_keluar, tujuan, id_barang, jumlah, no_surat_jalan) 
                          VALUES ('$id_transaksi', '$tanggal_keluar', '$tujuan', '$id_barang', '$jumlah', '$no_surat_jalan')";
        
        if (mysqli_query($koneksi, $insert_mutasi)) {
            // 3. UPDATE STOK: Kurangi stok di tabel data_barang
            $update_stok = "UPDATE data_barang SET stok_tersedia = stok_tersedia - $jumlah WHERE id_barang = '$id_barang'";
            mysqli_query($koneksi, $update_stok);

            header("Location: barang_keluar.php?status=sukses");
            exit;
        } else {
            $error_msg = "Terjadi kesalahan sistem: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Catat Pengeluaran Barang</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; }
        body { background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; width: 100vw; }
        
        .modal-card { background: #ffffff; width: 650px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02); overflow: hidden; border: 1px solid #e2e8f0; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 30px; border-bottom: 1px solid #f1f5f9; }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .close-btn { text-decoration: none; color: #94a3b8; font-size: 22px; transition: color 0.2s; }
        .close-btn:hover { color: #64748b; }
        
        .modal-body { padding: 30px; }
        
        /* BANNER ERROR CUSTOM */
        .error-banner { background-color: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; display: <?= ($error_msg != '') ? 'block' : 'none' ?>; }

        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13.5px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
        
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #f8fafc; color: #334155; transition: all 0.2s ease; }
        .form-group input:focus, .form-group select:focus { border-color: #8b5cf6; background: #ffffff; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .input-readonly { background: #e2e8f0 !important; cursor: not-allowed; font-weight: bold;}

        .modal-footer { padding: 20px 30px 30px 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9;}
        .btn { padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-cancel { background: #ffffff; border: 1px solid #e2e8f0; color: #334155; }
        .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
        
        /* TOMBOL SAVE WARNA UNGU */
        .btn-save { background: #8b5cf6; border: none; color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2); }
        .btn-save:hover { background: #7c3aed; }
    </style>
</head>
<body>

    <div class="modal-card">
        <div class="modal-header">
            <h3>Catat Pengeluaran Barang</h3>
            <a href="barang_keluar.php" class="close-btn">&times;</a>
        </div>
        
        <form action="" method="POST">
            <div class="modal-body">
                <div class="error-banner">
                    <?= $error_msg ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>ID Transaksi</label>
                        <input type="text" name="id_transaksi" class="input-readonly" value="<?= $auto_id ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Keluar</label>
                        <input type="date" name="tanggal_keluar" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Tujuan / Penerima</label>
                    <input type="text" name="tujuan" placeholder="Contoh: Proyek Site Bekasi" required>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Pilih Barang yang Keluar</label>
                    <select name="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php
                        $q_brg = mysqli_query($koneksi, "SELECT * FROM data_barang ORDER BY nama_barang ASC");
                        while($b = mysqli_fetch_assoc($q_brg)){
                            echo "<option value='{$b['id_barang']}'>{$b['kode_sku']} - {$b['nama_barang']} (Sisa Stok: {$b['stok_tersedia']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah Keluar (Quantity)</label>
                        <input type="number" name="jumlah" placeholder="Masukkan angka" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>No. Surat Jalan</label>
                        <input type="text" name="no_surat_jalan" placeholder="Contoh: SJ-001 (Isi jika ada)">
                    </div>
                </div>

            </div>
            
            <div class="modal-footer">
                <a href="barang_keluar.php" class="btn btn-cancel">Batal</a>
                <button type="submit" name="simpan" class="btn btn-save">Simpan & Kurangi Stok</button>
            </div>
        </form>
    </div>

</body>
</html>