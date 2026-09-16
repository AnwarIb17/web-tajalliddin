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
            $error = "Password salah!";
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
    <title>Login - Pondok Pesantren Tajalliddin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 380px;
            text-align: center;
        }
        .logo-container {
            width: 70px;
            height: 70px;
            background: #1e3c72;
            color: white;
            font-size: 28px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            box-shadow: 0 4px 10px rgba(30,60,114,0.3);
        }
        .login-card h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .login-card p {
            color: #666;
            font-size: 13px;
            margin-bottom: 25px;
        }
        .alert {
            background: #ffe6e6;
            color: #d9534f;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
            color: #444;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #1e3c72;
            outline: none;
        }
        .btn-login {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #218838;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Logo Pondok Pesantren Tajalliddin -->
        <div class="logo-container" style="background: transparent; box-shadow: none; width: 90px; height: 90px; margin: 0 auto 15px auto;">
            <img src="assets/img/logo.png" alt="Logo Tajalliddin" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        
        <h2>PP. Tajalliddin</h2>
        <p>Sistem Informasi Akademik & Keuangan</p>
        
        <?php if ($error): ?>
            <div class="alert"><?= $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username..." required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password..." required>
            </div>
            <button type="submit" name="login" class="btn-login">Masuk Sistem</button>
        </form>
    </div>

</body>
</html>