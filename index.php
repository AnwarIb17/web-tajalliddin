<?php
session_start();
include 'config/koneksi.php';

$error = '';
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        if ($password === $row['password']) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } elseif ($row['role'] === 'guru') {
                header("Location: guru/dashboard.php");
                exit;
            } elseif ($row['role'] === 'bendahara') {
                header("Location: bendahara/dashboard.php");
                exit;
            }
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PP. Tajalliddin</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #e8edf2; /* Menyesuaikan latar belakang dashboard admin */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        /* Kartu Utama Login */
        .login-card {
            width: 900px;
            max-width: 100%;
            min-height: 500px;
            background: #ffffff;
            border-radius: 24px;
            display: flex;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Sisi Kiri: Form Login (Berlatar Belakang Warna Utama) */
        .form-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, #021024 0%, #052659 100%);
            color: #ffffff;
        }

        .form-side h2 {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .form-side p.subtitle {
            font-size: 13px;
            color: #90cdf4;
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .alert-box {
            background: rgba(229, 62, 62, 0.2);
            color: #fed7d7;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12px;
            margin-bottom: 16px;
            border-left: 4px solid #f56565;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            font-size: 14px;
            color: #ffffff;
            outline: none;
            transition: all 0.2s;
        }

        .input-group input::placeholder {
            color: #a0aec0;
        }

        .input-group input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #00b4d8;
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.2);
        }

        .btn-submit {
            width: 100%;
            background: #00b4d8;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 5px;
            box-shadow: 0 6px 15px rgba(0, 180, 216, 0.3);
        }

        .btn-submit:hover {
            background: #0077b6;
            transform: translateY(-1px);
        }

        /* Sisi Kanan: Logo & Identitas Pesantren (Berlatar Putih Bersih) */
        .visual-side {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
            color: #2d3748;
        }

        .visual-side img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .visual-side h3 {
            font-size: 20px;
            font-weight: 800;
            color: #1a365d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .visual-side p {
            font-size: 12px;
            color: #718096;
            line-height: 1.5;
            font-weight: 600;
        }

        /* Responsif untuk Tampilan HP */
        @media (max-width: 768px) {
            .login-card {
                flex-direction: column-reverse; /* Di HP, bagian logo di atas atau form di atas sesuai selera */
                height: auto;
            }
            .form-side, .visual-side {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Sisi Kiri: Form Login -->
        <div class="form-side">
            <h2>Selamat Datang</h2>
            <p class="subtitle">Masuk ke Sistem Akademik & Keuangan</p>

            <?php if ($error): ?>
                <div class="alert-box"><?= $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username..." required autocomplete="off">
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password..." required>
                </div>
                <button type="submit" name="login" class="btn-submit">Masuk Sistem</button>
            </form>
        </div>

        <!-- Sisi Kanan: Logo & Identitas Pesantren -->
        <div class="visual-side">
            <img src="assets/img/logo.png" alt="Logo Tajalliddin">
            <h3>PP. TAJALLIDDIN</h3>
            <p>Sistem Informasi Akademik & Keuangan<br>Kp. Sinar Jaya Samarang - Garut</p>
        </div>
    </div>

</body>
</html>