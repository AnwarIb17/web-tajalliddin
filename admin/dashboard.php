<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

// Ambil data ringkasan statistik
$jml_santri = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM santri WHERE status='aktif'"))['total'] ?? 0;
$jml_guru = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='guru'"))['total'] ?? 0;
$jml_bendahara = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='bendahara'"))['total'] ?? 0;
$jml_request = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM request_persetujuan WHERE status_request='pending'"))['total'] ?? 0;

$query_request = mysqli_query($koneksi, "SELECT r.*, u.nama_lengkap AS nama_pengaju FROM request_persetujuan r JOIN users u ON r.id_pengaju = u.id WHERE r.status_request='pending' ORDER BY r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PP. Tajalliddin</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --bg-main: #e8edf2;
            --card-bg: #ffffff;
            --sidebar-bg: #ffffff;
            --text-main: #2d3748;
            --text-muted: #718096;
            --primary: #00b4d8;
            --primary-gradient: linear-gradient(135deg, #00b4d8, #0077b6);
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--bg-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: var(--text-main);
        }

        /* Overlay Gelap di Belakang Sidebar saat Terbuka di HP */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 99;
            display: none; /* Default disembunyikan */
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            transition: transform 0.3s ease-in-out;
            z-index: 100;
            height: 100%;
        }

        .sidebar-brand {
            font-size: 16px;
            font-weight: 800;
            color: #1a365d;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Tombol Tutup Sidebar khusus tampilan HP */
        .close-sidebar-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            display: none; /* Hanya muncul di HP */
        }

        .sidebar-menu {
            list-style: none;
            flex: 1;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            color: var(--text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(0, 180, 216, 0.08);
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background: var(--primary-gradient);
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 180, 216, 0.3);
        }

        .sidebar-footer a {
            color: #e53e3e;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .sidebar-footer a:hover {
            background: rgba(229, 62, 62, 0.08);
        }

        /* Konten Utama */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: var(--bg-main);
        }

        .top-header {
            background: transparent;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toggle-btn {
            background: var(--card-bg);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            font-size: 18px;
            color: var(--text-main);
            transition: transform 0.2s;
        }

        .toggle-btn:hover {
            transform: scale(1.05);
        }

        .top-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: #1a365d;
        }

        .user-profile {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            background: var(--card-bg);
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .dashboard-body {
            padding: 10px 30px 30px 30px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-stat {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .card-stat h3 {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .card-stat .number {
            font-size: 28px;
            font-weight: 800;
            color: #1a365d;
        }

        .table-container {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .table-container h3 {
            font-size: 16px;
            color: #1a365d;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table th, table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }

        table th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
        }

        table td {
            color: var(--text-main);
            font-weight: 500;
        }

        .badge {
            background: #fefcbf;
            color: #b7791f;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 180, 216, 0.2);
        }

        /* Pengaturan Responsif Khusus HP / Layar Kecil */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                transform: translateX(-100%); /* Sembunyikan sidebar di luar layar secara default */
                box-shadow: 5px 0 25px rgba(0,0,0,0.1);
            }
            .sidebar.active {
                transform: translateX(0); /* Munculkan saat aktif */
            }
            .close-sidebar-btn {
                display: block; /* Tampilkan tombol tutup di HP */
            }
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Lapisan Gelap Transparan (Overlay) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span>PP. TAJALLIDDIN</span>
            <button class="close-sidebar-btn" onclick="toggleSidebar()">&times;</button>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="data_santri.php">Kelola Santri</a></li>
            <li><a href="data_guru.php">Pengguna</a></li>
            <li><a href="penugasan_mapel.php">Mapel & Kelas</a></li>
            <li><a href="tahun_ajaran.php">Tahun Ajaran</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../logout.php">Keluar</a>
        </div>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <div class="top-header">
            <div class="header-left">
                <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
                <h2>Dashboard</h2>
            </div>
            <div class="user-profile">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
        </div>

        <div class="dashboard-body">
            <!-- Kartu Statistik -->
            <div class="cards-grid">
                <div class="card-stat">
                    <h3>Total Santri Aktif</h3>
                    <div class="number"><?= $jml_santri; ?></div>
                </div>
                <div class="card-stat">
                    <h3>Total Guru</h3>
                    <div class="number"><?= $jml_guru; ?></div>
                </div>
                <div class="card-stat">
                    <h3>Total Bendahara</h3>
                    <div class="number"><?= $jml_bendahara; ?></div>
                </div>
                <div class="card-stat">
                    <h3>Request Pending</h3>
                    <div class="number"><?= $jml_request; ?></div>
                </div>
            </div>

            <!-- Tabel Request -->
            <div class="table-container">
                <h3>Permintaan (Request) Masuk dari Guru</h3>
                <?php if (mysqli_num_rows($query_request) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Pengaju (Guru)</th>
                                    <th>Jenis Request</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_request)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_pengaju']); ?></td>
                                    <td><?= ucwords(str_replace('_', ' ', $row['jenis_request'])); ?></td>
                                    <td><?= $row['created_at']; ?></td>
                                    <td><span class="badge"><?= ucfirst($row['status_request']); ?></span></td>
                                    <td>
                                        <a href="proses_request.php?id=<?= $row['id']; ?>" class="btn-action">Tinjau</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #718096; font-size: 13px; padding: 10px 0;">Tidak ada permintaan baru yang tertunda saat ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Script JavaScript Interaktif untuk Sidebar & Overlay -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>

</body>
</html>