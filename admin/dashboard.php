<?php
// ============================================================
// admin/dashboard.php
// Panel de control principal — muestra estadísticas del hotel
// ============================================================
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin(); // Redirige al login si no está autenticado

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';

$db = getDB();

// ---- Estadísticas para las tarjetas del dashboard ----

// Total habitaciones disponibles hoy
$stmt = $db->query("SELECT COUNT(*) as total FROM room WHERE status = 1 AND check_in_status = 0");
$habitacionesLibres = $stmt->fetch()['total'];

// Total habitaciones ocupadas hoy
$stmt = $db->query("SELECT COUNT(*) as total FROM room WHERE check_in_status = 1");
$habitacionesOcupadas = $stmt->fetch()['total'];

// Reservas de hoy (check-in hoy)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE check_in = CURDATE()");
$stmt->execute();
$checkinHoy = $stmt->fetch()['total'];

// Ingresos del mes actual
$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM booking WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())");
$stmt->execute();
$ingresosMes = $stmt->fetch()['total'];

// Quejas pendientes
$stmt = $db->query("SELECT COUNT(*) as total FROM complaint WHERE resolve_status = 0");
$quejasPendientes = $stmt->fetch()['total'];

// Últimas 5 reservas
$stmt = $db->query("
    SELECT b.booking_id, c.customer_name, r.room_no, b.check_in, b.check_out,
           b.total_price, b.payment_status, b.num_nights
    FROM booking b
    JOIN customer c ON b.customer_id = c.customer_id
    JOIN room r ON b.room_id = r.room_id
    ORDER BY b.booking_date DESC
    LIMIT 5
");
$ultimasReservas = $stmt->fetchAll();

// Ocupación por tipo de habitación
$stmt = $db->query("
    SELECT rt.room_type_name,
           COUNT(r.room_id) as total,
           SUM(r.check_in_status) as ocupadas
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    GROUP BY rt.room_type_id, rt.room_type_name
");
$ocupacionTipos = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-playfair mb-1">Dashboard</h2>
        <!-- Objeto Date de JavaScript: mostrar fecha actual -->
        <p class="text-muted mb-0" id="fechaHoy"></p>
    </div>
    <div>
        <a href="/hotel/admin/bookings/create.php" class="btn btn-gold">
            <i class="bi bi-plus-circle me-1"></i> Nueva Reserva
        </a>
    </div>
</div>

<!-- ---- Tarjetas de estadísticas ---- -->
<div class="row g-4 mb-4">
    <!-- Habitaciones libres -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm h-100" id="cardLibres">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-soft rounded-circle">
                    <i class="bi bi-door-open text-success fs-3"></i>
                </div>
                <div>
                    <div class="stat-number text-success fw-bold fs-2"><?= $habitacionesLibres ?></div>
                    <div class="stat-label text-muted small">Habitaciones libres</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Habitaciones ocupadas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-soft rounded-circle">
                    <i class="bi bi-door-closed text-danger fs-3"></i>
                </div>
                <div>
                    <div class="stat-number text-danger fw-bold fs-2"><?= $habitacionesOcupadas ?></div>
                    <div class="stat-label text-muted small">Habitaciones ocupadas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Check-ins hoy -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-soft rounded-circle">
                    <i class="bi bi-calendar-check text-primary fs-3"></i>
                </div>
                <div>
                    <div class="stat-number text-primary fw-bold fs-2"><?= $checkinHoy ?></div>
                    <div class="stat-label text-muted small">Check-ins hoy</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ingresos del mes -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-soft rounded-circle">
                    <i class="bi bi-currency-euro text-warning fs-3"></i>
                </div>
                <div>
                    <div class="stat-number text-warning fw-bold fs-2"><?= number_format($ingresosMes, 0, ',', '.') ?>€</div>
                    <div class="stat-label text-muted small">Ingresos este mes</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Fila: últimas reservas + ocupación por tipo ---- -->
<div class="row g-4">
    <!-- Últimas reservas -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar2-week text-gold me-2"></i>Últimas Reservas</h5>
                <a href="/hotel/admin/bookings/index.php" class="btn btn-sm btn-outline-secondary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Hab.</th>
                                <th>Check-in</th>
                                <th>Noches</th>
                                <th>Total</th>
                                <th>Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasReservas as $r): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $r['booking_id'] ?></span></td>
                                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($r['room_no']) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                                <td><?= $r['num_nights'] ?> noches</td>
                                <td class="fw-bold"><?= number_format($r['total_price'], 2, ',', '.') ?>€</td>
                                <td>
                                    <?php if ($r['payment_status']): ?>
                                        <span class="badge bg-success">Pagado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ultimasReservas)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No hay reservas aún</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ocupación por tipo + quejas -->
    <div class="col-12 col-lg-4">
        <!-- Ocupación por tipo de habitación -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-bar-chart text-gold me-2"></i>Ocupación por tipo</h5>
            </div>
            <div class="card-body">
                <?php foreach ($ocupacionTipos as $tipo): ?>
                <?php
                    $pct = $tipo['total'] > 0 ? round(($tipo['ocupadas'] / $tipo['total']) * 100) : 0;
                    $color = $pct >= 80 ? 'danger' : ($pct >= 50 ? 'warning' : 'success');
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($tipo['room_type_name']) ?></span>
                        <span><?= $tipo['ocupadas'] ?>/<?= $tipo['total'] ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Alerta de quejas pendientes -->
        <?php if ($quejasPendientes > 0): ?>
        <div class="card border-0 shadow-sm border-start border-danger border-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold"><?= $quejasPendientes ?> queja(s) pendiente(s)</div>
                        <a href="/hotel/admin/complaints/index.php" class="small text-danger">Resolver ahora →</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // --------------------------------------------------------
    // Objeto Date (DWEC): mostrar fecha y hora actual
    // --------------------------------------------------------
    function actualizarFecha() {
        const ahora = new Date();
        const diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const meses = ['enero','febrero','marzo','abril','mayo','junio',
                       'julio','agosto','septiembre','octubre','noviembre','diciembre'];

        const texto = diasSemana[ahora.getDay()] + ', ' +
                      ahora.getDate() + ' de ' +
                      meses[ahora.getMonth()] + ' de ' +
                      ahora.getFullYear() + ' — ' +
                      String(ahora.getHours()).padStart(2,'0') + ':' +
                      String(ahora.getMinutes()).padStart(2,'0');

        $('#fechaHoy').text(texto);
    }

    actualizarFecha();
    setInterval(actualizarFecha, 60000); // Actualizar cada minuto

    // --------------------------------------------------------
    // Animación de entrada de las tarjetas (jQuery fade)
    // --------------------------------------------------------
    $('.stat-card').each(function(i) {
        $(this).hide().delay(i * 150).fadeIn(500);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
