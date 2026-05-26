<?php
// admin/rooms/edit.php — Editar habitación
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();
$pageTitle = 'Editar Habitación'; $currentPage = 'rooms';
$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$hab = $db->prepare("SELECT r.*, rt.base_price FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = ?");
$hab->execute([$id]); $hab = $hab->fetch();
if (!$hab) { $_SESSION['error'] = "Habitación no encontrada."; header("Location: index.php"); exit; }
$tipos = $db->query("SELECT * FROM room_type")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $roomNo = trim($_POST['room_no']); $roomTypeId = (int)$_POST['room_type_id'];
    $floor  = (int)$_POST['floor'];   $desc = trim($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;
    $db->prepare("UPDATE room SET room_no=?,room_type_id=?,floor=?,description=?,status=? WHERE room_id=?")
       ->execute([$roomNo,$roomTypeId,$floor,$desc,$status,$id]);
    $_SESSION['success'] = "Habitación actualizada correctamente.";
    header("Location: index.php"); exit;
}
require_once '../../includes/header.php';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0">Editar Habitación <?= htmlspecialchars($hab['room_no']) ?></h2>
</div>
<div class="row justify-content-center"><div class="col-12 col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Número</label>
            <input type="text" class="form-control" name="room_no" value="<?= htmlspecialchars($hab['room_no']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="room_type_id" required>
                <?php foreach ($tipos as $t): ?>
                <option value="<?= $t['room_type_id'] ?>" <?= $hab['room_type_id'] == $t['room_type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['room_type_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Planta</label>
            <input type="number" class="form-control" name="floor" value="<?= $hab['floor'] ?>" min="1" max="20" required>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" id="status" <?= $hab['status'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="status">Activa (no en mantenimiento)</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($hab['description'] ?? '') ?></textarea>
        </div>
    </div>
    <hr>
    <div class="d-flex gap-2 justify-content-end">
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-gold"><i class="bi bi-save me-1"></i>Guardar cambios</button>
    </div>
</form>
</div></div></div></div>
<?php require_once '../../includes/footer.php'; ?>
