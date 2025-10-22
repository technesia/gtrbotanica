<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? ($_POST['username'] ?? '');
    $password = $input['password'] ?? ($_POST['password'] ?? '');
    $ok = login_admin($username, $password);
    if ($ok) {
        echo json_encode(['ok' => true, 'role' => 'admin']);
    } else {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Username atau password salah']);
    }
    exit;
}

if ($method === 'DELETE') {
    logout();
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Method not supported']);