<?php
// Router untuk PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$public = __DIR__;
$root = dirname(__DIR__);

// Jika request ke /api/*, map ke folder root /api
if (strpos($uri, '/api/') === 0) {
    $target = $root . $uri; // contoh: /api/auth.php -> C:\..\gtrb\api\auth.php
    if (is_file($target)) {
        require $target;
        return;
    }
    http_response_code(404);
    echo 'API not found';
    return;
}

// Untuk file yang ada di public, serahkan ke server
$path = $public . $uri;
if ($uri !== '/' && is_file($path)) {
    return false; // biarkan PHP dev server melayani file
}

// Default: jika tidak ada file, arahkan ke index.php
require $public . '/index.php';