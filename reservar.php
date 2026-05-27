<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Solo pueden reservar los clientes logueados
if (!isLoggedIn() || currentRole() !== 'cliente') {
    $_SESSION['redirect_after_login'] = '/hotel/reservar.php?' . http_build_query($_GET);
    header("Location: /hotel/login-cliente.php");
    exit;
}

$db = getDB();

// Parámetros que vienen de la URL (del buscador de la página principal)
$room_id  = (int)($_GET['room_id']  ?? 0);
$checkin  = $_GET['checkin']  ?? '';
$checkout = $_GET['checkout'] ?? '';

// Cargamos todas las habitaciones disponibles
$habitaciones = $db->query("
    SELECT r.room_id, r.room_no, rt.room_type_name, rt.base_price
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.status = 1 AND r.check_in_status = 0
    ORDER BY r.room_no
")->fetchAll();

$success = '';
$error   = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $room_id_sel = (int)$_POST['room_id'];
    $checkin_sel  = $_POST['check_in']  ?? '';
    $checkout_sel = $_POST['check_out'] ?? '';
    $notas        = trim($_POST['notes'] ?? '');

    // Validaciones
    if (!$room_id_sel)   $errores[] = "Selecciona una habitación.";
    if (!$checkin_sel)   $errores[] = "La fecha de entrada es obligatoria.";
    if (!$checkout_sel)  $errores[] = "La fecha de salida es obligatoria.";

    if (count($errores) === 0) {
        $fechaEntrada = new DateTime($checkin_sel);
        $fechaSalida  = new DateTime($checkout_sel);

        if ($fechaSalida <= $fechaEntrada) {
            $errores[] = "La fecha de salida debe ser posterior a la de entrada.";
        } else {
            $numNoches = $fechaEntrada->diff($fechaSalida)->days;

            // Obtenemos el precio de la habitación
            $stmtHab = $db->prepare("SELECT rt.base_price FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = ?");
            $stmtHab->execute([$room_id_sel]);
            $precioBase  = $stmtHab->fetch()['base_price'];
            $precioTotal = $numNoches * $precioBase;

            // Comprobamos que la habitación esté disponible en esas fechas
            $stmtCheck = $db->prepare("SELECT booking_id FROM booking WHERE room_id = ? AND check_in < ? AND check_out > ?");
            $stmtCheck->execute([$room_id_sel, $checkout_sel, $checkin_sel]);

            if ($stmtCheck->fetch()) {
                $errores[] = "La habitación no está disponible en esas fechas. Prueba con otras fechas.";
            } else {
                // Obtenemos el customer_id del usuario
                $uid = $_SESSION['customer_user_id'];
                $stmt = $db->prepare("SELECT customer_id FROM customer_user WHERE id = ?");
                $stmt->execute([$uid]);
                $cu = $stmt->fetch();

                // Si el cliente no tiene customer_id, lo creamos
                if (empty($cu['customer_id'])) {
                    $userData = $db->prepare("SELECT * FROM customer_user WHERE id = ?");
                    $userData->execute([$uid]);
                    $userData = $userData->fetch();

                    // Tipo de documento por defecto (1 = DNI)
                    $db->prepare("INSERT INTO customer (customer_name, email, id_card_type_id, id_card_no) VALUES (?, ?, 1, ?)")
                       ->execute([$userData['name'], $userData['email'], 'PENDIENTE']);

                    $customerId = $db->lastInsertId();
                    $db->prepare("UPDATE customer_user SET customer_id = ? WHERE id = ?")
                       ->execute([$customerId, $uid]);
                } else {
                    $customerId = $cu['customer_id'];
                }

                // Insertamos la reserva
                $db->prepare("
                    INSERT INTO booking (customer_id, room_id, check_in, check_out, num_nights, total_price, notes, booking_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ")->execute([$customerId, $room_id_sel, $checkin_sel, $checkout_sel, $numNoches, $precioTotal, $notas]);

                $success = "¡Reserva realizada correctamente! Te esperamos en el Hotel Bellavista.";
            }
        }
    }

    if (count($errores) > 0) {
        $error = implode('<br>', $errores);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar habitación — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container">
        <a class="navbar-brand" href="/hotel/">
            <i class="bi bi-building text-gold me-2"></i>Hotel Bellavista
        </a>
        <div class="d-flex gap-2">
            <a href="/hotel/mi-cuenta.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-person me-1"></i><?= htmlspecialchars(currentUser()) ?>
            </a>
            <a href="/hotel/logout-cliente.php" class="btn btn-sm btn-outline-danger">Salir</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <h2 class="mb-4">
                <i class="bi bi-calendar-plus text-gold me-2"></i>Reservar habitación
            </h2>

            <?php if ($success !== ''): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <div class="mt-2">
                    <a href="/hotel/mi-cuenta.php" class="btn btn-sm btn-success">Ver mis reservas</a>
                    <a href="/hotel/" class="btn btn-sm btn-outline-success ms-2">Volver al inicio</a>
                </div>
            </div>
            <?php else: ?>

            <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Error de JavaScript -->
            <div class="alert alert-danger d-none" id="errorJs">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorJsText"></span>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" id="formReserva">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                        <!-- Selección de habitación -->
                        <div class="mb-3">
                            <label for="room_id" class="form-label">Habitación</label>
                            <select class="form-select" id="room_id" name="room_id">
                                <option value="">-- Selecciona una habitación --</option>
                                <?php foreach ($habitaciones as $hab): ?>
                                <option value="<?= $hab['room_id'] ?>"
                                    data-precio="<?= $hab['base_price'] ?>"
                                    <?= $room_id == $hab['room_id'] ? 'selected' : '' ?>>
                                    Hab. <?= htmlspecialchars($hab['room_no']) ?> — <?= htmlspecialchars($hab['room_type_name']) ?> (<?= number_format($hab['base_price'], 2) ?>€/noche)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="check_in" class="form-label">Fecha de entrada</label>
                                <input type="date" class="form-control" id="check_in" name="check_in"
                                       value="<?= htmlspecialchars($_POST['check_in'] ?? $checkin) ?>">
                            </div>
                            <div class="col-6">
                                <label for="check_out" class="form-label">Fecha de salida</label>
                                <input type="date" class="form-control" id="check_out" name="check_out"
                                       value="<?= htmlspecialchars($_POST['check_out'] ?? $checkout) ?>">
                            </div>
                        </div>

                        <!-- Resumen del precio (se calcula con JavaScript) -->
                        <div class="alert alert-info d-none" id="resumenPrecio">
                            <i class="bi bi-info-circle me-2"></i>
                            <span id="textoPrecio"></span>
                        </div>

                        <!-- Notas -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Observaciones (opcional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Alergias, preferencias, llegada tarde..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-2">
                            <i class="bi bi-check-circle me-2"></i>Confirmar reserva
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {

    // Calculamos el precio total cuando cambian las fechas o la habitación
    function calcularPrecio() {
        var entrada  = $('#check_in').val();
        var salida   = $('#check_out').val();
        var opcion   = $('#room_id option:selected');
        var precio   = parseFloat(opcion.data('precio'));

        if (entrada && salida && precio) {
            var fechaEntrada = new Date(entrada);
            var fechaSalida  = new Date(salida);
            var diferencia   = fechaSalida - fechaEntrada;
            var noches       = diferencia / (1000 * 60 * 60 * 24);

            if (noches > 0) {
                var total = noches * precio;
                $('#textoPrecio').text(noches + ' noche(s) × ' + precio.toFixed(2) + '€ = ' + total.toFixed(2) + '€ total');
                $('#resumenPrecio').removeClass('d-none');
            } else {
                $('#resumenPrecio').addClass('d-none');
            }
        }
    }

    $('#room_id, #check_in, #check_out').on('change', calcularPrecio);

    // Establecemos la fecha mínima de hoy
    var hoy = new Date().toISOString().split('T')[0];
    $('#check_in').attr('min', hoy);
    $('#check_out').attr('min', hoy);

    // Cuando cambia la entrada, actualizamos el mínimo de salida
    $('#check_in').on('change', function() {
        $('#check_out').attr('min', $(this).val());
    });

    // Validación antes de enviar
    $('#formReserva').submit(function(e) {
        var habitacion = $('#room_id').val();
        var entrada    = $('#check_in').val();
        var salida     = $('#check_out').val();
        var errorDiv   = $('#errorJs');
        var errorText  = $('#errorJsText');

        errorDiv.hide();

        if (!habitacion) {
            e.preventDefault();
            errorText.text('Debes seleccionar una habitación.');
            errorDiv.fadeIn(300);
            return;
        }

        if (!entrada) {
            e.preventDefault();
            errorText.text('La fecha de entrada es obligatoria.');
            errorDiv.fadeIn(300);
            return;
        }

        if (!salida) {
            e.preventDefault();
            errorText.text('La fecha de salida es obligatoria.');
            errorDiv.fadeIn(300);
            return;
        }

        if (new Date(salida) <= new Date(entrada)) {
            e.preventDefault();
            errorText.text('La fecha de salida debe ser posterior a la de entrada.');
            errorDiv.fadeIn(300);
        }
    });

    // Calculamos al cargar si hay valores en la URL
    calcularPrecio();
});
</script>
</body>
</html>
