<?php
// header.php — cabecera HTML común del panel de administración
// Se incluye en todas las páginas del admin con: require_once '../includes/header.php';

$pageTitle   = $pageTitle   ?? 'Hotel Bellavista';
$currentPage = $currentPage ?? '';
$userRole    = currentRole();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Barra de navegación del panel admin -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/hotel/admin/dashboard.php">
            <i class="bi bi-building fs-4 text-gold"></i>
            <span>Hotel Bellavista</span>
        </a>

        <!-- Botón hamburguesa para móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>

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
                    <i class="bi bi-person-circle me-1"></i>
                    <?= htmlspecialchars(currentUser()) ?>
                    <span class="badge bg-gold ms-1"><?= htmlspecialchars($userRole) ?></span>
                </span>
                <a href="/hotel/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>Salir
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Mensajes de sesión (éxito o error) -->
<div class="container-fluid mt-3">
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</div>

<main class="container-fluid py-4">
