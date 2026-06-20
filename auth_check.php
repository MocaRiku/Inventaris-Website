<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=Silakan login terlebih dahulu");
    exit;
}

function isAdmin() { return $_SESSION['user_role'] === 'Koor Perkap'; }
function isUser()  { return $_SESSION['user_role'] === 'Koor Divisi'; }

function requireAdmin() {
    if (!isAdmin()) { header("Location: ../login.php?error=Akses ditolak"); exit; }
}
function requireUser() {
    if (!isUser())  { header("Location: ../login.php?error=Akses ditolak"); exit; }
}
function currentDivisiId() { return $_SESSION['user_divisi'] ?? null; }