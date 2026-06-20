<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_logistikG');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function redirect($url, $message = '') {
    if ($message) $_SESSION['flash_message'] = $message;
    header("Location: $url");
    exit;
}

function statusBadge($status) {
    $badge = ['Diajukan'=>'warning','Diproses'=>'info','Selesai'=>'success'];
    $class = isset($badge[$status]) ? $badge[$status] : 'secondary';
    return "<span class='badge bg-$class'>$status</span>";
}

function metodeBadge($metode) {
    if (!$metode) return "<span class='badge bg-secondary'>—</span>";
    $class = ($metode === 'BELI') ? 'primary' : 'dark';
    return "<span class='badge bg-$class'>$metode</span>";
}