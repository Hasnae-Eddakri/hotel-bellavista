<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$pageTitle   = 'Editar Empleado';
$currentPage = 'staff';

$db = getDB();
$id = intval($_GET['id']);

$stmt = $db->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    $_SESSION['error'] = "Empleado no encontrado.";
    header("Location: /hotel/admin/staff/index.php");
    exit;
}

$tipos  = $db->query("SELECT * FROM staff_type")->fetchAll();
$turnos = $db->query("SELECT * FROM shift")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre   = trim($_POST['staff_name']);
    $tipoId   = intval($_POST['staff_type_id']);
    $turnoId  = intval($_POST['shift_id']);
    $email    = trim($_POST['email']);
    $telefono = trim($_POST['contact_no']);
    $salario  = $_POST['salary'];
    $fecha    = $_POST['hire_date'];
    $activo   = isset($_POST['active']) ? 1 : 0;

    $stmt = $db->prepare("UPDATE staff SET staff_type_id=?, staff_name=?, email=?, contact_no=?, shift_id=?, salary=?, hire_date=?, active=? WHERE staff_id=?");
    $stmt->execute([$tipoId, $nombre, $email, $telefono, $turnoId, $salario ?: null, $fecha ?: null, $activo, $id]);

    $_SESSION['success'] = "Empleado actualizado correctamente.";
    header("Location: /hotel/admin/staff/index.php");
    exit;
}

require_once '../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="/hotel/admin/staff/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="font-playfair mb-0">Editar Empleado</h2>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card border-0 shadow-sm">
<div class="card-body p-4">

    <form method="POST">

        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label class="form-label">Nombre completo *</label>
                <input type="text" class="form-control" name="staff_name"
                       value="<?= htmlspecialchars($emp['staff_name']) ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Tipo de puesto</label>
                <select class="form-select" name="staff_type_id">
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['staff_type_id'] ?>"
                        <?= $emp['staff_type_id'] == $t['staff_type_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['staff_type_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Turno</label>
                <select class="form-select" name="shift_id">
                    <?php foreach ($turnos as $t): ?>
                    <option value="<?= $t['shift_id'] ?>"
                        <?= $emp['shift_id'] == $t['shift_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['shift_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email"
                       value="<?= htmlspecialchars($emp['email'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="tel" class="form-control" name="contact_no"
                       value="<?= htmlspecialchars($emp['contact_no'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Salario (€/mes)</label>
                <input type="number" class="form-control" name="salary"
                       value="<?= $emp['salary'] ?>" step="0.01" min="0">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Fecha de alta</label>
                <input type="date" class="form-control" name="hire_date"
                       value="<?= $emp['hire_date'] ?>">
            </div>

            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="active" id="activo"
                           <?= $emp['active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Empleado activo</label>
                </div>
            </div>

        </div>

        <hr>

        <div class="d-flex gap-2 justify-content-end">
            <a href="/hotel/admin/staff/index.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-gold">
                <i class="bi bi-save me-1"></i> Guardar cambios
            </button>
        </div>

    </form>

</div>
</div>
</div>
</div>

<?php require_once '../../includes/footer.php'; ?>
