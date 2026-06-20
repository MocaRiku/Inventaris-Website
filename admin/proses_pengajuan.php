<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireAdmin();

$conn = getConnection();
$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;


$sql = "
    SELECT 
        p.id_pengajuan, p.kuantitas, p.gambar_referensi, p.catatan AS catatan_pengajuan, p.created_at,
        d.nama_divisi,
        mb.nama_barang, mb.satuan,
        pd.id_pengadaan, pd.status, pd.metode, pd.catatan_kondisi, pd.foto_bukti
    FROM tabel_pengajuan p
    JOIN tabel_divisi d         ON p.id_divisi = d.id_divisi
    JOIN tabel_master_barang mb ON p.id_barang = mb.id_barang
    JOIN tabel_pengadaan pd     ON p.id_pengajuan = pd.id_pengajuan
    WHERE p.id_pengajuan = $id_pengajuan
    LIMIT 1
";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    redirect('dashboard.php', 'Data tidak ditemukan.');
}
$data = mysqli_fetch_assoc($result);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_baru = $_POST['status'] ?? '';
    $metode      = $_POST['metode'] ?? null;
    $catatan     = trim($_POST['catatan_kondisi'] ?? '');
    $foto_bukti  = $data['foto_bukti'];

    if (!in_array($status_baru, ['Diajukan','Diproses','Selesai'])) $error = "Status tidak valid.";
    if ($status_baru === 'Diproses' && !in_array($metode, ['BELI','PINJAM'])) $error = "Pilih metode.";


    if (!$error && isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024;
        if (!in_array($_FILES['foto_bukti']['type'], $allowed)) {
            $error = "Format harus JPG/PNG/WebP.";
        } elseif ($_FILES['foto_bukti']['size'] > $max_size) {
            $error = "Ukuran maks 2MB.";
        } else {
            $ext  = pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION);
            $nama = 'bukti_' . $id_pengajuan . '_' . time() . '.' . $ext;
            $dest = '../assets/uploads/bukti/' . $nama;
            if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $dest)) {
                if ($data['foto_bukti'] && file_exists('../assets/uploads/bukti/' . $data['foto_bukti'])) {
                    unlink('../assets/uploads/bukti/' . $data['foto_bukti']);
                }
                $foto_bukti = $nama;
            }
        }
    }

    if (!$error) {
        $metode_sql   = $metode ? "'$metode'" : "NULL";
        $catatan_sql  = "'$catatan'";
        $foto_sql     = $foto_bukti ? "'$foto_bukti'" : "NULL";
        $id_pengadaan = $data['id_pengadaan'];

        $update_sql = "UPDATE tabel_pengadaan 
                       SET status='$status_baru', metode=$metode_sql, 
                           catatan_kondisi=$catatan_sql, foto_bukti=$foto_sql
                       WHERE id_pengadaan=$id_pengadaan";

        if (mysqli_query($conn, $update_sql)) {
            $success = "Status berhasil diperbarui!";
            $data['status'] = $status_baru;
            $data['metode'] = $metode;
            $data['catatan_kondisi'] = $catatan;
            $data['foto_bukti'] = $foto_bukti;
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
    <title>Proses Pengadaan — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin-top: 2rem; }
        .card { border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); border: none; }
        .card-header { background: transparent; border-bottom: 1px solid #e2e8f0; font-weight: 700; padding: 1.25rem 1.5rem; }
        .detail-row { display: flex; margin-bottom: 0.5rem; }
        .detail-label { width: 150px; font-weight: 600; color: #64748b; flex-shrink: 0; }
        .img-preview { max-width: 200px; border-radius: 10px; border: 2px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Detail Pengajuan #<?= $id_pengajuan ?></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-row"><span class="detail-label">Divisi</span>: <?= $data['nama_divisi'] ?></div>
                    <div class="detail-row"><span class="detail-label">Barang</span>: <?= $data['nama_barang'] ?></div>
                    <div class="detail-row"><span class="detail-label">Kuantitas</span>: <?= $data['kuantitas'] ?> <?= $data['satuan'] ?></div>
                    <div class="detail-row"><span class="detail-label">Status</span>: <?= statusBadge($data['status']) ?></div>
                    <div class="detail-row"><span class="detail-label">Metode</span>: <?= metodeBadge($data['metode']) ?></div>
                    <div class="detail-row"><span class="detail-label">Tgl Diajukan</span>: <?= date('d M Y H:i', strtotime($data['created_at'])) ?></div>
                </div>
                <div class="col-md-6">
                    <label class="detail-label">Gambar Referensi:</label>
                    <?php if ($data['gambar_referensi']): ?>
                        <a href="../assets/uploads/referensi/<?= $data['gambar_referensi'] ?>" target="_blank">
                            <img src="../assets/uploads/referensi/<?= $data['gambar_referensi'] ?>" class="img-preview" alt="Ref">
                        </a>
                    <?php else: ?><p class="text-muted">Tidak ada.</p><?php endif; ?>
                    <?php if ($data['catatan_pengajuan']): ?>
                        <div class="mt-2"><small class="text-muted">Catatan: <?= $data['catatan_pengajuan'] ?></small></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-sliders me-2"></i>Update Status Pengadaan & Quality Control</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status Pengadaan</label>
                    <select name="status" class="form-select" required>
                        <option value="Diajukan" <?= $data['status']=='Diajukan'?'selected':'' ?>>Diajukan</option>
                        <option value="Diproses" <?= $data['status']=='Diproses'?'selected':'' ?>>Diproses</option>
                        <option value="Selesai"  <?= $data['status']=='Selesai'?'selected':'' ?>>Selesai</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Metode Pengadaan</label>
                    <select name="metode" class="form-select">
                        <option value="">— Pilih —</option>
                        <option value="BELI"  <?= $data['metode']=='BELI'?'selected':'' ?>>BELI</option>
                        <option value="PINJAM" <?= $data['metode']=='PINJAM'?'selected':'' ?>>PINJAM</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-journal-check me-1"></i>Catatan Kondisi Barang (QC)</label>
                    <textarea name="catatan_kondisi" class="form-control" rows="3" placeholder="Contoh: Barang mulus, tidak ada goresan."><?= $data['catatan_kondisi'] ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-camera me-1"></i>Upload Foto Bukti Fisik</label>
                    <input type="file" name="foto_bukti" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if ($data['foto_bukti']): ?>
                        <div class="mt-2"><small>Foto saat ini:</small><br>
                            <a href="../assets/uploads/bukti/<?= $data['foto_bukti'] ?>" target="_blank">
                                <img src="../assets/uploads/bukti/<?= $data['foto_bukti'] ?>" class="img-preview mt-1" alt="Bukti">
                            </a>
                        </div>
                    <?php endif; ?>
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