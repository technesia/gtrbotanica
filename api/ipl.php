<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$month_map = [
  'januari' => 'ipl_januari',
  'februari' => 'ipl_februari',
  'maret' => 'ipl_maret',
  'april' => 'ipl_april',
  'mei' => 'ipl_mei',
  'juni' => 'ipl_juni',
  'juli' => 'ipl_juli',
  'agustus' => 'ipl_agustus',
  'september' => 'ipl_september',
  'oktober' => 'ipl_oktober',
  'desember' => 'ipl_desember',
];

if ($method === 'GET') {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
    $totals = get_ipl_month_totals($pdo, $year);
    echo json_encode(['ok' => true, 'month_totals' => $totals, 'year' => $year]);
    exit;
}

if ($method === 'POST') {
    if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin required']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $blok = trim($input['blok'] ?? '');
    $no_rumah = trim($input['no_rumah'] ?? '');
    $nama_warga = trim($input['nama_warga'] ?? '');
    $status = trim($input['status'] ?? 'huni');
    $tahun = (int)($input['tahun'] ?? date('Y'));
    $bulan = strtolower(trim($input['bulan'] ?? ''));
    $nominal = (int)($input['nominal'] ?? 0);

    if (!$blok || !$no_rumah || !$nama_warga || !$bulan || $nominal < 0) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Data tidak lengkap']);
        exit;
    }
    if (!isset($month_map[$bulan])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Bulan tidak valid']);
        exit;
    }
    $column = $month_map[$bulan];

    try {
        $pdo->beginTransaction();
        // Ensure warga exists/updated
        $stmt = $pdo->prepare('SELECT id FROM user WHERE blok_rumah = ? AND nama_warga = ?');
        $stmt->execute([$blok, $nama_warga]);
        $user = $stmt->fetch();
        if ($user) {
            $stmt = $pdo->prepare('UPDATE user SET status = ? WHERE id = ?');
            $stmt->execute([$status, $user['id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO user (blok_rumah, nama_warga, status) VALUES (?,?,?)');
            $stmt->execute([$blok, $nama_warga, $status]);
        }
        // Upsert IPL row per tahun
        $stmt = $pdo->prepare('SELECT id FROM ipl WHERE blok = ? AND no_rumah = ? AND tahun = ?');
        $stmt->execute([$blok, $no_rumah, $tahun]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $pdo->prepare("UPDATE ipl SET nama_warga = ?, `$column` = ? WHERE id = ?");
            $stmt->execute([$nama_warga, $nominal, $row['id']]);
        } else {
            // Build insert columns
            $cols = ['blok','no_rumah','nama_warga','tahun', $column];
            $placeholders = '?,?,?,?,?';
            $stmt = $pdo->prepare("INSERT INTO ipl (".implode(',', $cols).") VALUES ($placeholders)");
            $stmt->execute([$blok, $no_rumah, $nama_warga, $tahun, $nominal]);
        }
        $pdo->commit();
        echo json_encode(['ok'=>true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Method not supported']);