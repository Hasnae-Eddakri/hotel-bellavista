<?php
// ============================================================
// admin/bookings/create.php
// Formulario de nueva reserva — usa objeto Date en JS
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Nueva Reserva';
$currentPage = 'bookings';

$db = getDB();

// Cargar datos para los selects
$clientes = $db->query("SELECT customer_id, customer_name FROM customer ORDER BY customer_name")->fetchAll();
$habitaciones = $db->query("
    SELECT r.room_id, r.room_no, rt.room_type_name, rt.base_price
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.status = 1 AND r.check_in_status = 0
    ORDER BY r.room_no
")->fetchAll();

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $customerId    = (int)$_POST['customer_id'];
    $roomId        = (int)$_POST['room_id'];
    $checkin       = $_POST['check_in']  ?? '';
    $checkout      = $_POST['check_out'] ?? '';
    $notes         = trim($_POST['notes'] ?? '');
    $paymentStatus = isset($_POST['payment_status']) ? 1 : 0;

    $errors = [];
    if (!$customerId)  $errors[] = "Selecciona un cliente.";
    if (!$roomId)      $errors[] = "Selecciona una habitación.";
    if (!$checkin)     $errors[] = "La fecha de check-in es obligatoria.";
    if (!$checkout)    $errors[] = "La fecha de check-out es obligatoria.";

    if (empty($errors)) {
        // Calcular número de noches y precio total en PHP
        $d1 = new DateTime($checkin);
        $d2 = new DateTime($checkout);

        if ($d2 <= $d1) {
            $errors[] = "La fecha de check-out debe ser posterior al check-in.";
        } else {
            $numNights = $d1->diff($d2)->days;

            // Obtener precio base de la habitación
            $stmtRoom = $db->prepare("SELECT rt.base_price FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = ?");
            $stmtRoom->execute([$roomId]);
            $basePrice  = $stmtRoom->fetch()['base_price'];
            $totalPrice = $numNights * $basePrice;
            $remaining  = $paymentStatus ? 0 : $totalPrice;

            // Verificar disponibilidad: que no haya otra reserva para esa habitación en esas fechas
            $stmtCheck = $db->prepare("
                SELECT booking_id FROM booking
                WHERE room_id = ?
                AND check_in < ? AND check_out > ?
            ");
            $stmtCheck->execute([$roomId, $checkout, $checkin]);
            if ($stmtCheck->fetch()) {
                $errors[] = "La habitación no está disponible en esas fechas.";
            } else {
                // Insertar reserva
                $stmt = $db->prepare("
                    INSERT INTO booking (customer_id, room_id, check_in, check_out, num_nights, total_price, remaining_price, payment_status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$customerId, $roomId, $checkin, $checkout, $numNights, $totalPrice, $remaining, $paymentStatus, $notes]);

                // Marcar habitación como ocupada si el check-in es hoy
                if ($checkin === date('Y-m-d')) {
                    $db->prepare("UPDATE room SET check_in_status = 1 WHERE room_id = ?")->execute([$roomId]);
                }

                $_SESSION['success'] = "Reserva creada correctamente. Total: " . number_format($totalPrice, 2, ',', '.') . "€";
                header("Location: index.php");
                exit;
            }
        }
    }
}

require_once '../../includes/header.php';

// Precios de habitaciones para JS
$preciosJs = [];
foreach ($habitaciones as $h) {
    $preciosJs[$h['room_id']] = (float)$h['base_price'];
}
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0">Nueva Reserva</h2>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
                </div>
                <?php endif; ?>

                <div class="alert alert-danger d-none" id="errorJs">
                    <i class="bi bi-exclamation-triangle me-2"></i><span id="errorJsText"></span>
                </div>

                <form method="POST" id="formReserva" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="row g-3">
                        <!-- Cliente -->
                        <div class="col-12 col-md-6">
                            <label for="customer_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">-- Selecciona cliente --</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['customer_id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['customer_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <a href="../customers/create.php" target="_blank">+ Crear nuevo cliente</a>
                            </div>
                        </div>

                        <!-- Habitación -->
                        <div class="col-12 col-md-6">
                            <label for="room_id" class="form-label">Habitación <span class="text-danger">*</span></label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">-- Selecciona habitación --</option>
                                <?php foreach ($habitaciones as $h): ?>
                                <option value="<?= $h['room_id'] ?>" data-price="<?= $h['base_price'] ?>"
                                    <?= ($_POST['room_id'] ?? '') == $h['room_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($h['room_no']) ?> — <?= htmlspecialchars($h['room_type_name']) ?>
                                    (<?= number_format($h['base_price'],2,',','.') ?>€/noche)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div class="col-12 col-md-6">
                            <label for="check_in" class="form-label">Check-in <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="check_in" name="check_in"
                                   value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="check_out" class="form-label">Check-out <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="check_out" name="check_out"
                                   value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>

                        <!-- Notas -->
                        <div class="col-12">
                            <label for="notes" class="form-label">Notas / Observaciones</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                      placeholder="Cama supletoria, llegada tardía, alergias..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>

                        <!-- Pago -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="payment_status" name="payment_status"
                                       <?= isset($_POST['payment_status']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="payment_status">
                                    Marcar como pagado en este momento
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-save me-1"></i>Crear reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resumen de la reserva (calculado con JS) -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
            <div class="card-header bg-dark-hotel text-white">
                <h5 class="mb-0"><i class="bi bi-calculator text-gold me-2"></i>Resumen</h5>
            </div>
            <div class="card-body" id="resumenReserva">
                <p class="text-muted text-center py-3">
                    <i class="bi bi-arrow-left-circle d-block fs-3 mb-2"></i>
                    Selecciona habitación y fechas para ver el resumen
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Precios de habitaciones disponibles
const preciosHabitaciones = <?= json_encode($preciosJs) ?>;

$(document).ready(function() {

    /**
     * Calcula el resumen de la reserva usando el objeto Date (DWEC)
     * Se llama cada vez que cambia la habitación o las fechas
     */
    function calcularResumen() {
        const roomId   = $('#room_id').val();
        const checkin  = $('#check_in').val();
        const checkout = $('#check_out').val();

        if (!roomId || !checkin || !checkout) {
            $('#resumenReserva').html('<p class="text-muted text-center py-3"><i class="bi bi-arrow-left-circle d-block fs-3 mb-2"></i>Selecciona habitación y fechas</p>');
            return;
        }

        // Usar objeto Date para calcular noches (DWEC: uso del objeto Date)
        const d1 = new Date(checkin);
        const d2 = new Date(checkout);

        // Diferencia en milisegundos → convertir a días
        const diffMs = d2 - d1;
        const noches = Math.round(diffMs / (1000 * 60 * 60 * 24));

        if (noches <= 0) {
            $('#resumenReserva').html('<p class="text-danger text-center py-3"><i class="bi bi-exclamation-triangle d-block fs-3 mb-2"></i>La fecha de salida debe ser posterior a la de entrada</p>');
            return;
        }

        const precioPorNoche = preciosHabitaciones[roomId] || 0;
        const total = noches * precioPorNoche;

        // Formatear fechas con Date (DWEC)
        const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
        const fechaEntrada = d1.toLocaleDateString('es-ES', opciones);
        const fechaSalida  = d2.toLocaleDateString('es-ES', opciones);

        // Actualizar el DOM con el resumen
        $('#resumenReserva').html(`
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Check-in</td><td class="fw-bold">${fechaEntrada}</td></tr>
                <tr><td class="text-muted">Check-out</td><td class="fw-bold">${fechaSalida}</td></tr>
                <tr><td class="text-muted">Noches</td><td class="fw-bold">${noches}</td></tr>
                <tr><td class="text-muted">Precio/noche</td><td>${precioPorNoche.toFixed(2).replace('.',',')}€</td></tr>
                <tr class="table-warning">
                    <td class="fw-bold">TOTAL</td>
                    <td class="fw-bold fs-5 text-gold">${total.toFixed(2).replace('.',',')}€</td>
                </tr>
            </table>
        `);
    }

    // Recalcular al cambiar habitación o fechas
    $('#room_id, #check_in, #check_out').on('change', calcularResumen);

    // Mínimo de check_out = check_in + 1 día
    $('#check_in').on('change', function() {
        const d = new Date($(this).val());
        d.setDate(d.getDate() + 1);
        const min = d.toISOString().split('T')[0];
        $('#check_out').attr('min', min);
        if ($('#check_out').val() && $('#check_out').val() <= $(this).val()) {
            $('#check_out').val(min);
        }
        calcularResumen();
    });

    // --------------------------------------------------------
    // Validación del formulario (DWEC)
    // --------------------------------------------------------
    $('#formReserva').on('submit', function(e) {
        const customerId = $('#customer_id').val();
        const roomId     = $('#room_id').val();
        const checkin    = $('#check_in').val();
        const checkout   = $('#check_out').val();

        $('#errorJs').addClass('d-none');

        if (!customerId) {
            e.preventDefault();
            $('#errorJsText').text('Selecciona un cliente.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!roomId) {
            e.preventDefault();
            $('#errorJsText').text('Selecciona una habitación.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }
        if (!checkin || !checkout) {
            e.preventDefault();
            $('#errorJsText').text('Las fechas de check-in y check-out son obligatorias.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }

        // Validar con objeto Date
        const d1 = new Date(checkin);
        const d2 = new Date(checkout);
        if (d2 <= d1) {
            e.preventDefault();
            $('#errorJsText').text('La fecha de check-out debe ser posterior al check-in.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
