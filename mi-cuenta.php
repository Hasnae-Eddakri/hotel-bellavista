<?php
// ============================================================
// mi-cuenta.php — Panel del cliente logueado
// ============================================================
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn() || currentRole() !== 'cliente') {
    header("Location: /hotel/login-cliente.php");
    exit;
}

$db  = getDB();
$uid = $_SESSION['customer_user_id'];

// Obtener datos del usuario
$stmt = $db->prepare("SELECT * FROM customer_user WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

// Obtener reservas si tiene customer_id vinculado
$misReservas = [];
if ($usuario['customer_id']) {
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container">
        <a class="navbar-brand font-playfair" href="/hotel/">
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
    <h2 class="font-playfair mb-4">
        <i class="bi bi-person-circle text-gold me-2"></i>
        Bienvenida, <?= htmlspecialchars(explode(' ', $usuario['name'])[0]) ?>
    </h2>

    <div class="row g-4">
        <!-- Datos de la cuenta -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark-hotel text-white">
                    <h5 class="mb-0"><i class="bi bi-person text-gold me-2"></i>Mi cuenta</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong><br><?= htmlspecialchars($usuario['name']) ?></p>
                    <p><strong>Email:</strong><br><?= htmlspecialchars($usuario['email']) ?></p>
                    <p class="mb-0"><strong>Cliente desde:</strong><br>
                        <?= date('d/m/Y', strtotime($usuario['created_at'])) ?>
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <a href="/hotel/" class="btn btn-gold w-100">
                        <i class="bi bi-search me-1"></i>Buscar habitaciones
                    </a>
                </div>
            </div>
        </div>

        <!-- Mis reservas -->
        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Mis reservas</h5>
                    <span class="badge bg-primary"><?= count($misReservas) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($misReservas)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x d-block fs-1 mb-3"></i>
                        <p>Todavía no tienes reservas.</p>
                        <a href="/hotel/" class="btn btn-gold">
                            <i class="bi bi-search me-1"></i>Buscar habitaciones
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Habitación</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misReservas as $r): ?>
                                <tr>
                                    <td><span class="badge bg-dark"><?= htmlspecialchars($r['room_no']) ?></span> <small><?= htmlspecialchars($r['room_type_name']) ?></small></td>
                                    <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['check_out'])) ?></td>
                                    <td class="fw-bold"><?= number_format($r['total_price'],2,',','.') ?>€</td>
                                    <td><?= $r['payment_status'] ? '<span class="badge bg-success">Confirmada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>' ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
