<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Grand Tenjo Residence - Cluster Botanica</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>
<header class="site-header">
    <a href="/index.php" class="brand" aria-label="Beranda">
        <div class="logo-badge"><img src="/assets/fallback-logo.svg" alt="Logo" class="logo" /></div>
        <div>
            <div class="title">Grand Tenjo Residence</div>
            <div class="subtitle">Cluster Botanica</div>
        </div>
    </a>
    <nav class="navbar">
        <a href="/index.php">Home</a>
        <div class="dropdown">
            <a href="#" class="dropbtn">Dashboard ▾</a>
            <div class="dropdown-content">
                <a href="/dashboard.php?view=laporan">Laporan Keuangan</a>
                <a href="/dashboard.php?view=warga">Daftar Warga</a>
                <a href="/upload_bukti.php">Upload Bukti Transfer</a>
            </div>
        </div>
        <?php if (is_admin()): ?>
        <div class="dropdown">
            <a href="#" class="dropbtn">Master Data ▾</a>
            <div class="dropdown-content">
                <a href="/isi_warga.php">Isi Data Warga</a>
                <a href="/input_ipl.php">Input IPL</a>
                <a href="/input_pemasukan.php">Input Pemasukan</a>
                <a href="/input_pengeluaran.php">Input Pengeluaran</a>
                <a href="/bukti_transfer.php">Daftar Bukti Transfer</a>
                <a href="/rangkuman.php">Rangkuman</a>
            </div>
        </div>
        <?php else: ?>
            <!-- Non-admin sees single link to Rangkuman only if desired -->
        <?php endif; ?>
        <a href="/about.php">About Us</a>
    </nav>
    <div class="auth-links">
        <?php if (is_logged_in()): ?>
            <span>Hi, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'user') ?></span>
            <a href="/logout.php" class="btn">Logout</a>
        <?php else: ?>
            <a href="/login.php" class="btn primary">Login</a>
        <?php endif; ?>
    </div>
</header>
<script>
// Try to load PNG; if success, swap into the visible <img>
window.addEventListener('DOMContentLoaded', function(){
  var logoImg = document.querySelector('.brand .logo');
  if(!logoImg) return;
  var test = new Image();
  test.onload = function(){ logoImg.src = '/Logo1.png?cb=' + Date.now(); };
  test.onerror = function(){ /* keep fallback SVG */ };
  test.src = '/Logo1.png?cb=' + Date.now();
});
</script>
<main class="container">