<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    header("Location: /hotel/mi-cuenta.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirm_password'] ?? '';

    // Comprobaciones del servidor
    if ($nombre === '' || $email === '' || $password === '') {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $db = getDB();

        // Comprobamos que el email no esté ya registrado
        $check = $db->prepare("SELECT id FROM customer_user WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Ya existe una cuenta con ese email. ¿Quieres iniciar sesión?";
        } else {
            // Creamos la cuenta con la contraseña cifrada
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO customer_user (name, email, password) VALUES (?, ?, ?)")
               ->execute([$nombre, $email, $hash]);
            $success = "¡Cuenta creada correctamente! Ya puedes iniciar sesión.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg" style="width:100%;max-width:460px;">

        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="fs-3 mb-0">Hotel Bellavista</h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.7);">Crea tu cuenta de cliente</p>
        </div>

        <div class="card-body p-4">

            <?php if ($success !== ''): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <div class="mt-2">
                    <a href="/hotel/login-cliente.php" class="btn btn-sm btn-success">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
                    </a>
                </div>
            </div>
            <?php else: ?>

            <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Error de JavaScript -->
            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <form method="POST" id="formRegistro">
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="name" name="name"
                           placeholder="Tu nombre y apellidos"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="tu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Mínimo 6 caracteres">
                    <div class="form-text" id="indicadorClave"></div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Repetir contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Repite la contraseña">
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2">
                    <i class="bi bi-person-plus me-2"></i>Crear cuenta
                </button>
            </form>

            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="/hotel/login-cliente.php" class="small">¿Ya tienes cuenta? Inicia sesión</a>
                <span class="text-muted mx-2">|</span>
                <a href="/hotel/index.php" class="text-muted small">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {

    // Indicador de seguridad de la contraseña
    $('#password').on('keyup', function() {
        var clave = $(this).val();
        var indicador = $('#indicadorClave');

        if (clave.length === 0) {
            indicador.text('');
        } else if (clave.length < 6) {
            indicador.html('<span class="text-danger">Contraseña muy corta</span>');
        } else if (clave.length < 10) {
            indicador.html('<span class="text-warning">Contraseña aceptable</span>');
        } else {
            indicador.html('<span class="text-success">Contraseña segura</span>');
        }
    });

    // Validación antes de enviar
    $('#formRegistro').submit(function(e) {
        var nombre   = $('#name').val().trim();
        var email    = $('#email').val().trim();
        var clave    = $('#password').val();
        var repetir  = $('#confirm_password').val();
        var errorDiv  = $('#errorJs');
        var errorText = $('#errorJsText');

        errorDiv.hide();

        if (nombre === '') {
            e.preventDefault();
            errorText.text('El nombre es obligatorio.');
            errorDiv.fadeIn(300);
            $('#name').focus();
            return;
        }

        if (email === '' || email.indexOf('@') === -1) {
            e.preventDefault();
            errorText.text('Introduce un email válido.');
            errorDiv.fadeIn(300);
            $('#email').focus();
            return;
        }

        if (clave.length < 6) {
            e.preventDefault();
            errorText.text('La contraseña debe tener al menos 6 caracteres.');
            errorDiv.fadeIn(300);
            $('#password').focus();
            return;
        }

        if (clave !== repetir) {
            e.preventDefault();
            errorText.text('Las contraseñas no coinciden.');
            errorDiv.fadeIn(300);
            $('#confirm_password').focus();
        }
    });

});
</script>
</body>
</html>
