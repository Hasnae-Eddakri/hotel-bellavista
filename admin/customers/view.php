<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Ver Cliente'; $currentPage = 'customers';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT c.*, ict.id_card_type FROM customer c JOIN id_card_type ict ON c.id_card_type_id = ict.id_card_type_id WHERE c.customer_id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();
if (!$cliente) { $_SESSION['error'] = "Cliente no encontrado."; header("Location: /hotel/admin/customers/index.php"); exit; }
$reservas = $db->prepare("SELECT b.*, r.room_no, rt.room_type_name FROM booking b JOIN room r ON b.room_id = r.room_id JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE b.customer_id = ? ORDER BY b.check_in DESC");
$reservas->execute([$id]); $reservas = $reservas->fetchAll();
require_once '../../includes/header.php';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="/hotel/admin/customers/index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0"><i class="bi bi-person-circle text-gold me-2"></i><?= htmlspecialchars($cliente['customer_name']) ?></h2>
    <a href="/hotel/admin/customers/edit.php?id=<?= $id ?>" class="btn btn-gold ms-auto"><i class="bi bi-pencil me-1"></i>Editar</a>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-person text-gold me-2"></i>Datos personales</h5></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted fw-bold" style="width:130px;">Nombre</td><td><?= htmlspecialchars($cliente['customer_name']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Email</td><td><a href="mailto:<?= htmlspecialchars($cliente['email']) ?>"><?= htmlspecialchars($cliente['email']) ?></a></td></tr>
                    <tr><td class="text-muted fw-bold">Teléfono</td><td><?= htmlspecialchars($cliente['contact_no']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Documento</td><td><span class="badge bg-secondary"><?= htmlspecialchars($cliente['id_card_type']) ?></span> <?= htmlspecialchars($cliente['id_card_no']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Dirección</td><td><?= htmlspecialchars($cliente['address']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Nacionalidad</td><td><?= htmlspecialchars($cliente['nationality'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted fw-bold">Nacimiento</td><td><?= $cliente['birth_date'] ? date('d/m/Y', strtotime($cliente['birth_date'])) : '—' ?></td></tr>
                    <tr><td class="text-muted fw-bold">Edad</td><td><span id="edadCliente" class="badge bg-primary">—</span></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-check text-gold me-2"></i>Reservas</h5>
                <span class="badge bg-primary"><?= count($reservas) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Habitación</th><th>Check-in</th><th>Noches</th><th>Total</th><th>Pago</th></tr></thead>
                    <tbody>
                        <?php foreach ($reservas as $r): ?>
                        <tr>
                            <td><span class="badge bg-secondary">#<?= $r['booking_id'] ?></span></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($r['room_no']) ?></span> <small class="text-muted"><?= htmlspecialchars($r['room_type_name']) ?></small></td>
                            <td><?= date('d/m/Y', strtotime($r['check_in'])) ?></td>
                            <td><?= $r['num_nights'] ?></td>
                            <td class="fw-bold"><?= number_format($r['total_price'], 2, ',', '.') ?>€</td>
                            <td><?= $r['payment_status'] ? '<span class="badge bg-success">Pagado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservas)): ?><tr><td colspan="6" class="text-center text-muted py-3">Sin reservas.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const fecha = '<?= $cliente['birth_date'] ?? '' ?>';
    if (fecha) {
        const nac = new Date(fecha); const hoy = new Date();
        let edad = hoy.getFullYear() - nac.getFullYear();
        if (hoy.getMonth() - nac.getMonth() < 0 || (hoy.getMonth() - nac.getMonth() === 0 && hoy.getDate() < nac.getDate())) edad--;
        $('#edadCliente').text(edad + ' años');
    }
});
</script>
<?php require_once '../../includes/footer.php'; ?>
