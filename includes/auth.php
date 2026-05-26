<?php
// ============================================================
// includes/auth.php
// Control de sesiones y autenticación de usuarios
// ============================================================

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Comprueba si el usuario ha iniciado sesión.
 * Si no, redirige al login.
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /hotel/login.php");
        exit;
    }
}

/**
 * Comprueba si el usuario tiene rol de administrador.
 * Si no, redirige al panel con un mensaje de error.
 */
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos para acceder a esa sección.";
        header("Location: /hotel/admin/dashboard.php");
        exit;
    }
}

/**
 * Devuelve true si el usuario está logueado.
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Devuelve el nombre del usuario logueado o vacío.
 *
 * @return string
 */
function currentUser(): string {
    return $_SESSION['username'] ?? '';
}

/**
 * Devuelve el rol del usuario logueado.
 *
 * @return string
 */
function currentRole(): string {
    return $_SESSION['user_role'] ?? '';
}

/**
 * Cierra la sesión del usuario y redirige al login.
 */
function logout(): void {
    session_unset();
    session_destroy();
    header("Location: /hotel/login.php");
    exit;
}

/**
 * Genera un token CSRF para proteger formularios.
 * Se guarda en la sesión y se incluye en el formulario como campo oculto.
 *
 * @return string Token CSRF
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF enviado en el formulario sea válido.
 * Si no es válido, para la ejecución.
 */
function verifyCsrfToken(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token de seguridad inválido. Vuelve atrás e inténtalo de nuevo.");
    }
}
