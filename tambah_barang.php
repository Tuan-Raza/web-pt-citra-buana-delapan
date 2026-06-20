<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

if (isset($_POST['simpan'])) {
    $kode_sku = mysqli_real_escape_string($koneksi, $_POST['kode_sku']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi_rak = mysqli_real_escape_string($koneksi, $_POST['lokasi_rak']);
    $harga_satuan = mysqli_real_escape_string($koneksi, $_POST['harga_satuan']);
    $stok_tersedia = mysqli_real_escape_string($koneksi, $_POST['stok_tersedia']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);

    // Validasi SKU agar tidak duplikat
    $cek_sku = mysqli_query($koneksi, "SELECT kode_sku FROM data_barang WHERE kode_sku = '$kode_sku'");
    if(mysqli_num_rows($cek_sku) > 0) {
        echo "<script>alert('Gagal! Kode SKU ($kode_sku) sudah terpakai. Gunakan SKU lain!');</script>";
    } else {
        $query = "INSERT INTO data_barang (kode_sku, nama_barang, kategori, lokasi_rak, harga_satuan, stok_tersedia, satuan) 
                  VALUES ('$kode_sku', '$nama_barang', '$kategori', '$lokasi_rak', '$harga_satuan', '$stok_tersedia', '$satuan')";

        if (mysqli_query($koneksi, $query)) {
            header("Location: data_barang.php");
            exit;
        } else {
            echo "<script>alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Barang Baru</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* BACKGROUND FULL HALUS (Sesuai image_3d4e7f.png) */
        body { 
            background-color: #f1f5f9; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            width: 100vw;
        }
        
        /* CONTAINER FORM (CARD WHITE PREMIUM) */
        .modal-card { 
            background: #ffffff; 
            width: 650px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02); 
            overflow: hidden; 
            border: 1px solid #e2e8f0;
        }
        
        /* HEADER */
        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 24px 30px; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .close-btn { text-decoration: none; color: #94a3b8; font-size: 22px; transition: color 0.2s; }
        .close-btn:hover { color: #64748b; }
        
        /* BODY FORM */
        .modal-body { padding: 30px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 13.5px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
        
        /* INPUT STYLING */
        .form-group input { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 14px; 
            outline: none; 
            background: #f8fafc; 
            color: #334155;
            transition: all 0.2s ease;
        }
        .form-group input::placeholder { color: #94a3b8; }
        .form-group input:focus { border-color: #3b82f6; background: #ffffff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        /* FOOTER ACTIONS */
        .modal-footer { 
            padding: 20px 30px 30px 30px; 
            display: flex; 
            justify-content: flex-end; 
            gap: 12px; 
        }
        .btn { padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-cancel { background: #ffffff; border: 1px solid #e2e8f0; color: #334155; }
        .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-save { background: #2563eb; border: none; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .btn-save:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Barang Baru</h3>
            <a href="data_barang.php" class="close-btn">&times;</a>
        </div>
        <form action="" method="POST">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" placeholder="Contoh: Lampu LED Philips 18W" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode SKU</label>
                        <input type="text" name="kode_sku" placeholder="Harus Unik (Cth: CB-01)" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="kategori" placeholder="Contoh: ATK" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="harga_satuan" placeholder="Contoh: 50000" required>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Rak</label>
                        <input type="text" name="lokasi_rak" placeholder="Contoh: Rak A-01" required>
                    </div>
                </div>

                <div class="form-row" style="max-width: 50%; padding-right: 10px;">
                    <div class="form-group">
                        <label>Stok Awal</label>
                        <input type="number" name="stok_tersedia" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="satuan" placeholder="Pcs/Rol" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <a href="data_barang.php" class="btn btn-cancel">Batal</a>
                <button type="submit" name="simpan" class="btn btn-save">Simpan Data</button>
            </div>
        </form>
    </div>

</body>
</html>