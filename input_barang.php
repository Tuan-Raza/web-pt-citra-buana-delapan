<?php
session_start();
require 'koneksi.php'; // Menggunakan $koneksi yang sudah disepakati

// Proteksi: Pastikan yang masuk benar-benar staff
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php?pesan=akses_ditolak");
    exit();
}

// --- MENGAMBIL DATA USER & FOTO PROFIL UNTUK TOPBAR ---
$id_user_login = $_SESSION['id_user'];
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

$inisial = 'ST';
if (!empty($me['nama_lengkap'])) {
    $words = explode(" ", $me['nama_lengkap']);
    $inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}

$avatar_html = "";
if(!empty($me['foto_profil']) && file_exists('uploads/' . $me['foto_profil'])) {
    $foto_url = 'uploads/' . $me['foto_profil'];
    $avatar_html = "<img src='$foto_url' alt='Profil' style='width: 100%; height: 100%; object-fit: cover;'>";
} else {
    $avatar_html = $inisial;
}

// Ambil data barang yang SUDAH ADA di database untuk dropdown
$query_barang = mysqli_query($koneksi, "SELECT id_barang, nama_barang, kode_sku FROM data_barang ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Input Barang - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f8fafc; height: 100vh; overflow: hidden; }
        
        /* --- SIDEBAR RESPONSIVE (Sama dengan Staff Dashboard) --- */
        .sidebar { width: 250px; background: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; transition: 0.3s; }
        .sidebar-header { display: block; text-align: center; padding: 25px 20px; border-bottom: 1px solid #f1f5f9; }
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: block; padding: 12px 20px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; }
        .menu a.active { background: #e0f2fe; color: #0ea5e9; border-left: 4px solid #0ea5e9; }
        
        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { padding: 15px 30px; background: white; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .user-profile { display: flex; align-items: center; gap: 10px; text-align: right; }
        .avatar { width: 35px; height: 35px; background: #1e293b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        /* Layout Form di Tengah */
        .content-area { padding: 40px; display: flex; justify-content: center; }
        .card { background: white; padding: 35px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); width: 100%; max-width: 600px; }
        .card-title { font-size: 18px; color: #0f172a; font-weight: bold; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }

        /* Form Input Styles */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.3s; background: #f8fafc; }
        .form-control:focus { border-color: #0ea5e9; background: white; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
        .btn-submit { width: 100%; padding: 14px; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; transition: background 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #0284c7; }

        /* --- MEDIA QUERY HP --- */
        @media (max-width: 768px) {
            body { flex-direction: column; overflow-y: auto; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 15px; }
            .user-profile { align-self: flex-end; }
            .content-area { padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3 style="font-size: 18px; color: #0ea5e9; letter-spacing: 1px;">PT Citra Buana 8</h3>
            <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Inventory System v3.5</p>
        </div>
        
        <ul class="menu">
            <li><a href="staff_dashboard.php">Beranda Operasional</a></li>
            <li><a href="cek_stok.php">Cek Stok Gudang</a></li>
            <li><a href="input_barang.php" class="active">Input Barang Masuk/Keluar</a></li>
            <li><a href="staff_pengaturan.php">Pengaturan Akun</a></li>
        </ul>
        <div style="margin-top: auto; padding: 20px;">
            <a href="logout.php" style="color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold;">Keluar Aplikasi</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h3>Input Mutasi Barang</h3>
            <div class="user-profile">
                <div>
                    <strong style="display:block; font-size: 14px; color: #0f172a;"><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span style="font-size: 12px; color: #64748b;">Staff Operasional</span>
                </div>
                <div class="avatar" style="overflow: hidden;"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="content-area">
            <div class="card">
                <div class="card-title">Formulir Catat Mutasi Barang Gudang</div>
                
                <form action="proses_quick_input.php" method="POST">
                    <div class="form-group">
                        <label>Pilih Barang (Hanya Barang Terdaftar)</label>
                        <select class="form-control" name="id_barang" required>
                            <option value="">-- Cari dan Pilih Barang --</option>
                            <?php
                            if($query_barang && mysqli_num_rows($query_barang) > 0){
                                while($row_brg = mysqli_fetch_assoc($query_barang)){
                                    echo "<option value='".$row_brg['id_barang']."'>".htmlspecialchars($row_brg['nama_barang'])." (SKU: ".htmlspecialchars($row_brg['kode_sku']).")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipe Transaksi Mutasi</label>
                        <select class="form-control" name="tipe_mutasi" required>
                            <option value="masuk">Barang Masuk (Penambahan Stok)</option>
                            <option value="keluar">Barang Keluar (Pengurangan Stok)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Unit</label>
                        <input type="number" class="form-control" name="jumlah" placeholder="Contoh: 50" required min="1">
                    </div>
                    <div class="form-group">
                        <label>No. Surat Jalan (Opsional)</label>
                        <input type="text" class="form-control" name="no_surat_jalan" placeholder="Contoh: SJ-001 (Isi jika ada)">
                    </div>
                    <div class="form-group">
                        <label>Keterangan / Tujuan Mutasi</label>
                        <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Restock dari vendor / Dikirim ke proyek..." required>
                    </div>
                    <button type="submit" class="btn-submit">Simpan Perubahan Stok</button>
                </form>

            </div>
        </div>
    </div>

</body>
</html>