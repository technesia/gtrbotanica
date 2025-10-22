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
        <div class="logo-badge"><img src="/Logo1.png" alt="Logo" class="logo" onerror="this.src='/assets/fallback-logo.svg';" /></div>
        <div>
            <div class="title">Grand Tenjo Residence</div>
            <div class="subtitle">Cluster Botanica</div>
        </div>
    </a>
    <div class="menu-trigger">
         <button class="burger" id="burgerBtn" aria-label="Buka menu" aria-controls="site-nav" aria-expanded="false">
             <span class="burger-bar"></span>
             <span class="burger-bar"></span>
             <span class="burger-bar"></span>
         </button>
         <span class="burger-label">MENU</span>
     </div>
     <nav id="site-nav" class="navbar">
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
    
    <!-- Auth (mobile/tablet) inside burger menu -->
    <div class="nav-auth-mobile">
      <?php if (is_logged_in()): ?>
        <a href="/logout.php" class="btn">Logout</a>
      <?php else: ?>
        <a href="/login.php" class="btn primary">Masuk</a>
      <?php endif; ?>
    </div>
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
<!-- Floating arrow toggle and sidebar overlay -->
<button id="arrowBtn" class="floating-arrow" aria-label="Buka Menu" aria-expanded="false"><span class="arrow-icon"></span></button>
<div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>
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
<script>
(function(){
  var burger = document.getElementById('burgerBtn');
  var arrow = document.getElementById('arrowBtn');
  var nav = document.getElementById('site-nav');
  var overlay = document.getElementById('sidebarOverlay');
  if(!nav) return;
  function toggleNav(btn){
    var open = nav.classList.toggle('open');
    if(btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if(overlay){ overlay.classList.toggle('open', open); }
    document.body.classList.toggle('no-scroll', open);
  }
  if(burger){ burger.addEventListener('click', function(e){ e.preventDefault(); toggleNav(burger); }); }
  if(arrow){ arrow.addEventListener('click', function(e){ e.preventDefault(); toggleNav(arrow); }); }
  if(overlay){ overlay.addEventListener('click', function(){
    nav.classList.remove('open');
    if(burger) burger.setAttribute('aria-expanded','false');
    if(arrow) arrow.setAttribute('aria-expanded','false');
    overlay.classList.remove('open');
    document.body.classList.remove('no-scroll');
  }); }
})();
</script>
<main class="container">