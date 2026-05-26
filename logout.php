<?php
// ============================================================
// logout.php
// Cierra la sesión del usuario
// ============================================================
require_once 'includes/auth.php';
logout(); // Esta función destruye la sesión y redirige al login
