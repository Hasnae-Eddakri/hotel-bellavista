<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Solo pueden entrar los clientes logueados
if (!isLoggedIn() || currentRole() !== 'cliente') {
    header("Location: /hotel/login-cliente.php");
    exit;
}

$db  = getDB();
$uid = $_SESSION['customer_user_id'];

// Obtenemos los datos del cliente
$stmt = $db->prepare("SELECT * FROM customer_user WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

// Obtenemos sus reservas si tiene customer_id vinculado
$misReservas = [];
if (!empty($usuario['customer_id'])) {
    $stmt = $db->prepare("
        SELECT b.*, r.room_no, rt.room_type_name
        FROM booking b
        JOIN room r ON b.room_id = r.room_id
        JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE b.customer_id = ?
        ORDER BY b.check_in DESC
    ");
    $stmt->execute([$usuario['customer_id']]);
    $misReservas = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container">
        <a class="navbar-brand" href="/hotel/">
            <i class="bi bi-building text-gold me-2"></i>Hotel Bellavista
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light small">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($usuario['name']) ?>
            </span>
            <a href="/hotel/logout-cliente.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i>Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">
        <i class="bi bi-person-circle text-gold me-2"></i>
        Bienvenido, <?= htmlspecialchars(explode(' ', $usuario['name'])[0]) ?>
    </h2>

    <div class="row g-4">
        <!-- Datos de la cuenta -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark-hotel text-white">
                    <h5 class="mb-0"><i class="bi bi-person text-gold me-2"></i>Mi cuenta</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Nombre:</strong></p>
                    <p class="text-muted"><?= htmlspecialchars($usuario['name']) ?></p>
                    <p class="mb-1"><strong>Email:</strong></p>
                    <p class="text-muted"><?= htmlspecialchars($usuario['email']) ?></p>
                    <p class="mb-1"><strong>Miembro desde:</strong></p>
                    <p class="text-muted"><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></p>
                </div>
                <div class="card-footer bg-white">
                    <a href="/hotel/reservar.php" class="btn btn-gold w-100">
                        <i class="bi bi-calendar-plus me-2"></i>Hacer una reserva
                    </a>
                </div>
            </div>
        </div>

        <!-- Mis reservas -->
        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark-hotel text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check text-gold me-2"></i>
                        Mis reservas
                        <span class="badge bg-gold ms-2"><?= count($misReservas) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($misReservas) === 0): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No tienes reservas todavía.
                        <div class="mt-3">
                            <a href="/hotel/reservar.php" class="btn btn-sm btn-gold">Reservar ahora</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Habitación</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misReservas as $r): ?>
                                <tr>
                                    <td>Hab. <?= htmlspecialchars($r['room_no']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['check_out'])) ?></td>
                                    <td><?= number_format($r['total_price'], 2) ?> €</td>
                                    <td>
                                        <?php if ($r['payment_status'] == 1): ?>
                                            <span class="badge bg-success">Pagada</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark-hotel text-light py-3 mt-5">
    <div class="container text-center">
        <small>Hotel Bellavista &copy; <?= date('Y') ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
