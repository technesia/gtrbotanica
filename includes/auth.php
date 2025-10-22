<?php
// Simple auth utilities for session-based admin login
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

function is_logged_in(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function login_admin(string $username, string $password): bool {
    // Credentials via env vars for flexibility; plus known local defaults
    $envUser = getenv('ADMIN_USER') ?: '';
    $envPass = getenv('ADMIN_PASS') ?: '';
    $candidates = [];
    if ($envUser !== '' && $envPass !== '') { $candidates[] = [$envUser, $envPass]; }
    // Accept the provided local admin password as default
    $candidates[] = ['admin', 'Botan!ca#'];
    // Fallback dev default
    $candidates[] = ['admin', 'admin'];

    foreach ($candidates as [$u,$p]) {
        if ($username === $u && $password === $p) {
            $_SESSION['user'] = [ 'username' => $username, 'role' => 'admin' ];
            return true;
        }
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_admin(): void {
    if (!is_admin()) {
        // Redirect to login with next param back to current URL
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080';
        $uri = $_SERVER['REQUEST_URI'] ?? '/index.php';
        $next = urlencode($uri);
        header("Location: {$scheme}://{$host}/login.php?next={$next}");
        exit;
    }
}