<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: laporan.php"); exit; }

$id_barang = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil Identitas Barang
$q_brg = mysqli_query($koneksi, "SELECT * FROM data_barang WHERE id_barang = '$id_barang'");
$brg = mysqli_fetch_assoc($q_brg);

if (!$brg) { echo "Data barang tidak ditemukan!"; exit; }

// --- LOGIKA MENGHITUNG STOK AWAL ASLI ---
// (Stok saat ini dikurangi total masuk ditambah total keluar = Stok Awal saat barang didaftarkan)
$q_in = mysqli_query($koneksi, "SELECT SUM(jumlah) as v FROM barang_masuk WHERE id_barang = '$id_barang'");
$tot_in = mysqli_fetch_assoc($q_in)['v'] ?? 0;

$q_out = mysqli_query($koneksi, "SELECT SUM(jumlah) as v FROM barang_keluar WHERE id_barang = '$id_barang'");
$tot_out = mysqli_fetch_assoc($q_out)['v'] ?? 0;

$stok_awal_sistem = $brg['stok_tersedia'] - $tot_in + $tot_out;

// --- AMBIL RIWAYAT GABUNGAN (MASUK & KELUAR) ---
$sql_history = "
    SELECT tanggal_masuk as tgl, no_surat_jalan as no_dokumen, supplier as keterangan, jumlah as masuk, 0 as keluar, created_at 
    FROM barang_masuk WHERE id_barang = '$id_barang'
    UNION ALL
    SELECT tanggal_keluar as tgl, id_transaksi as no_dokumen, tujuan as keterangan, 0 as masuk, jumlah as keluar, created_at 
    FROM barang_keluar WHERE id_barang = '$id_barang'
    ORDER BY tgl ASC, created_at ASC
";
$query_history = mysqli_query($koneksi, $sql_history);

// Array untuk Grafik Chart.js
$chart_labels = ["Stok Awal"];
$chart_data = [$stok_awal_sistem];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat: <?= $brg['nama_barang'] ?></title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f1f5f9; padding: 30px; color: #333; }
        
        /* CONTAINER UTAMA */
        .wrapper { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
        
        /* ACTION BUTTONS TOP */
        .top-actions { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .btn-back { display: inline-block; background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s;}
        .btn-back:hover { background: #e2e8f0; color: #0f172a;}
        
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; border: none; color: white; transition: 0.2s;}
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15);}
        .btn-pdf { background: #ef4444; }
        .btn-png { background: #f59e0b; }
        .btn-sheet { background: #10b981; }

        /* AREA YANG AKAN DICETAK/DOWNLOAD */
        #area-cetak { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 40px; }
        
        /* HEADER KOP BUKU */
        .kop-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;}
        .kop-left h1 { font-size: 26px; color: #0f172a; margin-bottom: 8px; font-weight: 800;}
        .badge-info { display: inline-block; background: #eff6ff; color: #1d4ed8; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; margin-bottom: 10px; border: 1px solid #bfdbfe;}
        .kop-left p { color: #475569; font-size: 14px; margin-bottom: 4px;}
        .kop-right { text-align: right; }
        .kop-right h2 { font-size: 18px; color: #0f172a;}
        .kop-right p { font-size: 13px; color: #64748b;}
        
        /* GRAFIK SECTION */
        .chart-container { margin-bottom: 40px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;}
        .chart-container h3 { margin-bottom: 15px; font-size: 16px; color: #0f172a; text-align: center;}

        /* TABLE RIWAYAT */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; color: #475569; padding: 15px; font-size: 12px; text-transform: uppercase; text-align: left; border-bottom: 2px solid #cbd5e1;}
        td { padding: 16px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b;}
        
        /* WARNA BARIS KHUSUS STOK AWAL */
        .row-awal { background-color: #fdfde8; }
        .row-awal td { font-weight: 600; color: #ca8a04; }

        .masuk-col { color: #10b981; font-weight: bold; }
        .keluar-col { color: #ef4444; font-weight: bold; }
        .sisa-col { background: #f8fafc; font-weight: bold; color: #0f172a; font-size: 15px;}
    </style>
</head>
<body>

    <div class="wrapper">
        
        <div class="top-actions">
            <a href="laporan.php" class="btn-back">&larr; Kembali ke Daftar Laporan</a>
            <div class="btn-group">
                <button onclick="downloadPDF()" class="btn btn-pdf">Download PDF (A4)</button>
                <button onclick="downloadPNG()" class="btn btn-png">Download PNG</button>
                <button onclick="downloadCSV('Riwayat_<?= $brg['kode_sku'] ?>.csv')" class="btn btn-sheet">Download Sheet</button>
            </div>
        </div>

        <div id="area-cetak">
            
            <div class="kop-header">
                <div class="kop-left">
                    <h1>Buku Riwayat Pergerakan Barang</h1>
                    <div class="badge-info">SKU: <?= $brg['kode_sku'] ?></div>
                    <p>Nama Barang : <strong><?= strtoupper($brg['nama_barang']) ?></strong></p>
                    <p>Kategori &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $brg['kategori'] ?></p>
                    <p>Satuan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $brg['satuan'] ?></p>
                </div>
                <div class="kop-right">
                    <h2>PT Citra Buana Delapan</h2>
                    <p>Divisi Logistik & Inventory</p>
                    <p>Dicetak: <?= date('d M Y, H:i') ?> WIB</p>
                </div>
            </div>

            <div class="chart-container">
                <h3>Grafik Sisa Stok Berjalan (<?= $brg['nama_barang'] ?>)</h3>
                <canvas id="stokChart" height="60"></canvas>
            </div>

            <table id="ledgerTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Dokumen / Invoice</th>
                        <th>Keterangan Transaksi</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th class="sisa-col">Sisa Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="row-awal">
                        <td>-</td>
                        <td>-</td>
                        <td><em>Stok Awal Gudang (Saat barang didaftarkan)</em></td>
                        <td>-</td>
                        <td>-</td>
                        <td class="sisa-col"><?= $stok_awal_sistem ?> <?= $brg['satuan'] ?></td>
                    </tr>

                    <?php
                    $sisa_berjalan = $stok_awal_sistem; 
                    
                    if(mysqli_num_rows($query_history) > 0) {
                        while($row = mysqli_fetch_assoc($query_history)) {
                            // Kalkulasi Saldo Berjalan
                            $sisa_berjalan = $sisa_berjalan + $row['masuk'] - $row['keluar'];
                            
                            $tgl = date("d M Y", strtotime($row['tgl']));
                            $masuk_txt = ($row['masuk'] > 0) ? "+ ".$row['masuk'] : "";
                            $keluar_txt = ($row['keluar'] > 0) ? "- ".$row['keluar'] : "";

                            // Push data ke Array JS untuk Grafik
                            $chart_labels[] = $tgl;
                            $chart_data[] = $sisa_berjalan;

                            echo "<tr>";
                            echo "<td>{$tgl}</td>";
                            echo "<td><strong>{$row['no_dokumen']}</strong></td>";
                            echo "<td>{$row['keterangan']}</td>";
                            echo "<td class='masuk-col'>{$masuk_txt}</td>";
                            echo "<td class='keluar-col'>{$keluar_txt}</td>";
                            echo "<td class='sisa-col'>{$sisa_berjalan} {$brg['satuan']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #64748b;'>Belum ada mutasi masuk/keluar untuk barang ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // 1. RENDER GRAFIK STOK BERJALAN (AREA CHART)
        const ctx = document.getElementById('stokChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Sisa Stok Berjalan',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#3b82f6', // Biru terang
                    backgroundColor: 'rgba(59, 130, 246, 0.15)', // Biru transparan untuk area fill
                    borderWidth: 3,
                    tension: 0.3, // Melengkung (Smooth)
                    fill: true,
                    pointBackgroundColor: '#1d4ed8',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true },
                    x: { ticks: { autoSkip: true, maxTicksLimit: 10 } }
                }
            }
        });

        // 2. FUNGSI DOWNLOAD PDF (UKURAN A4 LANDSCAPE)
        function downloadPDF() {
            const element = document.getElementById('area-cetak');
            const opt = {
                margin:       10,
                filename:     'Riwayat_<?= $brg['kode_sku'] ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true },
                // Diatur ke A4 Landscape biar tabel dan grafik tidak terpotong
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            // html2pdf akan otomatis mendownload file, tidak membuka jendela print
            html2pdf().set(opt).from(element).save();
        }

        // 3. FUNGSI DOWNLOAD GAMBAR PNG
        function downloadPNG() {
            html2canvas(document.getElementById('area-cetak'), { scale: 2 }).then(canvas => {
                let link = document.createElement('a');
                link.download = 'Riwayat_<?= $brg['kode_sku'] ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        // 4. FUNGSI DOWNLOAD EXCEL (CSV/SHEET)
        function downloadCSV(filename) {
            let csv = [];
            const rows = document.querySelectorAll("#ledgerTable tr");
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) 
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(","));        
            }

            const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            const downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>