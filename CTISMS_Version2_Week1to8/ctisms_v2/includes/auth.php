<?php
// includes/auth.php
// Week 5 — Role-based authentication added

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function userId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function userName(): string {
    return $_SESSION['user_name'] ?? '';
}

function userRole(): string {
    return $_SESSION['user_role'] ?? '';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /ctisms_v2/login.php');
        exit;
    }
}

// Week 5 — role checking added
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array(userRole(), $roles)) {
        http_response_code(403);
        echo '<div style="padding:40px;font-family:Arial;text-align:center">
              <h3>Access Denied</h3>
              <p>You do not have permission to view this page.</p>
              <a href="/ctisms_v2/login.php">Back to Login</a></div>';
        exit;
    }
}

function dashboardUrl(): string {
    return match(userRole()) {
        'admin'      => '/ctisms_v2/admin_dashboard.php',
        'technician' => '/ctisms_v2/technician_dashboard.php',
        default      => '/ctisms_v2/customer_dashboard.php',
    };
}

// Flash messages — Week 6
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function showFlash(): void {
    if (!empty($_SESSION['flash'])) {
        $f   = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $cls = $f['type'] === 'success' ? 'success' : 'danger';
        echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show">'
            . htmlspecialchars($f['msg'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function e(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Week 8 — CSRF tokens added
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        die('Security token mismatch. Please go back and try again.');
    }
}
