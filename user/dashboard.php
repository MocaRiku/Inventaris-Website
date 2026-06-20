<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireUser();

$conn = getConnection();
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$id_divisi_raw = currentDivisiId();
$id_divisi = !empty($id_divisi_raw) ? (int)$id_divisi_raw : 0;

$sql = "
    SELECT 
        p.id_pengajuan, mb.nama_barang, p.kuantitas, mb.satuan,
        p.gambar_referensi, p.catatan, p.created_at,
        pd.status, pd.metode, pd.catatan_kondisi, pd.foto_bukti, pd.updated_at
    FROM tabel_pengajuan p
    JOIN tabel_master_barang mb ON p.id_barang = mb.id_barang
    JOIN tabel_pengadaan pd     ON p.id_pengajuan = pd.id_pengajuan
    WHERE p.id_divisi = ? 
    ORDER BY p.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_divisi);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

$total_diajukan = 0; $total_diproses = 0; $total_selesai = 0;
foreach ($data as $row) {
    if ($row['status'] === 'Diajukan') $total_diajukan++;
    if ($row['status'] === 'Diproses') $total_diproses++;
    if ($row['status'] === 'Selesai')  $total_selesai++;
}


if (!function_exists('statusBadge')) {
    function statusBadge($status) {
        if ($status === 'Diajukan') return '<span class="badge bg-warning text-dark">Diajukan</span>';
        if ($status === 'Diproses') return '<span class="badge bg-info">Diproses</span>';
        if ($status === 'Selesai') return '<span class="badge bg-success">Selesai</span>';
        return '<span class="badge bg-secondary">Unknown</span>';
    }
}
if (!function_exists('metodeBadge')) {
    function metodeBadge($metode) {
        if (empty($metode)) return '<span class="text-muted">—</span>';
        return '<span class="badge border border-secondary text-secondary">' . htmlspecialchars($metode) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Divisi — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; }
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; overflow-x: hidden; }
        
        
        .sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: var(--sidebar-width); 
            height: 100vh;
            background: #1e293b; 
            color: #fff; 
            padding: 1.5rem; 
            z-index: 1040; 
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar .brand { font-size: 1.2rem; font-weight: 700; margin-bottom: 2rem; }
        .sidebar .brand i { color: #60a5fa; }
        .sidebar .nav-link { color: #cbd5e1; border-radius: 8px; padding: 0.65rem 1rem;
                             margin-bottom: 0.25rem; display: block; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        
    
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 2rem; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }
        
        .stat-card { border-radius: 12px; padding: 1.25rem; color: #fff; display: flex; align-items: center; justify-content: space-between; }
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.info    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-card.success { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-number { font-size: 2rem; font-weight: 800; } 
        .table-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0;}
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.78rem; white-space: nowrap;}
        
     
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

   
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1030;
        }
        .sidebar-overlay.active { display: block; }

       
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 4.5rem 1rem 1rem 1rem; } 
            .mobile-toggle { display: block; }
            .table-card { padding: 1rem; }
            
           
            .header-container { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
            .header-container .btn { align-self: flex-start; }
        }
    </style>
</head>
<body>

<button class="mobile-toggle d-print-none" id="sidebarToggle" aria-label="Toggle Navigation">
    <i class="bi bi-list fs-4"></i>
</button>

<div class="sidebar-overlay d-print-none" id="sidebarOverlay"></div>

<aside class="sidebar d-flex flex-column" id="sidebar">
    <div class="brand"><i class="bi bi-box-seam"></i> LogistikG</div>
    <nav class="nav flex-column">
        <a href="#" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard Divisi</a>
        <a href="buat_pengajuan.php" class="nav-link"><i class="bi bi-plus-circle"></i> Buat Pengajuan</a>
        <a href="../logout.php" class="nav-link mt-auto text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
    <div class="mt-auto pt-3 border-top border-secondary">
        <small class="text-muted">
            <i class="bi bi-person-badge"></i> <?= $_SESSION['nama_lengkap'] ?><br>
            <span class="badge bg-info mt-1"><?= $_SESSION['user_role'] ?></span>
        </small>
    </div>
</aside>

<div class="main-content">
    <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><?= $message ?> <button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 header-container">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-clipboard-data me-2"></i>Dashboard Divisi</h4>
            <p class="text-muted small mb-0">Lihat dan kelola pengajuan logistik divisi Anda.</p>
        </div>
        <a href="buat_pengajuan.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Buat Pengajuan Baru</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-12"><div class="stat-card warning"><div><div class="stat-number"><?= $total_diajukan ?></div><small>Diajukan</small></div><i class="bi bi-send" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card info"><div><div class="stat-number"><?= $total_diproses ?></div><small>Diproses</small></div><i class="bi bi-hourglass-split" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card success"><div><div class="stat-number"><?= $total_selesai ?></div><small>Selesai</small></div><i class="bi bi-check2-all" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-table me-1"></i> Daftar Pengajuan Divisi Anda</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap">
                <thead class="table-light">
                    <tr><th>#</th><th>Nama Barang</th><th>Qty</th><th>Ref. Gambar</th><th>Status</th><th>Metode</th><th>Catatan Anda</th><th>Catatan QC</th><th>Tgl Diajukan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>Belum ada pengajuan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= $row['nama_barang'] ?></strong><br><small class="text-muted"><?= $row['satuan'] ?></small></td>
                            <td><?= $row['kuantitas'] ?></td>
                            <td>
                                <?php if ($row['gambar_referensi']): ?>
                                    <a href="../assets/uploads/referensi/<?= $row['gambar_referensi'] ?>" target="_blank">
                                        <img src="../assets/uploads/referensi/<?= $row['gambar_referensi'] ?>" class="img-thumb" alt="Ref">
                                    </a>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td><?= metodeBadge($row['metode']) ?></td>
                            <td>
                                <small><?= $row['catatan'] ? htmlspecialchars($row['catatan']) : '<span class="text-muted">—</span>' ?></small>
                            </td>
                            <td>
                                <small><?= $row['catatan_kondisi'] ? htmlspecialchars($row['catatan_kondisi']) : '<span class="text-muted">—</span>' ?></small>
                                <?php if ($row['foto_bukti']): ?>
                                    <br><a href="../assets/uploads/bukti/<?= $row['foto_bukti'] ?>" target="_blank" class="small">Lihat bukti</a>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('d/m/Y', strtotime($row['created_at'])) ?></small></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($row['status'] === 'Diajukan'): ?>
                                        <a href="edit_pengajuan.php?id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-warning btn-action"><i class="bi bi-pencil"></i></a>
                                        <a href="hapus_pengajuan.php?id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('Yakin hapus?')"><i class="bi bi-trash"></i></a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Terkunci</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    document.addEventListener("DOMContentLoaded", function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        }

        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleMenu);
        }
        
        if(overlay) {
            overlay.addEventListener('click', toggleMenu);
        }
    });
</script>
</body>
</html>
<?php mysqli_close($conn); ?>