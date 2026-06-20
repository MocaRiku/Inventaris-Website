<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireUser();

$conn = getConnection();
$id_divisi = currentDivisiId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';


$sql = "SELECT p.*, mb.nama_barang, mb.satuan 
        FROM tabel_pengajuan p 
        JOIN tabel_master_barang mb ON p.id_barang = mb.id_barang
        JOIN tabel_pengadaan pd ON p.id_pengajuan = pd.id_pengadaan
        WHERE p.id_pengajuan = $id AND p.id_divisi = $id_divisi AND pd.status = 'Diajukan'
        LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['flash_message'] = "Data tidak ditemukan atau sudah diproses.";
    header("Location: dashboard.php");
    exit;
}
$data = mysqli_fetch_assoc($result);

$barang_result = mysqli_query($conn, "SELECT id_barang, nama_barang, satuan FROM tabel_master_barang ORDER BY nama_barang");
$barang_list = [];
while ($b = mysqli_fetch_assoc($barang_result)) {
    $barang_list[] = $b;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang  = (int)($_POST['id_barang'] ?? 0);
    $kuantitas  = (int)($_POST['kuantitas'] ?? 0);
    $catatan    = trim($_POST['catatan'] ?? '');
    $gambar_ref = $data['gambar_referensi'];

    if ($id_barang <= 0) $error = "Pilih barang.";
    if ($kuantitas <= 0) $error = "Kuantitas minimal 1.";

    if (!$error && isset($_FILES['gambar_referensi']) && $_FILES['gambar_referensi']['error'] === UPLOAD_ERR_OK) {
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024;
        if (!in_array($_FILES['gambar_referensi']['type'], $allowed)) {
            $error = "Format harus JPG/PNG/WebP.";
        } elseif ($_FILES['gambar_referensi']['size'] > $max_size) {
            $error = "Ukuran maks 2MB.";
        } else {
            $ext  = pathinfo($_FILES['gambar_referensi']['name'], PATHINFO_EXTENSION);
            $nama = 'ref_' . $id_divisi . '_' . time() . '.' . $ext;
            $dest = '../assets/uploads/referensi/' . $nama;
            if (move_uploaded_file($_FILES['gambar_referensi']['tmp_name'], $dest)) {
                if ($data['gambar_referensi'] && file_exists('../assets/uploads/referensi/' . $data['gambar_referensi'])) {
                    unlink('../assets/uploads/referensi/' . $data['gambar_referensi']);
                }
                $gambar_ref = $nama;
            }
        }
    }

    if (!$error) {
        $update_sql = "UPDATE tabel_pengajuan 
                       SET id_barang=$id_barang, kuantitas=$kuantitas, 
                           gambar_referensi='$gambar_ref', catatan='$catatan'
                       WHERE id_pengajuan=$id";
        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['flash_message'] = "Pengajuan berhasil diperbarui!";
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Gagal update: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengajuan — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f1f5f9; font-family:'Segoe UI',sans-serif; }
        .img-preview { max-width: 200px; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container py-4" style="max-width:700px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-pencil-square me-2"></i>Edit Pengajuan #<?= $id ?></div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Barang</label>
                    <select name="id_barang" class="form-select" required>
                        <?php foreach ($barang_list as $b): ?>
                            <option value="<?= $b['id_barang'] ?>" <?= $b['id_barang'] == $data['id_barang'] ? 'selected' : '' ?>>
                                <?= $b['nama_barang'] ?> (<?= $b['satuan'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kuantitas</label>
                    <input type="number" name="kuantitas" class="form-control" min="1" value="<?= $data['kuantitas'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Gambar Referensi (biarkan kosong jika tidak diganti)</label>
                    <input type="file" name="gambar_referensi" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if ($data['gambar_referensi']): ?>
                        <div class="mt-2"><small>Gambar saat ini:</small><br>
                            <img src="../assets/uploads/referensi/<?= $data['gambar_referensi'] ?>" class="img-preview mt-1" alt="Ref">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3"><?= $data['catatan'] ?></textarea>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Batal</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>