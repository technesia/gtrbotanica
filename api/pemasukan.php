<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function read_json_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    $json = json_decode($raw, true);
    if (is_array($json)) { return $json; }
    // Fallback ke POST form-urlencoded
    return $_POST ?: [];
}

try {
    if ($method === 'GET') {
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
        $stmt = $pdo->prepare('SELECT id, tanggal, keterangan, jumlah FROM pemasukan ORDER BY tanggal DESC, id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['ok'=>true, 'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }
        $data = read_json_body();
        $tanggal = trim($data['tanggal'] ?? date('Y-m-d'));
        $keterangan = trim($data['keterangan'] ?? '');
        $jumlah = (int)($data['jumlah'] ?? 0);
        if (!$tanggal || $jumlah <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Tanggal/jumlah tidak valid']); exit; }
        $stmt = $pdo->prepare('INSERT INTO pemasukan (tanggal, jumlah, keterangan) VALUES (:tanggal, :jumlah, :keterangan)');
        $stmt->bindValue(':tanggal', $tanggal);
        $stmt->bindValue(':jumlah', $jumlah, PDO::PARAM_INT);
        $stmt->bindValue(':keterangan', $keterangan);
        $stmt->execute();
        echo json_encode(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok'=>false, 'error'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}