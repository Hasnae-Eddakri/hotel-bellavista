<?php
// ============================================================
// admin/bookings/index.php
// Listado de todas las reservas
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Reservas';
$currentPage = 'bookings';

$db = getDB();

// Filtro de búsqueda por estado de pago
$filtro = $_GET['filtro'] ?? 'todas';

$sql = "
    SELECT b.*, c.customer_name, r.room_no, rt.room_type_name
    FROM booking b
    JOIN customer c  ON b.customer_id = c.customer_id
    JOIN room r      ON b.room_id = r.room_id
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
";

if ($filtro === 'pendientes')  $sql .= " WHERE b.payment_status = 0";
if ($filtro === 'pagadas')     $sql .= " WHERE b.payment_status = 1";
if ($filtro === 'hoy')         $sql .= " WHERE b.check_in = CURDATE()";

$sql .= " ORDER BY b.booking_date DESC";
$reservas = $db->query($sql)->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-playfair mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Reservas</h2>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-plus-circle me-1"></i> Nueva reserva
    </a>
</div>

<!-- Pestañas de filtro -->
<ul class="nav nav-tabs mb-4" id="filtroTabs">
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
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Noches</th>
                        <th>Total</th>
                        <th>Pendiente</th>
                        <th>Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                    <?php
                        // Calcular si la reserva es pasada, actual o futura con PHP/fecha
                        $hoy = new DateTime();
                        $checkin  = new DateTime($r['check_in']);
                        $checkout = new DateTime($r['check_out']);
                        $rowClass = '';
                        if ($hoy > $checkout) $rowClass = 'table-secondary'; // pasada
                        elseif ($hoy >= $checkin && $hoy <= $checkout) $rowClass = 'table-success'; // activa
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><span class="badge bg-dark">#<?= $r['booking_id'] ?></span></td>
                        <td>
                            <a href="../customers/view.php?id=<?= $r['customer_id'] ?>" class="text-decoration-none fw-bold">
                                <?= htmlspecialchars($r['customer_name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-dark"><?= htmlspecialchars($r['room_no']) ?></span>
                            <small class="text-muted d-block"><?= htmlspecialchars($r['room_type_name']) ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['check_out'])) ?></td>
                        <td class="text-center"><?= $r['num_nights'] ?></td>
                        <td class="fw-bold"><?= number_format($r['total_price'], 2, ',', '.') ?>€</td>
                        <td>
                            <?php if ($r['remaining_price'] > 0): ?>
                            <span class="text-danger fw-bold"><?= number_format($r['remaining_price'], 2, ',', '.') ?>€</span>
                            <?php else: ?>
                            <span class="text-success">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['payment_status']): ?>
                                <span class="badge bg-success"><i class="bi bi-check"></i> Pagado</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $r['booking_id'] ?>" class="btn btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $r['booking_id'] ?>" class="btn btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a href="/hotel/admin/bookings/delete.php?id=<?= $r['booking_id'] ?>"
                                   class="btn btn-outline-danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Seguro que quieres eliminar la reserva #<?= $r['booking_id'] ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (!$r['payment_status']): ?>
                                <a href="pay.php?id=<?= $r['booking_id'] ?>" class="btn btn-outline-success" title="Marcar como pagado">
                                    <i class="bi bi-currency-euro"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reservas)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No hay reservas con este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>




<?php require_once '../../includes/footer.php'; ?>
