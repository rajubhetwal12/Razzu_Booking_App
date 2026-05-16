<?php
/**
 * Auth helpers — call after config/db.php (session already started)
 */

function isLoggedIn(): bool {
    return !empty($_SESSION['uid']);
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['uid']    ?? null,
        'name'  => $_SESSION['uname']  ?? '',
        'email' => $_SESSION['uemail'] ?? '',
        'role'  => $_SESSION['urole']  ?? 'customer',
    ];
}

function loginUser(array $u): void {
    session_regenerate_id(true);
    $_SESSION['uid']    = (int)$u['id'];
    $_SESSION['uname']  = $u['name'];
    $_SESSION['uemail'] = $u['email'];
    $_SESSION['urole']  = $u['role'];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    $r = $_SESSION['urole'] ?? '';
    if ($r !== $role && $r !== 'admin') {
        header('Location: ' . BASE_URL . '/login.php?err=access');
        exit;
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg = ''): ?string {
    if ($msg !== '') { $_SESSION['flash'][$type] = $msg; return null; }
    $v = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $v;
}

function e(mixed $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function genRef(): string {
    return 'LUX' . strtoupper(bin2hex(random_bytes(4)));
}

function fmtNPR(float $n): string {
    return 'NPR ' . number_format($n, 0);
}
