<?php
// ============================================================
// login-cliente.php — Login para clientes de la web pública
// ============================================================
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header("Location: /hotel/mi-cuenta.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Introduce tu email y contraseña.";
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM customer_user WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = 'c_' . $user['id']; // prefijo c_ para distinguir de admin
            $_SESSION['username']      = $user['name'];
            $_SESSION['user_role']     = 'cliente';
            $_SESSION['customer_user_id'] = $user['id'];
            session_regenerate_id(true);

            // Si venía de reservar, redirigir allí
            $redirect = $_SESSION['redirect_after_login'] ?? '/hotel/';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect);
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg" style="width:100%;max-width:420px;border-radius:12px;border:none;">

        <div class="card-header text-center bg-dark-hotel text-white py-4">
            <i class="bi bi-building fs-1 text-gold d-block mb-2"></i>
            <h1 class="font-playfair fs-3 mb-0">Hotel Bellavista</h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.7);">Acceso para clientes</p>
        </div>

        <div class="card-body p-4">

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <form method="POST" id="loginForm" novalidate>

                <div class="mb-3">
                    <label for="email" class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="tu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Tu contraseña" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-3">
                <p class="small text-muted mb-1">¿No tienes cuenta?</p>
                <a href="/hotel/registro.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-plus me-1"></i>Crear cuenta gratis
                </a>
            </div>
            <div class="text-center mt-2">
                <a href="/hotel/" class="small text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Volver a la web
                </a>
            </div>
            <hr>
            <div class="text-center">
                <small class="text-muted">¿Eres del personal del hotel?</small><br>
                <a href="/hotel/login.php" class="small text-muted">
                    <i class="bi bi-shield-lock me-1"></i>Acceso panel admin
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {
    $('#togglePwd').on('click', function () {
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

    $('#loginForm').on('submit', function (e) {
        const email = $('#email').val().trim();
        const pwd   = $('#password').val();
        const regex = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;

        $('#errorJs').addClass('d-none');

        if (!regex.test(email)) {
            e.preventDefault();
            $('#errorJsText').text('Introduce un email válido.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (pwd.length < 4) {
            e.preventDefault();
            $('#errorJsText').text('Introduce tu contraseña.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>
</body>
</html>
