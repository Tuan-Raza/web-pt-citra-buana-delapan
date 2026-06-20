<?php
session_start();
require 'koneksi.php'; // Menggunakan $koneksi

// Proteksi: Pastikan yang masuk benar-benar staff
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php?pesan=akses_ditolak");
    exit();
}

// --- MENGAMBIL DATA USER & FOTO PROFIL UNTUK TOPBAR ---
$id_user_login = $_SESSION['id_user'];
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

// Bikin Inisial (Jaga-jaga kalau belum upload foto)
$inisial = 'ST';
if (!empty($me['nama_lengkap'])) {
    $words = explode(" ", $me['nama_lengkap']);
    $inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}

// Bikin Tag HTML Avatar
$avatar_html = "";
if(!empty($me['foto_profil']) && file_exists('uploads/' . $me['foto_profil'])) {
    $foto_url = 'uploads/' . $me['foto_profil'];
    $avatar_html = "<img src='$foto_url' alt='Profil' style='width: 100%; height: 100%; object-fit: cover;'>";
} else {
    $avatar_html = $inisial;
}

// MENGAMBIL DATA WIDGET HARI INI
$tanggal_hari_ini = date('Y-m-d');
$total_masuk = 0;
$total_keluar = 0;

// Menghitung Barang Masuk Hari Ini
try {
    $q_masuk_hari_ini = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM barang_masuk WHERE tanggal_masuk = '$tanggal_hari_ini'");
    if($q_masuk_hari_ini) {
        $row_m = mysqli_fetch_assoc($q_masuk_hari_ini);
        $total_masuk = $row_m['total'] ?? 0;
    }
} catch (Exception $e) { $total_masuk = 0; }

// Menghitung Barang Keluar Hari Ini
try {
    $q_keluar_hari_ini = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM barang_keluar WHERE tanggal_keluar = '$tanggal_hari_ini'");
    if($q_keluar_hari_ini) {
        $row_k = mysqli_fetch_assoc($q_keluar_hari_ini);
        $total_keluar = $row_k['total'] ?? 0;
    }
} catch (Exception $e) { $total_keluar = 0; }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>CB Portal Staff - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f8fafc; height: 100vh; overflow: hidden; }
        
        /* --- SIDEBAR RESPONSIVE --- */
        .sidebar { width: 250px; background: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; transition: 0.3s; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid #f1f5f9; display: block; }
        .sidebar-header h3 { font-size: 18px; color: #0ea5e9; letter-spacing: 1px;}
        .sidebar-header p { font-size: 12px; color: #64748b; margin-top: 5px;}
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: block; padding: 12px 20px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; }
        .menu a.active { background: #e0f2fe; color: #0ea5e9; border-left: 4px solid #0ea5e9; }
        
        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { padding: 15px 30px; background: white; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .user-profile { display: flex; align-items: center; gap: 10px; text-align: right; }
        .avatar { width: 35px; height: 35px; background: #1e293b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .content-area { padding: 30px; }
        .welcome-banner { background: #0ea5e9; color: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; }
        .welcome-banner h2 { margin-bottom: 5px; }
        .welcome-banner p { font-size: 14px; opacity: 0.9; }
        
        /* --- WIDGETS --- */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .card h4 { font-size: 13px; color: #64748b; margin-bottom: 10px; }
        .card .value { font-size: 28px; font-weight: bold; color: #0f172a; }
        .card .unit { font-size: 14px; color: #94a3b8; font-weight: normal; }

        /* Form Quick Input */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; color: #64748b; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
        .btn-submit { width: 100%; padding: 12px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }

        /* Media Query untuk HP */
        @media (max-width: 768px) {
            body { flex-direction: column; overflow-y: auto; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 15px; }
            .user-profile { align-self: flex-end; }
        }
        
        /* Table Custom Untuk Log Aktivitas */
        .log-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .log-table th { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; }
        .log-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header" style="display: block; text-align: center; padding: 25px 20px;">
            <h3 style="font-size: 18px; color: #0ea5e9; letter-spacing: 1px;">PT Citra Buana 8</h3>
            <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Inventory System v3.5</p>
        </div>
        
        <ul class="menu">
            <li><a href="staff_dashboard.php" class="active">Beranda Operasional</a></li>
            <li><a href="cek_stok.php">Cek Stok Gudang</a></li>
            <li><a href="input_barang.php">Input Barang Masuk/Keluar</a></li>
            <li><a href="staff_pengaturan.php">Pengaturan Akun</a></li>
        </ul>
        <div style="margin-top: auto; padding: 20px;">
            <a href="logout.php" style="color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold;">Keluar Aplikasi</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h3>Dashboard Operasional</h3>
            <div class="user-profile">
                <div>
                    <strong style="display:block; font-size: 14px; color: #0f172a;"><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span style="font-size: 12px; color: #64748b;">Staff Operasional</span>
                </div>
                <div class="avatar" style="overflow: hidden;"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="content-area">
            <div class="welcome-banner">
                <h2>Selamat datang, <?= explode(' ', htmlspecialchars($me['nama_lengkap']))[0] ?>!</h2>
                <p>Silakan input data barang masuk atau keluar secara real-time. Data akan otomatis memotong/menambah stok di database Admin.</p>
            </div>

            <div class="grid-container">
                <div class="card">
                    <h4>Barang Masuk Hari Ini</h4>
                    <div class="value text-green"><?= $total_masuk ?> <span class="unit">Unit</span></div>
                </div>
                <div class="card">
                    <h4>Barang Keluar Hari Ini</h4>
                    <div class="value text-orange"><?= $total_keluar ?> <span class="unit">Unit</span></div>
                </div>
            </div>

            <div class="grid-container" style="grid-template-columns: 2fr 1fr;">
                <div class="card" style="overflow-x: auto;">
                    <h4>Log Aktivitas Terakhir (Direct Input)</h4>
                    
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>BARANG</th>
                                <th>TIPE</th>
                                <th>JUMLAH</th>
                                <th>WAKTU / TANGGAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $nama_user = mysqli_real_escape_string($koneksi, $me['nama_lengkap']);
                            try {
                                $q_log = mysqli_query($koneksi, "
                                    (SELECT b.nama_barang, 'Masuk' as tipe, m.jumlah as jumlah, m.created_at as waktu 
                                     FROM barang_masuk m JOIN data_barang b ON m.id_barang = b.id_barang 
                                     WHERE m.supplier LIKE '%$nama_user%')
                                    UNION ALL
                                    (SELECT b.nama_barang, 'Keluar' as tipe, k.jumlah as jumlah, k.created_at as waktu 
                                     FROM barang_keluar k JOIN data_barang b ON k.id_barang = b.id_barang 
                                     WHERE k.tujuan LIKE '%$nama_user%')
                                    ORDER BY waktu DESC LIMIT 5
                                ");

                                if($q_log && mysqli_num_rows($q_log) > 0) {
                                    while($log = mysqli_fetch_assoc($q_log)) {
                                        $warna_tipe = $log['tipe'] == 'Masuk' ? '#10b981' : '#ef4444';
                                        echo "<tr>";
                                        echo "<td style='color: #0f172a; font-weight: 500;'>".$log['nama_barang']."</td>";
                                        echo "<td style='color: ".$warna_tipe."; font-weight: 600;'>".$log['tipe']."</td>";
                                        echo "<td>".$log['jumlah']." Unit</td>";
                                        echo "<td>".date('d M Y, H:i', strtotime($log['waktu']))."</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center; color: #94a3b8; padding: 40px 0;'>Belum ada riwayat aktivitas input.</td></tr>";
                                }
                            } catch (Exception $e) {
                                echo "<tr><td colspan='4' style='text-align: center; color: #ef4444; font-weight: bold; padding: 40px 0;'>Terjadi kesalahan saat memuat data.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                </div>
                <div class="card">
                    <h4 style="color: #0f172a; font-weight: bold; margin-bottom: 20px;">Input Cepat (Quick Input)</h4>
                    <form action="proses_quick_input.php" method="POST">
                        <div class="form-group">
                            <label>Pilih Barang</label>
                            <select class="form-control" name="id_barang" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php
                                $query_barang = mysqli_query($koneksi, "SELECT id_barang, nama_barang, kode_sku FROM data_barang ORDER BY nama_barang ASC");
                                if($query_barang && mysqli_num_rows($query_barang) > 0){
                                    while($row_brg = mysqli_fetch_assoc($query_barang)){
                                        echo "<option value='".$row_brg['id_barang']."'>".$row_brg['nama_barang']." (SKU: ".$row_brg['kode_sku'].")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipe Transaksi</label>
                            <select class="form-control" name="tipe_mutasi" required>
                                <option value="masuk">Barang Masuk (Penambahan)</option>
                                <option value="keluar">Barang Keluar (Pengurangan)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Unit</label>
                            <input type="number" class="form-control" name="jumlah" placeholder="Contoh: 5" required min="1">
                        </div>
                        <div class="form-group">
                            <label>No. Surat Jalan</label>
                            <input type="text" class="form-control" name="no_surat_jalan" placeholder="Contoh: SJ-001 (Isi jika ada)">
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Restock / Digunakan untuk...">
                        </div>
                        <button type="submit" class="btn-submit">Simpan Perubahan Stok</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>