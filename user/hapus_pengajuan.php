<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireUser();

$conn = getConnection();
$id_divisi = currentDivisiId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$cek_sql = "SELECT p.id_pengajuan, p.gambar_referensi, pd.status, pd.foto_bukti
            FROM tabel_pengajuan p
            JOIN tabel_pengadaan pd ON p.id_pengajuan = pd.id_pengajuan
            WHERE p.id_pengajuan = $id AND p.id_divisi = $id_divisi AND pd.status = 'Diajukan'
            LIMIT 1";
$cek = mysqli_query($conn, $cek_sql);

if (!$cek || mysqli_num_rows($cek) === 0) {
    $_SESSION['flash_message'] = "Data tidak ditemukan atau sudah diproses, tidak bisa dihapus.";
    header("Location: dashboard.php");
    exit;
}

$row = mysqli_fetch_assoc($cek);

if ($row['gambar_referensi'] && file_exists('../assets/uploads/referensi/' . $row['gambar_referensi'])) {
    unlink('../assets/uploads/referensi/' . $row['gambar_referensi']);
}

$delete_sql = "DELETE FROM tabel_pengajuan WHERE id_pengajuan = $id";
if (mysqli_query($conn, $delete_sql)) {
    $_SESSION['flash_message'] = "Pengajuan berhasil dihapus.";
} else {
    $_SESSION['flash_message'] = "Gagal menghapus: " . mysqli_error($conn);
}

header("Location: dashboard.php");
exit;