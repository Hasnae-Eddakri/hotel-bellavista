<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Ver Reserva';
$currentPage = 'bookings';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT b.*, c.customer_name, c.email, c.contact_no, r.room_no, rt.room_type_name, rt.base_price FROM booking b JOIN customer c ON b.customer_id=c.customer_id JOIN room r ON b.room_id=r.room_id JOIN room_type rt ON r.room_type_id=rt.room_type_id WHERE b.booking_id=?");
$stmt->execute([$id]);
$reserva = $stmt->fetch();
if (!$reserva) {
    $_SESSION['error'] = "Reserva no encontrada.";
    header("Location: /hotel/admin/bookings/index.php");
    exit;
}
require_once '../../includes/header.php';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="/hotel/admin/bookings/index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Reserva #<?= $id ?></h2>
    <div class="ms-auto d-flex gap-2">
        <a href="/hotel/admin/bookings/pdf.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i>Factura PDF
        </a>
        <?php if (!$reserva['payment_status']): ?>
            <a href="/hotel/admin/bookings/pay.php?id=<?= $id ?>" class="btn btn-success">
                <i class="bi bi-currency-euro me-1"></i>Marcar como pagado
            </a>
        <?php endif; ?>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle text-gold me-2"></i>Datos</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-bold" style="width:130px;">Cliente</td>
                        <td><a href="/hotel/admin/customers/view.php?id=<?= $reserva['customer_id'] ?>"><?= htmlspecialchars($reserva['customer_name']) ?></a></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Habitación</td>
                        <td><span class="badge bg-dark"><?= htmlspecialchars($reserva['room_no']) ?></span> <?= htmlspecialchars($reserva['room_type_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Check-in</td>
                        <td><?= date('d/m/Y', strtotime($reserva['check_in'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Check-out</td>
                        <td><?= date('d/m/Y', strtotime($reserva['check_out'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Noches</td>
                        <td><?= $reserva['num_nights'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Total</td>
                        <td class="fw-bold text-gold fs-5"><?= number_format($reserva['total_price'], 2, ',', '.') ?>€</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Pendiente</td>
                        <td class="<?= $reserva['remaining_price'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>"><?= number_format($reserva['remaining_price'], 2, ',', '.') ?>€</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-bold">Pago</td>
                        <td><?= $reserva['payment_status'] ? '<span class="badge bg-success">Pagado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>' ?></td>
                    </tr>
                    <?php if ($reserva['notes']): ?><tr>
                            <td class="text-muted fw-bold">Notas</td>
                            <td><?= htmlspecialchars($reserva['notes']) ?></td>
                        </tr><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-calculator text-gold me-2"></i>Estado</h5>
            </div>
            <div class="card-body" id="estadoDiv"></div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        const checkin = new Date('<?= $reserva['check_in'] ?>');
        const checkout = new Date('<?= $reserva['check_out'] ?>');
        const hoy = new Date();
        const opciones = {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        };
        let estado = '';
        if (hoy < checkin) {
            const dias = Math.round((checkin - hoy) / (1000 * 60 * 60 * 24));
            estado = '<span class="badge bg-primary">Faltan ' + dias + ' día(s)</span>';
        } else if (hoy >= checkin && hoy <= checkout) {
            estado = '<span class="badge bg-success">En curso ahora</span>';
        } else {
            estado = '<span class="badge bg-secondary">Finalizada</span>';
        }
        $('#estadoDiv').html('<p><strong>Check-in:</strong> ' + checkin.toLocaleDateString("es-ES", opciones) + '</p><p><strong>Check-out:</strong> ' + checkout.toLocaleDateString("es-ES", opciones) + '</p><p><strong>Estado:</strong> ' + estado + '</p><p><strong>Total:</strong> <span class="fs-4 text-gold fw-bold"><?= number_format($reserva['total_price'], 2, ',', '.') ?>€</span></p>');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>