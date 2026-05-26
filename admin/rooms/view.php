<?php
// ============================================================
// admin/rooms/view.php — Ver detalle de una habitación
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Ver Habitación';
$currentPage = 'rooms';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Obtener habitación
$stmt = $db->prepare("
    SELECT r.*, rt.room_type_name, rt.base_price, rt.capacity, rt.description as type_desc
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.room_id = ?
");
$stmt->execute([$id]);
$hab = $stmt->fetch();

if (!$hab) {
    $_SESSION['error'] = "Habitación no encontrada.";
    header("Location: /hotel/admin/rooms/index.php");
    exit;
}

// Reservas de esta habitación
$reservas = $db->prepare("
    SELECT b.*, c.customer_name
    FROM booking b
    JOIN customer c ON b.customer_id = c.customer_id
    WHERE b.room_id = ?
    ORDER BY b.check_in DESC
    LIMIT 10
");
$reservas->execute([$id]);
$reservas = $reservas->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="/hotel/admin/rooms/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="font-playfair mb-0">
        <i class="bi bi-door-open text-gold me-2"></i>
        Habitación <?= htmlspecialchars($hab['room_no']) ?>
    </h2>
    <?php if (currentRole() === 'admin'): ?>
    <a href="/hotel/admin/rooms/edit.php?id=<?= $id ?>" class="btn btn-gold ms-auto">
        <i class="bi bi-pencil me-1"></i>Editar
    </a>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Datos de la habitación -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-info-circle text-gold me-2"></i>Información</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-bold" style="width:140px;">Número</td>
                        <td><span class="badge bg-dark fs-6"><?= htmlspecialchars($hab['room_no']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Tipo</td>
                        <td><?= htmlspecialchars($hab['room_type_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Planta</td>
                        <td>Planta <?= $hab['floor'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Capacidad</td>
                        <td><?= $hab['capacity'] ?> persona(s)</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Precio/noche</td>
                        <td class="fw-bold text-gold fs-5"><?= number_format($hab['base_price'], 2, ',', '.') ?>€</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Estado</td>
                        <td>
                            <?php if (!$hab['status']): ?>
                                <span class="badge bg-secondary">En mantenimiento</span>
                            <?php elseif ($hab['check_in_status']): ?>
                                <span class="badge bg-danger">Ocupada</span>
                            <?php else: ?>
                                <span class="badge bg-success">Libre</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Descripción</td>
                        <td><?= htmlspecialchars($hab['description'] ?? '—') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Imagen placeholder -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-center text-white rounded"
                     style="height:200px; background:linear-gradient(135deg,#1a2942,#2c4a7c);">
                    <div class="text-center">
                        <i class="bi bi-building" style="font-size:4rem; opacity:0.4;"></i>
                        <p class="mt-2 mb-0 small opacity-75">
                            <?= htmlspecialchars($hab['room_type_name']) ?> · Nº <?= htmlspecialchars($hab['room_no']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservas de esta habitación -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Últimas reservas</h5>
                <span class="badge bg-primary"><?= count($reservas) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                                <th>Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $r['booking_id'] ?></span></td>
                                <td>
                                    <a href="/hotel/admin/customers/view.php?id=<?= $r['customer_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($r['customer_name']) ?>
                                    </a>
                                </td>
                                <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['check_out'])) ?></td>
                                <td class="fw-bold"><?= number_format($r['total_price'], 2, ',', '.') ?>€</td>
                                <td>
                                    <?= $r['payment_status']
                                        ? '<span class="badge bg-success">Pagado</span>'
                                        : '<span class="badge bg-warning text-dark">Pendiente</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reservas)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Esta habitación no tiene reservas aún.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Estadística rápida con JS -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-bar-chart text-gold me-2"></i>Estadísticas</h5>
            </div>
            <div class="card-body" id="statsDiv">
                <p class="text-muted text-center">Calculando...</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Calcular estadísticas con objeto Date (DWEC)
    const reservas = <?= json_encode(array_map(fn($r) => [
        'check_in'    => $r['check_in'],
        'check_out'   => $r['check_out'],
        'total_price' => $r['total_price'],
        'payment_status' => $r['payment_status']
    ], $reservas)) ?>;

    const hoy        = new Date();
    let totalIngresos = 0;
    let reservasActivas = 0;
    let reservasFuturas = 0;

    reservas.forEach(function(r) {
        const checkin  = new Date(r.check_in);
        const checkout = new Date(r.check_out);
        totalIngresos += parseFloat(r.total_price);
        if (hoy >= checkin && hoy <= checkout) reservasActivas++;
        if (checkin > hoy) reservasFuturas++;
    });

    $('#statsDiv').html(`
        <div class="row g-3 text-center">
            <div class="col-4">
                <div class="p-3 bg-light rounded">
                    <div class="fw-bold fs-4 text-primary">${reservas.length}</div>
                    <div class="small text-muted">Reservas totales</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded">
                    <div class="fw-bold fs-4 text-success">${reservasActivas}</div>
                    <div class="small text-muted">Activas ahora</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded">
                    <div class="fw-bold fs-4 text-gold">${totalIngresos.toFixed(2).replace('.',',')}€</div>
                    <div class="small text-muted">Ingresos totales</div>
                </div>
            </div>
        </div>
    `);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
