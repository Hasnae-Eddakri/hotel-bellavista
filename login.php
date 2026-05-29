<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si ya tiene sesión iniciada lo mandamos al dashboard
if (isLoggedIn()) {
    header("Location: /hotel/admin/dashboard.php");
    exit;
}

$error = '';

// Cuando el formulario se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Por favor, introduce usuario y contraseña.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login correcto, guardamos datos en la sesión
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            session_regenerate_id(true);
            header("Location: /hotel/admin/dashboard.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-card card shadow-lg">

        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="fs-3 mb-0">Hotel Bellavista</h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.7);">Panel de Administración</p>
        </div>

        <div class="card-body p-4">

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Mensaje de error de JavaScript -->
            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="bi bi-person"></i> Usuario
                    </label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Tu nombre de usuario"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Contraseña
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Tu contraseña">
                        <button class="btn btn-outline-secondary" type="button" id="verPassword">
                            <i class="bi bi-eye" id="iconoOjo"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="/hotel/index.php" class="text-muted small">
                    <i class="bi bi-arrow-left"></i> Volver a la web
                </a>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {

    // Botón para mostrar/ocultar contraseña
    $('#verPassword').click(function() {
        var input = $('#password');
        var icono = $('#iconoOjo');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icono.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icono.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Validación del formulario antes de enviarlo
    $('#loginForm').submit(function(e) {
        var usuario = $('#username').val().trim();
        var clave   = $('#password').val();
        var errorDiv  = $('#errorJs');
        var errorText = $('#errorJsText');

        errorDiv.hide();

        if (usuario === '') {
            e.preventDefault();
            errorText.text('El campo usuario no puede estar vacío.');
            errorDiv.fadeIn(300);
            $('#username').focus();
            return;
        }

        if (clave.length < 4) {
            e.preventDefault();
            errorText.text('La contraseña debe tener al menos 4 caracteres.');
            errorDiv.fadeIn(300);
            $('#password').focus();
        }
    });

});
</script>
</body>
</html>
