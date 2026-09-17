<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

$pesan = '';
$error = '';

// Proses Tambah Kelas
if (isset($_POST['tambah_kelas'])) {
    try {
        $nama_kelas = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
        if (empty($nama_kelas)) throw new Exception("Nama kelas tidak boleh kosong.");

        $query = "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kelas')";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal menambah kelas: " . mysqli_error($koneksi));
        }
        header("Location: penugasan_mapel.php?status=sukses_kelas");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Hapus Kelas
if (isset($_GET['hapus_kelas'])) {
    try {
        $id_kelas = intval($_GET['hapus_kelas']);
        mysqli_query($koneksi, "DELETE FROM kelas WHERE id = $id_kelas");
        header("Location: penugasan_mapel.php");
        exit;
    } catch (Exception $e) {
        $error = "Gagal menghapus kelas.";
    }
}

// Proses Tambah Mata Pelajaran
if (isset($_POST['tambah_mapel'])) {
    try {
        $nama_mapel = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
        $id_kelas = !empty($_POST['id_kelas']) ? intval($_POST['id_kelas']) : "NULL";

        if (empty($nama_mapel)) throw new Exception("Nama mata pelajaran tidak boleh kosong.");

        $query = "INSERT INTO mapel (nama_mapel, id_kelas) VALUES ('$nama_mapel', $id_kelas)";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal menambah mapel: " . mysqli_error($koneksi));
        }
        header("Location: penugasan_mapel.php?status=sukses_mapel");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Hapus Mapel
if (isset($_GET['hapus_mapel'])) {
    try {
        $id_mapel = intval($_GET['hapus_mapel']);
        mysqli_query($koneksi, "DELETE FROM mapel WHERE id = $id_mapel");
        header("Location: penugasan_mapel.php");
        exit;
    } catch (Exception $e) {
        $error = "Gagal menghapus mata pelajaran.";
    }
}

// Proses Simpan Penugasan Guru
if (isset($_POST['tambah_penugasan'])) {
    try {
        $id_user = intval($_POST['id_user']);
        $id_mapel = intval($_POST['id_mapel']);

        if (!$id_user || !$id_mapel) {
            throw new Exception("Pilih Guru dan Mata Pelajaran terlebih dahulu.");
        }

        $cek_mapel = mysqli_query($koneksi, "SELECT id_kelas FROM mapel WHERE id = $id_mapel");
        $data_mapel = mysqli_fetch_assoc($cek_mapel);
        $id_kelas_mapel = ($data_mapel['id_kelas'] !== null) ? $data_mapel['id_kelas'] : "NULL";

        $query = "INSERT INTO penugasan_mapel (id_user, id_mapel, id_kelas) VALUES ($id_user, $id_mapel, $id_kelas_mapel)";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal menyimpan penugasan guru.");
        }
        header("Location: penugasan_mapel.php?status=sukses_tugas");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Hapus Penugasan Guru
if (isset($_GET['hapus_tugas'])) {
    try {
        $id_tugas = intval($_GET['hapus_tugas']);
        mysqli_query($koneksi, "DELETE FROM penugasan_mapel WHERE id = $id_tugas");
        header("Location: penugasan_mapel.php");
        exit;
    } catch (Exception $e) {
        $error = "Gagal menghapus penugasan.";
    }
}

// Menangkap Notifikasi URL Redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sukses_kelas') $pesan = "Kelas baru berhasil ditambahkan!";
    elseif ($_GET['status'] === 'sukses_mapel') $pesan = "Mata pelajaran baru berhasil ditambahkan!";
    elseif ($_GET['status'] === 'sukses_tugas') $pesan = "Penugasan guru berhasil disimpan!";
}

// Ambil data pendukung
$kelas_result = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY id DESC");
$guru_result = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'guru' ORDER BY nama_lengkap ASC");

// Logika Filter untuk Daftar Mata Pelajaran
$filter_mapel_kelas = isset($_GET['filter_mapel_kelas']) ? $_GET['filter_mapel_kelas'] : '';
$query_mapel = "SELECT m.*, k.nama_kelas FROM mapel m LEFT JOIN kelas k ON m.id_kelas = k.id WHERE 1=1";

if ($filter_mapel_kelas !== '') {
    if ($filter_mapel_kelas === 'umum') {
        $query_mapel .= " AND m.id_kelas IS NULL";
    } else {
        $filter_id_kelas = intval($filter_mapel_kelas);
        $query_mapel .= " AND (m.id_kelas = $filter_id_kelas OR m.id_kelas IS NULL)"; // Menampilkan mapel khusus kelas tersebut DAN mapel umum
    }
}
$query_mapel .= " ORDER BY m.id DESC";
$mapel_result = mysqli_query($koneksi, $query_mapel);

// Ambil data mapel tanpa filter untuk dropdown penugasan guru (agar guru bisa memilih mapel apa saja dengan leluasa)
$mapel_dropdown_result = mysqli_query($koneksi, "SELECT m.*, k.nama_kelas FROM mapel m LEFT JOIN kelas k ON m.id_kelas = k.id ORDER BY m.id DESC");

$penugasan_result = mysqli_query($koneksi, "
    SELECT p.id, u.nama_lengkap AS nama_guru, m.nama_mapel, k.nama_kelas 
    FROM penugasan_mapel p 
    JOIN users u ON p.id_user = u.id 
    JOIN mapel m ON p.id_mapel = m.id 
    LEFT JOIN kelas k ON p.id_kelas = k.id 
    ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mapel, Kelas & Penugasan - PP. Tajalliddin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root {
            --bg-main: #e8edf2; --card-bg: #ffffff; --sidebar-bg: #ffffff;
            --text-main: #2d3748; --text-muted: #718096; --primary: #00b4d8;
            --primary-gradient: linear-gradient(135deg, #00b4d8, #0077b6); --sidebar-width: 260px;
        }
        body { background-color: var(--bg-main); display: flex; height: 100vh; overflow: hidden; color: var(--text-main); }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px); z-index: 99; display: none; }
        .sidebar-overlay.active { display: block; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); border-right: 1px solid rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; padding: 24px 20px; transition: transform 0.3s ease-in-out; z-index: 100; height: 100%; flex-shrink: 0; }
        .sidebar-brand { font-size: 16px; font-weight: 800; color: #1a365d; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .close-sidebar-btn { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; display: none; }
        .sidebar-menu { list-style: none; flex: 1; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { color: var(--text-muted); text-decoration: none; display: flex; align-items: center; padding: 12px 16px; border-radius: 14px; font-size: 14px; font-weight: 600; transition: all 0.2s ease; }
        .sidebar-menu a:hover { background: rgba(0, 180, 216, 0.08); color: var(--primary); }
        .sidebar-menu a.active { background: var(--primary-gradient); color: #ffffff; box-shadow: 0 8px 20px rgba(0, 180, 216, 0.3); }
        .sidebar-footer a { color: #e53e3e; text-decoration: none; display: flex; align-items: center; padding: 12px 16px; border-radius: 14px; font-size: 14px; font-weight: 600; }
        .sidebar-footer a:hover { background: rgba(229, 62, 62, 0.08); }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; background: var(--bg-main); width: 100%; max-width: 100vw; }
        .top-header { background: transparent; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .toggle-btn { background: var(--card-bg); border: none; width: 40px; height: 40px; border-radius: 12px; cursor: pointer; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); font-size: 18px; color: var(--text-main); }
        .top-header h2 { font-size: 22px; font-weight: 800; color: #1a365d; }
        .user-profile { font-size: 13px; color: var(--text-muted); font-weight: 600; background: var(--card-bg); padding: 8px 16px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

        .dashboard-body { padding: 10px 30px 30px 30px; max-width: 100%; }
        
        /* Layout 3 Kolom */
        .grid-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: start; }

        .card-box { background: var(--card-bg); padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); width: 100%; min-width: 0; overflow: hidden; margin-bottom: 20px; }
        .card-box h3 { font-size: 15px; color: #1a365d; margin-bottom: 15px; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 12px; color: var(--text-main); outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1); }

        .filter-box { display: flex; gap: 8px; margin-bottom: 12px; }
        .filter-box select { flex: 1; padding: 8px 10px; font-size: 11px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; }
        .filter-box button { padding: 8px 12px; font-size: 11px; font-weight: 600; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; }
        .filter-box a.btn-reset-filter { padding: 8px 10px; font-size: 11px; font-weight: 600; background: #e2e8f0; color: #4a5568; text-decoration: none; border-radius: 8px; display: flex; align-items: center; }

        .btn-primary { width: 100%; background: var(--primary-gradient); color: white; border: none; padding: 11px; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3); transition: opacity 0.2s; }
        .btn-primary:hover { opacity: 0.9; }

        .alert-success { background: #c6f6d5; color: #22543d; padding: 10px 14px; border-radius: 10px; font-size: 12px; margin-bottom: 15px; }
        .alert-error { background: #fed7d7; color: #742a2a; padding: 10px 14px; border-radius: 10px; font-size: 12px; margin-bottom: 15px; }

        /* Tabel dengan Scroll Vertikal Otomatis dan Sticky Header */
        .table-responsive { 
            width: 100%; 
            max-height: 240px; 
            overflow-y: auto;  
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
        }
        table { width: 100%; min-width: 250px; border-collapse: collapse; font-size: 12px; }
        table th, table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #edf2f7; white-space: nowrap; }
        table th { 
            background: #f8fafc; 
            color: var(--text-muted); 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 10px; 
            position: sticky; 
            top: 0; 
            z-index: 2; 
        }
        table td { color: var(--text-main); font-weight: 500; }

        .badge-target { padding: 3px 6px; border-radius: 5px; font-size: 10px; font-weight: 700; }
        .badge-umum { background: #c6f6d5; color: #22543d; }
        .badge-khusus { background: #feebc8; color: #9c4221; }

        .btn-danger { background: #fed7d7; color: #c53030; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 10px; font-weight: 700; }
        .btn-danger:hover { background: #feb2b2; }

        @media (max-width: 1100px) {
            .grid-section { grid-template-columns: 1fr; }
            .sidebar { position: fixed; left: 0; top: 0; height: 100%; transform: translateX(-100%); box-shadow: 5px 0 25px rgba(0,0,0,0.1); }
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
            <li><a href="data_santri.php">Kelola Santri</a></li>
            <li><a href="data_guru.php">Pengguna</a></li>
            <li><a href="penugasan_mapel.php" class="active">Mapel & Kelas</a></li>
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
                <h2>Kelola Mapel, Kelas & Penugasan</h2>
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
                <!-- KOLOM 1: KELOLA KELAS -->
                <div>
                    <div class="card-box">
                        <h3>Tambah Kelas Baru</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Nama Kelas</label>
                                <input type="text" name="nama_kelas" placeholder="Contoh: Ibtida 1..." required autocomplete="off">
                            </div>
                            <button type="submit" name="tambah_kelas" class="btn-primary">Simpan Kelas</button>
                        </form>
                    </div>

                    <div class="card-box">
                        <h3>Daftar Kelas Aktif</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kelas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($kelas_result && mysqli_num_rows($kelas_result) > 0): ?>
                                        <?php $no = 1; while ($k = mysqli_fetch_assoc($kelas_result)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($k['nama_kelas']); ?></td>
                                            <td>
                                                <a href="penugasan_mapel.php?hapus_kelas=<?= $k['id']; ?>" class="btn-danger" onclick="return confirm('Hapus kelas ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">Belum ada kelas.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- KOLOM 2: KELOLA MATA PELAJARAN -->
                <div>
                    <div class="card-box">
                        <h3>Tambah Mata Pelajaran</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Nama Mata Pelajaran</label>
                                <input type="text" name="nama_mapel" placeholder="Contoh: Zurumiah..." required autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>Target Kelas</label>
                                <select name="id_kelas">
                                    <option value="">-- Semua Kelas (Umum) --</option>
                                    <?php 
                                    mysqli_data_seek($kelas_result, 0);
                                    while ($k = mysqli_fetch_assoc($kelas_result)): 
                                    ?>
                                        <option value="<?= $k['id']; ?>"><?= htmlspecialchars($k['nama_kelas']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="tambah_mapel" class="btn-primary" style="background: linear-gradient(135deg, #38a169, #276749);">Simpan Mapel</button>
                        </form>
                    </div>

                    <div class="card-box">
                        <h3>Daftar Mata Pelajaran</h3>
                        
                        <!-- Form Filter Mata Pelajaran berdasarkan Kelas -->
                        <form method="GET" action="" class="filter-box">
                            <select name="filter_mapel_kelas">
                                <option value="">-- Semua Mapel --</option>
                                <option value="umum" <?= ($filter_mapel_kelas === 'umum') ? 'selected' : ''; ?>>Mapel Umum</option>
                                <?php 
                                mysqli_data_seek($kelas_result, 0);
                                while ($k = mysqli_fetch_assoc($kelas_result)): 
                                ?>
                                    <option value="<?= $k['id']; ?>" <?= ($filter_mapel_kelas == $k['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($k['nama_kelas']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit">Filter</button>
                            <?php if ($filter_mapel_kelas !== ''): ?>
                                <a href="penugasan_mapel.php" class="btn-reset-filter">Reset</a>
                            <?php endif; ?>
                        </form>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Mapel</th>
                                        <th>Target</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($mapel_result && mysqli_num_rows($mapel_result) > 0): ?>
                                        <?php $no = 1; while ($m = mysqli_fetch_assoc($mapel_result)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($m['nama_mapel']); ?></td>
                                            <td>
                                                <?php if (!empty($m['nama_kelas'])): ?>
                                                    <span class="badge-target badge-khusus"><?= htmlspecialchars($m['nama_kelas']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge-target badge-umum">Umum</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="penugasan_mapel.php?hapus_mapel=<?= $m['id']; ?>" class="btn-danger" onclick="return confirm('Hapus mapel ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 15px;">Tidak ada mapel.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- KOLOM 3: PENUGASAN GURU KE MAPEL -->
                <div>
                    <div class="card-box">
                        <h3>Tugaskan Mapel ke Guru</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Pilih Guru</label>
                                <select name="id_user" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php while ($g = mysqli_fetch_assoc($guru_result)): ?>
                                        <option value="<?= $g['id']; ?>"><?= htmlspecialchars($g['nama_lengkap']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Pilih Mata Pelajaran</label>
                                <select name="id_mapel" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php 
                                    mysqli_data_seek($mapel_dropdown_result, 0);
                                    while ($m = mysqli_fetch_assoc($mapel_dropdown_result)): 
                                    ?>
                                        <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?> <?= !empty($m['nama_kelas']) ? '(Khusus: '.$m['nama_kelas'].')' : '(Umum)'; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="tambah_penugasan" class="btn-primary" style="background: linear-gradient(135deg, #d69e2e, #b7791f);">Simpan Penugasan</button>
                        </form>
                    </div>

                    <div class="card-box">
                        <h3>Daftar Guru Pengampu</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Guru</th>
                                        <th>Mapel</th>
                                        <th>Kelas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($penugasan_result && mysqli_num_rows($penugasan_result) > 0): ?>
                                        <?php while ($t = mysqli_fetch_assoc($penugasan_result)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['nama_guru']); ?></td>
                                            <td><?= htmlspecialchars($t['nama_mapel']); ?></td>
                                            <td><?= htmlspecialchars($t['nama_kelas'] ?? 'Semua Kelas (Umum)'); ?></td>
                                            <td>
                                                <a href="penugasan_mapel.php?hapus_tugas=<?= $t['id']; ?>" class="btn-danger" onclick="return confirm('Hapus penugasan?')">Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 15px;">Belum ada penugasan.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

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