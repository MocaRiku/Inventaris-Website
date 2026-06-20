<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireAdmin();

$conn = getConnection();
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error_msg = '';

$id_event = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_event === 0) {
    header("Location: event.php");
    exit;
}

$q_event_info = mysqli_query($conn, "SELECT * FROM tabel_event WHERE id_event = $id_event");
$event_info = mysqli_fetch_assoc($q_event_info);

if (!$event_info) {
    header("Location: event.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_divisi_akun'])) {
    $nama_divisi  = mysqli_real_escape_string($conn, trim($_POST['nama_divisi']));
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $username     = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password     = trim($_POST['password']); 

    if (!empty($nama_divisi) && !empty($nama_lengkap) && !empty($username) && !empty($password)) {
        
        $cek_user = mysqli_query($conn, "SELECT id_user FROM tabel_user WHERE username = '$username' LIMIT 1");
        if (mysqli_num_rows($cek_user) > 0) {
            $error_msg = "Gagal! Username '$username' sudah digunakan oleh akun lain.";
        } else {
            mysqli_begin_transaction($conn);

            try {
                $ins_divisi = "INSERT INTO tabel_divisi (nama_divisi, id_event) VALUES ('$nama_divisi', $id_event)";
                mysqli_query($conn, $ins_divisi);
                
                $new_id_divisi = mysqli_insert_id($conn);

                $ins_user = "INSERT INTO tabel_user (username, password, nama_lengkap, id_role, id_divisi) 
                             VALUES ('$username', '$password', '$nama_lengkap', 2, $new_id_divisi)";
                mysqli_query($conn, $ins_user);

                mysqli_commit($conn);
                $_SESSION['flash_message'] = "Divisi '$nama_divisi' dan akun '$username' berhasil didaftarkan!";
                header("Location: detail_event.php?id=" . $id_event);
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_msg = "Gagal mendaftarkan divisi. Pastikan tidak ada nama divisi ganda di event ini.";
            }
        }
    } else {
        $error_msg = "Semua bidang formulir wajib diisi.";
    }
}

$filter_divisi = isset($_GET['divisi']) ? (int)$_GET['divisi'] : 0;
$filter_barang = isset($_GET['barang']) ? (int)$_GET['barang'] : 0;
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_metode = isset($_GET['metode']) ? trim($_GET['metode']) : '';

$q_divisi = mysqli_query($conn, "SELECT id_divisi, nama_divisi FROM tabel_divisi WHERE id_event = $id_event ORDER BY nama_divisi");
$q_barang = mysqli_query($conn, "SELECT id_barang, nama_barang FROM tabel_master_barang ORDER BY nama_barang");
$q_metode = mysqli_query($conn, "SELECT DISTINCT metode FROM tabel_pengadaan WHERE metode IS NOT NULL AND metode != '' ORDER BY metode");

$where_clauses = ["d.id_event = $id_event"]; 

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

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$sql = "
    SELECT 
        p.id_pengajuan,
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
    <title>Detail Event — LogistikG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; overflow-x: hidden; }
        
  
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
        
        .stat-card { border-radius: 10px; padding: 1rem 1.25rem; color: #fff; display: flex; align-items: center; justify-content: space-between; height: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card.warning { background: #f59e0b; }
        .stat-card.info    { background: #3b82f6; }
        .stat-card.success { background: #10b981; }
        .stat-number { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 0.1rem;}
        .table-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0; }
        .btn-action { padding: 0.3rem 0.6rem; font-size: 0.8rem; white-space: nowrap; }
        
        .event-header-box { background: #fff; border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1rem; border-left: 4px solid #0d6efd; }
        
     
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
            
          
            .action-buttons { flex-direction: column; width: 100%; gap: 0.5rem; margin-top: 1rem; }
            .action-buttons button { width: 100%; }
        }

        @media print {
            body { background: #fff !important; color: #000 !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .table-card, .event-header-box { box-shadow: none !important; padding: 0 !important; border: none !important; }
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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        <a href="event.php" class="nav-link active"><i class="bi bi-calendar-event me-2"></i> Event</a>
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
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-3 gap-2">
        <a href="event.php" class="text-decoration-none text-muted fw-semibold d-print-none mb-2 mb-md-0"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Event</a>
        <div class="d-flex action-buttons d-print-none">
            <button type="button" class="btn btn-primary btn-sm me-md-2" data-bs-toggle="modal" data-bs-target="#modalTambahDivisi">
                <i class="bi bi-person-plus me-1"></i> Tambah Divisi & Akun
            </button>
            <button onclick="window.print()" class="btn btn-success btn-sm">
                <i class="bi bi-printer me-1"></i> Cetak Rekapitulasi
            </button>
        </div>
    </div>

    <div class="event-header-box d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-calendar2-check text-primary me-2"></i><?= htmlspecialchars($event_info['nama_event']) ?></h5>
            <div class="text-muted small d-flex flex-column flex-md-row gap-1 gap-md-3">
                <span><i class="bi bi-clock me-1"></i> <?= date('d M Y', strtotime($event_info['tanggal'])) ?></span>
                <span><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($event_info['lokasi'] ?: 'Lokasi belum ditentukan') ?></span>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show d-print-none py-2"><?= $message ?> <button class="btn-close pb-2" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show d-print-none py-2"><?= $error_msg ?> <button class="btn-close pb-2" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-2 mb-3">
        <div class="col-md-4 col-sm-12"><div class="stat-card warning"><div><div class="stat-number"><?= $total_diajukan ?></div><small style="font-size: 0.8rem;">Pengajuan Baru</small></div><i class="bi bi-inbox" style="font-size:2rem; opacity:0.3;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card info"><div><div class="stat-number"><?= $total_diproses ?></div><small style="font-size: 0.8rem;">Sedang Diproses</small></div><i class="bi bi-gear" style="font-size:2rem; opacity:0.3;"></i></div></div>
        <div class="col-md-4 col-sm-12"><div class="stat-card success"><div><div class="stat-number"><?= $total_selesai ?></div><small style="font-size: 0.8rem;">Selesai</small></div><i class="bi bi-check-circle" style="font-size:2rem; opacity:0.3;"></i></div></div>
    </div>

    <div class="table-card mb-4 d-print-none">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?= $id_event ?>">
            
            <div class="col-xl-3 col-md-4 col-sm-6">
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
            <div class="col-xl-2 col-md-4 col-sm-6">
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
                    <option value="">-- Semua --</option>
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
                    <a href="detail_event.php?id=<?= $id_event ?>" class="btn btn-outline-secondary btn-sm flex-grow-1"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-list-ul me-1"></i> Data Kebutuhan Logistik Event</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap">
                <thead class="table-light">
                    <tr><th>#</th><th>Divisi</th><th>Nama Barang</th><th>Qty</th><th>Ref. Gambar</th><th>Status</th><th>Metode</th><th>Bukti QC</th><th>Tgl Diajukan</th><th class="d-print-none">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-box-seam d-block mb-3" style="font-size:2.5rem; opacity:0.4;"></i>Belum ada pengajuan logistik untuk event ini.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
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
                            <td class="d-print-none">
                                <a href="proses_pengajuan.php?id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-pencil-square"></i> Proses</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahDivisi" tabindex="-1" aria-labelledby="modalTambahDivisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form method="POST" action="" autocomplete="off">
                <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
                    <h5 class="modal-title fw-bold" id="modalTambahDivisiLabel"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Divisi & Akun Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-1"><i class="bi bi-building me-1"></i>Struktur Kepanitiaan</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Divisi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_divisi" class="form-control form-control-sm" placeholder="Contoh: Konsumsi, Humas, Acara" required>
                    </div>

                    <h6 class="fw-bold mb-3 mt-4 text-secondary border-bottom pb-1"><i class="bi bi-shield-lock me-1"></i>Kredensial Login Koor</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Lengkap Koordinator <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-sm" placeholder="Contoh: Yanto Kopling" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Username Akun <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control form-control-sm" placeholder="Contoh: humas_seminar" required>
                        <div class="form-text xsmall">Gunakan username unik (disarankan pakai postfix nama event).</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Masukkan password akun" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 mx-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_divisi_akun" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i> Daftarkan Divisi</button>
                </div>
            </form>
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