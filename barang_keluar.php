<?php
session_start();
include 'koneksi.php';

// --- AUTO-SETUP AMAN ANTI ERROR DUPLICATE ---
// (Biar pas narik foto profil gak error kalau kolom belum ada di database)
$cek_telp = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'no_telp'");
if($cek_telp && mysqli_num_rows($cek_telp) == 0) { mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20) DEFAULT '' AFTER email"); }

$cek_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
if($cek_foto && mysqli_num_rows($cek_foto) == 0) { mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) DEFAULT '' AFTER no_telp"); }

// --- MENGAMBIL DATA USER & FOTO PROFIL UNTUK TOPBAR ---
$id_user_login = $_SESSION['id_user'];
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

// Bikin Inisial (Jaga-jaga kalau belum upload foto)
$words = explode(" ", $me['nama_lengkap']);
$inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// Bikin Tag HTML Avatar
$avatar_html = "";
if(!empty($me['foto_profil']) && file_exists('uploads/' . $me['foto_profil'])) {
    $foto_url = 'uploads/' . $me['foto_profil'];
    $avatar_html = "<img src='$foto_url' alt='Profil' style='width: 100%; height: 100%; object-fit: cover;'>";
} else {
    $avatar_html = $inisial;
}

if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

$search = isset($_GET['cari']) ? $_GET['cari'] : '';

$query_sql = "SELECT bk.*, db.nama_barang, db.kode_sku 
              FROM barang_keluar bk 
              JOIN data_barang db ON bk.id_barang = db.id_barang 
              WHERE 1=1";

if ($search != '') {
    $query_sql .= " AND (bk.id_transaksi LIKE '%$search%' OR bk.tujuan LIKE '%$search%' OR db.nama_barang LIKE '%$search%')";
}
$query_sql .= " ORDER BY bk.id_keluar DESC";
$query = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f4f7fa; color: #333; overflow: hidden; }

        .sidebar { width: 260px; background-color: #172134; color: white; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header h3 { font-size: 18px; color: #3b82f6; letter-spacing: 1px;}
        .sidebar-header p { font-size: 12px; color: #64748b; margin-top: 5px;}
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: flex; align-items: center; padding: 14px 25px; color: #94a3b8; text-decoration: none; font-size: 14.5px; transition: all 0.3s ease; }
        .menu a:hover, .menu a.active { background-color: rgba(59, 130, 246, 0.1); color: white; border-left: 4px solid #3b82f6; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 20px 30px; position: relative;}
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0; }
        .topbar-left { display: flex; align-items: baseline; gap: 15px; }
        .topbar-left h2 { font-size: 22px; color: #0f172a; }
        .topbar-left span { font-size: 13px; color: #64748b; }
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; color: #0f172a; }
        .user-info span { font-size: 12px; color: #94a3b8; }
        .avatar { width: 40px; height: 40px; background-color: #1d4ed8; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }

        .table-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-input { width: 400px; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: #8b5cf6; }
        
        /* TOMBOL UNGU KHUSUS BARANG KELUAR */
        .btn-purple { background-color: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2); }
        .btn-purple:hover { background-color: #7c3aed; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 11px; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; font-size: 13px; color: #0f172a; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .item-info strong { display: block; font-size: 14px; margin-bottom: 3px; }
        .item-info span { font-size: 12px; color: #64748b; }
        .badge-success { background: #dcfce7; color: #16a34a; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block;}

        /* MODAL SUCCESS CUSTOM */
        .toast-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(3px); z-index: 9999; display: flex; justify-content: center; align-items: center; animation: fadeIn 0.3s ease-out;}
        .toast-box { background: white; padding: 40px 30px; border-radius: 20px; width: 400px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .toast-icon { width: 80px; height: 80px; background: #ede9fe; color: #8b5cf6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto; font-weight: bold; box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);}
        .toast-box h3 { color: #0f172a; margin-bottom: 12px; font-size: 22px; font-weight: 700;}
        .toast-box p { color: #64748b; font-size: 14.5px; margin-bottom: 30px; line-height: 1.6; }
        .btn-toast { background: #8b5cf6; color: white; border: none; padding: 14px 30px; border-radius: 12px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-block; transition: all 0.2s; width: 100%; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);}
        .btn-toast:hover { background: #7c3aed; transform: translateY(-2px);}
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>PT Citra Buana 8</h3>
            <p>Inventory System v3.5</p>
        </div>
        <ul class="menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="data_barang.php">Data Barang</a></li>
            <li><a href="barang_masuk.php">Barang Masuk</a></li>
            <li><a href="barang_keluar.php" class="active">Barang Keluar</a></li>
            <li><a href="laporan.php">Laporan</a></li>
            <li><a href="pengaturan.php">Pengaturan</a></li>
        </ul>
        <ul class="menu" style="flex: 0; border-top: 1px solid rgba(255,255,255,0.05);">
            <li><a href="logout.php" style="color: #ef4444;">Keluar</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Pengeluaran Barang</h2>
                <span id="realtime-clock">Memuat waktu...</span>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <strong><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span><?= ucfirst($me['role']) ?> Gudang</span>
                </div>
                <div class="avatar" style="overflow: hidden;"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="table-section">
            <div class="filter-bar">
                <form method="GET" action="barang_keluar.php">
                    <input type="text" name="cari" class="search-input" placeholder="Cari ID Transaksi atau Tujuan..." value="<?= $search ?>" onchange="this.form.submit()">
                </form>
                <a href="tambah_barang_keluar.php" class="btn-purple">+ Catat Pengeluaran</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi & Tanggal</th>
                        <th>Tujuan / Penerima</th>
                        <th>Item Barang (SKU)</th>
                        <th>No. Surat Jalan</th> <th>Jml Keluar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)){
                            $tgl = date("d M Y", strtotime($row['tanggal_keluar']));
                            
                            // Logika untuk menampilkan '-' jika no_surat_jalan kosong
                            $surat_jalan = !empty($row['no_surat_jalan']) ? $row['no_surat_jalan'] : '-';
                            
                            echo "<tr>";
                            echo "<td class='item-info'>
                                    <strong>{$row['id_transaksi']}</strong>
                                    <span>{$tgl}</span>
                                  </td>";
                            echo "<td>{$row['tujuan']}</td>";
                            echo "<td class='item-info'>
                                    <strong>{$row['nama_barang']}</strong>
                                    <span>SKU: {$row['kode_sku']}</span>
                                  </td>";
                            echo "<td>{$surat_jalan}</td>"; // TAMPILAN DATA BARU
                            echo "<td><strong style='color:#ef4444'>- {$row['jumlah']}</strong></td>";
                            echo "<td><span class='badge-success'>Selesai</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        // Colspan diubah jadi 6 karena kolom nambah 1
                        echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#64748b;'>Tidak ada data transaksi keluar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
    <div class="toast-overlay" id="successToast">
        <div class="toast-box">
            <div class="toast-icon">✓</div>
            <h3>Pengeluaran Berhasil!</h3>
            <p>Data barang keluar telah berhasil dicatat dan stok gudang otomatis berkurang.</p>
            <button onclick="tutupToast()" class="btn-toast">Oke, Mengerti</button>
        </div>
    </div>
    <script>
        function tutupToast() {
            document.getElementById('successToast').style.display = 'none';
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
    <?php endif; ?>

    <script>
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const timeString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} pukul ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')} WIB`;
            document.getElementById('realtime-clock').innerText = timeString;
        }
        setInterval(updateClock, 1000); updateClock(); 
    </script>
</body>
</html>