<?php
// ============================================================
// reservar.php — Formulario de reserva para clientes logueados
// ============================================================
require_once 'config/database.php';
require_once 'includes/auth.php';

// Si no está logueado como cliente, guardar la URL y redirigir al login
if (!isLoggedIn() || currentRole() !== 'cliente') {
    $_SESSION['redirect_after_login'] = '/hotel/reservar.php?' . http_build_query($_GET);
    header("Location: /hotel/login-cliente.php");
    exit;
}

$db = getDB();

// Parámetros de la URL (vienen del buscador)
$room_id  = (int)($_GET['room_id']  ?? 0);
$checkin  = $_GET['checkin']  ?? '';
$checkout = $_GET['checkout'] ?? '';

// Cargar habitaciones disponibles para el select
$habitaciones = $db->query("
    SELECT r.room_id, r.room_no, rt.room_type_name, rt.base_price
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.status = 1 AND r.check_in_status = 0
    ORDER BY r.room_no
")->fetchAll();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $room_id_post  = (int)$_POST['room_id'];
    $checkin_post  = $_POST['check_in']  ?? '';
    $checkout_post = $_POST['check_out'] ?? '';
    $notes         = trim($_POST['notes'] ?? '');

    // Validaciones
    $errors = [];
    if (!$room_id_post)    $errors[] = "Selecciona una habitación.";
    if (!$checkin_post)    $errors[] = "La fecha de entrada es obligatoria.";
    if (!$checkout_post)   $errors[] = "La fecha de salida es obligatoria.";

    if (empty($errors)) {
        $d1 = new DateTime($checkin_post);
        $d2 = new DateTime($checkout_post);

        if ($d2 <= $d1) {
            $errors[] = "La fecha de salida debe ser posterior a la de entrada.";
        } else {
            $numNights = $d1->diff($d2)->days;

            // Obtener precio de la habitación
            $stmtRoom = $db->prepare("SELECT rt.base_price FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = ?");
            $stmtRoom->execute([$room_id_post]);
            $basePrice  = $stmtRoom->fetch()['base_price'];
            $totalPrice = $numNights * $basePrice;

            // Verificar disponibilidad
            $stmtCheck = $db->prepare("SELECT booking_id FROM booking WHERE room_id = ? AND check_in < ? AND check_out > ?");
            $stmtCheck->execute([$room_id_post, $checkout_post, $checkin_post]);

            if ($stmtCheck->fetch()) {
                $errors[] = "La habitación no está disponible en esas fechas. Por favor elige otras fechas.";
            } else {
                // Obtener o crear el customer_id del usuario
                $uid  = $_SESSION['customer_user_id'];
                $stmt = $db->prepare("SELECT customer_id FROM customer_user WHERE id = ?");
                $stmt->execute([$uid]);
                $cu = $stmt->fetch();

                // Si el cliente no tiene customer_id todavía, creamos uno básico
                if (!$cu['customer_id']) {
                    $userData = $db->prepare("SELECT * FROM customer_user WHERE id = ?");
                    $userData->execute([$uid]);
                    $userData = $userData->fetch();

                    // Insertar en customer con datos básicos
                    $db->prepare("INSERT INTO customer (customer_name, email, contact_no, id_card_type_id, id_card_no, address) VALUES (?, ?, '000000000', 1, 'PENDIENTE', 'Pendiente de completar')")
                       ->execute([$userData['name'], $userData['email']]);
                    $newCustomerId = $db->lastInsertId();

                    // Vincular customer_user con customer
                    $db->prepare("UPDATE customer_user SET customer_id = ? WHERE id = ?")
                       ->execute([$newCustomerId, $uid]);

                    $customerId = $newCustomerId;
                } else {
                    $customerId = $cu['customer_id'];
                }

                // Insertar la reserva
                $db->prepare("
                    INSERT INTO booking (customer_id, room_id, check_in, check_out, num_nights, total_price, remaining_price, payment_status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
                ")->execute([$customerId, $room_id_post, $checkin_post, $checkout_post, $numNights, $totalPrice, $totalPrice, $notes]);

                $success = "¡Reserva realizada correctamente! Total: " . number_format($totalPrice, 2, ',', '.') . "€. El personal del hotel se pondrá en contacto contigo para confirmar.";
            }
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

// Precios para JS
$preciosJs = [];
foreach ($habitaciones as $h) {
    $preciosJs[$h['room_id']] = (float)$h['base_price'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hacer reserva — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-hotel">
    <div class="container">
        <a class="navbar-brand font-playfair" href="/hotel/">
            <i class="bi bi-building text-gold me-2"></i>Hotel Bellavista
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light small">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars(currentUser()) ?>
            </span>
            <a href="/hotel/mi-cuenta.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-person me-1"></i>Mi cuenta
            </a>
            <a href="/hotel/logout-cliente.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i>Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="/hotel/" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="font-playfair mb-0">
            <i class="bi bi-calendar-plus text-gold me-2"></i>Hacer una reserva
        </h2>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success shadow-sm">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <?= $success ?>
        <div class="mt-3">
            <a href="/hotel/mi-cuenta.php" class="btn btn-success me-2">
                <i class="bi bi-person me-1"></i>Ver mis reservas
            </a>
            <a href="/hotel/" class="btn btn-outline-success">
                <i class="bi bi-house me-1"></i>Volver al inicio
            </a>
        </div>
    </div>
    <?php else: ?>

    <div class="row g-4">
        <!-- Formulario -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark-hotel text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar2-check text-gold me-2"></i>Datos de la reserva</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-danger d-none" id="errorJs">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="errorJsText"></span>
                    </div>

                    <form method="POST" id="formReserva" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                        <!-- Habitación -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Habitación <span class="text-danger">*</span></label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">-- Selecciona una habitación --</option>
                                <?php foreach ($habitaciones as $h): ?>
                                <option value="<?= $h['room_id'] ?>"
                                    data-price="<?= $h['base_price'] ?>"
                                    <?= ($room_id == $h['room_id'] || ($_POST['room_id'] ?? 0) == $h['room_id']) ? 'selected' : '' ?>>
                                    Nº <?= htmlspecialchars($h['room_no']) ?> —
                                    <?= htmlspecialchars($h['room_type_name']) ?>
                                    (<?= number_format($h['base_price'],2,',','.') ?>€/noche)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Check-in <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in"
                                       value="<?= htmlspecialchars($_POST['check_in'] ?? $checkin) ?>"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Check-out <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_out" name="check_out"
                                       value="<?= htmlspecialchars($_POST['check_out'] ?? $checkout) ?>"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea class="form-control" name="notes" rows="3"
                                      placeholder="Llegada tardía, cuna para bebé, alergias..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-3 fs-5">
                            <i class="bi bi-calendar-check me-2"></i>Confirmar reserva
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                <div class="card-header bg-dark-hotel text-white">
                    <h5 class="mb-0"><i class="bi bi-calculator text-gold me-2"></i>Resumen</h5>
                </div>
                <div class="card-body" id="resumenReserva">
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-arrow-left-circle d-block fs-2 mb-2"></i>
                        Selecciona habitación y fechas
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const precios = <?= json_encode($preciosJs) ?>;

$(document).ready(function() {

    // Calcular resumen con objeto Date (DWEC)
    function calcularResumen() {
        const roomId   = $('#room_id').val();
        const checkin  = $('#check_in').val();
        const checkout = $('#check_out').val();

        if (!roomId || !checkin || !checkout) {
            $('#resumenReserva').html('<p class="text-muted text-center py-4"><i class="bi bi-arrow-left-circle d-block fs-2 mb-2"></i>Selecciona habitación y fechas</p>');
            return;
        }

        const d1 = new Date(checkin);
        const d2 = new Date(checkout);
        const noches = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));

        if (noches <= 0) {
            $('#resumenReserva').html('<p class="text-danger text-center py-3"><i class="bi bi-exclamation-triangle d-block fs-2 mb-2"></i>La fecha de salida debe ser posterior</p>');
            return;
        }

        const precio = precios[roomId] || 0;
        const total  = noches * precio;
        const opts   = { day: '2-digit', month: 'long', year: 'numeric' };

        $('#resumenReserva').html(`
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Check-in</td><td class="fw-bold">${d1.toLocaleDateString('es-ES', opts)}</td></tr>
                <tr><td class="text-muted">Check-out</td><td class="fw-bold">${d2.toLocaleDateString('es-ES', opts)}</td></tr>
                <tr><td class="text-muted">Noches</td><td class="fw-bold">${noches}</td></tr>
                <tr><td class="text-muted">Precio/noche</td><td>${precio.toFixed(2).replace('.',',')}€</td></tr>
                <tr class="table-warning">
                    <td class="fw-bold fs-5">TOTAL</td>
                    <td class="fw-bold fs-4 text-gold">${total.toFixed(2).replace('.',',')}€</td>
                </tr>
            </table>
        `);
    }

    // Actualizar al cambiar
    $('#room_id, #check_in, #check_out').on('change', calcularResumen);

    // Mínimo checkout = checkin + 1 día
    $('#check_in').on('change', function() {
        const d = new Date($(this).val());
        d.setDate(d.getDate() + 1);
        const min = d.toISOString().split('T')[0];
        $('#check_out').attr('min', min);
        if ($('#check_out').val() <= $(this).val()) $('#check_out').val(min);
        calcularResumen();
    });

    // Calcular al cargar si hay valores
    calcularResumen();

    // Validación al enviar
    $('#formReserva').on('submit', function(e) {
        const room    = $('#room_id').val();
        const checkin = $('#check_in').val();
        const checkout= $('#check_out').val();

        $('#errorJs').addClass('d-none');

        if (!room) {
            e.preventDefault();
            $('#errorJsText').text('Selecciona una habitación.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!checkin || !checkout) {
            e.preventDefault();
            $('#errorJsText').text('Las fechas son obligatorias.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        const noches = Math.round((new Date(checkout) - new Date(checkin)) / (1000*60*60*24));
        if (noches <= 0) {
            e.preventDefault();
            $('#errorJsText').text('La fecha de salida debe ser posterior a la de entrada.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>
</body>
</html>
