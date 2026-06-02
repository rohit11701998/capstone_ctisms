<?php
/**
 * Auth Middleware - Role-Based Access Control
 */

class Auth
{
    /**
     * Initialize secure session
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_name(SESSION_NAME);
            session_start();

            // Regenerate session ID periodically
            if (!isset($_SESSION['_initiated'])) {
                session_regenerate_id(true);
                $_SESSION['_initiated'] = true;
            }
        }
    }

    /**
     * Log in a user and set session
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['_ip']       = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_ua']       = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Destroy session and log out
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require authentication - redirect if not logged in
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }

        // Session hijacking protection
        if (isset($_SESSION['_ip']) && $_SESSION['_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            self::logout();
            header('Location: ' . APP_URL . '/auth/login.php?error=session_invalid');
            exit;
        }
    }

    /**
     * Require a specific role
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!in_array($_SESSION['user_role'], $roles)) {
            http_response_code(403);
            include __DIR__ . '/../app/views/errors/403.php';
            exit;
        }
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get current user name
     */
    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Check if current user has role
     */
    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles);
    }

    public static function isAdmin(): bool       { return self::role() === 'admin'; }
    public static function isTechnician(): bool  { return self::role() === 'technician'; }
    public static function isCustomer(): bool    { return self::role() === 'customer'; }
    public static function isStaff(): bool       { return in_array(self::role(), ['admin','technician']); }

    /**
     * Get dashboard URL based on role
     */
    public static function dashboardUrl(): string
    {
        return match(self::role()) {
            'admin'      => APP_URL . '/admin/dashboard.php',
            'technician' => APP_URL . '/technician/dashboard.php',
            default      => APP_URL . '/customer/dashboard.php',
        };
    }
}
