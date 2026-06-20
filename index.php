<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_role'] === 'Koor Perkap' ? "admin/dashboard.php" : "user/dashboard.php"));
} else {
    header("Location: login.php");
}
exit;