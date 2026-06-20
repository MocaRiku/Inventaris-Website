<?php
require_once '../auth_check.php';
require_once '../config/database.php';
requireAdmin();

$conn = getConnection();

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_event'])) {
    $nama_event = mysqli_real_escape_string($conn, trim($_POST['nama_event']));
    $tanggal = trim($_POST['tanggal']);
    $lokasi = mysqli_real_escape_string($conn, trim($_POST['lokasi']));

    if (!empty($nama_event) && !empty($tanggal)) {
        $insert_sql = "INSERT INTO tabel_event (nama_event, tanggal, lokasi) VALUES ('$nama_event', '$tanggal', '$lokasi')";
        if (mysqli_query($conn, $insert_sql)) {
            $_SESSION['flash_message'] = "Event '$nama_event' berhasil ditambahkan!";
            header("Location: event.php");
            exit;
        } else {
            $error_msg = "Gagal menambah event: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Nama event dan tanggal wajib diisi.";
    }
}

$sql = "
    SELECT 
        e.id_event, 
        e.nama_event, 
        e.tanggal, 
        e.lokasi,
        (SELECT COUNT(p.id_pengajuan) 
         FROM tabel_pengajuan p 
         JOIN tabel_divisi d ON p.id_divisi = d.id_divisi 
         WHERE d.id_event = e.id_event) AS total_pengajuan
    FROM tabel_event e
    ORDER BY e.tanggal DESC
";
$result = mysqli_query($conn, $sql);
$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Event — LogistikG</title>
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
                             margin-bottom: 0.25rem; transition: all 0.2s; display: block; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar .nav-link i { margin-right: 0.5rem; }
        
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 2rem; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }

        .event-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); 
                      transition: transform 0.2s ease, box-shadow 0.2s ease; border: 1px solid transparent; 
                      height: 100%; display: flex; flex-direction: column;}
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #cbd5e1; }
        .event-icon { width: 50px; height: 50px; border-radius: 12px; background: #eff6ff; color: #0d6efd; 
                      display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }

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
            
            .header-controls { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
            .header-controls button { width: 100%; }
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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="event.php" class="nav-link active"><i class="bi bi-calendar-event"></i> Event</a>
        <a href="../logout.php" class="nav-link mt-auto text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
    <div class="mt-auto pt-3 border-top border-secondary">
        <small class="text-muted">
            <i class="bi bi-person-badge"></i> <?= $_SESSION['nama_lengkap'] ?><br>
            <span class="badge bg-primary mt-1"><?= $_SESSION['user_role'] ?></span>
        </small>
    </div>
</aside>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 header-controls">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar-event me-2"></i>Daftar Event Kepanitiaan</h4>
            <p class="text-muted small mb-0">Pilih event untuk memfilter pengajuan logistik pada Dashboard.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahEvent">
            <i class="bi bi-plus-lg me-1"></i> Tambah Event Baru
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $message ?> <button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $error_msg ?> <button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="text-center text-muted py-5" style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                    <i class="bi bi-calendar-x d-block mb-3" style="font-size:3rem; opacity:0.5;"></i>
                    Belum ada event yang terdaftar di sistem.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $e): ?>
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="event-icon">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <span class="badge bg-warning text-dark border"><i class="bi bi-box-seam me-1"></i><?= $e['total_pengajuan'] ?> Pengajuan</span>
                    </div>
                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($e['nama_event']) ?></h5>
                    <p class="text-muted small mb-1"><i class="bi bi-clock me-1"></i> <?= date('d F Y', strtotime($e['tanggal'])) ?></p>
                    <p class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($e['lokasi']) ?></p>
                    
                    <div class="mt-auto pt-3 border-top">
                        <a href="detail_event.php?id=<?= $e['id_event'] ?>" class="btn btn-primary w-100">
                             <i class="bi bi-box-arrow-in-right me-1"></i> Lihat Logistik Event
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalTambahEvent" tabindex="-1" aria-labelledby="modalTambahEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form method="POST" action="" autocomplete="off">
                <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
                    <h5 class="modal-title fw-bold" id="modalTambahEventLabel"><i class="bi bi-calendar-plus me-2 text-primary"></i>Tambah Event Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Event <span class="text-danger">*</span></label>
                        <input type="text" name="nama_event" class="form-control" placeholder="Contoh: Seminar Nasional 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Auditorium Utama">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 mx-2 mb-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_event" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Event</button>
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