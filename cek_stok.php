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

// --- LOGIKA PENCARIAN, FILTER & PAGINATION ---
$search = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

// 1. Konfigurasi Pagination
$limit = 10; // Jumlah data per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_aktif - 1) * $limit;

// 2. Susun Kondisi SQL
$kondisi_sql = "";
if ($search != '') {
    $kondisi_sql .= " AND (nama_barang LIKE '%$search%' OR kode_sku LIKE '%$search%')";
}
if ($kategori_filter != '') {
    $kondisi_sql .= " AND kategori = '$kategori_filter'";
}

// 3. Hitung Total Data
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM data_barang WHERE 1=1" . $kondisi_sql);
$row_total = mysqli_fetch_assoc($q_total);
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $limit);

// 4. Query Utama dengan LIMIT dan OFFSET
$query_sql = "SELECT * FROM data_barang WHERE 1=1" . $kondisi_sql . " ORDER BY nama_barang ASC LIMIT $limit OFFSET $offset";
$q_barang = mysqli_query($koneksi, $query_sql);

// Mengambil daftar kategori unik dari database
$q_kategori_list = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM data_barang WHERE kategori != '' ORDER BY kategori ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Cek Stok Gudang - PT Citra Buana Delapan</title>
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
        
        .content-area { padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

        /* --- SEARCH BAR --- */
        .search-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-form { display: flex; gap: 10px; width: 100%; max-width: 600px; }
        .search-input { flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; }
        .search-select { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white; color: #334155; cursor: pointer; }
        .btn-search { padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }

        /* --- TABLE STYLES --- */
        .stock-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .stock-table th { padding: 15px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; }
        .stock-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        
        .item-info strong { display: block; font-size: 14px; color: #0f172a; margin-bottom: 3px; }
        .item-info span { font-size: 12px; color: #64748b; }

        /* --- BADGE STATUS --- */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge.tersedia { background: #dcfce7; color: #16a34a; }
        .badge.menipis { background: #fef08a; color: #ca8a04; }
        .badge.habis { background: #fee2e2; color: #dc2626; }

        /* --- CSS PAGINATION (Sama seperti Admin) --- */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; }
        .pagination-info { font-size: 13px; color: #64748b; }
        .pagination { display: flex; list-style: none; gap: 5px; }
        .page-link { display: inline-block; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #334155; text-decoration: none; transition: 0.2s; background: white; font-weight: 600; }
        .page-link:hover { background: #f8fafc; border-color: #cbd5e1; }
        .page-link.active { background: #0ea5e9; color: white; border-color: #0ea5e9; }

        /* --- MEDIA QUERY HP --- */
        @media (max-width: 768px) {
            body { flex-direction: column; overflow-y: auto; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 15px; }
            .user-profile { align-self: flex-end; }
            .search-form { flex-direction: column; max-width: 100%; }
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
            <li><a href="cek_stok.php" class="active">Cek Stok Gudang</a></li>
            <li><a href="input_barang.php">Input Barang Masuk/Keluar</a></li>
            <li><a href="staff_pengaturan.php">Pengaturan Akun</a></li>
        </ul>
        <div style="margin-top: auto; padding: 20px;">
            <a href="logout.php" style="color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold;">Keluar Aplikasi</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h3>Cek Stok Gudang (Read-Only)</h3>
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
                
                <div class="search-bar">
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="cari" class="search-input" placeholder="Cari nama barang atau SKU..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
                        
                        <select name="kategori" class="search-select">
                            <option value="">Semua Kategori</option>
                            <?php
                            if($q_kategori_list && mysqli_num_rows($q_kategori_list) > 0) {
                                while($kat = mysqli_fetch_assoc($q_kategori_list)) {
                                    $selected = ($kategori_filter == $kat['kategori']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($kat['kategori']) . "' $selected>" . htmlspecialchars($kat['kategori']) . "</option>";
                                }
                            }
                            ?>
                        </select>

                        <button type="submit" class="btn-search">Cari</button>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Informasi Barang</th>
                                <th>Kategori</th>
                                <th>Lokasi Rak</th>
                                <th>Stok Tersedia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($q_barang && mysqli_num_rows($q_barang) > 0) {
                                while($row = mysqli_fetch_assoc($q_barang)) {
                                    // Logika Badge Status
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
                                                <strong>" . htmlspecialchars($row['nama_barang']) . "</strong>
                                                <span>SKU: " . htmlspecialchars($row['kode_sku']) . "</span>
                                            </div>
                                          </td>";
                                    echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['lokasi_rak']) . "</td>";
                                    echo "<td><strong style='font-size: 15px; color: #0f172a;'>" . $stok . "</strong> " . htmlspecialchars($row['satuan']) . "</td>";
                                    echo "<td>" . $status . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; color: #94a3b8; padding: 40px 0;'>Tidak ada data barang yang ditemukan.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_halaman > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Menampilkan data ke-<?= ($total_data > 0) ? ($offset + 1) : 0 ?> sampai <?= min($offset + $limit, $total_data) ?> dari total <?= $total_data ?> data
                    </div>
                    <ul class="pagination">
                        <?php 
                        // Mengamankan URL biar fitur filter tetep jalan walau pindah halaman
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
    </div>

</body>
</html>