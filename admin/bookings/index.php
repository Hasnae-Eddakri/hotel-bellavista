<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Reservas';
$currentPage = 'bookings';

$db = getDB();

// Filtro por estado de pago o fecha
$filtro = $_GET['filtro'] ?? 'todas';

$sql = "
    SELECT b.*, c.customer_name, r.room_no, rt.room_type_name
    FROM booking b
    JOIN customer c   ON b.customer_id = c.customer_id
    JOIN room r       ON b.room_id = r.room_id
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
";

if ($filtro === 'pendientes') $sql .= " WHERE b.payment_status = 0";
if ($filtro === 'pagadas')    $sql .= " WHERE b.payment_status = 1";
if ($filtro === 'hoy')        $sql .= " WHERE b.check_in = CURDATE()";

$sql .= " ORDER BY b.booking_date DESC";
$reservas = $db->query($sql)->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Reservas</h2>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-plus-circle me-1"></i> Nueva reserva
    </a>
</div>

<!-- Pestañas de filtro -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'todas' ? 'active' : '' ?>" href="?filtro=todas">
            Todas <span class="badge bg-secondary"><?= count($reservas) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'hoy' ? 'active' : '' ?>" href="?filtro=hoy">
            Check-in hoy
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'pendientes' ? 'active' : '' ?>" href="?filtro=pendientes">
            Pago pendiente
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'pagadas' ? 'active' : '' ?>" href="?filtro=pagadas">
            Pagadas
        </a>
    </li>
</ul>

<!-- Tabla de reservas -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (count($reservas) === 0): ?>
        <p class="text-muted text-center py-4">No hay reservas con ese filtro.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Noches</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><strong>#<?= $r['booking_id'] ?></strong></td>
                        <td><?= htmlspecialchars($r['customer_name']) ?></td>
                        <td>Hab. <?= htmlspecialchars($r['room_no']) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['check_out'])) ?></td>
                        <td><?= $r['num_nights'] ?></td>
                        <td><?= number_format($r['total_price'], 2) ?>€</td>
                        <td>
                            <?php if ($r['payment_status'] == 1): ?>
                                <span class="badge bg-success">Pagada</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $r['booking_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $r['booking_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($r['payment_status'] == 0): ?>
                            <a href="pay.php?id=<?= $r['booking_id'] ?>" class="btn btn-sm btn-outline-success"
                               onclick="return confirm('¿Marcar esta reserva como pagada?')">
                                <i class="bi bi-credit-card"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-muted small">
            Total: <?= count($reservas) ?> reserva(s)
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
