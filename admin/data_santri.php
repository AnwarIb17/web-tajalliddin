<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

$pesan = '';
$error = '';

// Proses Tambah Santri Manual
if (isset($_POST['tambah_santri'])) {
    try {
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $kelas = intval($_POST['id_kelas']);
        $tgl_masuk = $_POST['tanggal_masuk'];

        $cek_nis = mysqli_query($koneksi, "SELECT * FROM santri WHERE nis = '$nis'");
        if (mysqli_num_rows($cek_nis) > 0) {
            throw new Exception("Nomor Induk Santri (NIS) '$nis' sudah terdaftar di sistem.");
        }

        $query = "INSERT INTO santri (nis, nama_lengkap, id_kelas, tanggal_masuk, status) VALUES ('$nis', '$nama', '$kelas', '$tgl_masuk', 'aktif')";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal menyimpan ke database: " . mysqli_error($koneksi));
        }

        $pesan = "Data santri berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Edit Santri
if (isset($_POST['edit_santri'])) {
    try {
        $id = intval($_POST['id']);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $kelas = intval($_POST['id_kelas']);
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);

        // Cek duplikasi NIS jika NIS diubah
        $cek_nis = mysqli_query($koneksi, "SELECT * FROM santri WHERE nis = '$nis' AND id != $id");
        if (mysqli_num_rows($cek_nis) > 0) {
            throw new Exception("NIS '$nis' sudah digunakan oleh santri lain.");
        }

        $query = "UPDATE santri SET nis = '$nis', nama_lengkap = '$nama', id_kelas = $kelas, status = '$status' WHERE id = $id";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal memperbarui data santri.");
        }

        $pesan = "Data santri berhasil diperbarui!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Hapus Santri
if (isset($_GET['hapus'])) {
    try {
        $id_santri = intval($_GET['hapus']);
        $hapus_query = mysqli_query($koneksi, "DELETE FROM santri WHERE id = $id_santri");
        if (!$hapus_query) {
            throw new Exception("Gagal menghapus data santri.");
        }
        header("Location: data_santri.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$kelas_result = mysqli_query($koneksi, "SELECT * FROM kelas");
$santri_result = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM santri s LEFT JOIN kelas k ON s.id_kelas = k.id ORDER BY s.id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Santri - PP. Tajalliddin</title>
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

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 99;
            display: none;
        }
        .sidebar-overlay.active {
            display: block;
        }

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
            flex-shrink: 0;
        }

        .sidebar-brand {
            font-size: 16px;
            font-weight: 800;
            color: #1a365d;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-sidebar-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
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
        }
        .sidebar-footer a:hover {
            background: rgba(229, 62, 62, 0.08);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--bg-main);
            width: 100%;
            max-width: 100vw;
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
            max-width: 100%;
        }

        .grid-section {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            align-items: start;
        }

        .left-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }

        .card-box {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .card-box h3 {
            font-size: 16px;
            color: #1a365d;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 11px 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13px;
            color: var(--text-main);
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1);
        }

        .btn-primary {
            width: 100%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3);
            transition: opacity 0.2s;
        }
        .btn-primary:hover { opacity: 0.9; }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 550px;
            border-collapse: collapse;
            font-size: 13px;
        }

        table th, table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
            white-space: nowrap;
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

        .badge-aktif {
            background: #c6f6d5;
            color: #22543d;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-action-group {
            display: flex;
            gap: 6px;
        }

        .btn-edit {
            background: #feebc8;
            color: #9c4221;
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }
        .btn-edit:hover { background: #fde68a; }

        .btn-danger {
            background: #fed7d7;
            color: #c53030;
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
        }
        .btn-danger:hover { background: #feb2b2; }

        /* Modal Popup Edit */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        @media (max-width: 900px) {
            .grid-section { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                left: 0; top: 0; height: 100%;
                transform: translateX(-100%);
                box-shadow: 5px 0 25px rgba(0,0,0,0.1);
            }
            .sidebar.active { transform: translateX(0); }
            .close-sidebar-btn { display: block; }
            .dashboard-body { padding: 10px 15px 30px 15px; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span>PP. TAJALLIDDIN</span>
            <button class="close-sidebar-btn" onclick="toggleSidebar()">&times;</button>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="data_santri.php" class="active">Kelola Santri</a></li>
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
                <h2>Kelola Data Santri</h2>
            </div>
            <div class="user-profile">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
        </div>

        <div class="dashboard-body">
            
            <?php if ($pesan): ?>
                <div class="alert-success"><?= $pesan; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><?= $error; ?></div>
            <?php endif; ?>

            <div class="grid-section">
                <!-- Kolom Kiri: Form Tambah & Import -->
                <div class="left-column">
                    <div class="card-box">
                        <h3>Tambah Santri Baru</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>NIS</label>
                                <input type="text" name="nis" placeholder="Nomor Induk Santri..." required autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" placeholder="Nama lengkap santri..." required autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>Kelas</label>
                                <select name="id_kelas" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php 
                                    mysqli_data_seek($kelas_result, 0);
                                    while ($k = mysqli_fetch_assoc($kelas_result)): 
                                    ?>
                                        <option value="<?= $k['id']; ?>"><?= htmlspecialchars($k['nama_kelas']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Masuk</label>
                                <input type="date" name="tanggal_masuk" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                            <button type="submit" name="tambah_santri" class="btn-primary">Simpan Santri</button>
                        </form>
                    </div>

                    <div class="card-box">
                        <h3>Import Data Excel / CSV</h3>
                        <form action="import_santri.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <input type="file" name="file_excel" accept=".csv, .xlsx" required style="padding: 8px; font-size: 12px;">
                            </div>
                            <button type="submit" name="import" class="btn-primary" style="background: linear-gradient(135deg, #38a169, #276749);">Upload & Import</button>
                        </form>
                    </div>
                </div>

                <!-- Kolom Kanan: Tabel Data Santri -->
                <div class="card-box">
                    <h3>Daftar Seluruh Santri Aktif</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($s = mysqli_fetch_assoc($santri_result)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($s['nis']); ?></td>
                                    <td><?= htmlspecialchars($s['nama_lengkap']); ?></td>
                                    <td><?= htmlspecialchars($s['nama_kelas'] ?? 'Belum ada'); ?></td>
                                    <td><span class="badge-aktif"><?= ucfirst($s['status']); ?></span></td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit" onclick="bukaModalEdit('<?= $s['id']; ?>', '<?= htmlspecialchars($s['nis'], ENT_QUOTES); ?>', '<?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES); ?>', '<?= $s['id_kelas']; ?>', '<?= $s['status']; ?>')">Edit</button>
                                            <a href="data_santri.php?hapus=<?= $s['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus data santri ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Form Edit Santri -->
    <div class="modal" id="modalEdit">
        <div class="modal-content">
            <h3>Edit Data Santri</h3>
            <form action="" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>NIS</label>
                    <input type="text" name="nis" id="edit_nis" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="id_kelas" id="edit_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php 
                        mysqli_data_seek($kelas_result, 0);
                        while ($k = mysqli_fetch_assoc($kelas_result)): 
                        ?>
                            <option value="<?= $k['id']; ?>"><?= htmlspecialchars($k['nama_kelas']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="aktif">Aktif</option>
                        <option value="keluar">Keluar</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_santri" class="btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn-primary" style="background: #718096;" onclick="tutupModalEdit()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function bukaModalEdit(id, nis, nama, id_kelas, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nis').value = nis;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas').value = id_kelas;
            document.getElementById('edit_status').value = status;
            document.getElementById('modalEdit').classList.add('active');
        }

        function tutupModalEdit() {
            document.getElementById('modalEdit').classList.remove('active');
        }
    </script>

</body>
</html>