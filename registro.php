<?php
// ============================================================
// registro.php — Página de registro para clientes
// ============================================================
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si ya está logueado redirigir
if (isLoggedIn()) {
    header("Location: /hotel/mi-cuenta.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validaciones servidor
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $db = getDB();
        // Comprobar que el email no exista ya
        $chk = $db->prepare("SELECT id FROM customer_user WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = "Ya existe una cuenta con ese email. ¿Quieres iniciar sesión?";
        } else {
            // Crear cuenta de cliente
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO customer_user (name, email, password) VALUES (?, ?, ?)")
               ->execute([$name, $email, $hash]);
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg" style="width:100%;max-width:460px;border-radius:12px;border:none;">

        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="font-playfair fs-3 mb-0">Hotel Bellavista</h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.7);">Crea tu cuenta de cliente</p>
        </div>

        <div class="card-body p-4">

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <div class="mt-2">
                    <a href="/hotel/login-cliente.php" class="btn btn-sm btn-success">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger" id="errorServer">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <?php if (!$success): ?>
            <form method="POST" id="formRegistro" novalidate>

                <div class="mb-3">
                    <label for="name" class="form-label"><i class="bi bi-person me-1"></i>Nombre completo</label>
                    <input type="text" class="form-control" id="name" name="name"
                           placeholder="Tu nombre y apellidos"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="tu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <div class="invalid-feedback">Introduce un email válido.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Mínimo 6 caracteres" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                            <i class="bi bi-eye" id="eyePwd"></i>
                        </button>
                    </div>
                    <!-- Barra de fortaleza de contraseña -->
                    <div class="progress mt-2" style="height:5px;">
                        <div class="progress-bar" id="pwdStrength" style="width:0%;"></div>
                    </div>
                    <small id="pwdStrengthText" class="text-muted"></small>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label"><i class="bi bi-lock-fill me-1"></i>Repetir contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Repite tu contraseña" required>
                    <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2">
                    <i class="bi bi-person-plus me-2"></i>Crear cuenta
                </button>
            </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <p class="small text-muted mb-1">¿Ya tienes cuenta?</p>
                <a href="/hotel/login-cliente.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
                </a>
            </div>
            <div class="text-center mt-2">
                <a href="/hotel/" class="small text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Volver a la web
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {

    // Mostrar/ocultar contraseña
    $('#togglePwd').on('click', function () {
        const input = $('#password');
        const icon  = $('#eyePwd');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Barra de fortaleza de contraseña (DOM + eventos)
    $('#password').on('input', function () {
        const pwd    = $(this).val();
        const bar    = $('#pwdStrength');
        const texto  = $('#pwdStrengthText');
        let fuerza   = 0;
        if (pwd.length >= 6)               fuerza++;
        if (pwd.length >= 10)              fuerza++;
        if (/[A-Z]/.test(pwd))             fuerza++;
        if (/[0-9]/.test(pwd))             fuerza++;
        if (/[^A-Za-z0-9]/.test(pwd))      fuerza++;

        const niveles = [
            { pct: 0,   color: '',        texto: '' },
            { pct: 20,  color: 'bg-danger',  texto: 'Muy débil' },
            { pct: 40,  color: 'bg-warning', texto: 'Débil' },
            { pct: 60,  color: 'bg-info',    texto: 'Regular' },
            { pct: 80,  color: 'bg-primary', texto: 'Fuerte' },
            { pct: 100, color: 'bg-success', texto: 'Muy fuerte' },
        ];
        const n = niveles[fuerza];
        bar.attr('class', 'progress-bar ' + n.color).css('width', n.pct + '%');
        texto.text(n.texto);
    });

    // Validación email en tiempo real
    $('#email').on('blur', function () {
        const regex = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        if (regex.test($(this).val().trim())) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });

    // Validación confirmación contraseña en tiempo real
    $('#confirm_password').on('input', function () {
        if ($(this).val() === $('#password').val()) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });

    // Validación completa al enviar
    $('#formRegistro').on('submit', function (e) {
        const name    = $('#name').val().trim();
        const email   = $('#email').val().trim();
        const pwd     = $('#password').val();
        const confirm = $('#confirm_password').val();
        const regexEmail = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;

        $('#errorJs').addClass('d-none');

        if (name.length < 2) {
            e.preventDefault();
            $('#errorJsText').text('El nombre debe tener al menos 2 caracteres.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!regexEmail.test(email)) {
            e.preventDefault();
            $('#errorJsText').text('Introduce un email válido.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (pwd.length < 6) {
            e.preventDefault();
            $('#errorJsText').text('La contraseña debe tener al menos 6 caracteres.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (pwd !== confirm) {
            e.preventDefault();
            $('#errorJsText').text('Las contraseñas no coinciden.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>
</body>
</html>
