<?php
// ============================================================
// includes/header.php
// Cabecera HTML común para TODAS las páginas del admin
// Se incluye con: require_once '../includes/header.php';
// ============================================================

// $pageTitle debe definirse antes de incluir este archivo
$pageTitle = $pageTitle ?? 'Hotel Bellavista';
$currentPage = $currentPage ?? '';
$userRole = currentRole();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Hotel Bellavista</title>

    <!-- Bootstrap 5 CSS (diseño responsivo) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Playfair Display + Lato -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- CSS propio -->
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Barra de navegación del panel admin -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="/hotel/admin/dashboard.php">
            <i class="bi bi-building fs-4 text-gold"></i>
            <span class="font-playfair">Hotel Bellavista</span>
        </a>

        <!-- Botón hamburguesa para móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú de navegación -->
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/hotel/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'rooms' ? 'active' : '' ?>" href="/hotel/admin/rooms/index.php">
                        <i class="bi bi-door-open"></i> Habitaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'bookings' ? 'active' : '' ?>" href="/hotel/admin/bookings/index.php">
                        <i class="bi bi-calendar-check"></i> Reservas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'customers' ? 'active' : '' ?>" href="/hotel/admin/customers/index.php">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'staff' ? 'active' : '' ?>" href="/hotel/admin/staff/index.php">
                        <i class="bi bi-person-badge"></i> Personal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'complaints' ? 'active' : '' ?>" href="/hotel/admin/complaints/index.php">
                        <i class="bi bi-exclamation-triangle"></i> Quejas
                    </a>
                </li>
            </ul>

            <!-- Usuario logueado -->
            <div class="d-flex align-items-center gap-3">
                <span class="text-light small">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars(currentUser()) ?>
                    <span class="badge bg-gold text-dark ms-1"><?= htmlspecialchars($userRole) ?></span>
                </span>
                <a href="/hotel/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
                <!-- Enlace a la web pública -->
                <a href="/hotel/index.php" class="btn btn-sm btn-gold" target="_blank">
                    <i class="bi bi-globe"></i> Ver web
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Contenedor principal -->
<main class="container-fluid py-4 px-4">

<?php
// Mostrar mensajes de éxito o error de la sesión
if (!empty($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['success']);
}
if (!empty($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error']);
}
?>
