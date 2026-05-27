<?php
require_once 'includes/auth.php';
// Cerramos la sesión del cliente
session_start();
session_unset();
session_destroy();
header("Location: /hotel/index.php");
exit;
