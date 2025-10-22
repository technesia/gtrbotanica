<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

function normalize_name(string $name): string {
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') return '';
  return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
}

function cleanse_users(PDO $pdo): array {
  $updated = 0; $deleted = 0;
  $rows = $pdo->query('SELECT id, blok_rumah, nama_warga, status FROM user ORDER BY id')->fetchAll();
  $seen = [];
  foreach ($rows as $r) {
    $normBlok = strtoupper(trim(preg_replace('/\s+/', ' ', $r['blok_rumah'])));
    $normNama = normalize_name($r['nama_warga']);
    $key = $normBlok . '|' . mb_strtolower($normNama);
    if (!isset($seen[$key])) {
      $seen[$key] = $r['id'];
      if ($normBlok !== $r['blok_rumah'] || $normNama !== $r['nama_warga']) {
        $u = $pdo->prepare('UPDATE user SET blok_rumah = ?, nama_warga = ? WHERE id = ?');
        $u->execute([$normBlok, $normNama, $r['id']]);
        $updated++;
      }
    } else {
      // Duplicate: remove later record
      $pdo->prepare('DELETE FROM user WHERE id = ?')->execute([$r['id']]);
      $deleted++;
    }
  }
  return ['updated'=>$updated, 'deleted'=>$deleted];
}

if ($method === 'GET') {
    // Support filters (q, blok); paginate only if page/limit provided
    $hasPagination = isset($_GET['page']) || isset($_GET['limit']);
    $filters = [];
    $params = [];
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $blok = isset($_GET['blok']) ? strtoupper(trim($_GET['blok'])) : '';

    if ($q !== '') {
        if ($blok === '') {
            // Single-field search: match either nama_warga OR blok_rumah
            $filters[] = '(LOWER(nama_warga) LIKE LOWER(?) OR UPPER(blok_rumah) LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . strtoupper($q) . '%';
        } else {
            // When blok filter is also provided, keep q as name filter (AND)
            $filters[] = 'LOWER(nama_warga) LIKE LOWER(?)';
            $params[] = '%' . $q . '%';
        }
    }
    if ($blok !== '') {
        $filters[] = 'UPPER(blok_rumah) LIKE ?';
        $params[] = '%' . $blok . '%';
    }

    $where = count($filters) ? ('WHERE ' . implode(' AND ', $filters)) : '';
    if ($hasPagination) {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, intval($_GET['limit'] ?? 10));
        try {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM user $where");
            $stmtCount->execute($params);
            $total = intval($stmtCount->fetchColumn());
            $offset = ($page - 1) * $limit;
            $sql = "SELECT id, blok_rumah, nama_warga, status FROM user $where ORDER BY blok_rumah, nama_warga LIMIT ? OFFSET ?";
            $stmt = $pdo->prepare($sql);
            // bind filters
            $idx = 1;
            foreach ($params as $p) { $stmt->bindValue($idx++, $p); }
            $stmt->bindValue($idx++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();
            $total_pages = max(1, (int)ceil($total / $limit));
            echo json_encode(['ok'=>true,'users'=>$users,'page'=>$page,'limit'=>$limit,'total'=>$total,'total_pages'=>$total_pages]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    } else {
        // No pagination: still apply filters if present
        if ($where) {
            $stmt = $pdo->prepare("SELECT id, blok_rumah, nama_warga, status FROM user $where ORDER BY blok_rumah, nama_warga");
            $stmt->execute($params);
            echo json_encode(['ok'=>true,'users'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->query('SELECT id, blok_rumah, nama_warga, status FROM user ORDER BY blok_rumah, nama_warga');
            echo json_encode(['ok'=>true,'users'=>$stmt->fetchAll()]);
        }
        exit;
    }
}

if ($method === 'POST') {
    if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin required']); exit; }

    // Special action: cleanse data
    if ($action === 'cleanse') {
      try {
        $result = cleanse_users($pdo);
        echo json_encode(['ok'=>true] + $result);
      } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
      }
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $blok = strtoupper(trim($input['blok_rumah'] ?? ''));
    $nama = normalize_name($input['nama_warga'] ?? '');
    $status = trim($input['status'] ?? 'huni');
    if (!$blok || !$nama) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Data tidak lengkap']); exit; }
    try {
        // Case-insensitive duplicate check
        $stmt = $pdo->prepare('SELECT id FROM user WHERE UPPER(blok_rumah) = ? AND LOWER(nama_warga) = LOWER(?)');
        $stmt->execute([$blok, $nama]);
        $row = $stmt->fetch();
        if ($row) {
            http_response_code(409);
            echo json_encode(['ok'=>false,'error'=>'nama & blok sudah ada']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO user (blok_rumah, nama_warga, status) VALUES (?,?,?)');
        $stmt->execute([$blok, $nama, $status]);
        echo json_encode(['ok'=>true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin required']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $blok = strtoupper(trim($input['blok_rumah'] ?? ''));
    $nama = normalize_name($input['nama_warga'] ?? '');
    $status = trim($input['status'] ?? '');
    if (!$id || !$blok || !$nama) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Data tidak lengkap']); exit; }
    try {
        // Duplicate check excluding current id
        $stmt = $pdo->prepare('SELECT id FROM user WHERE UPPER(blok_rumah) = ? AND LOWER(nama_warga) = LOWER(?) AND id <> ?');
        $stmt->execute([$blok, $nama, $id]);
        $row = $stmt->fetch();
        if ($row) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'nama & blok sudah ada']); exit; }
        $stmt = $pdo->prepare('UPDATE user SET blok_rumah = ?, nama_warga = ?, status = ? WHERE id = ?');
        $stmt->execute([$blok, $nama, ($status ?: 'huni'), $id]);
        echo json_encode(['ok'=>true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin required']); exit; }
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    $id = intval($_GET['id'] ?? ($input['id'] ?? 0));
    try {
      // bulk delete by array of ids
      if (!$id && isset($input['ids']) && is_array($input['ids']) && count($input['ids']) > 0) {
        $ids = array_values(array_filter(array_map('intval', $input['ids']), function($v){ return $v > 0; }));
        if (count($ids) === 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Daftar ID kosong']); exit; }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM user WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]);
        exit;
      }
      if ($id) {
        $stmt = $pdo->prepare('DELETE FROM user WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]);
        exit;
      }
      // support delete by blok+nama
      $blok = strtoupper(trim($_GET['blok_rumah'] ?? ($input['blok_rumah'] ?? '')));
      $nama = normalize_name($_GET['nama_warga'] ?? ($input['nama_warga'] ?? ''));
      if (!$blok || !$nama) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Parameter tidak lengkap']); exit; }
      $stmt = $pdo->prepare('SELECT id FROM user WHERE UPPER(blok_rumah) = ? AND LOWER(nama_warga) = LOWER(?)');
      $stmt->execute([$blok, $nama]);
      $row = $stmt->fetch();
      if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Data tidak ditemukan']); exit; }
      $stmt = $pdo->prepare('DELETE FROM user WHERE id = ?');
      $stmt->execute([$row['id']]);
      echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'Method not supported']);