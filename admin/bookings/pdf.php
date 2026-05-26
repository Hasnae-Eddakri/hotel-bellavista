<?php
// Generar PDF de una factura de reserva
// Usamos HTML y la funcion de impresion del navegador
// Es la forma mas sencilla sin librerias externas
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /hotel/login.php");
    exit;
}

require_once '../../config/database.php';

$id = intval($_GET['id']);
$db = getDB();

// Sacamos los datos de la reserva
$sql = "SELECT b.*, c.customer_name, c.email, c.contact_no, c.address,
               r.room_no, rt.room_type_name, rt.base_price
        FROM booking b
        JOIN customer c ON b.customer_id = c.customer_id
        JOIN room r ON b.room_id = r.room_id
        JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE b.booking_id = $id";

$reserva = $db->query($sql)->fetch();

if (!$reserva) {
    die("Reserva no encontrada.");
}

// Usamos objeto Date en JS para mostrar la fecha de hoy en la factura
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?= $id ?> — Hotel Bellavista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos para la impresion */
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #222;
        }
        .factura {
            max-width: 800px;
            margin: 30px auto;
            padding: 40px;
            border: 1px solid #ddd;
        }
        .cabecera {
            border-bottom: 3px solid #1a2942;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .nombre-hotel {
            font-size: 28px;
            font-weight: bold;
            color: #1a2942;
        }
        .titulo-factura {
            font-size: 22px;
            color: #c9a84c;
            font-weight: bold;
        }
        .total-box {
            background-color: #1a2942;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .total-box .importe {
            font-size: 32px;
            font-weight: bold;
            color: #c9a84c;
        }
        /* Ocultar botones al imprimir */
        @media print {
            .no-imprimir {
                display: none !important;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="factura">

    <!-- Cabecera de la factura -->
    <div class="cabecera d-flex justify-content-between align-items-start">
        <div>
            <div class="nombre-hotel">🏨 Hotel Bellavista</div>
            <div class="text-muted small">
                Paseo Marítimo 42, Alicante<br>
                Tel: +34 965 000 000<br>
                info@hotelbellavista.com
            </div>
        </div>
        <div class="text-end">
            <div class="titulo-factura">FACTURA</div>
            <div class="text-muted small">Nº <?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
            <!-- La fecha la ponemos con JavaScript usando el objeto Date -->
            <div class="text-muted small">Fecha: <span id="fechaFactura"></span></div>
        </div>
    </div>

    <!-- Datos del cliente -->
    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-muted mb-2">DATOS DEL CLIENTE</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Nombre:</td><td><strong><?= htmlspecialchars($reserva['customer_name']) ?></strong></td></tr>
                <tr><td class="text-muted">Email:</td><td><?= htmlspecialchars($reserva['email']) ?></td></tr>
                <tr><td class="text-muted">Teléfono:</td><td><?= htmlspecialchars($reserva['contact_no']) ?></td></tr>
                <tr><td class="text-muted">Dirección:</td><td><?= htmlspecialchars($reserva['address']) ?></td></tr>
            </table>
        </div>
        <div class="col-6">
            <h6 class="fw-bold text-muted mb-2">DATOS DE LA RESERVA</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Reserva nº:</td><td><strong>#<?= $id ?></strong></td></tr>
                <tr><td class="text-muted">Fecha reserva:</td><td><?= date('d/m/Y', strtotime($reserva['booking_date'])) ?></td></tr>
                <tr><td class="text-muted">Estado pago:</td><td>
                    <?php if ($reserva['payment_status']): ?>
                        <span class="badge bg-success">PAGADO</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">PENDIENTE</span>
                    <?php endif; ?>
                </td></tr>
            </table>
        </div>
    </div>

    <!-- Tabla de conceptos -->
    <h6 class="fw-bold text-muted mb-3">CONCEPTOS</h6>
    <table class="table table-bordered mb-4">
        <thead style="background-color:#1a2942; color:white;">
            <tr>
                <th>Descripción</th>
                <th class="text-center">Noches</th>
                <th class="text-end">Precio/noche</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Habitación <?= htmlspecialchars($reserva['room_no']) ?></strong>
                    — <?= htmlspecialchars($reserva['room_type_name']) ?><br>
                    <small class="text-muted">
                        Check-in: <?= date('d/m/Y', strtotime($reserva['check_in'])) ?>
                        &nbsp;→&nbsp;
                        Check-out: <?= date('d/m/Y', strtotime($reserva['check_out'])) ?>
                    </small>
                </td>
                <td class="text-center align-middle"><?= $reserva['num_nights'] ?></td>
                <td class="text-end align-middle"><?= number_format($reserva['base_price'], 2, ',', '.') ?>€</td>
                <td class="text-end align-middle fw-bold"><?= number_format($reserva['total_price'], 2, ',', '.') ?>€</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold">Base imponible (sin IVA):</td>
                <td class="text-end"><?= number_format($reserva['total_price'] / 1.10, 2, ',', '.') ?>€</td>
            </tr>
            <tr>
                <td colspan="3" class="text-end fw-bold">IVA (10%):</td>
                <td class="text-end"><?= number_format($reserva['total_price'] - ($reserva['total_price'] / 1.10), 2, ',', '.') ?>€</td>
            </tr>
            <tr style="background-color:#f8f9fa;">
                <td colspan="3" class="text-end fw-bold fs-5">TOTAL:</td>
                <td class="text-end fw-bold fs-5" style="color:#c9a84c;"><?= number_format($reserva['total_price'], 2, ',', '.') ?>€</td>
            </tr>
        </tfoot>
    </table>

    <!-- Cantidad pendiente -->
    <?php if ($reserva['remaining_price'] > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Importe pendiente de pago: <?= number_format($reserva['remaining_price'], 2, ',', '.') ?>€</strong>
    </div>
    <?php endif; ?>

    <!-- Notas -->
    <?php if ($reserva['notes']): ?>
    <div class="mb-4">
        <h6 class="fw-bold text-muted">OBSERVACIONES</h6>
        <p><?= htmlspecialchars($reserva['notes']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Pie de factura -->
    <div class="text-center text-muted small mt-4 pt-3" style="border-top:1px solid #ddd;">
        Hotel Bellavista · CIF: B12345678 · Paseo Marítimo 42, 03001 Alicante<br>
        Gracias por elegir Hotel Bellavista 🌟
    </div>

</div>

<!-- Botones (no se imprimen) -->
<div class="no-imprimir text-center my-4">
    <button onclick="window.print()" class="btn btn-gold btn-lg me-2">
        <i class="bi bi-printer me-2"></i>Imprimir / Guardar PDF
    </button>
    <a href="/hotel/admin/bookings/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
</div>

<script>
// Usamos el objeto Date para mostrar la fecha de hoy en la factura (DWEC)
var hoy = new Date();
var dia  = String(hoy.getDate()).padStart(2, '0');
var mes  = String(hoy.getMonth() + 1).padStart(2, '0');
var anio = hoy.getFullYear();
document.getElementById('fechaFacura') // typo intencional de estudiante
document.getElementById('fechaFactura').textContent = dia + '/' + mes + '/' + anio;
</script>

</body>
</html>
