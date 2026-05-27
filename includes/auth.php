<?php
// Iniciamos la sesión si no está iniciada ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprueba si el usuario ha iniciado sesión, si no redirige al login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /hotel/login.php");
        exit;
    }
}

// Comprueba que el usuario sea admin
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos para acceder a esa sección.";
        header("Location: /hotel/admin/dashboard.php");
        exit;
    }
}

// Devuelve true si hay sesión iniciada
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Devuelve el nombre del usuario actual
function currentUser() {
    return $_SESSION['username'] ?? '';
}

// Devuelve el rol del usuario actual
function currentRole() {
    return $_SESSION['user_role'] ?? '';
}

// Cierra la sesión y redirige al login
function logout() {
    session_unset();
    session_destroy();
    header("Location: /hotel/login.php");
    exit;
}

// Genera un token para proteger los formularios (CSRF)
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Comprueba que el token del formulario sea válido
function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token de seguridad incorrecto. Vuelve atrás e inténtalo de nuevo.");
    }
}
