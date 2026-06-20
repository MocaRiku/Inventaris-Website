<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireUser();

$conn = getConnection();
$error = '';


$barang_result = mysqli_query($conn, "SELECT id_barang, nama_barang, satuan FROM tabel_master_barang ORDER BY nama_barang");
$barang_list = [];
while ($b = mysqli_fetch_assoc($barang_result)) {
    $barang_list[] = $b;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kuantitas  = (int)($_POST['kuantitas'] ?? 0);
    $catatan    = trim($_POST['catatan'] ?? '');
    $id_divisi  = currentDivisiId();
    $gambar_ref = '';

    if (empty($nama_barang)) $error = "Nama barang harus diisi.";
    if ($kuantitas <= 0) $error = "Kuantitas minimal 1.";

    $id_barang = 0;

  
    if (!$error) {
        $cek_sql = "SELECT id_barang FROM tabel_master_barang WHERE nama_barang = '$nama_barang' LIMIT 1";
        $cek_result = mysqli_query($conn, $cek_sql);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $row = mysqli_fetch_assoc($cek_result);
            $id_barang = $row['id_barang'];
        } else {
            $insert_master = "INSERT INTO tabel_master_barang (nama_barang, satuan) VALUES ('$nama_barang', 'Pcs')";
            if (mysqli_query($conn, $insert_master)) {
                $id_barang = mysqli_insert_id($conn); 
            } else {
                $error = "Gagal mendaftarkan barang baru: " . mysqli_error($conn);
            }
        }
    }

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
                $gambar_ref = $nama;
            } else {
                $error = "Gagal upload gambar.";
            }
        }
    } elseif (!$error) {
        $error = "Gambar referensi wajib diunggah.";
    }

    if (!$error) {
        $insert_sql = "INSERT INTO tabel_pengajuan (id_divisi, id_barang, kuantitas, gambar_referensi, catatan)
                       VALUES ($id_divisi, $id_barang, $kuantitas, '$gambar_ref', '$catatan')";

        if (mysqli_query($conn, $insert_sql)) {
            $_SESSION['flash_message'] = "Pengajuan berhasil dibuat!";
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Gagal mengajukan: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengajuan — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background:#f1f5f9; font-family:'Segoe UI',sans-serif;">
<div class="container py-4" style="max-width:700px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-header bg-transparent fw-bold py-3" style="border-bottom:1px solid #e2e8f0;"><i class="bi bi-plus-circle me-2"></i>Buat Pengajuan Baru</div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Barang</label>
                    <input type="text" name="nama_barang" class="form-control" list="daftarBarang" placeholder="Ketik nama barang..." autocomplete="off" required>
                    <datalist id="daftarBarang">
                        <?php foreach ($barang_list as $b): ?>
                            <option value="<?= htmlspecialchars($b['nama_barang']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">Pilih dari saran yang muncul, atau ketik manual jika barang belum ada.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kuantitas</label>
                    <input type="number" name="kuantitas" class="form-control" min="1" placeholder="Masukkan jumlah" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-image me-1"></i>Upload Gambar Referensi <span class="text-danger">*</span></label>
                    <input type="file" name="gambar_referensi" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text">Wajib diunggah. Maks 2MB. JPG/PNG/WebP.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Catatan Tambahan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: Warna merah, merk Sony."></textarea>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Ajukan</button>
                <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Batal</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>