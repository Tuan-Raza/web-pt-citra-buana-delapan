<?php
session_start();

// Cek apakah user sudah login (Dipindah ke paling atas agar tidak memicu error undefined session index)
if (!isset($_SESSION["login"])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// --- MENGAMBIL DATA USER & FOTO PROFIL UNTUK TOPBAR ---
$id_user_login = $_SESSION['id_user'];
// UPDATE: Menggunakan $koneksi
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

// Bikin Inisial (Jaga-jaga kalau belum upload foto)
$inisial = 'U';
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

// --- LOGIKA MENGAMBIL DATA DARI DATABASE ---
// 1. Menghitung Total Variasi Barang
$q_total_barang = mysqli_query($koneksi, "SELECT COUNT(id_barang) as total FROM data_barang");
$r_total_barang = mysqli_fetch_assoc($q_total_barang);
$total_barang = $r_total_barang['total'] ? $r_total_barang['total'] : 0;

// 2. Menghitung Total Mutasi Masuk & Keluar
$q_masuk = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM barang_masuk");
$total_masuk = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM barang_keluar");
$total_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

// --- LOGIKA TOGGLE PERINGATAN STOK MENIPIS ---
$is_stok_alert_active = 0;
$count_menipis = 0;

// Cek aman apakah tabel pengaturan sudah ada di DB
$cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'pengaturan_perusahaan'");
if(mysqli_num_rows($cek_tabel) > 0) {
    $q_pengaturan = mysqli_query($koneksi, "SELECT stok_menipis_aktif FROM pengaturan_perusahaan WHERE id=1");
    if($q_pengaturan && mysqli_num_rows($q_pengaturan) > 0) {
        $is_stok_alert_active = mysqli_fetch_assoc($q_pengaturan)['stok_menipis_aktif'];
    }
}

// Kalau fitur dihidupkan (Aktif = 1), cari ada berapa barang yang stoknya <= 20
if ($is_stok_alert_active == 1) {
    $q_menipis = mysqli_query($koneksi, "SELECT COUNT(id_barang) as total_menipis FROM data_barang WHERE stok_tersedia <= 20");
    $r_menipis = mysqli_fetch_assoc($q_menipis);
    $count_menipis = $r_menipis['total_menipis'] ? $r_menipis['total_menipis'] : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PT Citra Buana Delapan</title>
    <style>
        /* CSS SAMA SEPERTI SEBELUMNYA */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f4f7fa; color: #333; overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { width: 260px; background-color: #172134; color: white; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header h3 { font-size: 18px; color: #3b82f6; letter-spacing: 1px;}
        .sidebar-header p { font-size: 12px; color: #64748b; margin-top: 5px;}
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: flex; align-items: center; padding: 14px 25px; color: #94a3b8; text-decoration: none; font-size: 14.5px; transition: all 0.3s ease; }
        .menu a:hover, .menu a.active { background-color: rgba(59, 130, 246, 0.1); color: white; border-left: 4px solid #3b82f6; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 20px 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0; }
        .topbar-left { display: flex; align-items: baseline; gap: 15px; }
        .topbar-left h2 { font-size: 22px; color: #0f172a; }
        .topbar-left span { font-size: 13px; color: #64748b; }
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; color: #0f172a; }
        .user-info span { font-size: 12px; color: #94a3b8; }
        .avatar { width: 40px; height: 40px; background-color: #1d4ed8; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }

        /* --- CARDS & TABLES --- */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .stat-card h4 { font-size: 14px; color: #64748b; font-weight: 500; margin-bottom: 10px; }
        .stat-card h2 { font-size: 32px; color: #0f172a; margin-bottom: 5px; }
        .stat-card p { font-size: 12px; }
        .text-green { color: #10b981; }
        .text-red { color: #ef4444; }

        .table-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header h3 { font-size: 16px; color: #0f172a; }
        .btn-blue { background-color: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 15px; font-size: 11px; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; font-weight: 600; }
        td { padding: 15px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .empty-state { text-align: center; padding: 30px; color: #94a3b8; font-size: 13px; }

        /* FORMAT ITEM INFO VERTIKAL (Mencegah teks lari ke samping) */
        .item-info { display: flex; flex-direction: column; gap: 2px; }
        .item-info strong { font-size: 14px; color: #0f172a; font-weight: 700; }
        .item-info span { font-size: 12px; color: #64748b; font-weight: 400; }

        /* BADGE STATUS */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge.tersedia { background: #dcfce7; color: #16a34a; }
        .badge.menipis { background: #fef08a; color: #ca8a04; }
        .badge.habis { background: #fee2e2; color: #dc2626; }

        /* --- WARNING BANNER STOK MENIPIS --- */
        .warning-banner { background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1); }
        .warning-text { display: flex; align-items: center; gap: 10px; color: #b91c1c; font-size: 14px; font-weight: 600; }
        .warning-icon { background: #ef4444; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .btn-warning { background-color: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-warning:hover { background-color: #dc2626; }
        
        /* --- RESTRIKSI DEVICE (HANYA PC/LAPTOP) --- */
        @media (max-width: 1024px) {
            body {
                display: none !important;
            }
        }
    </style>
    <script>
        // Memunculkan alert jika dibuka di device kecil (HP/Tablet) dan balik ke login
        if (window.innerWidth <= 1024) {
            alert("Akses Ditolak: Dashboard Admin PT Citra Buana 8 hanya dapat diakses melalui Komputer atau Laptop.");
            window.location.href = "login.php";
        }
    </script>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>PT Citra Buana 8</h3>
            <p>Inventory System v3.5</p>
        </div>
        <ul class="menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="data_barang.php">Data Barang</a></li>
            <li><a href="barang_masuk.php">Barang Masuk</a></li>
            <li><a href="barang_keluar.php">Barang Keluar</a></li>
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
                <h2>Ringkasan Sistem</h2>
                <span id="realtime-clock">Memuat waktu...</span>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <strong><?= htmlspecialchars($me['nama_lengkap'] ?? 'Admin Gudang') ?></strong>
                    <span><?= ucfirst($me['role'] ?? 'Admin') ?> Gudang</span>
                </div>
                <div class="avatar" style="overflow: hidden;"><?= $avatar_html ?></div>
            </div>
        </div>

        <?php if($is_stok_alert_active == 1 && $count_menipis > 0): ?>
        <div class="warning-banner">
            <div class="warning-text">
                <div class="warning-icon">!</div>
                <span>PERINGATAN STOK MENIPIS! Terdapat <?= $count_menipis ?> barang dengan stok di bawah ambang batas (<= 20). Segera lakukan restock!</span>
            </div>
            <a href="data_barang.php" class="btn-warning">Cek Data Barang</a>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Barang Kategori</h4>
                <h2><?= $total_barang ?></h2>
                <p class="text-green">Total variasi barang saat ini</p>
            </div>
            <div class="stat-card">
                <h4>Mutasi Masuk Gudang</h4>
                <h2><?= $total_masuk ?></h2>
                <p class="text-green">Total unit masuk</p>
            </div>
            <div class="stat-card">
                <h4>Mutasi Keluar Gudang</h4>
                <h2><?= $total_keluar ?></h2>
                <p class="text-red">Total unit keluar</p>
            </div>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Log Aktivitas Staff (Read-Only)</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Staff / Keterangan</th>
                        <th>Barang</th>
                        <th>Tipe Mutasi</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // MENGAMBIL DATA DARI TRANSAKSI MASUK & KELUAR STAFF
                    $q_log = mysqli_query($koneksi, "
                        SELECT 'Masuk' as tipe, bm.created_at as waktu, bm.supplier as info, bm.jumlah, db.nama_barang 
                        FROM barang_masuk bm LEFT JOIN data_barang db ON bm.id_barang = db.id_barang
                        UNION ALL
                        SELECT 'Keluar' as tipe, bk.created_at as waktu, bk.tujuan as info, bk.jumlah, db.nama_barang 
                        FROM barang_keluar bk LEFT JOIN data_barang db ON bk.id_barang = db.id_barang
                        ORDER BY waktu DESC LIMIT 5
                    ");

                    if($q_log && mysqli_num_rows($q_log) > 0) {
                        while($log = mysqli_fetch_assoc($q_log)) {
                            $warna_tipe = $log['tipe'] == 'Masuk' ? '#10b981' : '#ef4444';
                            $nama_b = $log['nama_barang'] ?? 'Unknown';
                            
                            echo "<tr>";
                            echo "<td>" . date('d M Y, H:i', strtotime($log['waktu'])) . "</td>";
                            echo "<td>" . htmlspecialchars($log['info']) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($nama_b) . "</strong></td>";
                            echo "<td><span style='color: {$warna_tipe}; font-weight: 600;'>" . $log['tipe'] . "</span></td>";
                            echo "<td>" . $log['jumlah'] . " Unit</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='empty-state'>Belum ada log aktivitas dari Staff.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Status Inventori Terbaru</h3>
                <a href="data_barang.php" class="btn-blue">Lihat Semua Data &rarr;</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang & SKU</th>
                        <th>Kategori</th>
                        <th>Stok Tersedia</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Menampilkan 5 barang terbaru yang ditambahkan ke sistem
                    $q_terbaru = mysqli_query($koneksi, "SELECT * FROM data_barang ORDER BY id_barang DESC LIMIT 5");
                    if(mysqli_num_rows($q_terbaru) > 0) {
                        while($row = mysqli_fetch_assoc($q_terbaru)){
                            // Logika untuk menampilkan badge status
                            $stok = $row['stok_tersedia'];
                            if($stok > 20) {
                                $status = "<span class='badge tersedia'>Tersedia</span>";
                            } elseif($stok > 0 && $stok <= 20) {
                                $status = "<span class='badge menipis'>Stok Menipis</span>";
                            } else {
                                $status = "<span class='badge habis'>Habis</span>";
                            }

                            echo "<tr>";
                            echo "<td>
                                    <div class='item-info'>
                                        <strong>{$row['nama_barang']}</strong>
                                        <span>SKU: {$row['kode_sku']}</span>
                                    </div>
                                  </td>";
                            echo "<td>{$row['kategori']}</td>";
                            echo "<td><strong>{$stok}</strong> {$row['satuan']}</td>";
                            echo "<td>{$status}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='empty-state'>Belum ada data barang.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

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