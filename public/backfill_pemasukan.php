<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();

function parse_bulan_ledger_date(string $bulan, string $fallback): string {
  $mmap = [
    'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
    'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
  ];
  $bl = strtolower(trim($bulan));
  if (preg_match('/(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i', $bl, $mm)) {
    $mon = $mmap[strtolower($mm[1])] ?? null;
    $yr = (int)($mm[2] ?? 0);
    if ($mon && $yr) { return sprintf('%04d-%02d-01', $yr, $mon); }
  }
  return $fallback;
}

$inserted = 0; $skipped = 0; $errors = [];
try {
  $rows = $pdo->query('SELECT id, nomor_rumah, nama_lengkap, bulan, nominal, tanggal FROM bukti_transfer ORDER BY id ASC')->fetchAll();
  foreach ($rows as $r) {
    $ket = 'Bukti Transfer IPL ' . ($r['bulan'] ?? '') . ' - ' . ($r['nama_lengkap'] ?? '') . ' (' . ($r['nomor_rumah'] ?? '') . ')';
    $ledgerDate = parse_bulan_ledger_date((string)($r['bulan'] ?? ''), (string)($r['tanggal'] ?? date('Y-m-d')));
    $stmt = $pdo->prepare('SELECT COUNT(1) AS c FROM pemasukan WHERE jumlah = ? AND keterangan = ?');
    $stmt->execute([(int)($r['nominal'] ?? 0), $ket]);
    $exists = (int)($stmt->fetch()['c'] ?? 0) > 0;
    if ($exists) { $skipped++; continue; }
    $stmt2 = $pdo->prepare('INSERT INTO pemasukan (tanggal, jumlah, keterangan) VALUES (?,?,?)');
    $stmt2->execute([$ledgerDate, (int)($r['nominal'] ?? 0), $ket]);
    $inserted++;
  }
} catch (Throwable $e) {
  $errors[] = $e->getMessage();
}
?>
<section class="hero" style="padding-top:24px;padding-bottom:8px;">
  <h1 style="margin:0;font-weight:700;">Backfill Pemasukan dari Bukti Transfer</h1>
  <p class="small" style="margin-top:6px;color:#4b5563;">Menyalin bukti transfer yang belum tercatat sebagai pemasukan.</p>
</section>
<div class="card" style="margin-top:8px;">
  <div class="small">Inserted: <?= (int)$inserted ?>, Skipped: <?= (int)$skipped ?></div>
  <?php if (!empty($errors)): ?>
    <div class="small" style="padding:8px;background:#ffecec;color:#b00;border:1px solid #ffb3b3;margin-top:8px;">Error: <?= htmlspecialchars(implode('; ', $errors)) ?></div>
  <?php endif; ?>
  <div style="margin-top:8px;">
    <a class="button" href="/dashboard.php?view=laporan">Ke Dashboard</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>