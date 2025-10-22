<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();

$kind = $_GET['kind'] ?? 'summary';
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$month = strtolower(trim($_GET['month'] ?? '')) ?: 'all';

function bulan_map(): array {
    return [
        'januari' => ['col' => 'ipl_januari', 'num' => 1, 'name' => 'Januari'],
        'februari' => ['col' => 'ipl_februari', 'num' => 2, 'name' => 'Februari'],
        'maret' => ['col' => 'ipl_maret', 'num' => 3, 'name' => 'Maret'],
        'april' => ['col' => 'ipl_april', 'num' => 4, 'name' => 'April'],
        'mei' => ['col' => 'ipl_mei', 'num' => 5, 'name' => 'Mei'],
        'juni' => ['col' => 'ipl_juni', 'num' => 6, 'name' => 'Juni'],
        'juli' => ['col' => 'ipl_juli', 'num' => 7, 'name' => 'Juli'],
        'agustus' => ['col' => 'ipl_agustus', 'num' => 8, 'name' => 'Agustus'],
        'september' => ['col' => 'ipl_september', 'num' => 9, 'name' => 'September'],
        'oktober' => ['col' => 'ipl_oktober', 'num' => 10, 'name' => 'Oktober'],
        // November tidak ada kolom IPL di skema saat ini; tetap izinkan filter bulan
        'november' => ['col' => null, 'num' => 11, 'name' => 'November'],
        'desember' => ['col' => 'ipl_desember', 'num' => 12, 'name' => 'Desember'],
    ];
}

try {
    if ($kind === 'pengeluaran_per_bulan') {
        $data = get_pengeluaran_per_bulan($pdo, $year);
        echo json_encode(['ok'=>true,'data'=>$data]);
        exit;
    }

    if ($kind === 'summary') {
        $total_ipl = sum_ipl_all_months($pdo, $year);
        $stmt = $pdo->query('SELECT COALESCE(SUM(jumlah),0) AS total FROM pemasukan');
        $row = $stmt->fetch();
        $total_pemasukan = (int)($row['total'] ?? 0);
        $stmt = $pdo->query('SELECT COALESCE(SUM(jumlah),0) AS total FROM pengeluaran');
        $row = $stmt->fetch();
        $total_pengeluaran = (int)($row['total'] ?? 0);
        $saldo_total = $total_ipl + $total_pemasukan - $total_pengeluaran;
        echo json_encode(['ok'=>true,'summary'=>[
            'total_ipl' => $total_ipl,
            'total_pemasukan_lain' => $total_pemasukan,
            'total_pengeluaran' => $total_pengeluaran,
            'saldo_total' => $saldo_total,
        ]]);
        exit;
    }

    if ($kind === 'dashboard') {
        $map = bulan_map();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Hitung pendapatan IPL berdasarkan bulan (atau semua bulan)
        if ($month === 'all') {
            $pendapatan_ipl = sum_ipl_all_months($pdo, $year);
            $bulan_label = 'Semua Bulan';
            $bulan_num = null;
        } else {
            if (!isset($map[$month])) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Bulan tidak valid']); exit; }
            $m = $map[$month];
            if (!empty($m['col'])) {
                $pendapatan_ipl = sum_ipl_column_by_year($pdo, $m['col'], $year);
            } else {
                $pendapatan_ipl = 0; // Kolom IPL untuk bulan ini tidak tersedia
            }
            $bulan_label = $m['name'];
            $bulan_num = $m['num'];
        }

        // Total pengeluaran untuk filter year/bulan
        $where = [];
        $params = [];
        if ($driver === 'sqlite') {
            if ($year) { $where[] = "CAST(strftime('%Y', tanggal) AS INTEGER) = :year"; $params[':year'] = $year; }
            if ($bulan_num) { $where[] = "CAST(strftime('%m', tanggal) AS INTEGER) = :month"; $params[':month'] = $bulan_num; }
            $sqlTotal = 'SELECT COALESCE(SUM(jumlah),0) AS total FROM pengeluaran' . (count($where)?' WHERE '.implode(' AND ',$where):'');
        } else { // mysql
            if ($year) { $where[] = 'YEAR(tanggal) = :year'; $params[':year'] = $year; }
            if ($bulan_num) { $where[] = 'MONTH(tanggal) = :month'; $params[':month'] = $bulan_num; }
            $sqlTotal = 'SELECT COALESCE(SUM(jumlah),0) AS total FROM pengeluaran' . (count($where)?' WHERE '.implode(' AND ',$where):'');
        }
        $stmt = $pdo->prepare($sqlTotal);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $total_pengeluaran = (int)($stmt->fetch()['total'] ?? 0);

        // Hitung total pemasukan untuk filter year/bulan
        $whereInc = [];
        $paramsInc = [];
        if ($driver === 'sqlite') {
            if ($year) { $whereInc[] = "CAST(strftime('%Y', tanggal) AS INTEGER) = :year"; $paramsInc[':year'] = $year; }
            if ($bulan_num) { $whereInc[] = "CAST(strftime('%m', tanggal) AS INTEGER) = :month"; $paramsInc[':month'] = $bulan_num; }
            $sqlIncTotal = 'SELECT COALESCE(SUM(jumlah),0) AS total FROM pemasukan' . (count($whereInc)?' WHERE '.implode(' AND ',$whereInc):'');
            $sqlIncList = 'SELECT id, tanggal, keterangan AS deskripsi, jumlah FROM pemasukan' . (count($whereInc)?' WHERE '.implode(' AND ',$whereInc):'') . ' ORDER BY tanggal ASC, id ASC';
        } else {
            if ($year) { $whereInc[] = 'YEAR(tanggal) = :year'; $paramsInc[':year'] = $year; }
            if ($bulan_num) { $whereInc[] = 'MONTH(tanggal) = :month'; $paramsInc[':month'] = $bulan_num; }
            $sqlIncTotal = 'SELECT COALESCE(SUM(jumlah),0) AS total FROM pemasukan' . (count($whereInc)?' WHERE '.implode(' AND ',$whereInc):'');
            $sqlIncList = 'SELECT id, tanggal, keterangan AS deskripsi, jumlah FROM pemasukan' . (count($whereInc)?' WHERE '.implode(' AND ',$whereInc):'') . ' ORDER BY tanggal ASC, id ASC';
        }
        $stmt = $pdo->prepare($sqlIncTotal);
        foreach ($paramsInc as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $total_pemasukan = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->prepare($sqlIncList);
        foreach ($paramsInc as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $incRows = $stmt->fetchAll();

        // Ambil pemasukan dari bukti_transfer (fallback), hindari duplikasi dengan yang sudah ada di pemasukan
        $whereBT = [];
        $paramsBT = [];
        if ($driver === 'sqlite') {
            if ($year) { $whereBT[] = "CAST(strftime('%Y', tanggal) AS INTEGER) = :year"; $paramsBT[':year'] = $year; }
            if ($bulan_num) { $whereBT[] = "CAST(strftime('%m', tanggal) AS INTEGER) = :month"; $paramsBT[':month'] = $bulan_num; }
            $sqlBtTotal = 'SELECT COALESCE(SUM(nominal),0) AS total FROM bukti_transfer' . (count($whereBT)?' WHERE '.implode(' AND ',$whereBT):'');
            $sqlBtList = 'SELECT nomor_rumah, nama_lengkap, bulan, nominal, tanggal FROM bukti_transfer' . (count($whereBT)?' WHERE '.implode(' AND ',$whereBT):'') . ' ORDER BY tanggal ASC';
        } else {
            if ($year) { $whereBT[] = 'YEAR(tanggal) = :year'; $paramsBT[':year'] = $year; }
            if ($bulan_num) { $whereBT[] = 'MONTH(tanggal) = :month'; $paramsBT[':month'] = $bulan_num; }
            $sqlBtTotal = 'SELECT COALESCE(SUM(nominal),0) AS total FROM bukti_transfer' . (count($whereBT)?' WHERE '.implode(' AND ',$whereBT):'');
            $sqlBtList = 'SELECT nomor_rumah, nama_lengkap, bulan, nominal, tanggal FROM bukti_transfer' . (count($whereBT)?' WHERE '.implode(' AND ',$whereBT):'') . ' ORDER BY tanggal ASC';
        }
        $stmt = $pdo->prepare($sqlBtTotal);
        foreach ($paramsBT as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $total_bukti = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->prepare($sqlBtList);
        foreach ($paramsBT as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $buktiRows = $stmt->fetchAll();

        // Buat indeks deskripsi pemasukan untuk deteksi duplikasi
        $incKeys = [];
        foreach ($incRows as $r) {
            $incKeys[$r['deskripsi']] = true;
        }

        // Ambil daftar pengeluaran untuk tabel (kompatibel SQLite/MySQL)
        if ($driver === 'sqlite') {
            $sqlList = 'SELECT id, tanggal, COALESCE(kategori, "") AS kategori, COALESCE(deskripsi, keterangan) AS deskripsi, jumlah FROM pengeluaran' . (count($where)?' WHERE '.implode(' AND ',$where):'') . ' ORDER BY tanggal ASC, id ASC';
        } else {
            $sqlList = 'SELECT id, tanggal, kategori, deskripsi, jumlah FROM pengeluaran' . (count($where)?' WHERE '.implode(' AND ',$where):'') . ' ORDER BY tanggal ASC, id ASC';
        }
        $stmt = $pdo->prepare($sqlList);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Susun items dengan saldo berjalan
        $items = [];
        $saldo = 0;
        if ($month !== 'all') {
            // Tambah baris IPL sintetis sebagai pemasukan awal periode HANYA jika ada data
            if ($pendapatan_ipl > 0) {
                $tgl = ($year ?: date('Y')) . '-' . str_pad((string)$bulan_num, 2, '0', STR_PAD_LEFT) . '-01';
                $saldo += $pendapatan_ipl;
                $items[] = [
                    'tanggal' => $tgl,
                    'kategori' => 'IPL',
                    'deskripsi' => 'Pembayaran IPL Bulan ' . $bulan_label,
                    'pendapatan' => $pendapatan_ipl,
                    'pengeluaran' => 0,
                    'saldo' => $saldo,
                ];
            }
        } else {
            // Mode "Semua Bulan" (rekap Jan-Des tahun terpilih)
            if ($pendapatan_ipl > 0) {
                $tgl = ($year ?: date('Y')) . '-01-01';
                $saldo += $pendapatan_ipl;
                $items[] = [
                    'tanggal' => $tgl,
                    'kategori' => 'IPL',
                    'deskripsi' => 'Rekap IPL Tahun ' . ($year ?: date('Y')) . ' (Jan-Des)',
                    'pendapatan' => $pendapatan_ipl,
                    'pengeluaran' => 0,
                    'saldo' => $saldo,
                ];
            }
        }
        // Gabungkan pemasukan dan pengeluaran pada tanggal sebenarnya
        $tx = [];
        $total_bukti_effective = 0;
        // IPL sintetis sudah ditambahkan ke $items untuk menjaga saldo awal, tapi kita gabungkan lagi agar urutan konsisten
        foreach ($items as $x) { $tx[] = $x; }
        foreach ($incRows as $r) {
            $tx[] = [
                'tanggal' => $r['tanggal'],
                'kategori' => 'Pemasukan',
                'deskripsi' => $r['deskripsi'],
                'pendapatan' => (int)$r['jumlah'],
                'pengeluaran' => 0,
            ];
        }
        // Tambahkan pemasukan dari bukti_transfer (hindari duplikasi)
        foreach ($buktiRows as $r) {
            $ket = 'Bukti Transfer IPL ' . ($r['bulan'] ?? '') . ' - ' . ($r['nama_lengkap'] ?? '') . ' (' . ($r['nomor_rumah'] ?? '') . ')';
            if (!isset($incKeys[$ket])) {
                $total_bukti_effective += (int)$r['nominal'];
                $tx[] = [
                    'tanggal' => $r['tanggal'],
                    'kategori' => 'Pemasukan',
                    'deskripsi' => $ket,
                    'pendapatan' => (int)$r['nominal'],
                    'pengeluaran' => 0,
                ];
            }
        }
        foreach ($rows as $r) {
            $tx[] = [
                'tanggal' => $r['tanggal'],
                'kategori' => $r['kategori'],
                'deskripsi' => $r['deskripsi'],
                'pendapatan' => 0,
                'pengeluaran' => (int)$r['jumlah'],
            ];
        }
        // Urutkan berdasarkan tanggal
        usort($tx, function($a,$b){ return strcmp($a['tanggal'],$b['tanggal']); });
        // Hitung saldo berjalan dari awal
        $items = [];
        $saldo = 0;
        foreach ($tx as $t) {
            $saldo += (int)($t['pendapatan'] ?? 0);
            $saldo -= (int)($t['pengeluaran'] ?? 0);
            $t['saldo'] = $saldo;
            $items[] = $t;
        }
        // Jika bulan terpilih tidak memiliki data sama sekali (IPL=0, pemasukan=0, pengeluaran=0), kosongkan tabel
        if ($month !== 'all' && $pendapatan_ipl <= 0 && $total_pemasukan <= 0 && count($rows) === 0) {
            $items = [];
        }

        $pendapatan_total = $pendapatan_ipl + $total_pemasukan + $total_bukti_effective;
        $saldo_total = $pendapatan_total - $total_pengeluaran;
        echo json_encode(['ok'=>true, 'summary' => [
            'periode' => [ 'tahun' => $year, 'bulan' => $bulan_label ],
            'pendapatan_ipl' => $pendapatan_ipl,
            'total_pemasukan' => $total_pemasukan,
            'pendapatan_total' => $pendapatan_total,
            'total_pengeluaran' => $total_pengeluaran,
            'saldo' => $saldo_total,
        ], 'items' => $items]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Kind not supported']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}