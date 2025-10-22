<?php
// Database connection using PDO
// Adjust credentials if needed via environment variables or here
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'botanica';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_CHARSET = 'utf8mb4';

function get_pdo(): PDO {
    static $pdo = null;
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
    if ($pdo === null) {
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        } catch (Throwable $e) {
            // Fallback ke SQLite lokal jika MySQL tidak tersedia
            $sqlite_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'botanica.sqlite';
            try {
                $pdo = new PDO('sqlite:' . $sqlite_path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                ensure_sqlite_schema($pdo);
            } catch (Throwable $e2) {
                http_response_code(500);
                die('Database connection failed: ' . $e->getMessage() . ' ; SQLite fallback failed: ' . $e2->getMessage());
            }
        }
    }
    return $pdo;
}

function ensure_sqlite_schema(PDO $pdo): void {
    // Tabel user
    $pdo->exec("CREATE TABLE IF NOT EXISTS user (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        blok_rumah TEXT NOT NULL,
        nama_warga TEXT NOT NULL,
        status TEXT NOT NULL
    );");
    // Unique index untuk kombinasi blok+nama
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_user_blok_nama ON user(blok_rumah, nama_warga);");

    // Tabel IPL (kolom per bulan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS ipl (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        blok TEXT NOT NULL,
        no_rumah TEXT NOT NULL,
        nama_warga TEXT NOT NULL,
        ipl_januari INTEGER DEFAULT 0,
        ipl_februari INTEGER DEFAULT 0,
        ipl_maret INTEGER DEFAULT 0,
        ipl_april INTEGER DEFAULT 0,
        ipl_mei INTEGER DEFAULT 0,
        ipl_juni INTEGER DEFAULT 0,
        ipl_juli INTEGER DEFAULT 0,
        ipl_agustus INTEGER DEFAULT 0,
        ipl_september INTEGER DEFAULT 0,
        ipl_oktober INTEGER DEFAULT 0,
        ipl_desember INTEGER DEFAULT 0
    );");
    // Upgrade skema: tambahkan kolom tahun jika belum ada
    try { $pdo->exec("ALTER TABLE ipl ADD COLUMN tahun INTEGER"); } catch (Throwable $e) {}

    // Tabel pemasukan
    $pdo->exec("CREATE TABLE IF NOT EXISTS pemasukan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tanggal TEXT NOT NULL,
        jumlah INTEGER NOT NULL,
        keterangan TEXT
    );");

    // Tabel pengeluaran
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengeluaran (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tanggal TEXT NOT NULL,
        kategori TEXT,
        deskripsi TEXT,
        jumlah INTEGER NOT NULL,
        petugas TEXT,
        keterangan TEXT
    );");
    // Upgrade skema lama: tambahkan kolom jika belum ada
    try { $pdo->exec("ALTER TABLE pengeluaran ADD COLUMN kategori TEXT"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE pengeluaran ADD COLUMN deskripsi TEXT"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE pengeluaran ADD COLUMN petugas TEXT"); } catch (Throwable $e) {}

}

function sum_table_column(PDO $pdo, string $table, string $column): int {
    $stmt = $pdo->query("SELECT COALESCE(SUM(`$column`),0) AS total FROM `$table`");
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function sum_ipl_all_months(PDO $pdo, ?int $year = null): int {
    // Known IPL month columns in schema (note: November seems absent in dump)
    $months = [
        'ipl_januari','ipl_februari','ipl_maret','ipl_april','ipl_mei',
        'ipl_juni','ipl_juli','ipl_agustus','ipl_september','ipl_oktober','ipl_desember'
    ];
    $total = 0;
    foreach ($months as $col) {
        if ($year) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(`$col`),0) AS total FROM `ipl` WHERE tahun = :year");
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT COALESCE(SUM(`$col`),0) AS total FROM `ipl`");
        }
        $row = $stmt->fetch();
        $total += (int)($row['total'] ?? 0);
    }
    return $total;
}

function sum_ipl_column_by_year(PDO $pdo, string $column, ?int $year = null): int {
    if ($year) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(`$column`),0) AS total FROM `ipl` WHERE tahun = :year");
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
    return sum_table_column($pdo, 'ipl', $column);
}

function get_ipl_month_totals(PDO $pdo, ?int $year = null): array {
    $columns = [
        'ipl_januari' => 'Januari',
        'ipl_februari' => 'Februari',
        'ipl_maret' => 'Maret',
        'ipl_april' => 'April',
        'ipl_mei' => 'Mei',
        'ipl_juni' => 'Juni',
        'ipl_juli' => 'Juli',
        'ipl_agustus' => 'Agustus',
        'ipl_september' => 'September',
        'ipl_oktober' => 'Oktober',
        'ipl_desember' => 'Desember',
    ];
    $data = [];
    foreach ($columns as $col => $name) {
        if ($year) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(`$col`),0) AS total FROM `ipl` WHERE tahun = :year");
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT COALESCE(SUM(`$col`),0) AS total FROM `ipl`");
        }
        $row = $stmt->fetch();
        $data[] = ['bulan' => $name, 'kolom' => $col, 'total' => (int)($row['total'] ?? 0)];
    }
    return $data;
}

function get_pengeluaran_per_bulan(PDO $pdo, ?int $year = null): array {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        // SQLite: gunakan strftime untuk grup per bulan
        $sql = "SELECT strftime('%Y-%m', tanggal) AS periode, MIN(tanggal) AS min_tanggal, SUM(jumlah) AS total_pengeluaran FROM pengeluaran";
        if ($year) { $sql .= " WHERE CAST(strftime('%Y', tanggal) AS INTEGER) = :year"; }
        $sql .= " GROUP BY strftime('%Y-%m', tanggal) ORDER BY min_tanggal";
        $stmt = $pdo->prepare($sql);
        if ($year) { $stmt->bindValue(':year', $year, PDO::PARAM_INT); }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $bulan_map = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
            '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
        ];
        foreach ($rows as &$r) {
            $bulan_num = substr($r['periode'], 5, 2);
            $tahun = substr($r['periode'], 0, 4);
            $r['nama_bulan'] = ($bulan_map[$bulan_num] ?? $bulan_num) . ' ' . $tahun;
        }
        return $rows;
    }

    // MySQL: coba view, lalu fallback DATE_FORMAT
    try {
        if ($year) {
            $stmt = $pdo->prepare("SELECT periode, nama_bulan, total_pengeluaran FROM v_pengeluaran_per_bulan WHERE LEFT(periode,4)=? ORDER BY periode");
            $stmt->execute([strval($year)]);
        } else {
            $stmt = $pdo->query("SELECT periode, nama_bulan, total_pengeluaran FROM v_pengeluaran_per_bulan ORDER BY periode");
        }
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        $sql = "SELECT DATE_FORMAT(tanggal,'%Y-%m') AS periode, DATE_FORMAT(tanggal,'%M %Y') AS nama_bulan, SUM(jumlah) AS total_pengeluaran FROM pengeluaran";
        if ($year) { $sql .= " WHERE YEAR(tanggal) = :year"; }
        $sql .= " GROUP BY DATE_FORMAT(tanggal,'%Y-%m') ORDER BY MIN(tanggal)";
        $stmt = $pdo->prepare($sql);
        if ($year) { $stmt->bindValue(':year', $year, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

function sanitize($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }