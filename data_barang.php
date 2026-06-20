<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

// --- AUTO-SETUP AMAN ANTI ERROR DUPLICATE ---
// (Biar pas narik foto profil gak error kalau kolom belum ada di database)
$cek_telp = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'no_telp'");
if($cek_telp && mysqli_num_rows($cek_telp) == 0) { mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20) DEFAULT '' AFTER email"); }

$cek_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
if($cek_foto && mysqli_num_rows($cek_foto) == 0) { mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) DEFAULT '' AFTER no_telp"); }

// --- GLOBAL: AMBIL DATA USER & FOTO PROFIL UNTUK TOPBAR ---
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

// --- LOGIKA FILTER PENCARIAN & PAGINATION (PEMBAGIAN HALAMAN) ---
$search = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

// 1. Konfigurasi Pagination
$limit = 10; // Jumlah maksimal data per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_aktif - 1) * $limit;

// 2. Susun Kondisi Filter SQL
$kondisi_sql = "";
if ($search != '') {
    $kondisi_sql .= " AND (nama_barang LIKE '%$search%' OR kode_sku LIKE '%$search%')";
}
if ($kategori_filter != '' && $kategori_filter != 'Semua Kategori') {
    $kondisi_sql .= " AND kategori = '$kategori_filter'";
}

// 3. Hitung Total Data (Untuk bikin tombol 1, 2, 3)
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM data_barang WHERE 1=1" . $kondisi_sql);
$row_total = mysqli_fetch_assoc($q_total);
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $limit);

// 4. Query Utama untuk Nampilin Data Sesuai Halaman (Pakai LIMIT & OFFSET)
$query_sql = "SELECT * FROM data_barang WHERE 1=1" . $kondisi_sql . " ORDER BY id_barang DESC LIMIT $limit OFFSET $offset";
$query = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Barang - PT Citra Buana Delapan</title>
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

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 20px 30px; position: relative; }
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
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        .search-input { flex: 1; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; }
        .filter-select { padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; width: 200px; outline: none; }
        .btn-blue { background-color: #3b82f6; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 11px; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; font-size: 13px; color: #0f172a; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .item-info strong { display: block; font-size: 14px; margin-bottom: 3px; }
        .item-info span { font-size: 12px; color: #64748b; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge.tersedia { background: #dcfce7; color: #16a34a; }
        .badge.menipis { background: #fef08a; color: #ca8a04; }
        .badge.habis { background: #fee2e2; color: #dc2626; }

        .action-btns { display: flex; gap: 8px; }
        .btn-action { padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; cursor: pointer; }
        .btn-edit { color: #3b82f6; }
        .btn-edit:hover { background: #eff6ff; }
        .btn-delete { color: #ef4444; }
        .btn-delete:hover { background: #fef2f2; }

        /* --- TAMBAHAN CSS UNTUK PAGINATION --- */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; }
        .pagination-info { font-size: 13px; color: #64748b; }
        .pagination { display: flex; list-style: none; gap: 5px; }
        .page-link { display: inline-block; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #334155; text-decoration: none; transition: 0.2s; background: white; font-weight: 600; }
        .page-link:hover { background: #f8fafc; border-color: #cbd5e1; }
        .page-link.active { background: #3b82f6; color: white; border-color: #3b82f6; }

        /* MODAL KONFIRMASI HAPUS KEREN */
        .hapus-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.5); backdrop-filter: blur(2px); z-index: 100; justify-content: center; align-items: center; }
        .hapus-box { background: white; padding: 30px; border-radius: 12px; width: 350px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: popIn 0.3s ease-out; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .hapus-icon { width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px auto; }
        .hapus-box h3 { color: #0f172a; margin-bottom: 10px; font-size: 18px; }
        .hapus-box p { color: #64748b; font-size: 13px; margin-bottom: 25px; line-height: 1.5; }
        .hapus-btns { display: flex; justify-content: center; gap: 10px; }
        .hapus-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; flex: 1; }
        .btn-batal { background: #f1f5f9; color: #475569; }
        .btn-batal:hover { background: #e2e8f0; }
        .btn-yakin { background: #ef4444; color: white; }
        .btn-yakin:hover { background: #dc2626; }
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
            <li><a href="data_barang.php" class="active">Data Barang</a></li>
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
                <h2>Manajemen Data Barang</h2>
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
            <form method="GET" action="data_barang.php" class="filter-bar">
                <input type="text" name="cari" class="search-input" placeholder="Cari nama barang atau SKU..." value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
                <select name="kategori" class="filter-select" onchange="this.form.submit()">
                    <option value="Semua Kategori" <?= ($kategori_filter == 'Semua Kategori') ? 'selected' : '' ?>>Semua Kategori</option>
                    <?php
                    $q_kat = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM data_barang WHERE kategori != ''");
                    while($k = mysqli_fetch_assoc($q_kat)) {
                        $selected = ($kategori_filter == $k['kategori']) ? 'selected' : '';
                        echo "<option value='{$k['kategori']}' $selected>{$k['kategori']}</option>";
                    }
                    ?>
                </select>
                <a href="tambah_barang.php" class="btn-blue">+ Tambah Barang</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Informasi Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi Rak</th>
                        <th>Harga Satuan</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)){
                            $stok = $row['stok_tersedia'];
                            if($stok > 20) {
                                $status = "<span class='badge tersedia'>Tersedia</span>";
                            } elseif($stok > 0 && $stok <= 20) {
                                $status = "<span class='badge menipis'>Stok Menipis</span>";
                            } else {
                                $status = "<span class='badge habis'>Habis</span>";
                            }

                            echo "<tr>";
                            echo "<td class='item-info'>
                                    <strong>{$row['nama_barang']}</strong>
                                    <span>SKU: {$row['kode_sku']}</span>
                                  </td>";
                            echo "<td>{$row['kategori']}</td>";
                            echo "<td>{$row['lokasi_rak']}</td>";
                            echo "<td>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>";
                            echo "<td><strong>{$stok}</strong> {$row['satuan']}</td>";
                            echo "<td>{$status}</td>";
                            echo "<td class='action-btns'>
                                    <a href='edit_barang.php?id={$row['id_barang']}' class='btn-action btn-edit'>Edit</a>
                                    <button type='button' class='btn-action btn-delete' onclick='bukaModalHapus({$row['id_barang']})'>Hapus</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#64748b;'>Belum ada data barang sesuai filter/pencarian.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php if($total_halaman > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Menampilkan data ke-<?= ($total_data > 0) ? ($offset + 1) : 0 ?> sampai <?= min($offset + $limit, $total_data) ?> dari total <?= $total_data ?> data
                </div>
                <ul class="pagination">
                    <?php 
                    // Render Tombol Halaman & Menyimpan Parameter Filter/Cari
                    $url_cari = urlencode($search);
                    $url_kat = urlencode($kategori_filter);
                    
                    for($i = 1; $i <= $total_halaman; $i++): 
                        $active_class = ($i == $halaman_aktif) ? 'active' : '';
                    ?>
                        <li><a href="?halaman=<?= $i ?>&cari=<?= $url_cari ?>&kategori=<?= $url_kat ?>" class="page-link <?= $active_class ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div id="modalHapus" class="hapus-overlay">
        <div class="hapus-box">
            <div class="hapus-icon">!</div>
            <h3>Hapus Data Barang?</h3>
            <p>Data yang dihapus akan hilang dari database dan tidak bisa dikembalikan.</p>
            <div class="hapus-btns">
                <button onclick="tutupModalHapus()" class="hapus-btn btn-batal">Batal</button>
                <a href="#" id="linkHapusFinal" class="hapus-btn btn-yakin">Ya, Hapus</a>
            </div>
        </div>
    </div>

    <script>
        // Logika Jam Real-time
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const timeString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} pukul ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')} WIB`;
            document.getElementById('realtime-clock').innerText = timeString;
        }
        setInterval(updateClock, 1000); updateClock(); 

        // Logika Modal Hapus Keren
        function bukaModalHapus(idBarang) {
            document.getElementById('modalHapus').style.display = 'flex';
            document.getElementById('linkHapusFinal').href = 'hapus_barang.php?id=' + idBarang;
        }
        function tutupModalHapus() {
            document.getElementById('modalHapus').style.display = 'none';
        }
    </script>
</body>
</html>