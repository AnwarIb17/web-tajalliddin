<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

if (isset($_POST['import'])) {
    // Periksa apakah ada file yang diunggah
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_excel']['tmp_name'];
        $file_name = $_FILES['file_excel']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi ekstensi file (mendukung .csv)
        if ($file_ext === 'csv') {
            $handle = fopen($file_tmp, "r");
            
            // Lewati baris pertama (header: nis, nama_lengkap, id_kelas, tanggal_masuk)
            fgetcsv($handle);

            $berhasil = 0;
            $gagal = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Ambil data dari kolom CSV (sesuai urutan)
                $nis = isset($data[0]) ? trim($data[0]) : '';
                $nama = isset($data[1]) ? trim($data[1]) : '';
                $kelas = isset($data[2]) && trim($data[2]) !== '' ? intval($data[2]) : null;
                $tgl_masuk = isset($data[3]) && trim($data[3]) !== '' ? trim($data[3]) : date('Y-m-d');

                // Validasi data wajib (NIS dan Nama tidak boleh kosong)
                if (!empty($nis) && !empty($nama)) {
                    $nis_esc = mysqli_real_escape_string($koneksi, $nis);
                    $nama_esc = mysqli_real_escape_string($koneksi, $nama);
                    
                    // Cek apakah NIS sudah terdaftar di database
                    $cek = mysqli_query($koneksi, "SELECT id FROM santri WHERE nis = '$nis_esc'");
                    if (mysqli_num_rows($cek) === 0) {
                        // Atur format SQL untuk kelas jika bernilai null
                        $kelas_sql = ($kelas !== null) ? $kelas : "NULL";

                        $query = "INSERT INTO santri (nis, nama_lengkap, id_kelas, tanggal_masuk, status) 
                                  VALUES ('$nis_esc', '$nama_esc', $kelas_sql, '$tgl_masuk', 'aktif')";
                        
                        if (mysqli_query($koneksi, $query)) {
                            $berhasil++;
                        } else {
                            $gagal++;
                        }
                    } else {
                        // NIS sudah ada, dilewati agar tidak duplikat
                        $gagal++;
                    }
                } else {
                    $gagal++;
                }
            }
            fclose($handle);

            // Redirect kembali ke halaman data santri dengan membawa parameter sukses/info
            header("Location: data_santri.php?status=sukses&berhasil=$berhasil&gagal=$gagal");
            exit;
        } else {
            echo "<script>alert('Format file tidak didukung! Harap unggah file berformat .csv'); window.location='data_santri.php';</script>";
        }
    } else {
        echo "<script>alert('Gagal mengunggah file!'); window.location='data_santri.php';</script>";
    }
} else {
    header("Location: data_santri.php");
    exit;
}