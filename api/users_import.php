<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin required']); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not supported']); exit; }

function normalize_name(string $name): string {
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') return '';
  return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
}

try {
  $pdo = get_pdo();
  ensure_sqlite_schema($pdo);
  $tahun = intval($_POST['tahun'] ?? 0) ?: intval(date('Y'));
  if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'File tidak ditemukan']);
    exit;
  }
  $tmp = $_FILES['file']['tmp_name'];
  $name = $_FILES['file']['name'] ?? 'data.csv';
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext !== 'csv' && $ext !== 'xlsx') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Format tidak didukung. Gunakan CSV (export dari Excel).']);
    exit;
  }

  // Baca CSV (untuk .xlsx, minta user export ke CSV)
  $fh = fopen($tmp, 'r');
  if (!$fh) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Gagal membuka file']); exit; }
  // Deteksi delimiter
  $first = fgets($fh);
  $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
  // Kembali ke awal
  rewind($fh);
  $headers = fgetcsv($fh, 0, $delim);
  if (!$headers) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Header CSV tidak ditemukan']); exit; }
  $lower = array_map(fn($h)=>mb_strtolower(trim((string)$h),'UTF-8'), $headers);
  // Peta kolom yang diharapkan
  $idxNo = array_search('no.', $lower);
  $idxBlok = array_search('blok', $lower);
  $idxNoRumah = array_search('no rumah', $lower);
  $idxNama = array_search('nama warga', $lower);
  $idxStatus = array_search('status', $lower);
  if ($idxBlok===false || $idxNoRumah===false || $idxNama===false || $idxStatus===false) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Kolom wajib: NO., BLOK, NO RUMAH, NAMA WARGA, STATUS']);
    exit;
  }

  $inserted=0; $updated=0; $skipped=0; $ipl_upserted=0;

  // Transaksi untuk kecepatan
  $pdo->beginTransaction();
  while (($row = fgetcsv($fh, 0, $delim)) !== false) {
    // Lewati baris kosong
    if (count($row) < max($idxBlok,$idxNoRumah,$idxNama,$idxStatus)+1) { $skipped++; continue; }
    $blok = strtoupper(trim(preg_replace('/\s+/', ' ', (string)($row[$idxBlok] ?? ''))));
    $noRumah = trim(preg_replace('/\s+/', ' ', (string)($row[$idxNoRumah] ?? '')));
    $nama = normalize_name((string)($row[$idxNama] ?? ''));
    $statusRaw = mb_strtolower(trim((string)($row[$idxStatus] ?? 'huni')),'UTF-8');
    $status = ($statusRaw === 'tidak_huni' || $statusRaw === 'tidak huni' || $statusRaw === 'tidak-huni') ? 'tidak_huni' : 'huni';

    if ($blok === '' || $noRumah === '' || $nama === '') { $skipped++; continue; }

    $blokRumah = trim($blok . ' ' . $noRumah);

    // Upsert user: cek duplikasi case-insensitive
    $stmt = $pdo->prepare('SELECT id FROM user WHERE UPPER(blok_rumah) = ? AND LOWER(nama_warga) = LOWER(?)');
    $stmt->execute([$blokRumah, $nama]);
    $rowUser = $stmt->fetch();
    if ($rowUser) {
      $u = $pdo->prepare('UPDATE user SET blok_rumah = ?, nama_warga = ?, status = ? WHERE id = ?');
      $u->execute([$blokRumah, $nama, $status, $rowUser['id']]);
      $updated++;
    } else {
      $u = $pdo->prepare('INSERT INTO user (blok_rumah, nama_warga, status) VALUES (?,?,?)');
      $u->execute([$blokRumah, $nama, $status]);
      $inserted++;
    }

    // Upsert IPL untuk tahun
    $stmt = $pdo->prepare('SELECT id FROM ipl WHERE blok = ? AND no_rumah = ? AND tahun = ?');
    $stmt->execute([$blok, $noRumah, $tahun]);
    $rowIpl = $stmt->fetch();
    if ($rowIpl) {
      $u = $pdo->prepare('UPDATE ipl SET nama_warga = ? WHERE id = ?');
      $u->execute([$nama, $rowIpl['id']]);
      $ipl_upserted++;
    } else {
      $i = $pdo->prepare('INSERT INTO ipl (blok, no_rumah, nama_warga, tahun) VALUES (?,?,?,?)');
      $i->execute([$blok, $noRumah, $nama, $tahun]);
      $ipl_upserted++;
    }
  }
  fclose($fh);
  $pdo->commit();

  echo json_encode(['ok'=>true,'inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped,'ipl_upserted'=>$ipl_upserted,'tahun'=>$tahun]);
} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}