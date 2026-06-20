<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Koor Perkap') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $conn = getConnection();

        $sql = "SELECT u.id_user, u.username, u.password, u.nama_lengkap, 
                       u.id_divisi, r.nama_role
                FROM tabel_user u
                JOIN tabel_role r ON u.id_role = r.id_role
                WHERE u.username = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username); 
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if ($password === $user['password']) {
                $_SESSION['user_id']      = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['user_role']    = $user['nama_role'];
                $_SESSION['user_divisi']  = $user['id_divisi'];

                if ($user['nama_role'] === 'Koor Perkap') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit;
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Username tidak ditemukan.";
        }
        mysqli_close($conn);
    } else {
        $error = "Harap isi username dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" style="">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>LogistikG - Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-container": "#f0edef",
                        "tertiary": "#1e1200",
                        "surface": "#fbf8fa",
                        "surface-container-highest": "#e4e2e3",
                        "on-surface": "#1b1b1d",
                        "primary": "#091426",
                        "on-secondary-container": "#fefcff",
                        "on-secondary-fixed-variant": "#004395",
                        "on-tertiary-fixed-variant": "#564427",
                        "surface-tint": "#545f73",
                        "surface-container-high": "#eae7e9",
                        "secondary-fixed-dim": "#adc6ff",
                        "error-container": "#ffdad6",
                        "primary-fixed-dim": "#bcc7de",
                        "on-error": "#ffffff",
                        "primary-container": "#1e293b",
                        "surface-container-low": "#f5f3f4",
                        "inverse-primary": "#bcc7de",
                        "on-error-container": "#93000a",
                        "on-surface-variant": "#45474c",
                        "on-tertiary": "#ffffff",
                        "tertiary-fixed": "#fadfb8",
                        "secondary-fixed": "#d8e2ff",
                        "surface-variant": "#e4e2e3",
                        "tertiary-fixed-dim": "#ddc39d",
                        "on-primary-fixed": "#111c2d",
                        "on-primary-fixed-variant": "#3c475a",
                        "inverse-on-surface": "#f3f0f2",
                        "on-secondary-fixed": "#001a42",
                        "surface-container-lowest": "#ffffff",
                        "error": "#ba1a1a",
                        "primary-fixed": "#d8e3fb",
                        "on-primary": "#ffffff",
                        "on-background": "#1b1b1d",
                        "on-primary-container": "#8590a6",
                        "outline-variant": "#c5c6cd",
                        "surface-dim": "#dcd9db",
                        "on-secondary": "#ffffff",
                        "inverse-surface": "#303032",
                        "background": "#fbf8fa",
                        "on-tertiary-container": "#a38c6a",
                        "outline": "#75777d",
                        "surface-bright": "#fbf8fa",
                        "tertiary-container": "#35260c",
                        "secondary-container": "#2170e4",
                        "secondary": "#0058be",
                        "on-tertiary-fixed": "#271902"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "sidebar-width": "260px",
                        "row-height-dense": "2.5rem",
                        "gutter": "1rem",
                        "row-height-default": "3.5rem",
                        "container-padding": "1.5rem"
                    },
                    "fontFamily": {
                        "label-caps": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-base": ["Inter"],
                        "body-sm": ["Inter"],
                        "headline-md": ["Inter"],
                        "data-table": ["Inter"]
                    },
                    "fontSize": {
                        "label-caps": ["11px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "headline-lg": ["28px", { "lineHeight": "36px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "body-base": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "body-sm": ["13px", { "lineHeight": "18px", "fontWeight": "400" }],
                        "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "data-table": ["13.5px", { "lineHeight": "18px", "fontWeight": "400" }]
                    }
                }
            }
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex items-center justify-center font-body-base antialiased relative overflow-hidden">
<div class="absolute inset-0 z-0">
<div class="bg-cover bg-center w-full h-full opacity-20" data-alt="A modern, high-tech logistics warehouse interior viewed from a high angle." style="background-image: url(&quot;https://plus.unsplash.com/premium_photo-1663046050988-1b873a56dced?q=80&amp;w=1170&amp;auto=format&amp;fit=crop&amp;ixlib=rb-4.1.0&amp;ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&quot;);"></div>
<div class="absolute inset-0 bg-gradient-to-t from-primary-container via-primary-container/80 to-transparent"></div>
</div>
<div class="relative z-10 w-full max-w-lg px-4 sm:px-container-padding py-8">
<div class="text-center mb-8">
<h1 class="font-headline-lg text-4xl sm:text-5xl font-black text-on-primary mb-2 tracking-tight">LogistikG</h1>
<p class="font-body-base text-[14px] sm:text-[16px] font-semibold text-black tracking-wide">Operations Portal</p>
</div>
<div class="bg-surface rounded-xl shadow-[0px_10px_15px_-3px_rgba(0,0,0,0.1)] border border-outline-variant p-6 sm:p-8">
<form action="" class="space-y-6" method="POST" autocomplete="off">

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm" role="alert">
        <span class="block sm:inline"><?= htmlspecialchars($_GET['error']) ?></span>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm" role="alert">
        <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div>
<label class="block font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase tracking-wider" for="username">Username</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-[18px]">person</span>
</div>
<input class="block w-full pl-10 pr-3 py-2 border border-outline-variant rounded bg-surface text-on-surface font-body-base focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors" id="username" name="username" placeholder="Enter your username" type="text" required autofocus>
</div>
</div>
<div>
<label class="block font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase tracking-wider" for="password">Password</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-[18px]">lock</span>
</div>
<input class="block w-full pl-10 pr-3 py-2 border border-outline-variant rounded bg-surface text-on-surface font-body-base focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors" id="password" name="password" placeholder="Enter your password" type="password" required>
</div>
</div>

<div>
<button class="w-full flex justify-center py-2.5 px-4 mt-2 border border-transparent rounded bg-primary-container text-on-primary font-headline-md text-headline-md text-[16px] shadow-sm hover:bg-primary-container/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-container transition-colors" type="submit">
                        Login
                    </button>
</div>
</form>
<div class="mt-4 text-center">
    <p class="font-body-sm text-[10px] sm:text-[11px] text-on-surface-variant">
        Demo: <code class="bg-gray-100 px-1 rounded">perkap</code> / <code class="bg-gray-100 px-1 rounded">admin123</code> <br class="sm:hidden">&nbsp;|&nbsp; <code class="bg-gray-100 px-1 rounded">acara_seminar</code> / <code class="bg-gray-100 px-1 rounded">divisi123</code>
    </p>
</div>
</div>
<div class="mt-6 sm:mt-8 text-center">
<p class="font-label-caps text-label-caps text-on-primary-container/60 uppercase tracking-widest">
<span class="material-symbols-outlined inline-block align-middle text-[14px] mr-1">shield</span>&nbsp;Logistics Management&nbsp;</p>
</div>
</div>
</body></html>