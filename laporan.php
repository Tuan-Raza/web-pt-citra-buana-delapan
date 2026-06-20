<?php
session_start();
include 'koneksi.php';

// --- AUTO-SETUP AMAN ANTI ERROR DUPLICATE ---
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

// Cek Login
if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }

// --- AMBIL PARAMETER FILTER ---
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'Keseluruhan';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$search = isset($_GET['cari']) ? $_GET['cari'] : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua Kategori';

// --- LOGIKA KONDISI TANGGAL ---
$filter_tgl_masuk = "1=1";
$filter_tgl_keluar = "1=1";

if ($jenis_laporan == 'Harian') {
    $filter_tgl_masuk = "DATE(tanggal_masuk) = CURDATE()";
    $filter_tgl_keluar = "DATE(tanggal_keluar) = CURDATE()";
} elseif ($jenis_laporan == 'Mingguan') {
    $filter_tgl_masuk = "YEARWEEK(tanggal_masuk, 1) = YEARWEEK(CURDATE(), 1)";
    $filter_tgl_keluar = "YEARWEEK(tanggal_keluar, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($jenis_laporan == 'Bulanan') {
    $filter_tgl_masuk = "MONTH(tanggal_masuk) = MONTH(CURDATE()) AND YEAR(tanggal_masuk) = YEAR(CURDATE())";
    $filter_tgl_keluar = "MONTH(tanggal_keluar) = MONTH(CURDATE()) AND YEAR(tanggal_keluar) = YEAR(CURDATE())";
} elseif ($jenis_laporan == 'Tahunan') {
    $filter_tgl_masuk = "YEAR(tanggal_masuk) = YEAR(CURDATE())";
    $filter_tgl_keluar = "YEAR(tanggal_keluar) = YEAR(CURDATE())";
} elseif ($jenis_laporan == 'Custom') {
    $filter_tgl_masuk = "DATE(tanggal_masuk) BETWEEN '$tgl_mulai' AND '$tgl_akhir'";
    $filter_tgl_keluar = "DATE(tanggal_keluar) BETWEEN '$tgl_mulai' AND '$tgl_akhir'";
}

// --- QUERY TOTAL KESELURUHAN BERDASARKAN FILTER ---
$q_in = mysqli_query($koneksi, "SELECT SUM(jumlah) as total_in FROM barang_masuk WHERE $filter_tgl_masuk");
$masuk = mysqli_fetch_assoc($q_in)['total_in'] ?? 0;

$q_out = mysqli_query($koneksi, "SELECT SUM(jumlah) as total_out FROM barang_keluar WHERE $filter_tgl_keluar");
$keluar = mysqli_fetch_assoc($q_out)['total_out'] ?? 0;

// Nilai aset tetap real-time dari tabel data_barang
$q_aset = mysqli_query($koneksi, "SELECT SUM(stok_tersedia * harga_satuan) as total_aset FROM data_barang");
$aset = mysqli_fetch_assoc($q_aset)['total_aset'] ?? 0;

// --- QUERY TABEL & GRAFIK ---
$sql_lap = "SELECT b.id_barang, b.kode_sku, b.nama_barang, b.kategori, b.stok_tersedia, b.harga_satuan, b.satuan,
            COALESCE((SELECT SUM(jumlah) FROM barang_masuk WHERE id_barang = b.id_barang AND $filter_tgl_masuk), 0) as jml_masuk,
            COALESCE((SELECT SUM(jumlah) FROM barang_keluar WHERE id_barang = b.id_barang AND $filter_tgl_keluar), 0) as jml_keluar
            FROM data_barang b WHERE 1=1";

if ($search != '') { $sql_lap .= " AND (b.nama_barang LIKE '%$search%' OR b.kode_sku LIKE '%$search%')"; }
if ($kategori_filter != 'Semua Kategori') { $sql_lap .= " AND b.kategori = '$kategori_filter'"; }
$sql_lap .= " ORDER BY b.nama_barang ASC";

$query_laporan = mysqli_query($koneksi, $sql_lap);

// Siapkan Array untuk Chart.js
$chart_labels = [];
$chart_masuk = [];
$chart_keluar = [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Mutasi Stok - PT Citra Buana Delapan</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f1f5f9; color: #1e293b; overflow: hidden; }

        /* SIDEBAR TETAP ORIGINAL DARI FILE LAMA LU */
        .sidebar { width: 260px; background-color: #172134; color: white; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header h3 { font-size: 18px; color: #3b82f6; letter-spacing: 1px;}
        .sidebar-header p { font-size: 12px; color: #64748b; margin-top: 5px;}
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: flex; align-items: center; padding: 14px 25px; color: #94a3b8; text-decoration: none; font-size: 14.5px; transition: all 0.3s ease; }
        .menu a:hover, .menu a.active { background-color: rgba(59, 130, 246, 0.1); color: white; border-left: 4px solid #3b82f6; }

        /* MAIN CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .topbar-left { display: flex; align-items: baseline; gap: 15px; }
        .topbar-left h2 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .topbar-left span { font-size: 13px; color: #64748b; font-weight: 500;}
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; color: #0f172a; }
        .user-info span { font-size: 12px; color: #94a3b8; }
        .avatar { width: 42px; height: 42px; background-color: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; box-shadow: 0 4px 6px rgba(37,99,235,0.2); overflow: hidden;}

        /* CSS UNTUK MERAPIKAN KOTAK KONTEN (TIDAK ACAK-ACAKAN) */
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9;}
        
        /* CSS UNTUK FORM FILTER RAPI */
        .filter-grid { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: #334155; }
        .form-control { padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; background: #f8fafc; color: #0f172a; transition: border 0.2s;}
        .form-control:focus { border-color: #3b82f6; background: white;}
        .btn-blue { background-color: #3b82f6; color: white; border: none; padding: 0 20px; height: 40px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center;}
        .btn-blue:hover { background-color: #2563eb; }
        
        /* EXPORT BUTTONS RAPI */
        .export-btns { display: flex; gap: 10px; }
        .btn-export { padding: 8px 15px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; color: white; transition: 0.2s; }
        .btn-export:hover { transform: translateY(-2px); }
        .btn-pdf { background-color: #ef4444; }
        .btn-png { background-color: #f59e0b; }
        .btn-sheet { background-color: #10b981; }

        /* SUMMARY CARDS RAPI */
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;}
        .sum-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 4px solid #3b82f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);}
        .sum-card:nth-child(2) { border-top-color: #10b981; }
        .sum-card:nth-child(3) { border-top-color: #ef4444; }
        .sum-card h4 { font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 8px;}
        .sum-card h2 { font-size: 28px; font-weight: 700; color: #0f172a;}

        /* TABLE STYLING RAPI */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; background: #f8fafc;}
        td { padding: 15px; font-size: 14px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
        tbody tr.data-row { transition: all 0.2s; cursor: pointer; }
        tbody tr.data-row:hover { background-color: #f8fafc; }
        
        .text-green { color: #10b981; font-weight: 600;}
        .text-red { color: #ef4444; font-weight: 600;}
        
        /* BARIS TOTAL BAWAH RAPI & KONTRAST */
        .total-row { background-color: #0f172a !important; }
        .total-row td { color: #ffffff !important; font-size: 14px; padding: 15px; border: none;}
        .total-green { color: #34d399 !important; }
        .total-red { color: #f87171 !important; }

        .custom-date { display: <?= ($jenis_laporan == 'Custom') ? 'flex' : 'none' ?>; gap: 10px; align-items: flex-end; }
        
        #kop-cetak { display: none; text-align: center; padding: 20px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px;}
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
            <li><a href="barang_keluar.php">Barang Keluar</a></li>
            <li><a href="laporan.php" class="active">Laporan</a></li>
            <li><a href="pengaturan.php">Pengaturan</a></li>
        </ul>
        <ul class="menu" style="flex: 0; border-top: 1px solid rgba(255,255,255,0.05);">
            <li><a href="logout.php" style="color: #ef4444;">Keluar</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Laporan & Mutasi Stok</h2>
                <span id="realtime-clock">Memuat waktu...</span>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <strong><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span><?= ucfirst($me['role']) ?> Gudang</span>
                </div>
                <div class="avatar"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="content-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; color: #0f172a; font-weight: 700;">Filter Parameter Laporan</h3>
                <div class="export-btns">
                    <button onclick="downloadPDF()" class="btn-export btn-pdf" id="btnPdf">PDF</button>
                    <button onclick="downloadPNG()" class="btn-export btn-png">PNG</button>
                    <button onclick="downloadCSV('Laporan_Keseluruhan.csv')" class="btn-export btn-sheet">Sheet</button>
                </div>
            </div>

            <form method="GET" action="laporan.php" class="filter-grid" id="filterForm">
                <div class="form-group">
                    <label>Jenis Laporan</label>
                    <select name="jenis_laporan" class="form-control" id="jenis_laporan" onchange="toggleCustomDate()">
                        <option value="Keseluruhan" <?= ($jenis_laporan == 'Keseluruhan') ? 'selected' : '' ?>>Mutasi Stok Keseluruhan</option>
                        <option value="Harian" <?= ($jenis_laporan == 'Harian') ? 'selected' : '' ?>>Laporan Harian</option>
                        <option value="Mingguan" <?= ($jenis_laporan == 'Mingguan') ? 'selected' : '' ?>>Laporan Mingguan</option>
                        <option value="Bulanan" <?= ($jenis_laporan == 'Bulanan') ? 'selected' : '' ?>>Laporan Bulanan</option>
                        <option value="Tahunan" <?= ($jenis_laporan == 'Tahunan') ? 'selected' : '' ?>>Laporan Tahunan</option>
                        <option value="Custom" <?= ($jenis_laporan == 'Custom') ? 'selected' : '' ?>>Riwayat Laporan Sebelumnya</option>
                    </select>
                </div>

                <div class="custom-date" id="customDateDiv">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                    </div>
                    <div style="padding-bottom: 10px; font-weight: bold; color: #94a3b8;">-</div>
                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Cari Barang / SKU</label>
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau SKU..." value="<?= $search ?>">
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" class="form-control">
                        <option value="Semua Kategori">Semua Kategori</option>
                        <?php
                        $q_kat = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM data_barang WHERE kategori != ''");
                        while($k = mysqli_fetch_assoc($q_kat)) {
                            $sel = ($kategori_filter == $k['kategori']) ? 'selected' : '';
                            echo "<option value='{$k['kategori']}' $sel>{$k['kategori']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn-blue">Tampilkan Laporan</button>
                </div>
            </form>
            <p style="font-size: 13px; color: #64748b; font-style: italic; margin-top: 15px;">*Klik baris tabel di bawah untuk melihat rincian riwayat & download per barang.</p>
        </div>

        <div id="area-cetak">
            <div id="kop-cetak">
                <h1 style="color: #0f172a; font-size: 24px; margin-bottom: 5px;">PT Citra Buana Delapan</h1>
                <p style="color: #64748b; font-size: 14px;">Laporan: <?= $jenis_laporan ?> | Filter: <?= $kategori_filter ?></p>
            </div>

            <div class="summary-grid">
                <div class="sum-card">
                    <h4>Nilai Aset (Stok Akhir)</h4>
                    <h2>Rp <?= number_format($aset, 0, ',', '.') ?></h2>
                </div>
                <div class="sum-card">
                    <h4>Total Item Masuk</h4>
                    <h2 class="text-green">+ <?= number_format($masuk, 0, ',', '.') ?> Pcs</h2>
                </div>
                <div class="sum-card">
                    <h4>Total Item Keluar</h4>
                    <h2 class="text-red">- <?= number_format($keluar, 0, ',', '.') ?> Pcs</h2>
                </div>
            </div>

            <div class="content-box" style="padding-bottom: 30px;">
                <h3 style="margin-bottom: 20px; color: #0f172a; font-size: 18px; font-weight: 700;">Statistika Pergerakan Stok</h3>
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="mutasiChart"></canvas>
                </div>
            </div>

            <div class="content-box" style="padding: 0; overflow: hidden;">
                <table id="tabelLaporan">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Stok Akhir</th>
                            <th>Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(mysqli_num_rows($query_laporan) > 0) {
                            while($row = mysqli_fetch_assoc($query_laporan)){
                                $nilai = $row['stok_tersedia'] * $row['harga_satuan'];
                                
                                $chart_labels[] = $row['nama_barang'];
                                $chart_masuk[] = $row['jml_masuk'];
                                $chart_keluar[] = $row['jml_keluar'];

                                echo "<tr class='data-row' onclick=\"window.location='riwayat_barang.php?id={$row['id_barang']}'\">";
                                echo "<td>{$row['kode_sku']}</td>";
                                echo "<td><strong>{$row['nama_barang']}</strong></td>";
                                echo "<td>{$row['kategori']}</td>";
                                echo "<td class='text-green'>+ {$row['jml_masuk']}</td>";
                                echo "<td class='text-red'>- {$row['jml_keluar']}</td>";
                                echo "<td><strong>{$row['stok_tersedia']}</strong></td>";
                                echo "<td>Rp " . number_format($nilai, 0, ',', '.') . "</td>";
                                echo "</tr>";
                            }
                            // BARIS TOTAL
                            echo "<tr class='total-row'>";
                            echo "<td colspan='3' style='text-align:center; font-weight:700; letter-spacing: 1px;'>TOTAL KESELURUHAN</td>";
                            echo "<td class='total-green' style='font-weight:700;'>+ {$masuk}</td>";
                            echo "<td class='total-red' style='font-weight:700;'>- {$keluar}</td>";
                            echo "<td></td>";
                            echo "<td style='font-weight:700;'>Rp " . number_format($aset, 0, ',', '.') . "</td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding: 40px;'>Data tidak ditemukan. Coba ubah filter pencarian.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            document.getElementById('realtime-clock').innerText = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} pukul ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')} WIB`;
        }
        setInterval(updateClock, 1000); updateClock();

        function toggleCustomDate() {
            var val = document.getElementById('jenis_laporan').value;
            document.getElementById('customDateDiv').style.display = (val === 'Custom') ? 'flex' : 'none';
        }

        const ctx = document.getElementById('mutasiChart').getContext('2d');
        const labels = <?= json_encode($chart_labels) ?>;
        const dataMasuk = <?= json_encode($chart_masuk) ?>;
        const dataKeluar = <?= json_encode($chart_keluar) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: ' Barang Masuk',
                        data: dataMasuk,
                        borderColor: '#10b981', 
                        backgroundColor: 'rgba(16, 185, 129, 0.15)', 
                        borderWidth: 3,
                        tension: 0.5, 
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: ' Barang Keluar',
                        data: dataKeluar,
                        borderColor: '#ef4444', 
                        backgroundColor: 'rgba(239, 68, 68, 0.15)', 
                        borderWidth: 3,
                        tension: 0.5, 
                        fill: true,
                        pointBackgroundColor: '#ef4444',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'top', labels: { font: { size: 13, weight: 'bold' } } } 
                },
                scales: { 
                    y: { beginAtZero: true },
                    x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 11 } } }
                },
                animation: { duration: 1500 }
            }
        });

        function downloadPDF() {
            const btn = document.getElementById('btnPdf');
            btn.innerText = "Memproses...";
            btn.style.opacity = "0.7";
            
            window.scrollTo(0,0);
            document.getElementById('kop-cetak').style.display = 'block'; 
            
            const element = document.getElementById('area-cetak');
            const opt = {
                margin:       [10, 10, 10, 10], 
                filename:     'Laporan_Mutasi_Stok.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                document.getElementById('kop-cetak').style.display = 'none'; 
                btn.innerText = "PDF";
                btn.style.opacity = "1";
            });
        }

        function downloadPNG() {
            window.scrollTo(0,0);
            document.getElementById('kop-cetak').style.display = 'block';
            html2canvas(document.getElementById('area-cetak'), { scale: 2, useCORS: true }).then(canvas => {
                let link = document.createElement('a');
                link.download = 'Laporan_Mutasi_Stok.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                document.getElementById('kop-cetak').style.display = 'none';
            });
        }

        function downloadCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#tabelLaporan tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) 
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(","));        
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>