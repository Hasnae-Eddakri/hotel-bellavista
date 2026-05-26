<?php
// ============================================================
// admin/rooms/create.php
// Formulario para crear una nueva habitación
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin(); // Solo administradores pueden crear habitaciones

$pageTitle   = 'Nueva Habitación';
$currentPage = 'rooms';

$db    = getDB();
$tipos = $db->query("SELECT * FROM room_type ORDER BY room_type_name")->fetchAll();

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $roomNo      = trim($_POST['room_no'] ?? '');
    $roomTypeId  = (int)($_POST['room_type_id'] ?? 0);
    $floor       = (int)($_POST['floor'] ?? 1);
    $description = trim($_POST['description'] ?? '');

    // Validaciones del servidor
    $errors = [];
    if (empty($roomNo))       $errors[] = "El número de habitación es obligatorio.";
    if ($roomTypeId === 0)    $errors[] = "Selecciona un tipo de habitación.";
    if ($floor < 1 || $floor > 20) $errors[] = "La planta debe estar entre 1 y 20.";

    // Comprobar que el número de habitación no exista ya
    $check = $db->prepare("SELECT room_id FROM room WHERE room_no = ?");
    $check->execute([$roomNo]);
    if ($check->fetch()) $errors[] = "Ya existe una habitación con ese número.";

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO room (room_type_id, room_no, floor, status, check_in_status, description)
            VALUES (?, ?, ?, 1, 0, ?)
        ");
        $stmt->execute([$roomTypeId, $roomNo, $floor, $description]);
        $_SESSION['success'] = "Habitación {$roomNo} creada correctamente.";
        header("Location: index.php");
        exit;
    }
}

require_once '../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="font-playfair mb-0">Nueva Habitación</h2>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <!-- Errores de validación del servidor -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Error de validación JS -->
                <div class="alert alert-danger d-none" id="errorJs">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="errorJsText"></span>
                </div>

                <form method="POST" id="formHabitacion" novalidate>
                    <!-- Token CSRF para seguridad -->
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="row g-3">
                        <!-- Número de habitación -->
                        <div class="col-12 col-md-6">
                            <label for="room_no" class="form-label">
                                Número de habitación <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="room_no" name="room_no"
                                   placeholder="Ej: 101, 202A"
                                   value="<?= htmlspecialchars($_POST['room_no'] ?? '') ?>"
                                   maxlength="10" required>
                            <div class="form-text">Máximo 10 caracteres.</div>
                        </div>

                        <!-- Tipo de habitación -->
                        <div class="col-12 col-md-6">
                            <label for="room_type_id" class="form-label">
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="room_type_id" name="room_type_id" required>
                                <option value="">-- Selecciona un tipo --</option>
                                <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['room_type_id'] ?>"
                                    <?= ($_POST['room_type_id'] ?? '') == $t['room_type_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['room_type_name']) ?> — <?= number_format($t['base_price'],2,',','.') ?>€/noche
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Planta -->
                        <div class="col-12 col-md-6">
                            <label for="floor" class="form-label">
                                Planta <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="floor" name="floor"
                                   min="1" max="20"
                                   value="<?= htmlspecialchars($_POST['floor'] ?? '1') ?>" required>
                        </div>

                        <!-- Precio estimado (calculado con JS) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label">Precio base/noche</label>
                            <div class="form-control bg-light" id="precioEstimado">
                                <span class="text-muted">Selecciona un tipo para ver el precio</span>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="col-12">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" maxlength="500"
                                      placeholder="Descripción de la habitación, vistas, características..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/500 caracteres
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-save me-1"></i>Guardar habitación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Precios de tipos desde PHP (para usarlos en JS)
const tiposPrecios = {
    <?php foreach ($tipos as $t): ?>
    <?= $t['room_type_id'] ?>: <?= $t['base_price'] ?>,
    <?php endforeach; ?>
};

$(document).ready(function() {
    // --------------------------------------------------------
    // Mostrar precio al seleccionar tipo de habitación (DOM)
    // --------------------------------------------------------
    $('#room_type_id').on('change', function() {
        const id = $(this).val();
        const precio = tiposPrecios[id];

        if (precio) {
            // Actualizar el DOM con el precio seleccionado
            $('#precioEstimado').html(
                '<strong class="text-gold">' + precio.toFixed(2).replace('.', ',') + '€</strong> por noche'
            );
        } else {
            $('#precioEstimado').html('<span class="text-muted">Selecciona un tipo para ver el precio</span>');
        }
    });

    // --------------------------------------------------------
    // Contador de caracteres en tiempo real (DOM + eventos)
    // --------------------------------------------------------
    $('#description').on('input', function() {
        const len = $(this).val().length;
        $('#charCount').text(len);
        if (len > 450) {
            $('#charCount').addClass('text-danger').removeClass('text-muted');
        } else {
            $('#charCount').removeClass('text-danger').addClass('text-muted');
        }
    });

    // --------------------------------------------------------
    // Validación del formulario (DWEC)
    // --------------------------------------------------------
    $('#formHabitacion').on('submit', function(e) {
        const roomNo = $('#room_no').val().trim();
        const tipo   = $('#room_type_id').val();
        const floor  = parseInt($('#floor').val());

        $('#errorJs').addClass('d-none');

        // Expresión regular: solo letras y números para el número de habitación
        const regexRoom = /^[A-Za-z0-9]{1,10}$/;
        if (!regexRoom.test(roomNo)) {
            e.preventDefault();
            $('#errorJsText').text('El número de habitación solo puede contener letras y números (máx. 10).');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }

        if (!tipo) {
            e.preventDefault();
            $('#errorJsText').text('Debes seleccionar un tipo de habitación.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
            return;
        }

        if (isNaN(floor) || floor < 1 || floor > 20) {
            e.preventDefault();
            $('#errorJsText').text('La planta debe ser un número entre 1 y 20.');
            $('#errorJs').removeClass('d-none').hide().fadeIn(300);
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
