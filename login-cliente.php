<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si ya está logueado, lo mandamos a su cuenta
if (isLoggedIn()) {
    header("Location: /hotel/mi-cuenta.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Introduce tu email y contraseña.";
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM customer_user WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Guardamos los datos del cliente en la sesión
            $_SESSION['user_id']          = 'c_' . $user['id'];
            $_SESSION['username']         = $user['name'];
            $_SESSION['user_role']        = 'cliente';
            $_SESSION['customer_user_id'] = $user['id'];
            session_regenerate_id(true);

            // Si venía de reservar, le mandamos de vuelta allí
            $redirigir = $_SESSION['redirect_after_login'] ?? '/hotel/';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirigir);
            exit;
        } else {
            $error = "Email o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg" style="width:100%;max-width:420px;">

        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="fs-3 mb-0">Hotel Bellavista</h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.7);">Acceso para clientes</p>
        </div>

        <div class="card-body p-4">

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

            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="tu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Tu contraseña">
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="/hotel/registro.php" class="small">¿No tienes cuenta? Regístrate</a>
                <span class="text-muted mx-2">|</span>
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

    // Validación del formulario con jQuery
    $('#loginForm').submit(function(e) {
        var email  = $('#email').val().trim();
        var clave  = $('#password').val();
        var errorDiv  = $('#errorJs');
        var errorText = $('#errorJsText');

        errorDiv.hide();

        if (email === '') {
            e.preventDefault();
            errorText.text('El email no puede estar vacío.');
            errorDiv.fadeIn(300);
            $('#email').focus();
            return;
        }

        // Comprobación básica de formato de email
        if (email.indexOf('@') === -1) {
            e.preventDefault();
            errorText.text('Introduce un email válido.');
            errorDiv.fadeIn(300);
            $('#email').focus();
            return;
        }

        if (clave === '') {
            e.preventDefault();
            errorText.text('La contraseña no puede estar vacía.');
            errorDiv.fadeIn(300);
            $('#password').focus();
        }
    });

});
</script>
</body>
</html>
