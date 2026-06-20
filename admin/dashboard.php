<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireAdmin();

$conn = getConnection();
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$filter_divisi = isset($_GET['divisi']) ? (int)$_GET['divisi'] : 0;
$filter_barang = isset($_GET['barang']) ? (int)$_GET['barang'] : 0;
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_metode = isset($_GET['metode']) ? trim($_GET['metode']) : '';

$q_divisi = mysqli_query($conn, "SELECT id_divisi, nama_divisi FROM tabel_divisi ORDER BY nama_divisi");
$q_barang = mysqli_query($conn, "SELECT id_barang, nama_barang FROM tabel_master_barang ORDER BY nama_barang");
$q_metode = mysqli_query($conn, "SELECT DISTINCT metode FROM tabel_pengadaan WHERE metode IS NOT NULL AND metode != '' ORDER BY metode");

$where_clauses = [];

if ($filter_divisi > 0) {
    $where_clauses[] = "p.id_divisi = $filter_divisi";
}
if ($filter_barang > 0) {
    $where_clauses[] = "p.id_barang = $filter_barang";
}
if ($filter_status !== '') {
    $status_safe = mysqli_real_escape_string($conn, $filter_status);
    $where_clauses[] = "pd.status = '$status_safe'";
}
if ($filter_metode !== '') {
    if ($filter_metode === 'BELUM_ADA') {
        $where_clauses[] = "(pd.metode IS NULL OR pd.metode = '')";
    } else {
        $metode_safe = mysqli_real_escape_string($conn, $filter_metode);
        $where_clauses[] = "pd.metode = '$metode_safe'";
    }
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$sql = "
    SELECT 
        p.id_pengajuan,
        e.nama_event,
        d.nama_divisi,
        mb.nama_barang,
        p.kuantitas,
        mb.satuan,
        pd.status,
        pd.metode,
        pd.catatan_kondisi,
        pd.foto_bukti,
        p.gambar_referensi,
        p.catatan AS catatan_pengajuan,
        p.created_at,
        pd.updated_at
    FROM tabel_pengajuan p
    JOIN tabel_divisi d        ON p.id_divisi = d.id_divisi
    JOIN tabel_event e         ON d.id_event = e.id_event
    JOIN tabel_master_barang mb ON p.id_barang = mb.id_barang
    JOIN tabel_pengadaan pd    ON p.id_pengajuan = pd.id_pengajuan
    $where_sql
    ORDER BY FIELD(pd.status, 'Diajukan', 'Diproses', 'Selesai'), p.created_at DESC
";

$result = mysqli_query($conn, $sql);
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

// Helper function placeholder since it's used in the template
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
    <title>Dashboard Koor Perkap — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; }
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; overflow-x: hidden; }
        
        /* RESPONSIVE SIDEBAR */
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
                             margin-bottom: 0.25rem; transition: all 0.2s; display: block; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        
   
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 2rem; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }
        
        .stat-card { border-radius: 12px; padding: 1.25rem; color: #fff; display: flex; align-items: center; justify-content: space-between; height: 100%; }
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.info    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-card.success { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-number { font-size: 2rem; font-weight: 800; }
        .table-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; }
        .btn-action { padding: 0.3rem 0.6rem; font-size: 0.8rem; white-space: nowrap; }


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
            .main-content { margin-left: 0; padding: 4.5rem 1rem 1rem 1rem; } /* Tambah padding atas untuk tombol toggle */
            .mobile-toggle { display: block; }
            .table-card { padding: 1rem; }
        }

        @media print {
            body { background: #fff !important; color: #000 !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .table-card { box-shadow: none !important; padding: 0 !important; }
            .badge { border: 1px solid #64748b !important; color: #000 !important; background-color: transparent !important; }
            .mobile-toggle, .sidebar-overlay { display: none !important; }
        }
    </style>
</head>
<body>

<button class="mobile-toggle d-print-none" id="sidebarToggle" aria-label="Toggle Navigation">
    <i class="bi bi-list fs-4"></i>
</button>

<div class="sidebar-overlay d-print-none" id="sidebarOverlay"></div>

<aside class="sidebar d-flex flex-column d-print-none" id="sidebar">
    <div class="brand"><i class="bi bi-box-seam"></i> LogistikG</div>
   <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        <a href="event.php" class="nav-link"><i class="bi bi-calendar-event me-2"></i> Event</a>
        <a href="../logout.php" class="nav-link mt-auto text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </nav>
    <div class="mt-auto pt-3 border-top border-secondary">
        <small class="text-muted">
            <i class="bi bi-person-badge"></i> <?= $_SESSION['nama_lengkap'] ?><br>
            <span class="badge bg-primary mt-1"><?= $_SESSION['user_role'] ?></span>
        </small>
    </div>
</aside>

<div class="main-content">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clipboard-check me-2"></i>Dashboard Pengadaan</h4>
            <p class="text-muted small mb-0">Ringkasan seluruh pengajuan logistik kepanitiaan.</p>
        </div>
        <button onclick="window.print()" class="btn btn-success btn-sm d-print-none align-self-start align-self-md-auto">
            <i class="bi bi-printer me-1"></i> Cetak Rekapitulasi
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show d-print-none"><?= $message ?> <button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-12"><div class="stat-card warning"><div><div class="stat-number"><?= $total_diajukan ?></div><small>Pengajuan Baru</small></div><i class="bi bi-inbox" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card info"><div><div class="stat-number"><?= $total_diproses ?></div><small>Sedang Diproses</small></div><i class="bi bi-gear" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card success"><div><div class="stat-number"><?= $total_selesai ?></div><small>Selesai</small></div><i class="bi bi-check-circle" style="font-size:2.5rem; opacity:0.4;"></i></div></div>
    </div>

    <div class="table-card mb-4 d-print-none">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label small fw-bold">Filter Divisi</label>
                <select name="divisi" class="form-select form-select-sm">
                    <option value="0">-- Semua Divisi --</option>
                    <?php while ($d = mysqli_fetch_assoc($q_divisi)): ?>
                        <option value="<?= $d['id_divisi'] ?>" <?= $filter_divisi == $d['id_divisi'] ? 'selected' : '' ?>>
                            <?= $d['nama_divisi'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6">
                <label class="form-label small fw-bold">Filter Barang</label>
                <select name="barang" class="form-select form-select-sm">
                    <option value="0">-- Semua Barang --</option>
                    <?php while ($b = mysqli_fetch_assoc($q_barang)): ?>
                        <option value="<?= $b['id_barang'] ?>" <?= $filter_barang == $b['id_barang'] ? 'selected' : '' ?>>
                            <?= $b['nama_barang'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6">
                <label class="form-label small fw-bold">Filter Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="Diajukan" <?= $filter_status === 'Diajukan' ? 'selected' : '' ?>>Diajukan</option>
                    <option value="Diproses" <?= $filter_status === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-6 col-sm-6">
                <label class="form-label small fw-bold">Filter Metode</label>
                <select name="metode" class="form-select form-select-sm">
                    <option value="">-- Semua Metode --</option>
                    <option value="BELUM_ADA" <?= $filter_metode === 'BELUM_ADA' ? 'selected' : '' ?>>Belum Ada</option>
                    <?php while ($m = mysqli_fetch_assoc($q_metode)): ?>
                        <option value="<?= htmlspecialchars($m['metode']) ?>" <?= $filter_metode === $m['metode'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['metode']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-xl-2 col-md-6 col-sm-12 text-end d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Terapkan</button>
                <?php if ($filter_divisi > 0 || $filter_barang > 0 || $filter_status !== '' || $filter_metode !== ''): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm flex-grow-1"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-table me-1"></i> Data Terintegrasi — Divisi, Barang & Status</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap">
                <thead class="table-light">
                    <tr><th>#</th><th>Event</th><th>Divisi</th><th>Nama Barang</th><th>Qty</th><th>Ref. Gambar</th><th>Status</th><th>Metode</th><th>Bukti QC</th><th>Tgl Diajukan</th><th class="d-print-none">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4"><i class="bi bi-folder2-open d-block mb-2" style="font-size:2rem;"></i>Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><small class="text-muted"><?= $row['nama_event'] ?></small></td>
                            <td><span class="badge bg-secondary"><?= $row['nama_divisi'] ?></span></td>
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
                                <?php if ($row['foto_bukti']): ?>
                                    <a href="../assets/uploads/bukti/<?= $row['foto_bukti'] ?>" target="_blank">
                                        <img src="../assets/uploads/bukti/<?= $row['foto_bukti'] ?>" class="img-thumb" alt="Bukti">
                                    </a>
                                <?php else: ?><span class="text-muted">Belum ada</span><?php endif; ?>
                            </td>
                            <td><small><?= date('d/m/Y', strtotime($row['created_at'])) ?></small></td>
                            <td class="d-print-none"><a href="proses_pengajuan.php?id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-pencil-square"></i> Proses</a></td>
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