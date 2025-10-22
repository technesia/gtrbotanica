<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Kolom bulan IPL yang tersedia (mengacu ke skema SQLite/MySQL saat ini)
$monthCols = [
    'ipl_januari','ipl_februari','ipl_maret','ipl_april','ipl_mei',
    'ipl_juni','ipl_juli','ipl_agustus','ipl_september','ipl_oktober','ipl_desember'
];
$monthMap = [
    'januari'=>'ipl_januari','februari'=>'ipl_februari','maret'=>'ipl_maret','april'=>'ipl_april','mei'=>'ipl_mei',
    'juni'=>'ipl_juni','juli'=>'ipl_juli','agustus'=>'ipl_agustus','september'=>'ipl_september','oktober'=>'ipl_oktober','november'=>'ipl_november','desember'=>'ipl_desember'
];
$bulanNames = [
    'januari'=>'Januari','februari'=>'Februari','maret'=>'Maret','april'=>'April','mei'=>'Mei','juni'=>'Juni','juli'=>'Juli','agustus'=>'Agustus','september'=>'September','oktober'=>'Oktober','november'=>'November','desember'=>'Desember'
];

function sumIplRow(array $row, array $monthCols): int {
    $sum = 0;
    foreach ($monthCols as $c) { $sum += (int)($row[$c] ?? 0); }
    return $sum;
}

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method tidak didukung']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$month = strtolower(trim($_GET['month'] ?? 'all'));
$year = intval($_GET['year'] ?? date('Y'));

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = 'SELECT id, blok, no_rumah, nama_warga, ' . implode(',', array_map(fn($c)=>"`$c`", $monthCols)) . ' FROM ipl';
    $params = [];
    if ($driver === 'sqlite') {
        $sql .= ' WHERE tahun = :year ORDER BY blok, no_rumah';
        $params[':year'] = $year;
    } else {
        // MySQL: jika kolom tahun ada, filter; jika tidak, tetap berjalan tanpa WHERE
        try {
            $check = $pdo->query("SHOW COLUMNS FROM ipl LIKE 'tahun'");
            if ($check && $check->fetch()) {
                $sql .= ' WHERE tahun = :year ORDER BY blok, no_rumah';
                $params[':year'] = $year;
            } else {
                $sql .= ' ORDER BY blok, no_rumah';
            }
        } catch (Throwable $e) {
            $sql .= ' ORDER BY blok, no_rumah';
        }
    }
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $paid = [];
    $unpaid = [];
    foreach ($rows as $r) {
        $total = 0;
        if ($month === 'all' || !isset($monthMap[$month])) {
            $total = sumIplRow($r, $monthCols);
        } else {
            $col = $monthMap[$month];
            $total = (int)($r[$col] ?? 0);
        }
        $item = [
            'id' => (int)$r['id'],
            'blok' => $r['blok'],
            'no_rumah' => $r['no_rumah'],
            'nama_warga' => $r['nama_warga'],
            'total_bayar' => $total,
        ];
        if ($total > 0) { $paid[] = $item; } else { $unpaid[] = $item; }
    }

    // Filter pencarian jika ada query
    if ($q !== '') {
        $qLower = mb_strtolower($q, 'UTF-8');
        $filterFn = function($it) use ($qLower) {
            return (
                mb_strpos(mb_strtolower($it['nama_warga'], 'UTF-8'), $qLower) !== false ||
                mb_strpos(mb_strtolower($it['blok'], 'UTF-8'), $qLower) !== false ||
                mb_strpos(mb_strtolower($it['no_rumah'], 'UTF-8'), $qLower) !== false
            );
        };
        $paid = array_values(array_filter($paid, $filterFn));
        $unpaid = array_values(array_filter($unpaid, $filterFn));
    }

    // Ringkasan untuk kartu
    $counts = [
        'total_unit' => count($rows),
        'warga_terdaftar' => (int)($pdo->query('SELECT COUNT(*) AS c FROM user')->fetch()['c'] ?? 0),
        'sudah_bayar' => count($paid),
        'belum_bayar' => count($unpaid),
    ];

    $periode = [ 'tahun' => $year, 'bulan' => ($month === 'all' ? 'Semua Bulan' : ($bulanNames[$month] ?? 'Periode')) ];

    echo json_encode([
        'ok' => true,
        'counts' => $counts,
        'paid' => $paid,
        'unpaid' => $unpaid,
        'periode' => $periode,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}