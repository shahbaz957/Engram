<?php
// Shared session and access helpers (course-style).

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => false,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/** RBAC: admin vs user (default at signup). */
function requireRole(string $requiredRole): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $requiredRole) {
        http_response_code(403);
        exit('403 Forbidden: You do not have permission to access this page.');
    }
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Vault pages are for role user only; admins use the admin dashboard. */
function requireVaultUser(): void
{
    requireLogin();
    if (isAdmin()) {
        header('Location: /admin/index.php');
        exit;
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
