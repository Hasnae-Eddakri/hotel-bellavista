<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Editar Reserva'; $currentPage = 'bookings';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM booking WHERE booking_id=?"); $stmt->execute([$id]); $reserva = $stmt->fetch();
if (!$reserva) { $_SESSION['error']="Reserva no encontrada."; header("Location: /hotel/admin/bookings/index.php"); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrfToken();
    $notes = trim($_POST['notes']??''); $status = isset($_POST['payment_status'])?1:0;
    $remaining = $status ? 0 : $reserva['total_price'];
    $db->prepare("UPDATE booking SET notes=?,payment_status=?,remaining_price=? WHERE booking_id=?")->execute([$notes,$status,$remaining,$id]);
    $_SESSION['success']="Reserva actualizada."; header("Location: /hotel/admin/bookings/view.php?id=".$id); exit;
}
require_once '../../includes/header.php';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="/hotel/admin/bookings/view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0">Editar Reserva #<?= $id ?></h2>
</div>
<div class="row justify-content-center"><div class="col-12 col-lg-7">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    <div class="mb-3"><label class="form-label">Notas</label>
        <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($reserva['notes']??'') ?></textarea></div>
    <div class="mb-3"><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="payment_status" id="ps" <?= $reserva['payment_status']?'checked':'' ?>>
        <label class="form-check-label" for="ps">Marcar como pagado</label>
    </div></div>
    <hr>
    <div class="d-flex gap-2 justify-content-end">
        <a href="/hotel/admin/bookings/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-gold"><i class="bi bi-save me-1"></i>Guardar</button>
    </div>
</form>
</div></div></div></div>
<?php require_once '../../includes/footer.php'; ?>
