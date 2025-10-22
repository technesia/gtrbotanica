<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();

$successMsg = '';
$errorMsg = '';

function sanitize_filename($name){
  $name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $name);
  return substr($name, 0, 120);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $nomor_rumah = trim($_POST['nomor_rumah'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $bulan = trim($_POST['bulan'] ?? '');
    $bulan_lain = trim($_POST['bulan_lain'] ?? '');
    $nominal = (int)($_POST['nominal'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');

    if ($bulan === 'other') { $bulan = $bulan_lain ?: 'Other'; }

    if (!$nomor_rumah || !$nama_lengkap || !$whatsapp || !$bulan || !$nominal || !$tanggal) {
      throw new Exception('Mohon lengkapi semua field bertanda *');
    }

    if (!isset($_FILES['bukti']) || ($_FILES['bukti']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      throw new Exception('File bukti pembayaran wajib diunggah');
    }

    $file = $_FILES['bukti'];
    if ($file['error'] !== UPLOAD_ERR_OK) { throw new Exception('Gagal mengunggah file: kode ' . $file['error']); }
    $maxSize = 10 * 1024 * 1024; // 10 MB
    if ($file['size'] > $maxSize) { throw new Exception('Ukuran file melebihi 10 MB'); }

    $allowed_ext = ['jpg','jpeg','png','pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) { throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau PDF'); }

    // Simpan di dalam docroot agar bisa diakses via URL
    $destDir = __DIR__ . '/uploads/bukti';
    if (!is_dir($destDir)) { mkdir($destDir, 0777, true); }

    $base = sanitize_filename(pathinfo($file['name'], PATHINFO_FILENAME));
    $newName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $base . '.' . $ext;
    $destPath = $destDir . '/' . $newName;
    $urlPath = '/uploads/bukti/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) { throw new Exception('Gagal menyimpan file bukti pembayaran'); }

    // Buat tabel jika belum ada (SQLite/MySQL)
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

    // Simpan metadata + auto-entry pemasukan dalam transaksi
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO bukti_transfer (nomor_rumah, nama_lengkap, whatsapp, bulan, nominal, tanggal, filename, stored_path, created_at) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$nomor_rumah, $nama_lengkap, $whatsapp, $bulan, $nominal, $tanggal, $newName, $urlPath, date('Y-m-d H:i:s')]);

    // Tanggal pencatatan pemasukan mengikuti pilihan bulan (agar masuk ke periode yang dipilih)
    $ledgerDate = $tanggal;
    $bl = strtolower(trim($bulan));
    $mmap = [
      'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
      'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
    ];
    if (preg_match('/(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i', $bl, $mm)) {
      $mon = $mmap[strtolower($mm[1])] ?? null;
      $yr = (int)($mm[2] ?? 0);
      if ($mon && $yr) { $ledgerDate = sprintf('%04d-%02d-01', $yr, $mon); }
    }

    $ket = 'Bukti Transfer IPL ' . $bulan . ' - ' . $nama_lengkap . ' (' . $nomor_rumah . ')';
    $stmt2 = $pdo->prepare('INSERT INTO pemasukan (tanggal, jumlah, keterangan) VALUES (?,?,?)');
    $stmt2->execute([$ledgerDate, $nominal, $ket]);

    $pdo->commit();

    $successMsg = 'Bukti pembayaran berhasil diunggah dan otomatis tercatat sebagai pemasukan.';
  } catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    if (isset($destPath) && is_file($destPath)) { @unlink($destPath); }
    $errorMsg = $e->getMessage();
  }
}
?>

<section class="hero" style="padding-top:24px;padding-bottom:8px;">
  <h1 style="margin:0;font-weight:700;">Form Pembayaran IPL Botanica GTR</h1>
  <p style="margin-top:6px;color:#4b5563;">Pembayaran IPL dapat dilakukan melalui rekening di bawah ini dengan mengupload bukti pembayaran.</p>
</section>

<div class="card" style="margin-top:8px;">
  <div class="small" style="margin-bottom:8px;">
    <div><strong>Bank</strong>: BSI</div>
    <div><strong>No Rek</strong>: 7283407033</div>
    <div><strong>Atas Nama</strong>: Paguyuban Botanica GTR</div>
  </div>

  <?php if ($successMsg): ?>
    <div class="small" style="padding:8px;background:#e6ffed;color:#0b7;border:1px solid #b3f5c3;margin-bottom:10px;"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="small" style="padding:8px;background:#ffecec;color:#b00;border:1px solid #ffb3b3;margin-bottom:10px;"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <form class="form" method="POST" action="/upload_bukti.php" enctype="multipart/form-data" onsubmit="return validateUploadForm();">
    <label>Nomor Rumah (contoh: BB2/1)*</label>
    <input class="input" name="nomor_rumah" id="nomor_rumah" placeholder="BB2/1" required />

    <label>Nama Lengkap Pemilik Rumah*</label>
    <input class="input" name="nama_lengkap" id="nama_lengkap" placeholder="Nama lengkap" required />

    <label>Kontak Whatsapp*</label>
    <input class="input" name="whatsapp" id="whatsapp" placeholder="0812xxxxxx" required />

    <label>Pembayaran Bulan*</label>
    <select class="input" name="bulan" id="bulan" required onchange="toggleOtherBulan()">
      <?php
        $bulanNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        foreach ($bulanNames as $bn) {
          echo '<option value="' . strtolower($bn) . ' 2025">' . $bn . ' 2025</option>';
        }
      ?>
      <option value="other">Other</option>
    </select>
    <input class="input" name="bulan_lain" id="bulan_lain" placeholder="contoh: Januari 2026" style="display:none;" />

    <label>Nominal IPL*</label>
    <select class="input" name="nominal" id="nominal" required>
      <option value="100000">Huni 100.000</option>
      <option value="65000">Tidak Huni 65.000</option>
    </select>

    <label>Upload Bukti Bayar* <span class="small">(Upload 1 supported file. Max 10 MB)</span></label>
    <input class="input" type="file" name="bukti" id="bukti" accept="image/*,.pdf" required />

    <label>Tanggal Transfer IPL*</label>
    <input class="input" type="date" name="tanggal" id="tanggal" required />

    <div style="display:flex; gap:8px; align-items:center; margin-top:10px;">
      <button class="button" type="submit">Upload</button>
      <a class="button secondary" href="/dashboard.php?view=laporan">Kembali ke Dashboard</a>
    </div>
  </form>
</div>

<script>
function toggleOtherBulan(){
  const sel = document.getElementById('bulan');
  const other = document.getElementById('bulan_lain');
  if (sel.value === 'other') { other.style.display = 'block'; other.required = true; }
  else { other.style.display = 'none'; other.required = false; }
}

function validateUploadForm(){
  const fileInput = document.getElementById('bukti');
  if (!fileInput.files || fileInput.files.length !== 1) { alert('Unggah tepat 1 file bukti pembayaran.'); return false; }
  const f = fileInput.files[0];
  const maxBytes = 10 * 1024 * 1024;
  if (f.size > maxBytes) { alert('Ukuran file melebihi 10 MB.'); return false; }
  const allowed = ['image/jpeg','image/png','application/pdf'];
  if (allowed.indexOf(f.type) === -1 && !f.name.toLowerCase().endsWith('.pdf')) {
    alert('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
    return false;
  }
  return true;
}

window.addEventListener('DOMContentLoaded', () => {
  const t = document.getElementById('tanggal');
  if (t && !t.value) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    t.value = `${yyyy}-${mm}-${dd}`;
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>