<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();

// Pastikan tabel ada agar halaman tidak error meski belum ada upload
try {
  $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
  if ($driver === 'sqlite') {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bukti_transfer (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nomor_rumah TEXT NOT NULL,
      nama_lengkap TEXT NOT NULL,
      whatsapp TEXT NOT NULL,
      bulan TEXT NOT NULL,
      nominal INTEGER NOT NULL,
      tanggal TEXT NOT NULL,
      filename TEXT NOT NULL,
      stored_path TEXT NOT NULL,
      created_at TEXT DEFAULT (datetime('now'))
    );");
  } else {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bukti_transfer (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nomor_rumah VARCHAR(64) NOT NULL,
      nama_lengkap VARCHAR(128) NOT NULL,
      whatsapp VARCHAR(64) NOT NULL,
      bulan VARCHAR(64) NOT NULL,
      nominal INT NOT NULL,
      tanggal DATE NOT NULL,
      filename VARCHAR(255) NOT NULL,
      stored_path VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  }
} catch (Throwable $e) {
  // Abaikan error create table; tampilkan di bawah bila terjadi
}

function formatRupiah($n){ return 'Rp ' . number_format((int)($n ?? 0), 0, ',', '.'); }

$rows = [];$errorMsg='';
try {
  $stmt = $pdo->query('SELECT id, created_at, nomor_rumah, nama_lengkap, whatsapp, bulan, nominal, tanggal, stored_path FROM bukti_transfer ORDER BY created_at DESC, id DESC');
  $rows = $stmt->fetchAll();
} catch (Throwable $e) { $errorMsg = $e->getMessage(); }
?>

<section class="hero" style="padding-top:24px;padding-bottom:8px;">
  <h1 style="margin:0;font-weight:700;">Daftar Bukti Transfer</h1>
  <p style="margin-top:6px;color:#4b5563;">Review bukti pembayaran IPL yang diunggah warga</p>
</section>

<div class="card" style="margin-top:8px;">
  <?php if ($errorMsg): ?>
    <div class="small" style="padding:8px;background:#ffecec;color:#b00;border:1px solid #ffb3b3;margin-bottom:10px;">Error: <?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>
  <table class="table" style="width:100%;">
    <thead>
      <tr>
        <th style="white-space:nowrap;">Diupload</th>
        <th>Nomor Rumah</th>
        <th>Nama Lengkap</th>
        <th>WhatsApp</th>
        <th>Bulan</th>
        <th style="text-align:right;">Nominal</th>
        <th>Tanggal Transfer</th>
        <th>Bukti</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="small">Belum ada bukti yang diunggah.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="small" style="white-space:nowrap;"><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['nomor_rumah']) ?></td>
          <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
          <td><?= htmlspecialchars($r['whatsapp']) ?></td>
          <td><?= htmlspecialchars($r['bulan']) ?></td>
          <td style="text-align:right;"><?= formatRupiah($r['nominal']) ?></td>
          <td><?= htmlspecialchars($r['tanggal']) ?></td>
          <td><a href="<?= htmlspecialchars($r['stored_path']) ?>" target="_blank">Lihat</a></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>