<?php
// ============================================================
// admin/customers/create.php
// Formulario de nuevo cliente con validación JS completa (DWEC)
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Nuevo Cliente';
$currentPage = 'customers';

$db    = getDB();
$tipos = $db->query("SELECT * FROM id_card_type")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name       = trim($_POST['customer_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['contact_no'] ?? '');
    $idTypeId   = (int)($_POST['id_card_type_id'] ?? 0);
    $idCardNo   = trim($_POST['id_card_no'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $nationality= trim($_POST['nationality'] ?? 'Española');
    $birthDate  = $_POST['birth_date'] ?? null;

    $errors = [];
    if (empty($name))       $errors[] = "El nombre es obligatorio.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El email no es válido.";
    if (empty($phone))      $errors[] = "El teléfono es obligatorio.";
    if (!$idTypeId)         $errors[] = "Selecciona el tipo de documento.";
    if (empty($idCardNo))   $errors[] = "El número de documento es obligatorio.";
    if (empty($address))    $errors[] = "La dirección es obligatoria.";

    // Comprobar email único
    $chk = $db->prepare("SELECT customer_id FROM customer WHERE email = ?");
    $chk->execute([$email]);
    if ($chk->fetch()) $errors[] = "Ya existe un cliente con ese email.";

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO customer (customer_name, email, contact_no, id_card_type_id, id_card_no, address, nationality, birth_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $idTypeId, $idCardNo, $address, $nationality, $birthDate ?: null]);
        $_SESSION['success'] = "Cliente {$name} registrado correctamente.";
        header("Location: index.php");
        exit;
    }
}

require_once '../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0">Nuevo Cliente</h2>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-9">
<div class="card border-0 shadow-sm">
<div class="card-body p-4">

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
    </ul></div>
    <?php endif; ?>

    <div class="alert alert-danger d-none" id="errorJs">
        <i class="bi bi-exclamation-triangle me-2"></i><span id="errorJsText"></span>
    </div>

    <form method="POST" id="formCliente" novalidate>
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

        <div class="row g-3">
            <!-- Nombre -->
            <div class="col-12 col-md-6">
                <label for="customer_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="customer_name" name="customer_name"
                       placeholder="Nombre y apellidos"
                       value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" required>
                <div class="valid-feedback">¡Correcto!</div>
                <div class="invalid-feedback">El nombre debe tener al menos 3 caracteres.</div>
            </div>

            <!-- Email -->
            <div class="col-12 col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="ejemplo@correo.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <div class="invalid-feedback">Introduce un email válido.</div>
            </div>

            <!-- Teléfono -->
            <div class="col-12 col-md-6">
                <label for="contact_no" class="form-label">Teléfono <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="contact_no" name="contact_no"
                       placeholder="600123456"
                       value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>" required>
                <div class="form-text">Formato: 9 dígitos (ej: 612345678)</div>
                <div class="invalid-feedback">El teléfono debe tener 9 dígitos.</div>
            </div>

            <!-- Fecha de nacimiento -->
            <div class="col-12 col-md-6">
                <label for="birth_date" class="form-label">Fecha de nacimiento</label>
                <input type="date" class="form-control" id="birth_date" name="birth_date"
                       value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
                       max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                <div class="form-text" id="edadInfo">Selecciona una fecha para calcular la edad</div>
            </div>

            <!-- Tipo de documento -->
            <div class="col-12 col-md-6">
                <label for="id_card_type_id" class="form-label">Tipo de documento <span class="text-danger">*</span></label>
                <select class="form-select" id="id_card_type_id" name="id_card_type_id" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id_card_type_id'] ?>" <?= ($_POST['id_card_type_id'] ?? '') == $t['id_card_type_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['id_card_type']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Número de documento -->
            <div class="col-12 col-md-6">
                <label for="id_card_no" class="form-label">Número de documento <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="id_card_no" name="id_card_no"
                       placeholder="12345678A"
                       value="<?= htmlspecialchars($_POST['id_card_no'] ?? '') ?>" required>
                <div class="form-text" id="docHint">Selecciona el tipo de documento para ver el formato</div>
            </div>

            <!-- Nacionalidad -->
            <div class="col-12 col-md-6">
                <label for="nationality" class="form-label">Nacionalidad</label>
                <input type="text" class="form-control" id="nationality" name="nationality"
                       value="<?= htmlspecialchars($_POST['nationality'] ?? 'Española') ?>">
            </div>

            <!-- Dirección -->
            <div class="col-12 col-md-6">
                <label for="address" class="form-label">Dirección <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="address" name="address"
                       placeholder="Calle, número, ciudad"
                       value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
            </div>
        </div>

        <hr>
        <div class="d-flex gap-2 justify-content-end">
            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-gold">
                <i class="bi bi-person-plus me-1"></i>Registrar cliente
            </button>
        </div>
    </form>
</div>
</div>
</div>
</div>

<script>
$(document).ready(function() {

    // --------------------------------------------------------
    // Calcular edad con objeto Date (DWEC)
    // --------------------------------------------------------
    $('#birth_date').on('change', function() {
        const fecha = new Date($(this).val());
        const hoy   = new Date();

        // Calcular edad
        let edad = hoy.getFullYear() - fecha.getFullYear();
        const mes = hoy.getMonth() - fecha.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
            edad--;
        }

        if (edad >= 0 && edad < 150) {
            $('#edadInfo').text('Edad: ' + edad + ' años').removeClass('text-muted').addClass('text-primary');
        } else {
            $('#edadInfo').text('Fecha inválida').addClass('text-danger');
        }
    });

    // --------------------------------------------------------
    // Hint del formato del documento según tipo (DOM)
    // --------------------------------------------------------
    const formatosDoc = {
        '1': 'DNI: 8 números + 1 letra (ej: 12345678A)',
        '2': 'Pasaporte: letras y números (ej: AAA123456)',
        '3': 'NIE: X/Y/Z + 7 números + letra (ej: X1234567A)',
        '4': 'Permiso conducir: letras y números'
    };

    $('#id_card_type_id').on('change', function() {
        const tipo  = $(this).val();
        const hint  = formatosDoc[tipo] || 'Introduce el número del documento';
        // Actualizar el DOM con el hint
        $('#docHint').text(hint).removeClass('text-muted').addClass('text-info');
        // Limpiar el campo si cambia el tipo
        $('#id_card_no').val('').removeClass('is-valid is-invalid');
    });

    // --------------------------------------------------------
    // Validación en tiempo real del email (expresión regular)
    // --------------------------------------------------------
    $('#email').on('blur', function() {
        // Expresión regular para validar email
        const regexEmail = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        if (regexEmail.test($(this).val().trim())) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });

    // --------------------------------------------------------
    // Validación en tiempo real del teléfono (expresión regular)
    // --------------------------------------------------------
    $('#contact_no').on('blur', function() {
        // Expresión regular: exactamente 9 dígitos
        const regexTel = /^[0-9]{9}$/;
        if (regexTel.test($(this).val().trim())) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });

    // --------------------------------------------------------
    // Validación del formulario al enviar (DWEC)
    // --------------------------------------------------------
    $('#formCliente').on('submit', function(e) {
        const nombre  = $('#customer_name').val().trim();
        const email   = $('#email').val().trim();
        const telefono= $('#contact_no').val().trim();
        const tipo    = $('#id_card_type_id').val();
        const docNo   = $('#id_card_no').val().trim();
        const address = $('#address').val().trim();

        // Expresiones regulares
        const regexEmail = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        const regexTel   = /^[0-9]{9}$/;
        const regexDNI   = /^[0-9]{8}[A-Za-z]$/;
        const regexNIE   = /^[XYZxyz][0-9]{7}[A-Za-z]$/;

        $('#errorJs').addClass('d-none');

        if (nombre.length < 3) {
            e.preventDefault();
            $('#errorJsText').text('El nombre debe tener al menos 3 caracteres.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!regexEmail.test(email)) {
            e.preventDefault();
            $('#errorJsText').text('El formato del email no es válido.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!regexTel.test(telefono)) {
            e.preventDefault();
            $('#errorJsText').text('El teléfono debe tener exactamente 9 dígitos.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!tipo) {
            e.preventDefault();
            $('#errorJsText').text('Selecciona el tipo de documento.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        // Validar DNI con regex
        if (tipo === '1' && !regexDNI.test(docNo)) {
            e.preventDefault();
            $('#errorJsText').text('El DNI debe tener 8 números seguidos de una letra (ej: 12345678A).');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        // Validar NIE con regex
        if (tipo === '3' && !regexNIE.test(docNo)) {
            e.preventDefault();
            $('#errorJsText').text('El NIE debe tener formato X1234567A.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (address.length < 5) {
            e.preventDefault();
            $('#errorJsText').text('La dirección debe tener al menos 5 caracteres.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
