<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

// Bikin ID Transaksi Otomatis
$auto_id = "TRX-IN-" . date("dmyHis"); 

if (isset($_POST['simpan'])) {
    // UBAH: $conn menjadi $koneksi
    $id_transaksi = mysqli_real_escape_string($koneksi, $_POST['id_transaksi']);
    $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);
    $no_surat_jalan = mysqli_real_escape_string($koneksi, $_POST['no_surat_jalan']);
    $supplier = mysqli_real_escape_string($koneksi, $_POST['supplier']);
    $id_barang = mysqli_real_escape_string($koneksi, $_POST['id_barang']);
    $jumlah = mysqli_real_escape_string($koneksi, $_POST['jumlah']);

    $insert_mutasi = "INSERT INTO barang_masuk (id_transaksi, tanggal_masuk, no_surat_jalan, supplier, id_barang, jumlah) 
                      VALUES ('$id_transaksi', '$tanggal_masuk', '$no_surat_jalan', '$supplier', '$id_barang', '$jumlah')";
    
    // UBAH: $conn menjadi $koneksi
    if (mysqli_query($koneksi, $insert_mutasi)) {
        // UPDATE STOK: Tambahkan stok di tabel data_barang
        $update_stok = "UPDATE data_barang SET stok_tersedia = stok_tersedia + $jumlah WHERE id_barang = '$id_barang'";
        mysqli_query($koneksi, $update_stok);

        // KITA HAPUS ALERT BROWSER, GANTI DENGAN REDIRECT STATUS SUKSES
        header("Location: barang_masuk.php?status=sukses");
        exit;
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Catat Barang Masuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; }
        body { background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; width: 100vw; }
        
        .modal-card { background: #ffffff; width: 650px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02); overflow: hidden; border: 1px solid #e2e8f0; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 30px; border-bottom: 1px solid #f1f5f9; }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .close-btn { text-decoration: none; color: #94a3b8; font-size: 22px; transition: color 0.2s; }
        .close-btn:hover { color: #64748b; }
        
        .modal-body { padding: 30px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13.5px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
        
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #f8fafc; color: #334155; transition: all 0.2s ease; }
        .form-group input:focus, .form-group select:focus { border-color: #10b981; background: #ffffff; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        .input-readonly { background: #e2e8f0 !important; cursor: not-allowed; font-weight: bold;}

        .modal-footer { padding: 20px 30px 30px 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9;}
        .btn { padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-cancel { background: #ffffff; border: 1px solid #e2e8f0; color: #334155; }
        .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-save { background: #10b981; border: none; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-save:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="modal-card">
        <div class="modal-header">
            <h3>Catat Barang Masuk</h3>
            <a href="barang_masuk.php" class="close-btn">&times;</a>
        </div>
        <form action="" method="POST">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Transaksi</label>
                        <input type="text" name="id_transaksi" class="input-readonly" value="<?= $auto_id ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nomor Surat Jalan</label>
                        <input type="text" name="no_surat_jalan" placeholder="Contoh: SJ-2026/05/12" required>
                    </div>
                    <div class="form-group">
                        <label>Pemasok / Supplier</label>
                        <input type="text" name="supplier" placeholder="Contoh: PT. Indo Karya" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Pilih Barang yang Masuk</label>
                    <select name="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php
                        // UBAH: $conn menjadi $koneksi agar data barang bisa tampil
                        $q_brg = mysqli_query($koneksi, "SELECT * FROM data_barang ORDER BY nama_barang ASC");
                        while($b = mysqli_fetch_assoc($q_brg)){
                            echo "<option value='{$b['id_barang']}'>{$b['kode_sku']} - {$b['nama_barang']} (Stok Saat Ini: {$b['stok_tersedia']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah Masuk (Quantity)</label>
                    <input type="number" name="jumlah" placeholder="Masukkan angka" min="1" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <a href="barang_masuk.php" class="btn btn-cancel">Batal</a>
                <button type="submit" name="simpan" class="btn btn-save">Simpan & Update Stok</button>
            </div>
        </form>
    </div>
</body>
</html>