<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config/koneksi.php';

// Ambil parameter pilihan dari URL
$opsi = isset($_GET['opsi']) ? $_GET['opsi'] : 'semua';
$id_kelas = isset($_GET['id_kelas']) ? intval($_GET['id_kelas']) : 0;
$cari_keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';

// Susun Query Berdasarkan Pilihan
$query_sql = "SELECT s.nis, s.nama_lengkap, k.nama_kelas, s.tanggal_masuk, s.status 
              FROM santri s 
              LEFT JOIN kelas k ON s.id_kelas = k.id 
              WHERE 1=1";

if ($opsi === 'kelas' && $id_kelas > 0) {
    $query_sql .= " AND s.id_kelas = $id_kelas";
    $nama_file_suffix = "_Kelas_" . $id_kelas;
} else {
    $nama_file_suffix = "_Semua_Data";
}

if ($cari_keyword !== '') {
    $query_sql .= " AND (s.nama_lengkap LIKE '%$cari_keyword%' OR s.nis LIKE '%$cari_keyword%')";
}

$query_sql .= " ORDER BY s.id DESC";
$result = mysqli_query($koneksi, $query_sql);

// Nama file export
$filename = "Data_Santri_Tajalliddin" . $nama_file_suffix . "_" . date('Y-m-d') . ".csv";

// Header HTTP untuk unduh file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Tulis header kolom
fputcsv($output, array('NIS', 'Nama Lengkap', 'Kelas', 'Tanggal Masuk', 'Status'));

// Tulis baris data
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, array(
        $row['nis'],
        $row['nama_lengkap'],
        $row['nama_kelas'] ?? 'Belum ada',
        $row['tanggal_masuk'],
        ucfirst($row['status'])
    ));
}

fclose($output);
exit;