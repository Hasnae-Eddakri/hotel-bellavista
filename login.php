<?php
// ============================================================
// login.php
// Página de inicio de sesión del panel de administración
// ============================================================
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header("Location: /hotel/admin/dashboard.php");
    exit;
}

$error = '';

// Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas del servidor (además de las del cliente)
    if (empty($username) || empty($password)) {
        $error = "Por favor, introduce usuario y contraseña.";
    } else {
        $db = getDB();
        // Buscar el usuario en la BD por nombre de usuario
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verificar contraseña con password_verify (compara con el hash bcrypt)
        if ($user && password_verify($password, $user['password'])) {
            // Login correcto: guardar datos en la sesión
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            // Regenerar ID de sesión para prevenir session fixation
            session_regenerate_id(true);
        header("Location: /hotel/admin/dashboard.php");            exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-card card shadow-lg">
        <!-- Cabecera del card -->
        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="font-playfair fs-3 mb-0">Hotel Bellavista</h1>
            <p class="text-muted-light small mb-0">Panel de Administración</p>
        </div>

        <div class="card-body p-4">
            <!-- Mensaje de error (PHP) -->
            <?php if ($error): ?>
                <div class="alert alert-danger" id="errorServer">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Mensaje de error (JavaScript) -->
            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <form method="POST" action="" id="loginForm" novalidate>
                <!-- Campo usuario -->
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="bi bi-person"></i> Usuario
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Tu nombre de usuario"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <!-- Campo contraseña -->
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Contraseña
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Tu contraseña"
                            autocomplete="current-password"
                            required
                        >
                        <!-- Botón para mostrar/ocultar contraseña (jQuery) -->
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="eyeIcon"></i>
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

        <div class="card-footer text-center text-muted small py-2">
            <span class="text-muted">Usuario demo: <strong>admin</strong> / Contraseña: <strong>password</strong></span>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/hotel/assets/js/validation.js"></script>
<script>
$(document).ready(function() {

    // --------------------------------------------------------
    // Mostrar/ocultar contraseña con jQuery
    // --------------------------------------------------------
    $('#togglePassword').on('click', function() {
        const input = $('#password');
        const icon  = $('#eyeIcon');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // --------------------------------------------------------
    // Validación del formulario en el lado del cliente (DWEC)
    // --------------------------------------------------------
    $('#loginForm').on('submit', function(e) {
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const errorDiv  = $('#errorJs');
        const errorText = $('#errorJsText');

        // Ocultar error anterior con fadeOut (jQuery)
        errorDiv.fadeOut(200);

        // Validar que los campos no estén vacíos
        if (username === '') {
            e.preventDefault();
            errorText.text('El campo usuario no puede estar vacío.');
            errorDiv.fadeIn(300); // Efecto Fade de jQuery
            $('#username').focus();
            return;
        }

        // Validar longitud mínima de contraseña
        if (password.length < 4) {
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
