<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$pageTitle   = 'Nuevo Empleado';
$currentPage = 'staff';

$db     = getDB();
$tipos  = $db->query("SELECT * FROM staff_type")->fetchAll();
$turnos = $db->query("SELECT * FROM shift")->fetchAll();

$nombre   = "";
$email    = "";
$telefono = "";
$salario  = "";
$fecha    = date('Y-m-d');
$error    = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre   = trim($_POST['staff_name']);
    $tipoId   = intval($_POST['staff_type_id']);
    $turnoId  = intval($_POST['shift_id']);
    $email    = trim($_POST['email']);
    $telefono = trim($_POST['contact_no']);
    $salario  = $_POST['salary'];
    $fecha    = $_POST['hire_date'];

    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } elseif ($tipoId == 0) {
        $error = "Selecciona el tipo de puesto.";
    } elseif ($turnoId == 0) {
        $error = "Selecciona el turno.";
    } else {
        $stmt = $db->prepare("INSERT INTO staff (staff_type_id, staff_name, email, contact_no, shift_id, salary, hire_date, active) VALUES (?,?,?,?,?,?,?,1)");
        $stmt->execute([$tipoId, $nombre, $email, $telefono, $turnoId, $salario ?: null, $fecha ?: null]);
        $_SESSION['success'] = "Empleado $nombre añadido correctamente.";
        header("Location: /hotel/admin/staff/index.php");
        exit;
    }
}

require_once '../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="/hotel/admin/staff/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="font-playfair mb-0">Nuevo Empleado</h2>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card border-0 shadow-sm">
<div class="card-body p-4">

    <?php if ($error != ""): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-danger d-none" id="errorJs">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span id="textoError"></span>
    </div>

    <form method="POST" id="miFormulario">

        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label class="form-label">Nombre completo *</label>
                <input type="text" class="form-control" name="staff_name"
                       value="<?= htmlspecialchars($nombre) ?>"
                       placeholder="Nombre y apellidos">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Tipo de puesto *</label>
                <select class="form-select" name="staff_type_id">
                    <option value="0">-- Elige un puesto --</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['staff_type_id'] ?>">
                        <?= htmlspecialchars($t['staff_type_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Turno *</label>
                <select class="form-select" name="shift_id">
                    <option value="0">-- Elige un turno --</option>
                    <?php foreach ($turnos as $t): ?>
                    <option value="<?= $t['shift_id'] ?>">
                        <?= htmlspecialchars($t['shift_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="emailEmpleado" name="email"
                       value="<?= htmlspecialchars($email) ?>"
                       placeholder="correo@hotel.com">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefonoEmpleado" name="contact_no"
                       value="<?= htmlspecialchars($telefono) ?>"
                       placeholder="612345678">
                <div class="form-text">9 dígitos</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Salario mensual (€)</label>
                <input type="number" class="form-control" name="salary"
                       value="<?= htmlspecialchars($salario) ?>"
                       min="0" step="0.01" placeholder="1500">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Fecha de alta</label>
                <input type="date" class="form-control" name="hire_date"
                       value="<?= $fecha ?>">
            </div>

        </div>

        <hr>

        <div class="d-flex gap-2 justify-content-end">
            <a href="/hotel/admin/staff/index.php" class="btn btn-outline-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn btn-gold">
                <i class="bi bi-save me-1"></i> Guardar empleado
            </button>
        </div>

    </form>

</div>
</div>
</div>
</div>

<script>
$(document).ready(function() {

    // Validacion del email con expresion regular
    $('#emailEmpleado').on('blur', function() {
        var email = $(this).val();
        var regex = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        if (email != "" && !regex.test(email)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Validacion del telefono con expresion regular (9 digitos)
    $('#telefonoEmpleado').on('blur', function() {
        var tel = $(this).val();
        var regex = /^[0-9]{9}$/;
        if (tel != "" && !regex.test(tel)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Validacion al enviar
    $('#miFormulario').on('submit', function(e) {
        var nombre = $('[name="staff_name"]').val().trim();

        $('#errorJs').addClass('d-none');

        if (nombre.length < 3) {
            e.preventDefault();
            $('#textoError').text('El nombre debe tener al menos 3 caracteres.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });

});
</script>

<?php require_once '../../includes/footer.php'; ?>
