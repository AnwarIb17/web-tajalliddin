<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

$pesan = '';
$error = '';

// Proses Tambah Pengguna Baru
if (isset($_POST['tambah_user'])) {
    try {
        $username = mysqli_real_escape_string($koneksi, $_POST['username']);
        $password = $_POST['password'];
        $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $role = mysqli_real_escape_string($koneksi, $_POST['role']);

        $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            throw new Exception("Username '$username' sudah digunakan. Pilih username lain.");
        }

        $query = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal menyimpan pengguna ke database: " . mysqli_error($koneksi));
        }

        $pesan = "Pengguna baru berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Edit Pengguna
if (isset($_POST['edit_user'])) {
    try {
        $id = intval($_POST['id']);
        $username = mysqli_real_escape_string($koneksi, $_POST['username']);
        $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $role = mysqli_real_escape_string($koneksi, $_POST['role']);
        $password_baru = $_POST['password'];

        $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' AND id != $id");
        if (mysqli_num_rows($cek_user) > 0) {
            throw new Exception("Username '$username' sudah digunakan oleh akun lain.");
        }

        if (!empty(trim($password_baru))) {
            $query = "UPDATE users SET username = '$username', nama_lengkap = '$nama_lengkap', role = '$role', password = '$password_baru' WHERE id = $id";
        } else {
            $query = "UPDATE users SET username = '$username', nama_lengkap = '$nama_lengkap', role = '$role' WHERE id = $id";
        }

        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Gagal memperbarui data pengguna.");
        }

        $pesan = "Data pengguna berhasil diperbarui!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses Hapus Pengguna
if (isset($_GET['hapus'])) {
    try {
        $id_user = intval($_GET['hapus']);
        
        $data_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM users WHERE id = $id_user"));
        if ($data_user && $data_user['username'] === $_SESSION['username']) {
            throw new Exception("Anda tidak dapat menghapus akun yang sedang Anda gunakan saat ini.");
        }

        $hapus_query = mysqli_query($koneksi, "DELETE FROM users WHERE id = $id_user");
        if (!$hapus_query) {
            throw new Exception("Gagal menghapus pengguna.");
        }
        
        header("Location: data_guru.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$users_result = mysqli_query($koneksi, "SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - PP. Tajalliddin</title>
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
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 99;
            display: none;
        }
        .sidebar-overlay.active { display: block; }

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
            background: none; border: none; font-size: 20px;
            color: var(--text-muted); cursor: pointer; display: none;
        }

        .sidebar-menu { list-style: none; flex: 1; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a {
            color: var(--text-muted); text-decoration: none; display: flex;
            align-items: center; padding: 12px 16px; border-radius: 14px;
            font-size: 14px; font-weight: 600; transition: all 0.2s ease;
        }
        .sidebar-menu a:hover { background: rgba(0, 180, 216, 0.08); color: var(--primary); }
        .sidebar-menu a.active {
            background: var(--primary-gradient); color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 180, 216, 0.3);
        }

        .sidebar-footer a {
            color: #e53e3e; text-decoration: none; display: flex;
            align-items: center; padding: 12px 16px; border-radius: 14px;
            font-size: 14px; font-weight: 600;
        }
        .sidebar-footer a:hover { background: rgba(229, 62, 62, 0.08); }

        .main-content {
            flex: 1; display: flex; flex-direction: column;
            overflow-y: auto; overflow-x: hidden; background: var(--bg-main);
            width: 100%; max-width: 100vw;
        }

        .top-header {
            background: transparent; padding: 20px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .toggle-btn {
            background: var(--card-bg); border: none; width: 40px; height: 40px;
            border-radius: 12px; cursor: pointer; display: flex;
            justify-content: center; align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); font-size: 18px; color: var(--text-main);
        }
        .top-header h2 { font-size: 22px; font-weight: 800; color: #1a365d; }
        .user-profile {
            font-size: 13px; color: var(--text-muted); font-weight: 600;
            background: var(--card-bg); padding: 8px 16px; border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .dashboard-body { padding: 10px 30px 30px 30px; max-width: 100%; }

        .grid-section {
            display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start;
        }

        .card-box {
            background: var(--card-bg); padding: 25px; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); width: 100%; min-width: 0; overflow: hidden;
        }
        .card-box h3 { font-size: 16px; color: #1a365d; margin-bottom: 20px; }

        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 700;
            color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;
        }
        .form-group input, .form-group select {
            width: 100%; padding: 11px 15px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px;
            color: var(--text-main); outline: none;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary); background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1);
        }

        /* Pembungkus Input Password dengan Tombol Ikon Mata */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            padding-right: 45px; /* Memberikan ruang untuk tombol mata */
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-primary {
            width: 100%; background: var(--primary-gradient); color: white;
            border: none; padding: 12px; border-radius: 12px; font-weight: 700;
            font-size: 13px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3);
            transition: opacity 0.2s;
        }
        .btn-primary:hover { opacity: 0.9; }

        .alert-success {
            background: #c6f6d5; color: #22543d; padding: 10px 14px;
            border-radius: 10px; font-size: 12px; margin-bottom: 15px;
        }
        .alert-error {
            background: #fed7d7; color: #742a2a; padding: 10px 14px;
            border-radius: 10px; font-size: 12px; margin-bottom: 15px;
        }

        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; min-width: 500px; border-collapse: collapse; font-size: 13px; }
        table th, table td {
            padding: 12px 14px; text-align: left; border-bottom: 1px solid #edf2f7; white-space: nowrap;
        }
        table th {
            background: #f8fafc; color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 11px;
        }
        table td { color: var(--text-main); font-weight: 500; }

        .badge-role {
            padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: capitalize;
        }
        .role-admin { background: #e2e8f0; color: #1a365d; }
        .role-guru { background: #c6f6d5; color: #22543d; }
        .role-bendahara { background: #feebc8; color: #9c4221; }

        .btn-action-group { display: flex; gap: 6px; }
        .btn-edit {
            background: #feebc8; color: #9c4221; padding: 5px 10px;
            border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: 700; border: none; cursor: pointer;
        }
        .btn-edit:hover { background: #fde68a; }

        .btn-danger {
            background: #fed7d7; color: #c53030; padding: 5px 10px;
            border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: 700;
        }
        .btn-danger:hover { background: #feb2b2; }

        /* Modal Popup Edit */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
            justify-content: center; align-items: center; z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 30px; border-radius: 20px; width: 400px; max-width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        @media (max-width: 900px) {
            .grid-section { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed; left: 0; top: 0; height: 100%;
                transform: translateX(-100%); box-shadow: 5px 0 25px rgba(0,0,0,0.1);
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
            <li><a href="data_santri.php">Kelola Santri</a></li>
            <li><a href="data_guru.php" class="active">Pengguna</a></li>
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
                <h2>Kelola Pengguna Sistem</h2>
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
                <!-- Kolom Kiri: Form Tambah Pengguna -->
                <div class="card-box">
                    <h3>Tambah Pengguna Baru</h3>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap & Gelar</label>
                            <input type="text" name="nama_lengkap" placeholder="Contoh: Ustadz Ahmad, S.Pd" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Username untuk login..." required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="password-container">
                                <input type="password" name="password" id="password_tambah" placeholder="Kata sandi..." required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password_tambah', this)">👁</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Role / Hak Akses</label>
                            <select name="role" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="guru">Guru (Wali Kelas)</option>
                                <option value="bendahara">Bendahara</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah_user" class="btn-primary">Simpan Pengguna</button>
                    </form>
                </div>

                <!-- Kolom Kanan: Tabel Daftar Pengguna -->
                <div class="card-box">
                    <h3>Daftar Akun Pengguna Aktif</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($u = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($u['nama_lengkap']); ?></td>
                                    <td><?= htmlspecialchars($u['username']); ?></td>
                                    <td>
                                        <span class="badge-role role-<?= $u['role']; ?>">
                                            <?= $u['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit" onclick="bukaModalEdit('<?= $u['id']; ?>', '<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES); ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES); ?>', '<?= $u['role']; ?>')">Edit</button>
                                            <a href="data_guru.php?hapus=<?= $u['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus akun pengguna ini?')">Hapus</a>
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

    <!-- Modal Form Edit Pengguna -->
    <div class="modal" id="modalEditUser">
        <div class="modal-content">
            <h3>Edit Data Pengguna</h3>
            <form action="" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Lengkap & Gelar</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>Password Baru <span style="font-weight: normal; color: var(--text-muted);">(Opsional)</span></label>
                    <div class="password-container">
                        <input type="password" name="password" id="password_edit" placeholder="Kosongkan jika tidak diubah">
                        <button type="button" class="toggle-password" onclick="togglePassword('password_edit', this)">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Role / Hak Akses</label>
                    <select name="role" id="edit_role" required>
                        <option value="guru">Guru (Wali Kelas)</option>
                        <option value="bendahara">Bendahara</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_user" class="btn-primary">Simpan Perubahan</button>
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

        function bukaModalEdit(id, nama, username, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('password_edit').value = ''; // Kosongkan password saat modal dibuka
            document.getElementById('modalEditUser').classList.add('active');
        }

        function tutupModalEdit() {
            document.getElementById('modalEditUser').classList.remove('active');
        }

        // Fungsi Interaktif untuk Toggle Lihat/Sembunyikan Password
        function togglePassword(fieldId, btnElement) {
            const passwordInput = document.getElementById(fieldId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                btnElement.textContent = '🙈'; // Ubah ikon saat password terlihat
            } else {
                passwordInput.type = 'password';
                btnElement.textContent = '👁'; // Ubah kembali ke ikon mata biasa
            }
        }
    </script>

</body>
</html>