<?php
// includes/auth.php — Session & Auth helpers

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirect = '/luxstay/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        header('Location: /luxstay/login.php?error=unauthorized');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id'] ?? null,
        'name'   => $_SESSION['user_name'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['role'] ?? 'customer',
        'avatar' => $_SESSION['avatar'] ?? null,
    ];
}

function loginUser(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['avatar']     = $user['avatar'] ?? null;
    session_regenerate_id(true);
}

function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}

function generateBookingRef(): string {
    return 'LUX' . strtoupper(substr(uniqid(), -7));
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return null; }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
